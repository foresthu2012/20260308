<?php

return [
    // 合同基础相关
    'CONTRACT_NOT_EXIST' => '合同不存在',
    'CONTRACT_SIGNED' => '合同已签署',
    'CONTRACT_SIGNED_CANNOT_EDIT' => '合同已签署，无法编辑',
    'CONTRACT_SIGNED_CANNOT_DELETE' => '合同已签署，无法删除',
    'CONTRACT_ID_REQUIRED' => '合同ID不能为空',

    // 操作结果
    'ADD_SUCCESS' => '添加成功',
    'EDIT_SUCCESS' => '编辑成功',
    'DELETE_SUCCESS' => '删除成功',

    // 表单验证
    'CONTRACT_TITLE_REQUIRED' => '请输入合同标题',
    'CONTRACT_MEMBER_ID_REQUIRED' => '请选择会员',
    'CONTRACT_MEMBER_ID_NUMBER' => '会员ID必须为数字',
    'CONTRACT_FILE_PATH_REQUIRED' => '请上传合同文件',
    'CONTRACT_ORDER_ID_NUMBER' => '订单ID必须为数字',

    // 微信授权签署相关
    'CODE_REQUIRED' => '微信授权码不能为空',
    'WECHAT_MINIAPP_NOT_CONFIGURED' => '微信小程序未配置',
    'WECHAT_LOGIN_FAIL' => '微信登录失败',
    'WECHAT_API_REQUEST_FAILED' => '微信API请求失败',
    'WECHAT_API_RESPONSE_INVALID' => '微信API响应无效',
    'WECHAT_OPENID_MISSING' => '获取OpenID失败',
    'WECHAT_AUTH_ERROR' => '微信授权错误',
    'ALREADY_SIGNED_BY_THIS_USER' => '您已经签署过该合同',

    // 电子签章相关
    'STAMP_GENERATION_FAILED' => '电子签章生成失败',
    'CONTRACT_COMPOSITE_FAILED' => '合同合成失败',
    'CONTRACT_FILE_NOT_FOUND' => '合同文件不存在',
    'FAILED_TO_LOAD_CONTRACT_IMAGE' => '加载合同图片失败',
    'STAMP_FILE_NOT_FOUND' => '签章文件不存在',
    'FAILED_TO_LOAD_STAMP_IMAGE' => '加载签章图片失败',
    'UNSUPPORTED_IMAGE_TYPE' => '不支持的图片类型',
    'INVALID_IMAGE_DIMENSIONS' => '图片尺寸无效',
    'FAILED_TO_COMPOSITE_IMAGES' => '图片合成失败',
    'FAILED_TO_CREATE_OUTPUT_DIRECTORY' => '创建输出目录失败',
    'FAILED_TO_SAVE_FINAL_IMAGE' => '保存最终图片失败',

    // 数据保存相关
    'SIGNATURE_RECORD_SAVE_FAILED' => '签署记录保存失败',
    'CONTRACT_UPDATE_FAILED' => '合同更新失败',
];


