<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:34
 */

use Twdd\Ecpay\Invoice\EcpayInvoice;
use Twdd\Ecpay\Invoice\EcpayInvoiceItem;
use App\Drivermoney;

class TwddInvoice
{
    private $ecpayInvoice;
    private $RelateNumber;

    public function issueForDrivermoney(Drivermoney $drivermoney){
        $driver = $drivermoney->driver;
        if(!isset($driver->id)){
            throw new \Exception('沒有driver的物件');
        }
        $this->RelateNumber = 'Twdd'.date('YmdHis').str_pad(1, 2, '0', STR_PAD_LEFT)
                                 .str_pad($driver->DriverID, 9, '0', STR_PAD_LEFT)
                                 .rand(10000, 99999)
                                 ;
        return $this->issue([
            'CustomerID' => $driver->DriverID,
            'CustomerPhone' => $driver->DriverPhone,
            'CustomerEmail' => $driver->DriverEmail,
            'CustomerAddr' => $driver->DriverAddress,
        ]);
    }

    public function issue(array $params = []){
        $this->ecpayInvoice = new EcpayInvoice('issue');

        if(!isset($params['CustomerPhone']) && !isset($params['CustomerEmail'])){
            throw new \Exception('CustomerPhone 和 CustomerEmail至少其中一個要有值');
        }
        array_walk($params, function($row, $key) use($params){
            $this->ecpayInvoice->$key = $params[$key];
        });

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function addItem(array $params = [])
    {
        if(!isset($params['name']) || !isset($params['nums']) || !isset($params['price']) || !isset($params['word'])){
            throw new Exception('name / nums / price / word 要有值');
        }

        $item = new EcpayInvoiceItem();
        $item->setName($params['name'])
             ->setNums($params['nums'])
             ->setWord($params['word'])
             ->setPrice($params['price']);

        if(isset($params['remark'])){
            $item->setRemark($params['remark']);
        }

        $this->ecpayInvoice->pushItem($item);
        $this->ecpayInvoice->SalesAmount+=$item->getAmount();

        return $this;
    }

    public function makeNo($no = null, $type = 1){
        if(!is_nul($no)){
            return $no;
        }

        if(!is_null($this->RelateNumber)){
           return $this->RelateNumber;
        }

        $RelateNumber = 'Twdd' . str_pad($type, 2, '0', STR_PAD_LEFT) . date('YmdHis') . rand(1000000000, 2147483647);

        return $RelateNumber;
    }

    /**
     * @param int type
     * @return array
     */
    public function fire($no = null, $type = 1){
        $this->ecpayInvoice->RelateNumber = $this->makeNo($no, $type);

        $res = $this->ecpayInvoice->fire();

        return $res;
    }
}
