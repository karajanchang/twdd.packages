<?php
/*
 * 15分鐘內沒到的送優惠券
 */

namespace Twdd\Listeners;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twdd\Events\TaskDoneEvent;
use Twdd\Facades\CouponFactory;
use Twdd\Models\Task;
use Twdd\Repositories\ActivityRepository;
use Twdd\Services\Task\TaskNo;


class Send15MinutesArriveCouponListener
{
    private $task = null;
    /**
     * Send15MinutesArriveCoupon constructor.
     */
    public function __construct()
    {
    }

    public function handle(TaskDoneEvent $taskDoneEvent){
        $this->setTask($taskDoneEvent->task);
        if($this->checkIfValidate()===true){
            $activity = $this->getActivity();
            if(!isset($activity->id)){
                Log::info('(Send15MinutesArriveCouponListener) 找不到相對應的活動 taskId:' . $this->task->id);

                return ;
            }
            $this->createCoupon($activity);
        }
    }

    private function createCoupon(Model $activity){
        $params = [
            'title' => $activity->coupon_title,
            'money' => $activity->money,
            'startTS' => time(),
            'endTS' => Carbon::create(null, null, null, '23', '59', '59')->addDays(30)->timestamp,
            'only_first_use' => 0,
            'activity_id' =>  $activity->id,
        ];
        $coupon = CouponFactory::type('coupon')->member($this->task->member)->create($params);
        if(!isset($coupon->id)){
            Log::error('(Send15MinutesArriveCouponListener) 建立coupon失敗！！！'.$this->task->id);
        }

        $this->log($coupon);

        //--送出推播通知
        $this->pushNotification($coupon);
    }

    private function pushNotification(Model $coupon){
        $title = '關於抵達時間，讓您久候了...';
        $body = '由於 ' . TaskNo::make($this->task->id) .' 服務，司機抵達時間超過 15 分鐘，台灣代駕讓您下一趟享有 $'.$coupon->money.' 優惠，同時會致力於縮短您的等候時間。';

    }
    private function setTask(Task $task){
        $this->task = $task;
    }

    private function checkIfValidate(){
        //活動期間
        $taskCreateTS = Carbon::now()->timestamp;
        $activityStartTS = Carbon::create('2019', '11', '1', '0', '0', '0')->timestamp;
        $activityEndTS =  Carbon::create('2020', '4', '30', '23', '59', '59')->timestamp;

        //排除活動時間之外
        if ($taskCreateTS < $activityStartTS || $taskCreateTS > $activityEndTS) {

            return false;
        }

        //排除語音呼叫/電話呼叫/APP快速上單
        if ($this->task->type == 2 || $this->task->type == 6 || $this->task->is_quick_match_by_driver == 1) {

            return false;
        }

        //15分內抵達排除;剛好15分00秒也發優惠券;扣除預設保留時間
        $arriveInterval = $this->task->TaskArriveTS - $this->task->TaskStartCallTS - env('MINUS_DRIVER_WALK_SECOND', 10);
        if ($arriveInterval < 15 * 60) {

            return false;
        }

        return true;
    }

    private function getActivity(){
        $activityId = env('FIFTEEN_ARRIVE_ACTIVITY_ID');
        if (empty($activityId)) {
            Log::info('(Send15MinutesArriveCouponListener) 15分必達優惠券ID未設定 taskId:' . $this->task->id);

            return null;
        }

        $repository = app()->make(ActivityRepository::class);

        return $repository->find($activityId);
    }

    private function log(Model $coupon){
        $insertData = [
            'task_id' => $this->task->id,
            'coupon_id' => $coupon->id,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        $result = DB::table('fifteen_arrive_log')->insert($insertData);
        if (is_string($result)) {
            Log::info('(Send15MinutesArriveCouponListener) 新增Log失敗 taskId:' . $this->task->id);
        }
    }
}