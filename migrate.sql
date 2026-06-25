-- 赞盟 v1.0.3 数据库迁移
-- 删除代理商表 cert_number 和 domain 的 UNIQUE 索引
-- 这两个字段的 UNIQUE 约束会导致默认空字符串冲突，使入驻申请对所有新用户报错

ALTER TABLE `agents` DROP INDEX `idx_cert_number`;
ALTER TABLE `agents` DROP INDEX `idx_domain`;
