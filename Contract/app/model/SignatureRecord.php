<?php
namespace addon\Contract\app\model;

use core\base\BaseModel;

/**
 * Signature Record Model
 * 签署记录模型 - 记录使用微信授权签署的合同记录
 * 
 * @package addon\Contract\app\model
 */
class SignatureRecord extends BaseModel
{
    /**
     * Data table name
     * @var string
     */
    protected $name = 'addon_contract_signature_record';

    /**
     * Define primary key
     * @var string
     */
    protected $pk = 'id';

    /**
     * Type conversion
     * @var array
     */
    protected $type = [
        'sign_time'   => 'integer',
        'site_id'     => 'integer',
        'member_id'   => 'integer',
        'contract_id' => 'integer',
    ];

    /**
     * 字段注释
     * @var array
     */
    protected $fieldComment = [
        'id' => '记录ID',
        'site_id' => '站点ID',
        'contract_id' => '合同ID',
        'member_id' => '会员ID',
        'openid' => '微信OpenID(用于防重复签署)',
        'sign_time' => '签署时间戳',
        'sign_image' => '已签署合同图片路径(电子签章合成后的最终合同文件)',
    ];

    /**
     * Get Sign Time Attr
     * @param $value
     * @return string
     */
    public function getSignTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }
}
