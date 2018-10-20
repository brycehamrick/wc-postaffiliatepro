<?php
/*
 * Plugin Name: Post Affiliate Pro for WooCommerce
 * Plugin URI: https://bhamrick.com/
 * Description: A better system for integrating WooCommerce with Post Affiliate Pro
 * Author: Bryce Hamrick
 * Version: 0.0.4
 * Author URI: https://bhamrick.com/
 * License: GPL2
 * Text Domain: wc-postaffiliatepro
 * WC tested up to: 3.4
 * WC requires at least: 3.0
 *
 * @package WC_Post_Affiliate_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'WC_Post_Affiliate_Pro' ) ) :
class WC_Post_Affiliate_Pro {

  protected $integration;

  /**
  * Construct the plugin.
  */
  public function __construct() {
    $this->id = 'wc-postaffiliatepro';
    add_action( 'plugins_loaded', array( $this, 'init' ) );
  }
  /**
  * Initialize the plugin.
  */
  public function init() {
    // Checks if WooCommerce is installed.
    if ( class_exists( 'WC_Integration' ) ) {
      require_once 'includes/PapApi.class.php';
      require_once 'includes/class-wc-post-affiliate-pro-integration.php';
      // Register the integration.
      add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
      add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

      $this->integration = new WC_Post_Affiliate_Pro_Integration();
      add_action( 'wp_enqueue_scripts', array( $this, 'load_js' ) );
      add_filter( 'script_loader_tag', array( $this, 'add_id_to_js' ), 10, 3 );

      // Adds custom field behavior for storing visitor id
      add_action( 'woocommerce_after_order_notes', array( $this, 'print_visistor_id_field' ) );
      add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_visitor_id' ) );

      // Record the sale
      add_action( 'woocommerce_payment_complete', array( $this, 'track_sale' ));
      add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'track_sale' ));
    }
  }
  /**
   * Add a new integration to WooCommerce.
   */
  public function add_integration( $integrations ) {
    $integrations[] = 'WC_Post_Affiliate_Pro_Integration';
    return $integrations;
  }
  /**
   * Add plugin settings link
   */
  public function plugin_action_links( $links ) {
    $setting_link = admin_url( 'admin.php?page=wc-settings&tab=integration&section=' . $this->id );
    $plugin_links = array(
      '<a href="' . $setting_link . '">' . __( 'Settings', $this->id ) . '</a>'
    );

    return array_merge( $plugin_links, $links );
  }

  /**
   * Load up the track.js file
   */
  public function load_js() {
    wp_enqueue_script( $this->id, $this->integration->track_url(), array(), false, true );
    $checkout_script = (is_checkout()) ? 'PostAffTracker.writeCookieToCustomField("pap_visitor_id", null, null, false); PostAffTracker.writeAffiliateToCustomField("pap_affiliate_id");' : '';
    $track_script = 'PostAffTracker.setAccountId("default1"); try { PostAffTracker.track(); ' . $checkout_script . ' } catch (err) { }';
    wp_add_inline_script( $this->id, $track_script );
  }
  /**
   * Adds an ID to the script tag
   */
  public function add_id_to_js($tag, $handle, $src) {
    if ( $this->id === $handle ) {
      // adding id by inserting it in the first script tag
      $pos = strpos($tag, "<script");
      if ($pos !== false) {
        $tag = substr_replace($tag, "<script id='pap_x2s6df8d'", $pos, strlen("<script"));
      }
    }
    return $tag;
  }

  /**
   * Output the hidden field for storing visitor id
   */
  public function print_visistor_id_field($checkout) {
    echo '<input type="hidden" class="input-hidden" name="pap_visitor_id" id="pap_visitor_id" value=""><input type="hidden" class="input-hidden" name="pap_affiliate_id" id="pap_affiliate_id" value="">';
  }
  /**
   * Saves the visitor id to order meta
   */
  public function save_visitor_id($order_id) {
    if ( ! empty( $_POST['pap_visitor_id'] ) ) {
      update_post_meta( $order_id, '_pap_visitor_id', sanitize_text_field( $_POST['pap_visitor_id'] ) );
    }
    if ( ! empty( $_POST['pap_affiliate_id'] ) ) {
      update_post_meta( $order_id, '_pap_affiliate_id', sanitize_text_field( $_POST['pap_affiliate_id'] ) );
    }
  }

  /**
   * Register the sale
   *
   * TODO: Add some kind of queuing system for performance.
   *
   */
  public function track_sale($order_id) {
    if ( get_post_meta( $order_id, '_pap_sale_tracked', true ) !== 'true' ) {
      $visitor_id = get_post_meta( $order_id, '_pap_visitor_id', true );
      // Add support for 1 Click Upsells by WooCurve
      if (empty($visitor_id)) {
        $ocu_parent_order_id = get_post_meta( $order_id, '1cu_parent_order_id', true );
        $visitor_id = get_post_meta( $ocu_parent_order_id, '_pap_visitor_id', true );
      }
      if (!empty($visitor_id)) {
        $saleTracker = new Pap_Api_SaleTracker($this->integration->base_url() . 'scripts/sale.php');
        $saleTracker->setAccountId('default1');
        $saleTracker->setVisitorId($visitor_id);

        // Use CloudFlare's IP if we have it
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
          $saleTracker->setIp($_SERVER["HTTP_CF_CONNECTING_IP"]);
        }

        $order = new WC_Order( $order_id );
        $sales = array();
        foreach ( $order->get_items() as $item_key => $item ) {
          $sales[$item_key] = $saleTracker->createSale();
          $sales[$item_key]->setTotalCost($item['line_total']);

          // OrderID must be unique, append the item key for each line item.
          $sales[$item_key]->setOrderID("$order_id-$item_key");
          $product = $order->get_product_from_item( $item );
          $product_key = $product->get_sku();
          if (empty($product_key)) {
            $product_key = $product->get_id();
          }
          $sales[$item_key]->setProductID($product_key);
        }
        $saleTracker->register();
        update_post_meta( $order_id, '_pap_sale_tracked', 'true' );
      }
    }
  }
}
$WC_Post_Affiliate_Pro = new WC_Post_Affiliate_Pro( __FILE__ );
endif;
