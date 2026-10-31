<?php
/** 删除账号（可修改字段随外键级联删除） */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
if (!csrf_check($_POST['csrf'] ?? null)) {
    flash('error', '请求已过期，请重试。');
    redirect('dashboard.php');
}

ensure_credential_image_column();
$id = (int)($_POST['id'] ?? 0);
$projectId = (int)($_POST['project_id'] ?? 0);
$cred = find_owned_credential($id);
if (!$cred) {
    flash('error', '账号不存在或无权访问。');
    redirect('dashboard.php');
}

delete_credential_image($cred['image']);
db()->prepare('DELETE FROM credentials WHERE id = ?')->execute([$id]);
flash('success', '账号已删除。');
redirect('project_view.php?id=' . $projectId);
