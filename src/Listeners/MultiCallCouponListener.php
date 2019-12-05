<?php

namespace Twdd\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twdd\Facades\CouponFactory;
use Twdd\Facades\PushService;
use Twdd\Models\Task;
use Twdd\Repositories\MemberRepository;

class MultiCallCouponListener
{
    protected $couponExpDays = 30;
    /**
     * @var MemberRepository
     */
    private $memberRepository;

    /**
     * Create the event listener.
     *
     * @param MemberRepository $memberRepository
     */
    public function __construct(MemberRepository $memberRepository)
    {
        $this->memberRepository = $memberRepository;
    }

    /**
     * Handle the event.
     *
     * @param  Task  $task
     * @return bool
     */
    public function handle(Task $task)
    {
        $valid = $this->validMultiCallInvite($task);
        if (!$valid) {
            return false;
        }

        try {
            $this->sendCoupon($task);
            $this->pushNotification($task);
        } catch (\Exception $e) {
            Log::error('多位司機優惠券失敗'. $task->id . $e->getMessage());
        }
    }

    private function validMultiCallInvite($task)
    {
        //是多位司機呼叫 type=1 && call_type=3
        if ($task->type != 1 || $task->call_type != 3) {

            return false;
        }
        if (empty($task->call_member_id) || $task->member_id == $task->call_member_id) {

            return false;
        }
        if (isset($task->member->nums7) && $task->member->nums7 > 1) {

            return false;
        }

        return true;
    }

    private function sendCoupon($task)
    {
        $activity = $this->getActivity($task);
        if ($activity == false) {

            throw new \Exception('MultiCallCouponListener get activity fail');
        }

        $callMember = $this->memberRepository->find($task->call_member_id);
        $params = [
            'title' => $activity->coupon_title,
            'money' => $activity->money,
            'startTS' => time(),
            'endTS' => Carbon::create(null, null, null, '23', '59', '59')->addDays($this->couponExpDays)->timestamp,
            'only_first_use' => 0,
            'activity_id' =>  $activity->id,
        ];
        $coupon = CouponFactory::type('coupon')->member($callMember)->create($params);

        if (!isset($coupon)) {

            throw new \Exception('MultiCallCouponListener Coupon create fail');
        }
    }

    private function getActivity($task)
    {
        //發優惠券
        $activityId = env('MULTI_CALL_INVITE_ACTIVITY_ID');
        if (empty($activityId)) {
            Log::info('多位司機邀請優惠券ID未設定 taskId:' . $task->id);

            return false;
        }

        $activity = DB::table('activities')->where('id', $activityId)->first();
        if (empty($activity)) {
            Log::info('找不到相對應的活動 taskId:' . $task->id);

            return false;
        }

        return $activity;
    }

    private function pushNotification($task)
    {
        $res = PushService::task($task)->action('PushMsg')->title('幫朋友叫代駕獎勵已送達')->body('成功幫朋友叫代駕，點擊查看獎勵')->send2member();
        Log::info('MultiCallInviteCoupon push: ', [$res]);
    }
}
