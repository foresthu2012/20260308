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

use think\facade\Route;

use app\adminapi\middleware\AdminCheckRole;
use app\adminapi\middleware\AdminCheckToken;
use app\adminapi\middleware\AdminLog;

/**
 * 合同签署
 */
Route::group('Contract', function () {

    /***************************************************** Contract ****************************************************/
    Route::get('contract', 'addon\Contract\app\adminapi\controller\contract\Contract@lists');
    Route::get('contract/:id', 'addon\Contract\app\adminapi\controller\contract\Contract@info');
    Route::post('contract', 'addon\Contract\app\adminapi\controller\contract\Contract@add');
    Route::put('contract/:id', 'addon\Contract\app\adminapi\controller\contract\Contract@edit');
    Route::delete('contract/:id', 'addon\Contract\app\adminapi\controller\contract\Contract@delete');
    Route::get('contract/:id/view', 'addon\Contract\app\adminapi\controller\contract\Contract@viewSigned');

})->middleware([
    AdminCheckToken::class,
    AdminCheckRole::class,
    AdminLog::class
]);
/**
 * 商城订单 (用于合同关联)
 */
Route::group('Contract/Shop', function () {
    Route::get('order/lists', 'addon\Contract\app\adminapi\controller\shop\Order@lists');
})->middleware([
    AdminCheckToken::class,
    AdminCheckRole::class,
    AdminLog::class
]);