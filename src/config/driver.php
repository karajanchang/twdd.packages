<?php
return [
    'DriverState' => collect([
        0 => '下線',
        1 => '上線',
        2 => '接單',
    ]),

    'DriverNew' => collect([
        1 => '新手',
        2 => '夥伴',
        3 => '退出',
    ]),

    'is_online' => collect([
        0 => '停權',
        1 => '正常',
    ]),

    'is_out' => collect([
        0 => '正常',
        1 => '退出',
    ]),

    'grade' => collect([
        1 => '新手',
        2 => '夥伴',
        3 => '專家駕駛',
        4 => '大師駕駛',
        5 => '菁英駕駛',
    ]),
];
