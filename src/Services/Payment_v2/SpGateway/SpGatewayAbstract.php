<?php

namespace Twdd\Services\Payment_v2\SpGateway;


abstract class SpGatewayAbstract
{
    protected $key;
    protected $iv;
    protected $partnerId;
    protected $merchantPrefix;
    protected $queryUrl;
    protected $creditUrl;
    protected $cancelUrl;

    public function __construct()
    {
        $this->key = env('SPGATEWAY_KEY');
        $this->iv = env('SPGATEWAY_IV');
        $this->partnerId = env('SPGATEWAY_PARTNER_ID');
        $this->merchantPrefix = env('SPGATEWAY_MERCHANT_PREFIX','TWD');
        $this->creditUrl = env('SPGATEWAY_URL', 'https://ccore.spgateway.com/API/CreditCard');
        $this->cancelUrl = env('SPGATEWAY_CANCEL_URL', 'https://ccore.spgateway.com/API/CreditCard/Cancel');
        $this->queryUrl  = env('SPGATEWAY_QUERY_URL', 'https://ccore.spgateway.com/API/QueryTradeInfo');
        $this->closeUrl  = env('SPGATEWAY_BACK_URL', 'https://ccore.spgateway.com/API/CreditCard/Close');
    }

    protected function encrypt($postData, $key, $iv)
    {
        $postData = http_build_query($postData);
        return trim(bin2hex(openssl_encrypt($this->addpadding($postData), 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv)));
    }

    private function addPadding($string)
    {
        $blockSize = 32;
        $len = strlen($string);
        $pad = $blockSize - ($len % $blockSize);
        $string .= str_repeat(chr($pad), $pad);

        return $string;
    }
}
