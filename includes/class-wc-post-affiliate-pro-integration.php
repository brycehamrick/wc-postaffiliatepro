<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * WC_Post_Affiliate_Pro_Integration Class
 */
class WC_Post_Affiliate_Pro_Integration extends WC_Integration {
  /**
   * Init and hook in the integration.
   */
  public function __construct() {
    global $woocommerce;
    $this->id                 = 'wc-postaffiliatepro';
    $this->method_title       = __( 'Post Affiliate Pro', 'wc-postaffiliatepro' );
    $this->method_description = __( 'Tracks clicks and sales with Post Affiliate Pro.', 'wc-postaffiliatepro' );
    // Load the settings.
    $this->init_form_fields();
    $this->init_settings();

    // Actions.
    add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_api_session' ), 25 );
    add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ), 20 );
  }
  /**
   * Initialize integration settings form fields.
   */
  public function init_form_fields() {
    $this->form_fields = array(
      'pap_url' => array(
        'title'             => __( 'PostAffiliatePro URL', $this->id ),
        'type'              => 'text',
        'description'       => __( 'Enter the base URL of your PAP account. Example: http://businessname.postaffiliatepro.com', $this->id ),
        'desc_tip'          => true,
        'default'           => ''
      ),
      'pap_merchant_user' => array(
        'title'             => __( 'Merchant Username', $this->id ),
        'type'              => 'text',
        'description'       => __( 'Enter the username you use to log in to your merchant account.', $this->id ),
        'desc_tip'          => true,
        'default'           => ''
      ),
      'pap_merchant_pass' => array(
        'title'             => __( 'Merchant Password', $this->id ),
        'type'              => 'password',
        'description'       => __( 'Enter the password you use to log in to your merchant account.', $this->id ),
        'desc_tip'          => true,
        'default'           => ''
      ),
    );
  }
  /**
   * Validate API credentials and save session output
   */
  public function process_api_session() {
    $this->init_settings();
    $post_data = $this->get_post_data();

    $session = new Gpf_Api_Session($this->base_url() . 'scripts/server.php');
    try {
        $login = @$session->login($this->get_option( 'pap_merchant_user' ), $this->get_option( 'pap_merchant_pass'));
        // enable hashing if available
        $request = new Gpf_Rpc_DataRequest('Pap_Merchants_Tools_IntegrationMethods', 'getHashScriptNameParams', $session);

        try {
            $request->sendNow();
            $data = $request->getData();
            update_option('pap_script_hash', $data->getValue('hashTrackingScriptsValue'));
        }
        catch(Exception $e) {
            $this->add_error("API call error for 'getHashScriptNameParams': ".$e->getMessage());
            update_option('pap_script_hash', '0');
        }

    } catch (Gpf_Api_IncompatibleVersionException $e) {
        $this->add_error('Unable to login into PAP installation because of icompatible versions (probably your API file here in WP installation is older than your PAP installation)');
    }
  }
  public function base_url() {
    $this->init_settings();
    $url = $this->get_option('pap_url');
    if (substr($url, -1) != '/') {
      $url .= '/';
    }
    return $url;
  }
  /**
   * Construct the track js url
   */
  public function track_url() {
    $tracker = 'trackjs.js';
    $hashed = get_option('pap_script_hash', '0');
    if ($hashed !== '0') {
      $tracker = $hashed;
    }
    return $this->base_url() . 'scripts/' . $tracker;
  }
}
