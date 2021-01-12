<?php

class VoucherPayment
{
    public $voucher_code;

    public function setVoucherCode($voucher_code)
    {
        $this->voucher_code = $voucher_code;
    }

    public function getVoucherCode()
    {
        return $this->voucher_code;
    }

    public function saveVouchers($vouchers)
    {
        $this->truncateVouchersTable();

        $res = false;

        foreach ($vouchers as $voucher) {
            $res &= $this->saveVoucher($voucher);
        }

        return $res;
    }

    public function saveVoucher($voucher)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_revolut_test_predefined_vouchers';

        $sql = 'INSERT INTO `' . $table_name . '` (`voucher_string`, `voucher_currency`, `voucher_price`, `voucher_is_full_price`) 
                VALUES ("' . $voucher['voucher_string'] . '","' . $voucher['voucher_currency'] . '","' . $voucher['voucher_price'] . '","' . $voucher['voucher_is_full_price'] . '")';

        if ($wpdb->query($sql)) {
            return $voucher['voucher_string'];
        }

        return false;
    }

    public function truncateVouchersTable()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_revolut_test_predefined_vouchers';

        $wpdb->query('TRUNCATE TABLE ' . $table_name);
    }

    public function getVouchers()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_revolut_test_predefined_vouchers';
        $res = $wpdb->get_results('SELECT * FROM ' . $table_name);
        return json_decode(json_encode($res), true);
    }

    public function getVoucherByCode($voucher_code)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_revolut_test_predefined_vouchers';
        $query = 'SELECT * FROM ' . $table_name . ' WHERE voucher_string = "' . $wpdb->esc_like($voucher_code) . '"';
        return $wpdb->get_results($query);
    }

    public function generateVaucherString($length = 24)
    {
        $chars = '2345-6-78-9bc-dfhk-mnprs--tvzBCD-FHJK-LMNP-RST-VZ';
        $shuffled = str_shuffle($chars);
        $result = mb_substr($shuffled, 0, $length);

        return $result;
    }

    public function createTestVaoucher($price = 0, $isFullPrice = 0){
        $def_currency = get_woocommerce_currency();

        $test_voucher = array(
            'voucher_string'=> $this->generateVaucherString(24),
            'voucher_price'=> $price,
            'voucher_currency'=> $def_currency,
            'voucher_is_full_price'=> $isFullPrice,
        );

        $savedVaucherString = $this->saveVoucher($test_voucher);
        return $savedVaucherString;
    }

    public function payWithVoucher($order_total, $order_currency = '')
    {
        $result = array(
            'errors' => false,
        );

        if (empty($this->voucher_code)) {
            $result['errors'] = true;
            $result['errors_text'] = 'Voucher code is required';
            return $result;
        }

        $check_voucher = $this->getVoucherByCode($this->voucher_code);

        if (empty($check_voucher)) {
            $result['errors'] = true;
            $result['errors_text'] = 'Voucher code is not valid';
            return $result;
        }

        $check_voucher = reset($check_voucher);
        $voucher_total = $check_voucher->voucher_price;
        $voucher_is_full_price = $check_voucher->voucher_is_full_price;
        $voucher_currency = $check_voucher->voucher_currency;


        if ($voucher_is_full_price) {
            return $result;
        }

        if ($voucher_currency != $order_currency) {
            //for the test purpose
            // change currency
            //$order_total can be converted based on any external API or based on installed multicurrency plugin
        }

        if ($voucher_total >= $order_total) {
            return $result;
        }

        $result['errors'] = true;
        $result['errors_text'] = 'Voucher value is less than order total';
        return $result;
    }
}