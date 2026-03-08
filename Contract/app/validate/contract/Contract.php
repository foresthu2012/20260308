<?php
namespace addon\Contract\app\validate\contract;

use core\base\BaseValidate;

/**
 * Contract Validator
 * @package addon\Contract\app\validate\contract
 */
class Contract extends BaseValidate
{
    protected $rule = [
        'title'     => 'require',
        'member_id' => 'require|number',
        'file_path' => 'require',
        'order_id'  => 'number',
    ];

    protected $message = [
        'title.require'     => '请输入合同标题',
        'member_id.require' => '请选择会员',
        'member_id.number'  => '会员ID格式错误',
        'file_path.require' => '请上传合同文件',
        'order_id.number'   => '订单ID格式错误',
    ];

    protected $scene = [
        'add'  => ['title', 'member_id', 'file_path', 'order_id'],
        'edit' => ['title', 'member_id', 'file_path', 'order_id'],
    ];
}
