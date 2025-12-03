<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResult(400, 'Method Not Allowed');
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? 0;
$groupId = $input['group_id'] ?? '';

if (!$userId || !$groupId) {
    jsonResult(400, '请输入群号');
}

// 模拟：创建一条系统消息，假装用户加入了群聊
// 这样在聊天列表里就会出现这个"群"（其实是和机器人的对话）
$systemBotId = 99999;
$groupName = "担保交流群 " . htmlspecialchars($groupId);

// 检查是否已经有对话
$check = $pdo->prepare("SELECT id FROM db_messages WHERE (from_uid=? AND to_uid=?) OR (from_uid=? AND to_uid=?) LIMIT 1");
$check->execute([$userId, $systemBotId, $systemBotId, $userId]);

if (!$check->fetch()) {
    // 插入欢迎语，这样聊天列表就会显示这个群
    $stmt = $pdo->prepare("INSERT INTO db_messages (from_uid, to_uid, content, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$systemBotId, $userId, "欢迎加入 $groupName ！请文明交流，切勿私下交易。", time()]);
}

// 顺便把机器人名字存入用户表，防止列表查不到名字显示 undefined
$checkUser = $pdo->prepare("SELECT id FROM db_users WHERE id = ?");
$checkUser->execute([$systemBotId]);
if (!$checkUser->fetch()) {
    $pdo->prepare("INSERT INTO db_users (id, username, password, avatar, created_at) VALUES (?, ?, ?, ?, ?)")
        ->execute([$systemBotId, '官方群助手', '', '', time()]);
}

jsonResult(200, '加入群聊成功！请在消息列表中查看。');
?>