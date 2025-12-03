<?php
require_once 'db.php';
$id = $_GET['id'];
$uid = $_GET['user_id'];

$stmt = $pdo->prepare("SELECT o.*, u1.username as sponsor_name, u2.username as receiver_name 
                       FROM db_orders o 
                       LEFT JOIN db_users u1 ON o.sponsor_id=u1.id 
                       LEFT JOIN db_users u2 ON o.receiver_id=u2.id 
                       WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) jsonResult(404, '订单不存在');

// 权限：只有相关人员能看详情 (待接单状态下，为了让对方确认，如果是接收人也允许看)
if ($order['sponsor_id'] != $uid && $order['receiver_id'] != $uid) {
    jsonResult(403, '您无权查看此订单详情');
}

$order['created_at_fmt'] = date('Y-m-d H:i', $order['created_at']);
$order['is_sponsor'] = ($order['sponsor_id'] == $uid);
jsonResult(200, 'OK', $order);
?>