<?php


namespace Twdd\Services\Payments\TapPlay;


class Merchant
{
    private $acquirer = 'TW_SPGATEWAY';
    private $account = null;
    private $spgatewaykey = null;
    private $spgatewayiv = null;
    private $request_type = 'prod';
    private $url;

    const merchant_url = 'https://api.tappaysdk.com/partner/createmerchants';
    const merchant_url_sandbox = 'https://api.tappaysdk.com/partner/createmerchants';


    /**
     * Merchant constructor.
     * @param string $acquirer
     * @param string $account
     * @param string $spgatewaykey
     * @param string $spgatewayiv
     * @param string $request_type
     */
    public function __construct(string $account, string $spgatewaykey, string $spgatewayiv, string $acquirer = 'TW_SPGATEWAY', string $request_type = 'prod')
    {
        $this->setAccount($account);
        $this->setSpgatewaykey($spgatewaykey);
        $this->setSpgatewayiv($spgatewayiv);
        $this->setAcquirer($acquirer);

        if((bool) env('APP_DEBUG') === true) {
            $this->debug();
        }else{
            $this->prod();
        }

        $this->setUrl();
    }


    /**
     * @return Merchant
     */
    public function debug(): Merchant{
        $this->request_type = 'sandbox';
        $this->setUrl();

        return $this;
    }

    /**
     * @return Merchant
     */
    public function sandbox(): Merchant{

        return $this->debug();
    }

    /**
     * @return Merchant
     */
    public function prod(): Merchant{
        $this->request_type = 'prod';
        $this->setUrl();

        return $this;
    }

    /**
     * @return Merchant
     */
    public function prodtion(): Merchant{

        return $this->prod();
    }

    /**
     * @return string
     */
    public function getAcquirer(): string
    {
        return $this->acquirer;
    }

    /**
     * @param string $acquirer
     * @return Merchant
     */
    public function setAcquirer(string $acquirer): Merchant
    {
        $this->acquirer = $acquirer;

        return $this;
    }

    /**
     * @return null
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param null $account
     * @return Merchant
     */
    public function setAccount($account): Merchant
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return null
     */
    public function getSpgatewaykey()
    {
        return $this->spgatewaykey;
    }

    /**
     * @param null $spgatewaykey
     * @return Merchant
     */
    public function setSpgatewaykey($spgatewaykey): Merchant
    {
        $this->spgatewaykey = $spgatewaykey;

        return $this;
    }

    /**
     * @return null
     */
    public function getSpgatewayiv()
    {
        return $this->spgatewayiv;
    }

    /**
     * @param null $spgatewayiv
     * @return Merchant
     */
    public function setSpgatewayiv($spgatewayiv): Merchant
    {
        $this->spgatewayiv = $spgatewayiv;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl()
    {
        $this->url = $this->request_type == 'prod' ? self::merchant_url : self::merchant_url_sandbox;
    }

    /**
     * @return string
     */
    public function getRequestType(): string
    {
        return $this->request_type;
    }



    public function toArray() : array{

        return [
            'merchant_id' => $this->account,
            'merchant_account' => $this->account,
            'acquirer' => $this->acquirer,
            'spgatewaykey' => $this->spgatewaykey,
            'spgatewayiv' => $this->spgatewayiv,
        ];
    }
}