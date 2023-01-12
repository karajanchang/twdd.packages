<?php


namespace Twdd\Services;


use App\Member;
use Illuminate\Support\Carbon;
use Twdd\Facades\MatchFactory;
use Twdd\Facades\MatchService as TwddMatchService;
use Twdd\Repositories\AddressRepository;
use Twdd\Repositories\BlackHatDetailRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Repositories\ReserveDemandRepository;
use Twdd\Services\Match\CallTypes\CallType5;

class ReserveService
{
    private $calldriverTaskMapRepository;
    private $blackHatDetailRepository;
    private $addressService;
    private $reserveDemandRepository;

    public function __construct(CalldriverTaskMapRepository $calldriverTaskMapRepository,
                                BlackHatDetailRepository $blackHatDetailRepository,
                                AddressService $addressService,
                                ReserveDemandRepository $reserveDemandRepository)
    {
        $this->calldriverTaskMapRepository = $calldriverTaskMapRepository;
        $this->blackHatDetailRepository = $blackHatDetailRepository;
        $this->addressService = $addressService;
        $this->reserveDemandRepository = $reserveDemandRepository;
    }

    // 取得預約列表，已出發單排除
    public function getMemberReserves(int $memberId)
    {
        // 黑帽客列表
        $blackHats = $this->blackHatDetailRepository->getMemberReserves($memberId);

        // 未來一般預約merge
        $reserves = $this->reserveDemandRepository->getReserves($memberId);
        foreach ($reserves as $reserve) {
            $reserve->reserve_type = 2;
            $reserve->pay_status = 0;
            switch ($reserve->reserve_status) {
                case 0:
                    $reserve->prematch_status = 2; // 客服派單中
                    break;
                case 1:
                    $reserve->prematch_status = 1; // 成功
                    break;
                case 2:
                    $reserve->prematch_status = -1; // 已取消
                    break;
            }
        }

        $rows = $blackHats->merge($reserves);

        return $rows;
    }

    public function getMemberReserve(int $memberId, int $calldriverTaskMpaId)
    {
        $calldriverTaskMap = $this->calldriverTaskMapRepository->findMemberTaskMap($calldriverTaskMpaId, $memberId);

        if (empty($calldriverTaskMap)) {
            return null;
        }

        // call_type = 5 黑帽客
        if ($calldriverTaskMap->call_type == 5) {
            $row = $this->blackHatDetailRepository->getMemberReserve($memberId, $calldriverTaskMpaId);
            $row->cancel_status = (new CallType5())->getCancelStatus($row->start_date);
        }
        if ($calldriverTaskMap->call_type == 2) {
            $row = $calldriverTaskMap;
            $row->cancel_status = 0;
            if ($row->is_cancel == 1) {
                $row->prematch_status = -1;
            } else if (!empty($row->call_driver_id)) {
                $row->prematch_status = 1;
            } else {
                $row->prematch_status = 2;
            }

        }

        return $row ?? null;
    }

    public function storeReserveDemand(Member $member, array $data)
    {
        $startAddress = $this->addressService->storeAddress($data['start_address']);
        $endAddress = $this->addressService->storeAddress($data['end_address']);
        if (!isset($startAddress->id)) {
            throw new \Exception('出發地址無法分析，請重新輸入');
        }
        if (!isset($endAddress->id)) {
            throw new \Exception('結束地址無法分析，請重新輸入');
        }

        $insertData = [
            'member_id' => $member->id,
            'start_addr_id' => $startAddress->id,
            'end_addr_id' => $endAddress->id,
            'reserve_datetime' => $data['reserve_datetime'],
            'pay_type' => $data['pay_type'],
        ];
        $this->reserveDemandRepository->store($insertData);

        return true;
    }
}
