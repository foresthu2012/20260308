<?php
namespace addon\Contract\app\service\admin\shop;

use addon\Contract\app\model\order\Order;
use core\base\BaseAdminService;

/**
 * Order Service for Admin
 */
class OrderService extends BaseAdminService
{
    /**
     * 获取订单列表
     */
    public function getList(array $where = [], $page = 1, $page_size = 10)
    {
        $field = 'order_id, order_no, order_money, status, create_time';
        $order_model = new Order();
        
        return $this->getPageList($order_model, $where, $field, 'create_time desc', ['page' => $page, 'page_size' => $page_size]);
    }
}
