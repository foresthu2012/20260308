CREATE TABLE IF NOT EXISTS `{{prefix}}addon_contract_signature_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL DEFAULT '0' COMMENT '站点id',
  `contract_id` int(11) NOT NULL DEFAULT '0' COMMENT '合同ID',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
  `openid` varchar(255) NOT NULL DEFAULT '' COMMENT '用户OpenID',
  `sign_time` int(11) NOT NULL DEFAULT '0' COMMENT '签署时间',
  `sign_image` varchar(255) NOT NULL DEFAULT '' COMMENT '签署印章路径',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  KEY `contract_id` (`contract_id`),
  KEY `member_id` (`member_id`),
  KEY `openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='合同签署记录表';
