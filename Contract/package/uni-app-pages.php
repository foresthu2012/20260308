<?php
return [
    // 页面配置
    'pages' => <<<EOT
            // PAGE_BEGIN
            // *********************************** {{addon_name}} ***********************************
            {
                "root": "addon/{{addon_name}}",
                "pages": [
                    {
                        "path": "pages/contract/list",
                        "style": {
                            "navigationBarTitleText": "我的合同",
                            "enablePullDownRefresh": true
                        }
                    },
                    {
                        "path": "pages/contract/detail",
                        "style": {
                            "navigationBarTitleText": "合同详情"
                        }
                    }
                ]
            },
            // PAGE_END
EOT
    ,
    
    // 文件列表
    'files' => [
        'uni-app/pages/**/*',
        'uni-app/api/**/*',
        'uni-app/utils/**/*',
        'uni-app/locale/**/*',
    ],
];
