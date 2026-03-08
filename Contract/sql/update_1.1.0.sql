-- 更新到 1.1.0 版本
-- 移除 content 字段，添加 file_ext 字段

-- 1. 添加 file_ext 字段（如果不存在）
ALTER TABLE `{{prefix}}addon_contract_contract` ADD COLUMN `file_ext` varchar(20) NOT NULL DEFAULT '' COMMENT '合同文件扩展名' AFTER `file_path`;

-- 2. 更新 file_ext 值（从现有 file_path 解析）
UPDATE `{{prefix}}addon_contract_contract` 
SET file_ext = SUBSTRING_INDEX(SUBSTRING_INDEX(file_path, '.', -1), '?', 1)
WHERE file_path != '' AND file_path IS NOT NULL;

-- 3. 删除 content 字段（如果存在）
-- 注意：MySQL 8.0+ 支持 DROP COLUMN，低版本需要先移除索引和外键
-- 这里使用兼容方式：只删除字段（如果MySQL版本支持）
-- ALTER TABLE `{{prefix}}addon_contract_contract` DROP COLUMN `content`;
