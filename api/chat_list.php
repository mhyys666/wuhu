<?php
require_once 'db.php';

$currentUserId = $_GET['user_id'] ?? 0;

if (!$currentUserId) {
    jsonResult(200, '未登录', []);
}

// 核心修复：获取我的所有对话伙伴（去重）
// 逻辑：查找所有我参与的消息，按对方ID分组，取最新的消息ID
$sql = "
    SELECT 
        CASE 
            WHEN from_uid = ? THEN to_uid 
            ELSE from_uid 
        END AS partner_id,
        MAX(created_at) as last_time
    FROM db_messages 
    WHERE from_uid = ? OR to_uid = ?
    GROUP BY partner_id
    ORDER BY last_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
$conversations = $stmt->fetchAll();

$result = [];
foreach ($conversations as $conv) {
    $pid = $conv['partner_id'];
    
    // 1. 获取对方信息
    $uStmt = $pdo->prepare("SELECT id, username, avatar FROM db_users WHERE id = ?");
    $uStmt->execute([$pid]);
    $user = $uStmt->fetch();
    
    // 2. 获取最后一条消息内容
    $mStmt = $pdo->prepare("SELECT content FROM db_messages WHERE (from_uid = ? AND to_uid = ?) OR (from_uid = ? AND to_uid = ?) ORDER BY created_at DESC LIMIT 1");
    $mStmt->execute([$currentUserId, $pid, $pid, $currentUserId]);
    $lastMsg = $mStmt->fetch();

    if ($user) {
        $result[] = [
            'partner_id' => $user['id'],
            'name' => $user['username'],
            'avatar' => $user['avatar'], // 注意：如果没头像前端会用首字母代替
            'content' => $lastMsg ? $lastMsg['content'] : '[图片/语音]',
            'time' => date('m-d H:i', $conv['last_time'])
        ];
    }
}

jsonResult(200, '获取成功', $result);
?>