# Post Affiliate Pro for WooCommerce

This is a concept project to leverage server-side sale tracking between WooCommerce and Post Affiliate Pro. If you need WooCommerce sale tracking with Post Affiliate Pro for a typical WooCommerce site it is recommended that you use the official [Post Affiliate Pro WordPress plugin by QualityUnit](https://wordpress.org/plugins/postaffiliatepro/).

## Problems this attempts to solve

The official plugin relies on sale tracking code being printed on the WooCommerce order confirmation page. If your WooCommerce configuration doesn't send users to the order confirmation screen following purchase, the official plugin will not work. A server-side tracking method allows the user to navigate anywhere following purchase and the sale should still be registered.

This plugin was built to work with the [One Click Upsells plugin by WooCurve](https://woocurve.com/one-click-upsells-for-woocommerce/). It will track both the initial sale as well as any upsells, and sales will be tracked even if the user navigates away from the upsell page instead of continuing through the funnel.

## Future roadmap

* Test with a Combined Orders setup. Currently this has only been tested when upsells create new orders.
* Add support for recurring subscription tracking via WooCommerce Subscriptions.
* Automatically update commission status of refunded orders

_Use at your own risk. This has only been tested on a handful of sites._
