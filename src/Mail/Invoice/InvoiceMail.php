<?php

namespace Twdd\Mail\Invoice;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;


class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;
    private $msg;
    private $status;
    private $err;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->msg = $params["msg"];
        $this->status = $params["status"];
        $this->err = $params["err"] ?? '';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        switch ($this->status) {
            case 1:
                $subject = "Twdd發票開立成功通知";
                $view = 'twdd::emails.invoice.success';
                break;
            case 2:
                $subject = "Twdd發票開立失敗通知";
                $view = 'twdd::emails.invoice.fail';
                break;
            case 3:
                $subject = "Twdd發票作廢成功通知";
                $view = 'twdd::emails.invoice.success';
                break;
            case 4:
                $subject = "Twdd發票作廢失敗通知";
                $view = 'twdd::emails.invoice.fail';
                break;
        }

        return $this->subject($subject)
            ->view($view)
            ->with([
                'msg' => $this->msg,
                'err' => $this->err
            ]);
    }
}
