<?php
require_once 'db.php';

$userId = $_GET['user_id'] ?? 0; // 当前用户
$targetId = $_GET['target_id'] ?? 0; // 对方用户

if (!$userId || !$targetId) {
    jsonResult(200, '参数错误', []);
}

// 简单的查询双方往来记录
$stmt = $pdo->prepare("
    SELECT * FROM db_messages 
    WHERE (from_uid = ? AND to_uid = ?) 
       OR (from_uid = ? AND to_uid = ?) 
    ORDER BY id ASC
");
$stmt->execute([$userId, $targetId, $targetId, $userId]);
$msgs = $stmt->fetchAll();

$data = [];
foreach ($msgs as $msg) {
    $data[] = [
        'id' => $msg['id'],
        'type' => $msg['from_uid'] == $userId ? 'me' : 'other',
        'content' => $msg['content'],
        'created_at' => date('Y-m-d H:i:s', $msg['created_at'])
    ];
}

jsonResult(200, 'success', $data);
?>