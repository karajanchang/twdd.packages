<?php


namespace Twdd\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Twdd\Events\TaskDoneEvent;
use Twdd\Facades\CouponFactory;
use Twdd\Models\Member;
use Twdd\Models\Task;
use Twdd\Repositories\MemberRepository;

class TaskDoneGiveShareCouponListener
{
    private $memberRepository;
    private $task;
    /**
     * LineUseFirstDiscountListener constructor.
     */
    public function __construct(MemberRepository $memberRepository)
    {
        $this->memberRepository = $memberRepository;
    }

    public function handle(TaskDoneEvent $taskDoneEvent){
        $this->setTask($taskDoneEvent->task);

        if($this->checkIfCanGetCoupon()===true) {
            $sendMember = $this->getSendMember();
            if (!isset($sendMember->id)) {
                Log::info('(TaskDoneGiveShareCouponListener) 送優惠失敗: 找不到贈送的人');

                return false;
            }
        }

        $this->createCoupon($sendMember);
    }

    private function createCoupon(Member $sendMember){
        $params = [
            'title' => env('邀請優惠'),
            'money' => env('COUPON_SHARE', 50),
            'startTS' => time(),
            'endTS' => Carbon::create(null, null, null, '23', '59', '59')->addMonths(env('COUPON_SHARE_VALIDATE_MONTH', 1))->timestamp,
            'only_first_use' => 0,
            'mobile' => $sendMember->UserPhone,
            'member_id' => $sendMember->id,
            'isOnlyForThisMember' => 1,
        ];

        $coupon = CouponFactory::type('coupon')->member($this->task->member)->create($params);
        if(!isset($coupon->id)){
            Log::error('(TaskDoneGiveShareCouponListener) 建立coupon失敗！！！'.$this->task->id);
        }
    }
    private function setTask(Task $task){
        $this->task = $task;
    }

    private function checkIfCanGetCoupon(){
        if(!isset($this->task->member_id)){
            Log::error('(TaskDoneGiveShareCouponListener) 送優惠失敗，沒有member_id 任務id: '.$this->task->id);

            return false;
        }

        if($this->task->member->nums > 1){
            Log::error('(TaskDoneGiveShareCouponListener) 送優惠失敗，member_id '.$this->task->member_id.'非首用 任務id: '.$this->task->id);

            return false;
        }
    }

    private function getSendMember(){
        if(!isset($this->task->member->OtherInviteCode) && strlen($this->task->member->OtherInviteCode)==0){

            return null;
        }

        return $this->memberRepository->byInviteCode($this->task->member->OtherInviteCode);
    }
}
