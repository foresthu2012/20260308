<?php
namespace addon\Contract\app\model\order;

use think\Model;

/**
 * Order Model
 */
class Order extends Model
{
    /**
     * 数据表主键
     */
    protected $pk = 'order_id';

    /**
     * 模型名称
     */
    protected $name = 'shop_order';

    /**
     * 追加字段
     */
    protected $append = ['order_status_name'];

    /**
     * 获取订单列表
     */
    public function getList($where = [], $order = 'create_time desc', $field = '*')
    {
        return $this->where($where)->order($order)->field($field)->select();
    }

    /**
     * 获取订单分页
     */
    public function getPage($where = [], $order = 'create_time desc', $field = '*', $page = 1, $page_size = 10)
    {
        return $this->where($where)->order($order)->field($field)->page($page, $page_size)->select();
    }

    /**
     * 获取订单总数
     */
    public function getCount($where = [])
    {
        return $this->where($where)->count();
    }

    /**
     * 获取订单状态名称
     */
    public function getOrderStatusNameAttr($value, $data)
    {
        // 确保 status 存在，避免 Undefined index 警告
        if (!isset($data['status'])) {
            return '';
        }
        $status_map = [
            0 => '已取消',
            1 => '待付款',
            2 => '已付款',
            3 => '待发货',
            4 => '已发货',
            5 => '已完成',
            6 => '已退款'
        ];
        return $status_map[$data['status']] ?? '未知状态';
    }

    /**
     * 获取订单详情
     */
    public function getDetail($order_id)
    {
        return $this->find($order_id);
    }
}
