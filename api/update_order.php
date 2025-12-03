<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResult(400, '请求方式错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// 验证登录
if (empty($token)) {
    jsonResult(401, '请先登录');
}

$title = $input['title'] ?? '';
$amount = floatval($input['amount'] ?? 0);
$receiverName = $input['receiver'] ?? '';
$userId = $input['user_id'];

if (empty($title) || $amount <= 0 || empty($receiverName)) {
    jsonResult(400, '请填写完整信息且金额必须大于0');
}

// 1. 检查对方是否存在
$stmt = $pdo->prepare("SELECT id FROM db_users WHERE username = ?");
$stmt->execute([$receiverName]);
$receiver = $stmt->fetch();
if (!$receiver) jsonResult(400, '对方账户不存在');
if ($receiver['id'] == $userId) jsonResult(400, '不能给自己发起担保');

// 2. 检查自己余额是否充足 (买家需要预先检查，或者在托管时检查)
// 这里假设创建时不扣款，托管时才扣。但既然你说"看自己账号上有没有金币"，我们先做个预检。
$stmtBal = $pdo->prepare("SELECT balance FROM db_users WHERE id = ?");
$stmtBal->execute([$userId]);
$myBalance = floatval($stmtBal->fetchColumn());

// 如果是“买家”角色，通常需要保证有钱
if ($input['role'] === 'buyer' && $myBalance < $amount) {
    jsonResult(400, '您的余额不足支付此担保金额');
}

// 3. 创建订单
$orderNo = 'DB' . date('YmdHis') . rand(1000, 9999);
$sql = "INSERT INTO db_orders (order_no, title, type, sponsor_id, receiver_id, amount, status, step_desc, created_at) 
        VALUES (?, ?, 'general', ?, ?, ?, 0, '等待接单', ?)";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$orderNo, $title, $userId, $receiver['id'], $amount, time()])) {
    jsonResult(200, '订单创建成功', ['order_id' => $pdo->lastInsertId()]);
} else {
    jsonResult(500, '创建失败');
}
?>