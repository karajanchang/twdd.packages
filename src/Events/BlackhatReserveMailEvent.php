<?php


namespace Twdd\Events;


class BlackhatReserveMailEvent extends Event
{
    public $params;

    /**
     * Params should include:
     *
     * status 1:預約成功,2:預約取消, 3:預約失敗(30min 未付訂金)
     * driver
     * call_driver_task_map
     * email
     * 
     */
    public function __construct(Array $params)
    {
        $this->params = $params;
    }

}