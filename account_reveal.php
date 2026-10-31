<?php
/** 返回指定账号的解密密码（JSON）——仅在页面内点击“显示”时由前端请求 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
$cred = find_owned_credential($id);
if (!$cred) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => '账号不存在或无权访问。']);
    exit;
}

echo json_encode(['ok' => true, 'password' => decrypt_password($cred['password_enc']) ?? '']);
