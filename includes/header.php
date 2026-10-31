<?php
/**
 * 公共页头。使用前需设置：
 *   $page_title    页面标题（可选，默认应用名）
 *   $hide_nav      设为 true 时不显示顶部导航（登录页等）
 *   $require_auth  设为 true 时强制要求登录
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';

if (!empty($require_auth)) {
    require_login();
}

$page_title = $page_title ?? APP_NAME;
$hide_nav    = $hide_nav ?? false;
$extra_head  = $extra_head ?? '';
$flash       = flash_get();
$admin       = current_admin();
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navActive   = function (string $name) use ($currentPage): string {
    return $currentPage === $name ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(site_url('assets/css/style.css')) ?>">
<style><?= theme_css() ?></style>
<?= $extra_head ?>
</head>
<body<?= theme_mode() === 'dark' ? ' class="theme-dark"' : '' ?>>
<?php if (!$hide_nav): ?>
<nav class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= e(site_url('dashboard.php')) ?>"><span class="brand-icon">🔒</span><?= e(APP_NAME) ?></a>
    <div class="nav-links">
      <a href="<?= e(site_url('dashboard.php')) ?>" class="<?= $navActive('dashboard.php') ?>">我的密码库</a>
      <a href="<?= e(site_url('project_add.php')) ?>" class="<?= $navActive('project_add.php') ?>">添加密码库</a>
      <?php if ($admin && $admin['role'] === 'super'): ?>
        <a href="<?= e(site_url('managers.php')) ?>" class="<?= $navActive('managers.php') ?>">管理器账户</a>
        <a href="<?= e(site_url('settings.php')) ?>" class="<?= $navActive('settings.php') ?>">系统设置</a>
      <?php endif; ?>
    </div>
    <div class="nav-user">
      <span><?= e($admin['username'] ?? '') ?>
        <?php if ($admin && $admin['role'] === 'super'): ?><em class="role">超级管理员</em><?php endif; ?>
      </span>
      <a class="btn btn-small" href="<?= e(site_url('logout.php')) ?>">退出</a>
    </div>
  </div>
</nav>
<?php endif; ?>

<?php if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<main class="container">
