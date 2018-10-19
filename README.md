# Post Affiliate Pro for WooCommerce

This is a concept project to attempt to leverage server-side sale tracking between WooCommerce and Post Affiliate Pro. If you need WooCommerce sale tracking with Post Affiliate Pro for a production site it is recommended that you use the official [Post Affiliate Pro WordPress plugin by QualityUnit](https://wordpress.org/plugins/postaffiliatepro/)

## Problems this attempts to solve

The official plugin relies on sale tracking code being printed on the WooCommerce order confirmation page. I have several implementations of WooCommerce that don't send users to the order confirmation screen following purchase. A server-side tracking method would allow the user to navigate anywhere following purchase and the sale should still be registered.

## Limitations
This plugin was specifically built to be compatible with the 1 Click Upsells plugin by WooCurve. All products earning commissions must have a defined SKU, and combined orders must be disabled.

## Currently in development

Use at your own risk. This has only been tested on a handful of sites.
