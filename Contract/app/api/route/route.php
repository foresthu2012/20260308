<?php
// +----------------------------------------------------------------------
// | Niucloud-admin 企业快速开发的多应用管理平台
// +----------------------------------------------------------------------
// | 官方网址：https://www.niucloud.com
// +----------------------------------------------------------------------
// | niucloud团队 版权所有 开源版本可自由商用
// +----------------------------------------------------------------------
// | Author: Niucloud Team
// +----------------------------------------------------------------------

use app\api\middleware\ApiCheckToken;
use app\api\middleware\ApiLog;
use app\api\middleware\ApiChannel;
use think\facade\Route;


/**
 * 合同签署
 */
Route::group('Contract', function() {
    Route::get('contract', 'addon\Contract\app\api\controller\contract\Contract@lists');
    Route::get('contract/:id', 'addon\Contract\app\api\controller\contract\Contract@info');
    Route::post('contract/sign/:id', 'addon\Contract\app\api\controller\contract\Contract@sign');
    Route::post('contract/confirm_sign/:id', 'addon\Contract\app\api\controller\contract\Contract@confirmSign');
})->middleware(ApiChannel::class)
    ->middleware(ApiCheckToken::class, true) //表示验证登录
    ->middleware(ApiLog::class);

