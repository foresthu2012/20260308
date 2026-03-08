-- Update database table comments for v1.1.2
-- Version: 1.1.2
-- Description: Clean up hand-written signature functionality and optimize WeChat authorization signing

-- Update contract table comments
ALTER TABLE `addon_contract_contract`
MODIFY COLUMN `sign_image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '已签署合同图片路径(电子签章合成后的最终合同文件)';

ALTER TABLE `addon_contract_contract`
MODIFY COLUMN `status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '签署状态: 0待签署 1已签署';

-- Update signature_record table comments
ALTER TABLE `addon_contract_signature_record`
MODIFY COLUMN `openid` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '微信OpenID(用于防重复签署)';

ALTER TABLE `addon_contract_signature_record`
MODIFY COLUMN `sign_image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '已签署合同图片路径(电子签章合成后的最终合同文件)';

-- Update version record
INSERT INTO `addon_contract_version` (`version`, `update_time`, `description`)
VALUES ('1.1.2', UNIX_TIMESTAMP(), '删除手写签名功能,优化微信授权签署,改进电子签章生成和合成,完善错误处理和状态管理')
ON DUPLICATE KEY UPDATE
    `update_time` = UNIX_TIMESTAMP(),
    `description` = '删除手写签名功能,优化微信授权签署,改进电子签章生成和合成,完善错误处理和状态管理';
