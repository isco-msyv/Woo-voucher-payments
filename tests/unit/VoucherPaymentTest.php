<?php

require_once('./src/VoucherPayment.php');

class VoucherPaymentTest extends PHPUnit\Framework\TestCase
{
    public $voucherPayment;

    public function setUp(): void
    {
        $this->voucherPayment = new VoucherPayment();
    }

    public function testVoucherCodeGetterAndSetter()
    {
        $sampleVaucherCode = '23ca0e77-49d5-47fb-ae67-d1809f995198';
        $this->voucherPayment->setVoucherCode($sampleVaucherCode);

        $voucherCode = $this->voucherPayment->getVoucherCode();

        $this->assertEquals($voucherCode, $sampleVaucherCode);
    }

    public function testVouchersList()
    {
        $vouchers = $this->voucherPayment->getVouchers();
        $this->assertIsArray($vouchers);
    }

    public function testVoucherSave()
    {
        $def_currency = get_woocommerce_currency();

        $test_voucher = array(
            'voucher_string' => $this->voucherPayment->generateVaucherString(24),
            'voucher_price' => 0,
            'voucher_currency' => $def_currency,
            'voucher_is_full_price' => 1,
        );

        $savedVaucherString = $this->voucherPayment->saveVoucher($test_voucher);

        $this->assertEquals($test_voucher['voucher_string'], $savedVaucherString);
    }

    public function testFullPriceVoucherPayment()
    {
        $fullPriceVoucherCode = $this->voucherPayment->createTestVaoucher(0, 1);
        $this->voucherPayment->setVoucherCode($fullPriceVoucherCode);
        $paymentResult = $this->voucherPayment->payWithVoucher(10000);
        $this->assertFalse($paymentResult['errors']);
    }

    public function testEmptyFailVoucherPayment()
    {
        $this->voucherPayment->setVoucherCode('');
        $paymentResult = $this->voucherPayment->payWithVoucher(10000);
        $this->assertTrue($paymentResult['errors']);
    }

    public function testFailPriceVoucherPayment()
    {
        $priceVoucherCode = $this->voucherPayment->createTestVaoucher(100);
        $this->voucherPayment->setVoucherCode($priceVoucherCode);
        $paymentResult = $this->voucherPayment->payWithVoucher(10000);
        $this->assertTrue($paymentResult['errors']);
    }

    public function testSuccessPriceVoucherPayment()
    {
        $priceVoucherCode = $this->voucherPayment->createTestVaoucher(100);
        $this->voucherPayment->setVoucherCode($priceVoucherCode);
        $paymentResult = $this->voucherPayment->payWithVoucher(10);
        $this->assertFalse($paymentResult['errors']);
    }

}