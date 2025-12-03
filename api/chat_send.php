<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResult(400, 'Error');
}

$input = json_decode(file_get_contents('php://input'), true);
$fromId = $input['user_id'];
$toId = $input['target_id'];
$content = trim($input['content']);

if (empty($content)) {
    jsonResult(400, '不能发送空消息');
}

// 简单的建表检查，如果Phase1没建表，这里会报错，所以确保你运行过install.php
// 如果 db_messages 不存在，请手动在数据库执行 Phase 1 的 SQL
// 为了保险，我们在这里尝试自动建表（极简模式）
$pdo->exec("CREATE TABLE IF NOT EXISTS `db_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_uid` int(11) NOT NULL,
  `to_uid` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$stmt = $pdo->prepare("INSERT INTO db_messages (from_uid, to_uid, content, created_at) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$fromId, $toId, $content, time()])) {
    jsonResult(200, '发送成功');
} else {
    jsonResult(500, '发送失败');
}
?>
