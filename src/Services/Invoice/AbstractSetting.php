<?php

namespace Twdd\Services\Invoice;

abstract class AbstractSetting 
{
    public $url;
    protected $params;
    protected $calldriverTaskMap;
    protected $task;
    protected $member;
    
    protected $enterprise;

    public function __construct()
    {
        $this->url = sprintf('%s/api/invoice',env("INVOICE_API_URL","127.0.0.1"));
    }
    public function setMember($member)
    {
        $this->member = $member;
    }

    public function setEnterprise($enterprise)
    {
        $this->enterprise = $enterprise;
    }

    public function setCalldriverTaskMap($calldriverTaskMap)
    {
        $this->calldriverTaskMap = $calldriverTaskMap;
    }
    public function setTask($task)
    {
        $this->task = $task;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    protected function getParams()
    {
        return $this->params;
    }

    public function call($url,$data,$method='POST')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_USERPWD, sprintf('twdd:%s',env("INVOICE_API_PASSWORD")));

        switch ($method){
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            default:
                curl_setopt($ch, CURLOPT_POST, true);
                break;
        
        }

        $result = curl_exec($ch);

        return json_decode($result,true);
    }

}