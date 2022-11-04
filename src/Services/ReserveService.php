<?php


namespace Twdd\Services;


use Illuminate\Support\Carbon;
use Twdd\Facades\MatchFactory;
use Twdd\Facades\MatchService as TwddMatchService;
use Twdd\Repositories\BlackHatDetailRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;
use Twdd\Services\Match\CallTypes\CallType5;

class ReserveService
{
    private $calldriverTaskMapRepository;
    private $blackHatDetailRepository;

    public function __construct(CalldriverTaskMapRepository $calldriverTaskMapRepository,
                                BlackHatDetailRepository $blackHatDetailRepository)
    {
        $this->calldriverTaskMapRepository = $calldriverTaskMapRepository;
        $this->blackHatDetailRepository = $blackHatDetailRepository;
    }

    // 取得預約列表，已出發單排除
    public function getMemberReserves(int $memberId)
    {
        // 黑帽客列表
        $rows = $this->blackHatDetailRepository->getMemberReserves($memberId);

        // 未來一般預約merge

        return $rows;
    }

    public function getMemberReserve(int $memberId, int $calldriverTaskMpaId)
    {
        $calldriverTaskMap = $this->calldriverTaskMapRepository->findMemberTaskMap($calldriverTaskMpaId, $memberId);
        if (empty($calldriverTaskMap)) {
            return null;
        }
        // call_type = 5 黑帽客
        if ($calldriverTaskMap->call_type = 5) {
            $row = $this->blackHatDetailRepository->getMemberReserve($memberId, $calldriverTaskMpaId);
            $row->cancel_status = (new CallType5())->getCancelStatus($row->start_date);
        }

        return $row ?? null;
    }
}
