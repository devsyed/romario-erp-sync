<?php 

defined('ABSPATH') || exit; 

class ADCB_Payment_Gateway
{
    public function __construct()
    {
        add_filter( 'woocommerce_payment_gateways', array($this, 'adcb_payment_gateway'));
    }

    
    public function adcb_payment_gateway( $gateways ) {
        $gateways[] = 'ADCB Payment Gateway'; 
        return $gateways;
    }
}

new ADCB_Payment_Gateway();