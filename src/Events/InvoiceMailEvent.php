<?php


namespace Twdd\Events;


class InvoiceMailEvent extends Event
{
    public $params;

    /**
     * Params should include:
     *
     * status 1:預約成功,2:預約取消, 3:預約失敗(30min 未付訂金)
     * msg 包含呼叫編號(calldriver_task_map)或任務編號或帳單編號的提示訊息
     * err 未成功開立的原因
     * 
     * 
     */
    public function __construct(Array $params)
    {
        $this->params = $params;
    }

}