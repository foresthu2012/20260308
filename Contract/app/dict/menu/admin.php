<?php

return  [
    [
        'menu_name' => '合同签署',
        'menu_key' => 'Contract',
        'menu_type' => 0,
        'icon' => '',
        'api_url' => '',
        'router_path' => '',
        'view_path' => '',
        'methods' => '',
        'sort' => 100,
        'status' => 1,
        'is_show' => 1,
        'children' => [
            [
                'menu_name' => '合同管理',
                'menu_key' => 'contract_list',
                'menu_type' => 1,
                'icon' => '',
                'api_url' => 'Contract/contract',
                'router_path' => 'contract/list',
                'view_path' => 'contract/contract/index',
                'methods' => 'get',
                'sort' => 100,
                'status' => 1,
                'is_show' => 1,
                'children' => []
            ],
        ]
    ]
];
