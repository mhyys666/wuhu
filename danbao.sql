SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. 用户表 (对应个人中心)
-- ----------------------------
DROP TABLE IF EXISTS `db_users`;
CREATE TABLE `db_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '加密密码',
  `avatar` varchar(255) DEFAULT '/static/default_avatar.png',
  `balance` decimal(20,4) DEFAULT '0.0000' COMMENT '可用余额(USDT)',
  `frozen_balance` decimal(20,4) DEFAULT '0.0000' COMMENT '担保冻结金额',
  `credit_score` int(11) DEFAULT '100' COMMENT '诚信分',
  `is_admin` tinyint(1) DEFAULT '0' COMMENT '1为管理员',
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户核心表';

-- ----------------------------
-- 2. 担保订单表 (对应首页/任务列表)
-- ----------------------------
DROP TABLE IF EXISTS `db_orders`;
CREATE TABLE `db_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '唯一订单号',
  `title` varchar(100) NOT NULL COMMENT '任务标题(如: iPhone13换屏)',
  `type` varchar(20) DEFAULT 'general' COMMENT '类型: service(服务), trade(交易)',
  
  -- 角色关系
  `sponsor_id` int(11) NOT NULL COMMENT '发起人(买家/雇主)',
  `receiver_id` int(11) DEFAULT NULL COMMENT '接收人(卖家/服务商)',
  `group_id` int(11) DEFAULT '0' COMMENT '关联的担保群ID (对应图二)',
  
  -- 资金与状态
  `amount` decimal(20,4) NOT NULL COMMENT '担保金额',
  `status` tinyint(1) DEFAULT '0' COMMENT '0:待接单 1:待托管 2:进行中(已托管) 3:待验收 4:已完成 5:纠纷中 9:已取消',
  
  -- 业务字段
  `step_desc` varchar(255) DEFAULT '等待接单' COMMENT '当前进度描述 (如: 1/3单)',
  `deadline` int(11) DEFAULT NULL COMMENT '截止时间',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='核心担保订单表';

-- ----------------------------
-- 3. 聊天群组表 (对应图二: 担保群)
-- ----------------------------
DROP TABLE IF EXISTS `db_groups`;
CREATE TABLE `db_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '群名称',
  `owner_id` int(11) NOT NULL COMMENT '群主ID',
  `order_id` int(11) DEFAULT '0' COMMENT '关联订单ID(如果是临时担保群)',
  `member_count` int(11) DEFAULT '1',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='聊天群组表';

-- ----------------------------
-- 4. 纠纷仲裁表 (对应图三: 纠纷处理)
-- ----------------------------
DROP TABLE IF EXISTS `db_disputes`;
CREATE TABLE `db_disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT '发起纠纷的人',
  `reason` varchar(255) NOT NULL COMMENT '纠纷原因',
  `evidence_imgs` text COMMENT '证据图片JSON',
  `status` tinyint(1) DEFAULT '0' COMMENT '0:待处理 1:客服介入中 2:仲裁完成',
  `result` text COMMENT '仲裁结果',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='纠纷仲裁记录';

-- 预置一个管理员账号 (admin / 123456) --
INSERT INTO `db_users` (`username`, `password`, `is_admin`, `created_at`) VALUES ('admin', '$2y$10$ThK5...', 1, 1735689600);
