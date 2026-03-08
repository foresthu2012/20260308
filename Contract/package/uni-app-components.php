<?php
/**
 * uni-app 组件打包配置
 * 用于云编译时包含必要的组件文件
 */
return [
    'components' => [
        // 合同预览组件
        'Contract/components/ContractPreview/ContractPreview.vue' => 'uni-app/components/ContractPreview/ContractPreview.vue',
    ],
    'files' => [
        'uni-app/components/**/*',
        'uni-app/pages/**/*',
        'uni-app/api/**/*',
        'uni-app/utils/**/*',
        'uni-app/locale/**/*',
    ],
];
