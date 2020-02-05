<?php

return [
    'must_provide_email_for_spgateway_to_pay' => '刷卡付款必需要有email才能進行',
    'money_must_over_zero_for_spgateway_to_pay' => '刷卡付款的金額必需大於0',
    'spgateway_error' => '刷卡付款失敗',
    'spgateway_time_too_close' => '執行時間間隔太短，請過 :try_seconds 秒再試',
    'spgateway_exception' => '刷卡付款異常，金流公司無回應',
    'spgateway_query_error' => '查詢失敗',
    'not_spgateway_task' => '此單非信用卡付款，無法查詢',
];

