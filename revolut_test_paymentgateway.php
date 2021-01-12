<?php
/*
 * Plugin Name: Revolut Test Payment Gateway
 * Plugin URI: https://sample-url.com/payment-gateway-plugin.html
 * Description: This payment method allows customer to pay through predefined vouchers
 * Author: Ismayil Musayev
 * Author URI: http://sample-url.info
 * Version: 1.0.0
 */

if (!defined('VOUCHER_PLUGIN_DIR')) {
    define( 'VOUCHER_PLUGIN_DIR', dirname(__FILE__).'/' );
}

add_action( 'plugins_loaded', 'revolut_test_init_gateway_class' );

function revolut_test_init_gateway_class() {

    //check if the woocommerce environment loadded properly
    if (!class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    require_once(plugin_basename( 'includes/wc_revolut_test_paymentgateway.php' ) );

    add_filter( 'woocommerce_payment_gateways', 'revolut_test_add_gateway_class' );
}


/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
function revolut_test_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Revolut_Test_Payment_Gateway';
    return $gateways;
}


/**
 * Create table to save vouchers
 */
register_activation_hook( __FILE__, 'revolut_test_create_tables' );

/**
 * create table and init default vouchers on plugin activation
*/
function revolut_test_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'wc_revolut_test_predefined_vouchers';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
		voucher_id int(11) NOT NULL AUTO_INCREMENT,
		voucher_string varchar (255) NOT NULL UNIQUE,
		voucher_currency varchar (10) NOT NULL,
		voucher_price float (12,2) NOT NULL,
		voucher_is_full_price int(1)default 0,
		PRIMARY KEY (voucher_id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    require_once(VOUCHER_PLUGIN_DIR . '/src/VoucherPayment.php');
    dbDelta( $sql );

    $def_currency = get_woocommerce_currency();
    // init default vocuhers, merchant will be able to add custom vouchers on admin settings form
    $defaultVouchers = array(
        array(
            'voucher_string'=> 'bd28808c-380a-4274-bde1-b1ce31258ea1',
            'voucher_price'=> 5,
            'voucher_currency'=> $def_currency,
            'voucher_is_full_price'=> 0,
        ),array(
            'voucher_string'=> 'db15f80e-2f65-4794-aeb8-7a03d225eb53',
            'voucher_price'=> 10,
            'voucher_currency'=> $def_currency,
            'voucher_is_full_price'=> 0,
        ),array(
            'voucher_string'=> '8c086291-f90c-4208-892a-c77f9d9446ea',
            'voucher_price'=> 50,
            'voucher_currency'=> $def_currency,
            'voucher_is_full_price'=> 0,
        ),array(
            'voucher_string'=> '23ca0e77-49d5-47fb-ae67-d1809f995198',
            'voucher_price'=> 0,
            'voucher_currency'=> $def_currency,
            'voucher_is_full_price'=> 1,
        ),
    );

    $voucherPayment = new VoucherPayment();
    $voucherPayment->saveVouchers($defaultVouchers);
}


