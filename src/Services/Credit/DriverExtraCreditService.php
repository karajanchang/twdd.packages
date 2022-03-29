<?php

namespace Twdd\Services\Credit;

use Twdd\Repositories\DriverExtraCreditRepository;
use Twdd\Repositories\DriverExtraCreditLogRepository;
use Twdd\Repositories\DriverCreditChangeTypeRepository;

class DriverExtraCreditService
{
    private $driverExtraCreditRepository;
    private $driverExtraCreditLogRepository;
    private $driverCreditChangeTypeRepository;

    public function __construct(
        DriverExtraCreditRepository $driverExtraCreditRepository,
        DriverExtraCreditLogRepository $driverExtraCreditLogRepository,
        DriverCreditChangeTypeRepository $driverCreditChangeTypeRepository
    )
    {
        $this->driverExtraCreditRepository = $driverExtraCreditRepository;
        $this->driverExtraCreditLogRepository = $driverExtraCreditLogRepository;
        $this->driverCreditChangeTypeRepository = $driverCreditChangeTypeRepository;
    }

    private function allowTypeList()
    {
        $keyword   = $this->driverCreditChangeTypeRepository::ALLOW_EXTRA_CREDIT;
        $plusMinus = $this->driverCreditChangeTypeRepository::EXTRA_CREDIT_PLUS_MINUS;

        $list = $this->driverCreditChangeTypeRepository->model()::whereIn('keyword', $keyword)->get();

        foreach ($list as $row) {
            $row->plusMins = $plusMinus[$row->keyword] ?: 0;
        }

        return $list->toArray();
    }

    public function allowTypeSelectTagList()
    {
        $plusList  = [];
        $minusList = [];
        $list = $this->allowTypeList();

        foreach ($list as $row) {
            if ($row['plusMins'] > 0) {
                $plusList[] = $row;
            } else {
                $minusList[] = $row;
            }
        }

        return [
            'minus' => $minusList,
            'plus'  => $plusList
        ];
    }

    public function getExtraCredit(int $type, int $driver_id, $createTime)
    {
        $createTime = ($createTime) ?? date('Y-m-d H:i:s');
        $list = $this->driverExtraCreditRepository->model()::select([
            'driver_extra_credit.*',
            'driver_credit_change_type.keyword',
        ])
        ->where([
            ['type', $type],
            ['driver_id', $driver_id],
            ['contract_start_at', '<=', $createTime],
            ['contract_end_at', '>=', $createTime]
        ])
        ->join('driver_credit_change_type', 'driver_credit_change_type.id', '=', 'driver_extra_credit.type')
        ->whereNull('deleted_at')
        ->get();

        $plusMinus = $this->driverCreditChangeTypeRepository::EXTRA_CREDIT_PLUS_MINUS;

        $credit = 0;
        foreach ($list as $row) {
            $row->plusMins = $plusMinus[$row->keyword] ?: 0;

            $credit += $row->plusMins * $row->credit;
        }

        return [
            'credit' => $credit,
            'list'   => $list ? $list->toArray() : []
        ];
    }

    public function addExtraCreditLog(int $driverCreditId, array $extraCreditList)
    {
        if (!$extraCreditList) {
            return;
        }

        foreach($extraCreditList as $row) {
            $params = [
                'driver_credit_change_id' => $driverCreditId,
                'driver_id' => $row['driver_id'],
                'type'      => $row['type'],
                'credit'    => $row['credit'] * $row['plusMins'],
                'contract_start_at' => $row['contract_start_at'],
                'contract_end_at'   => $row['contract_end_at'],
                'updater_id'        => $row['updater_id'],
                'created_at'        => date('Y-m-d H:i:s')
            ];

            $class = $this->driverExtraCreditLogRepository->model();
            $repo = new $class();
            $repo->insert($params);
        }
    }
}
