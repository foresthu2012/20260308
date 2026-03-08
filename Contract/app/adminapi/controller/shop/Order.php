<?php
namespace addon\Contract\app\adminapi\controller\shop;

use core\base\BaseAdminController;
use addon\Contract\app\service\admin\shop\OrderService;
use think\Response;

/**
 * Order Controller for Admin
 */
class Order extends BaseAdminController
{
    /**
     * 获取订单列表
     */
    public function lists()
    {
        $data = $this->request->params([
            ['order_no', ''],
            ['member_id', ''],
            ['page', 1],
            ['limit', 10]
        ]);
        
        $where = [];
        
        // 订单号搜索
        if (!empty($data['order_no'])) {
            $where[] = ['order_no', 'like', '%' . $data['order_no'] . '%'];
        }
        
        // 会员 ID 筛选
        if (!empty($data['member_id'])) {
            $where[] = ['member_id', '=', $data['member_id']];
        }
        
        $service = new OrderService();
        $res = $service->getList($where, $data['page'], $data['limit']);
        
        return success($res);
    }
}
