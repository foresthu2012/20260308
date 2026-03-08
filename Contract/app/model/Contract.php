<?php
namespace addon\Contract\app\model;

use core\base\BaseModel;
use think\model\concern\SoftDelete;

/**
 * Contract Model
 * 合同模型
 * 
 * @package addon\Contract\app\model
 */
class Contract extends BaseModel
{
    use SoftDelete;

    /**
     * Data table name
     * @var string
     */
    protected $name = 'addon_contract_contract';

    /**
     * Define primary key
     * @var string
     */
    protected $pk = 'id';

    /**
     * Soft delete field
     * @var string
     */
    protected $deleteTime = 'delete_time';

    /**
     * Default soft delete value
     * @var int
     */
    protected $defaultSoftDelete = 0;

    /**
     * Type conversion
     * @var array
     */
    protected $type = [
        'create_time' => 'integer',
        'update_time' => 'integer',
        'delete_time' => 'integer',
        'sign_time'   => 'integer',
        'site_id'     => 'integer',
        'member_id'   => 'integer',
        'order_id'    => 'integer',
        'status'      => 'integer',
    ];

    /**
     * 字段注释
     * @var array
     */
    protected $fieldComment = [
        'id' => '合同ID',
        'site_id' => '站点ID',
        'title' => '合同标题',
        'file_path' => '合同文件路径(待签署状态显示)',
        'file_ext' => '文件扩展名',
        'member_id' => '会员ID',
        'order_id' => '关联订单ID',
        'status' => '签署状态: 0待签署 1已签署',
        'sign_image' => '已签署合同图片路径(电子签章合成后的最终合同文件)',
        'sign_time' => '签署时间',
        'create_time' => '创建时间',
        'update_time' => '更新时间',
        'delete_time' => '删除时间',
    ];

    /**
     * Get Create Time Attr
     * @param $value
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * Get Update Time Attr
     * @param $value
     * @return string
     */
    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * Get Sign Time Attr
     * @param $value
     * @return string
     */
    public function getSignTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * Relation with Member
     */
    public function member()
    {
        return $this->hasOne('app\model\member\Member', 'member_id', 'member_id');
    }

    /**
     * Relation with Order
     */
    public function order()
    {
        return $this->hasOne('addon\Contract\app\model\order\Order', 'order_id', 'order_id')
            ->field('order_id, order_no, order_money, status');
    }
}
