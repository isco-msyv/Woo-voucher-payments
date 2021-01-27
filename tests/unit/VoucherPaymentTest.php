<?php

require_once('./src/VoucherPayment.php');

class VoucherPaymentTest extends PHPUnit\Framework\TestCase
{
    public $voucherPayment;

    public function setUp(): void
    {
        $this->voucherPayment = new VoucherPayment(true);
    }

    public function testVoucherCodeGetterAndSetter()
    {
        $sampleVaucherCode = '23ca0e77-49d5-47fb-ae67-d1809f995198';
        $this->voucherPayment->setVoucherCode($sampleVaucherCode);

        $voucherCode = $this->voucherPayment->getVoucherCode();

        $this->assertEquals($voucherCode, $sampleVaucherCode);
    }

    public function testFullPriceVoucherPayment()
    {
        $this->voucherPayment->setVoucherCode('23ca0e77-49d5-47fb-ae67-d1809f995198');
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
        $this->voucherPayment->setVoucherCode('8c086291-f90c-4208-892a-c77f9d9446ea');
        $paymentResult = $this->voucherPayment->payWithVoucher(10000);
        $this->assertTrue($paymentResult['errors']);
    }

    public function testSuccessPriceVoucherPayment()
    {
        $this->voucherPayment->setVoucherCode('8c086291-f90c-4208-892a-c77f9d9446ea');
        $paymentResult = $this->voucherPayment->payWithVoucher(10);
        $this->assertFalse($paymentResult['errors']);
    }

}