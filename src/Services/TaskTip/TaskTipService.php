<?php
namespace Twdd\Services\TaskTip;


use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Twdd\Models\Driver;
use Twdd\Models\Member;
use Twdd\Models\MemberCreditcard;
use Twdd\Models\Task;
use Twdd\Models\TaskTip;
use Twdd\Repositories\DriverMerchantRepository;
use Twdd\Repositories\MemberRepository;
use Twdd\Repositories\TaskTipCreditcardLogRepository;
use Twdd\Repositories\TaskTipRepository;
use Twdd\Services\Payment_v2\PaymentAbstract;
use Twdd\Services\Payment_v2\SpGateway\Pay;
use Twdd\Services\Payment_v2\SpGateway\SpGatewayService;

class TaskTipService
{
    private $memberRepository;
    private $driverMerchantRepository;
    private $taskTipRepository;
    private $taskTipCreditcardLogRepository;

    public function __construct(MemberRepository $memberRepository, DriverMerchantRepository $driverMerchantRepository,
                                TaskTipRepository $taskTipRepository, TaskTipCreditcardLogRepository $taskTipCreditcardLogRepository)
    {
        $this->memberRepository = $memberRepository;
        $this->driverMerchantRepository = $driverMerchantRepository;
        $this->taskTipRepository = $taskTipRepository;
        $this->taskTipCreditcardLogRepository = $taskTipCreditcardLogRepository;
    }

    public function payWithCreditcard(int $money, Task $task, MemberCreditcard $creditcard)
    {
        $payType = 2; // 信用卡
        $payment = new SpGatewayService();
        $member = $this->memberRepository->profile($task->member_id);
        $merchant = $this->driverMerchantRepository->findBy('driver_id', $task->driver_id);

        if (empty($member)) {
            throw new \Exception('此會員不存在');
        }
        if (empty($member->UserEmail)) {
            throw new \Exception('尚未設定E-mail');
        }
        if ($creditcard->member_id != $member->id) {
            throw new \Exception('信用卡非此會員所有');
        }
        if (empty($merchant)) {
            throw new \Exception('此商店不存在');
        }

        $taskTip = $this->createTaskTip($task->id, $money, $payType);

        $payment->setPayerEmail($member->UserEmail);
        $payment->setProDesc('代駕任務小費');
        $res = $payment->pay($money, $creditcard, $merchant);
        
        $this->createTaskTipLog($taskTip, $creditcard, $res);
        $this->updateTaskTipToSuccess($taskTip->id, $res);

        return true;
    }

    private function createTaskTip(int $taskId, int $money, int $payType)
    {
        $data = [
            'task_id' => $taskId,
            'pay_type' => $payType,
            'status' => 0,
            'money' => $money,
        ];

        return $this->taskTipRepository->create($data);
    }

    private function createTaskTipLog(TaskTip $taskTip, MemberCreditcard $memberCreditcard, array $res)
    {
        $data = [
            'task_tip_id' => $taskTip->id,
            'member_creditcard_id' => $memberCreditcard->id,
            'order_no' => $res['Result']['MerchantOrderNo'] ?? '',
            'status' => $res['Status'] ?? '',
            'message' => $res['Message'] ?? '',
            'response' => json_encode($res),
        ];
        $this->taskTipCreditcardLogRepository->create($data);
        Log::info('createTaskTipLog data', $data);
    }

    private function updateTaskTipToSuccess(int $taskTipId, array $res)
    {
        if (!isset($res['Status']) || $res['Status'] != 'SUCCESS') {
            return;
        }

        $data = ['status' => 1];
        $this->taskTipRepository->where('id', $taskTipId)->update($data);
    }
}
