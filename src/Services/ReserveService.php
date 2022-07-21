<?php


namespace Twdd\Services;


use Twdd\Repositories\BlackHatDetailRepository;
use Twdd\Repositories\CalldriverTaskMapRepository;

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
        // 取得類型 type = 1 && call_type = 5 黑帽客
        if ($calldriverTaskMap->type == 1 && $calldriverTaskMap->call_type = 5) {
            $row = $this->blackHatDetailRepository->getMemberReserve($memberId, $calldriverTaskMpaId);
        }


        return $row ?? null;
    }
}
