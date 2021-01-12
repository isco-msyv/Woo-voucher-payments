<?php

require_once(VOUCHER_PLUGIN_DIR . '/src/VoucherPayment.php');

class WC_Revolut_Test_Payment_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {

        $this->id = 'revolut_test'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom card form
        $this->method_title = 'Revolut Test';
        $this->method_description = 'This payment method allows customer to pay through predefined vouchers'; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but this plugin can be implemented with simple payments
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->voucherPayment = new VoucherPayment();
        $this->vouchers = $this->voucherPayment->getVouchers();

        // Settings save hook registers
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_vouchers'));


        // If need JavaScript
//        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Revolut Test Payment Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Revolut Test Payment',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Pay with voucher code.',
            ),
            'payment_vouchers' => array(
                'type' => 'payment_vouchers',
            ),
        );
    }

    //method for implementing custom html field for adding vouchers
    public function generate_payment_vouchers_html()
    {


        $def_currency = get_woocommerce_currency();
        ob_start();


        ?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php esc_html_e('Vouchers:', 'woocommerce'); ?></th>
            <td class="forminp" id="vouchers">
                <div class="wc_input_table_wrapper">
                    <table class="widefat wc_input_table" cellspacing="0">
                        <thead>
                        <tr>
                            <th><?php esc_html_e('Voucher String', 'woocommerce'); ?></th>
                            <th><?php esc_html_e('Voucher Amount', 'woocommerce'); ?></th>
                            <th><?php esc_html_e('Currency', 'woocommerce'); ?></th>
                            <th><?php esc_html_e('Is Voucher for full price', 'woocommerce'); ?></th>
                        </tr>
                        </thead>
                        <tbody class="accounts">
                        <?php

                        $i = -1;
                        if ($this->vouchers) {
                            foreach ($this->vouchers as $voucher) {
                                $i++;
                                $checkedInput = '';

                                if ($voucher['voucher_is_full_price']) {
                                    $checkedInput = 'checked="true"';
                                }

                                echo '<tr class="account">
										<td><input type="text" value="' . esc_attr(wp_unslash($voucher['voucher_string'])) . '" name="voucher_string[' . esc_attr($i) . ']" /></td>
										<td><input type="text" inputmode="numeric" pattern="[-+]?[0-9]*[.,]?[0-9]+" value="' . esc_attr($voucher['voucher_price']) . '" name="voucher_price[' . esc_attr($i) . ']" /></td>
										<td><input type="text" disabled value="' . esc_attr(wp_unslash($voucher['voucher_currency'])) . '" name="voucher_string[' . esc_attr($i) . ']" /></td>
										<td><input type="checkbox" ' . $checkedInput . ' name="voucher_is_full_price[' . esc_attr($i) . ']" /></td>
									</tr>';
                            }
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="7"><a href="#"
                                               class="add button"><?php esc_html_e('+ Add Vocuher', 'woocommerce'); ?></a>
                                <a href="#"
                                   class="remove_rows button"><?php esc_html_e('Remove selected voucher(s)', 'woocommerce'); ?></a>
                            </th>
                        </tr>
                        </tfoot>
                    </table>
                    <!--                 [@Note for Testers] Because default woocommerce does not contain a multi-currency feature
                                        I did not make the process long by checking if the WC multicurrency (or any other(s)) plugin installed or not,
                                        I set the default currency for vouchers and will convert different currencies
                                        during the order process.
                                        But implementing the process based on the multicurrency system is also possible  -->
                    <p class="description">Default Currency for vouchers is <b><?= $def_currency ?></b>, Orders which
                        has currency other than default currency will be converted</p>
                    <p class="description"><b>(*)Voucher String and (*)Voucher Amount fields are required, empty lines
                            will be ignored without any future notice</p>
                </div>
                <script type="text/javascript">
                    jQuery(function () {
                        function genearteVoucherCode(length) {
                            var result = '';
                            var characters = 'ABCDE-FGHIJKLMN-OPQRST-UVWX-YZab-cdefg-hijkl-mnop-qrst-uvwx-yz012-345-6789';
                            var charactersLength = characters.length;
                            for (var i = 0; i < length; i++) {
                                result += characters.charAt(Math.floor(Math.random() * charactersLength));
                            }
                            return result;
                        }


                        jQuery('#vouchers').on('click', 'a.add', function () {

                            var size = jQuery('#vouchers').find('tbody .account').length;

                            jQuery('<tr class="account">\
									<td><input type="text" name="voucher_string[' + size + ']" value="' + genearteVoucherCode(25) + '" /></td>\
									<td><input type="text" inputmode="numeric" pattern="[-+]?[0-9]*[.,]?[0-9]+" name="voucher_price[' + size + ']" /></td>\
									<td><input type="text" disabled name="voucher_currency[' + size + ']" value="<?= $def_currency ?>" /></td>\
									<td><input type="checkbox" name="voucher_is_full_price[' + size + ']" /></td>\
								</tr>').appendTo('#vouchers table tbody');

                            return false;
                        });
                    });
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    // save vouchers from admin settings form
    public function save_vouchers()
    {
        $vouchers = array();
        if (isset($_POST['voucher_string']) && isset($_POST['voucher_price'])) {

            $voucher_string = wc_clean(wp_unslash($_POST['voucher_string']));
            $voucher_price = wc_clean(wp_unslash($_POST['voucher_price']));
            $voucher_is_full_price = wc_clean(wp_unslash($_POST['voucher_is_full_price']));
            $def_currency = get_woocommerce_currency();

            foreach ($voucher_string as $i => $name) {
                if (empty($voucher_string[$i]) || empty($voucher_price[$i])) {
                    continue;
                }

                $vouchers[] = array(
                    'voucher_string' => $voucher_string[$i],
                    'voucher_price' => $voucher_price[$i],
                    'voucher_currency' => $def_currency,
                    'voucher_is_full_price' => $voucher_is_full_price[$i] == 'on' ? 1 : 0,
                );
            }
        }

        $this->voucherPayment->saveVouchers($vouchers);
    }

    public function payment_fields()
    {

        //display some description before the payment form
        if ($this->description) {
            if ($this->testmode) {
                $this->description = trim($this->description);
            }
            // display the description with <p> tags etc.
            echo wpautop(wp_kses_post($this->description));
        }

        // echo() the form
        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        // Add this action hook as credit card forms
        do_action('woocommerce_credit_card_form_start', $this->id);

        echo '<div class="form-row form-row-wide">
		<div class="form-row">
			<label>Voucher Code <span class="required">*</span></label>
			<input id="voucher_code" name="voucher_code" type="text" autocomplete="off">
		</div>
		<div class="clear"></div>';

        do_action('woocommerce_credit_card_form_end', $this->id);

        echo '<div class="clear"></div></fieldset>';

    }

    public function payment_scripts()
    {
        // if any js needed
    }

    public function validate_fields()
    {
        // we can add some validations here, but there is no need for this plugin...
        return true;
    }

    // After Customer submits payment form process the payment
    public function process_payment($order_id)
    {

        global $woocommerce;

        // get order and payment details
        $order = wc_get_order($order_id);
        $vacuher_code = $_POST['voucher_code'];
        $order_total = (float)$order->get_total();
        $order_currency = (float)$order->get_currency();

        $this->voucherPayment->setVoucherCode($vacuher_code);

        $paymentResult = $this->voucherPayment->payWithVoucher($order_total, $order_currency);

        if($paymentResult['errors']){
            wc_add_notice($paymentResult['errors_text'], 'error');
            return;
        }

        return $this->validateOrder($order, $woocommerce);
    }

    // validate woocommerce order
    public function validateOrder($order, $woocommerce)
    {
        $order->payment_complete();
        $order->reduce_order_stock();

        // some notes to customer
        $order->add_order_note('Order succesfully paid by Revolut test vouchers', true);

        // Empty cart
        $woocommerce->cart->empty_cart();

        // Redirect to the thank you page
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}