<?php
/** 登录页 —— 系统入口，进入即登录 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/settings.php';

// 已登录则直接进入后台
if (current_admin()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = '请求已过期，请重试。';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = '请输入用户名和密码。';
        } else {
            $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password_hash'])) {
                set_session($admin);
                redirect('dashboard.php');
            }
            $error = '用户名或密码错误。';
        }
    }
}

$loginTitle    = setting('login_title', APP_NAME);
$loginSubtitle = setting('login_subtitle', '登录后管理你的密码库');
$loginLogo     = setting('login_logo', '');

$page_title = '登录';
$hide_nav = true;
$extra_head = '<style>' . login_css() . '</style>';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <?php if ($loginLogo): ?>
      <div class="auth-logo"><img src="<?= e(site_url($loginLogo)) ?>" alt="Logo"></div>
    <?php endif; ?>
    <h1 class="auth-title"><?= e($loginTitle) ?></h1>
    <p class="auth-sub"><?= e($loginSubtitle) ?></p>

    <?php if ($error): ?>
      <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(site_url('login.php')) ?>" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label class="field">
        <span>用户名</span>
        <input type="text" name="username" required autofocus>
      </label>
      <label class="field">
        <span>密码</span>
        <input type="password" name="password" required>
      </label>
      <button type="submit" class="btn btn-block">登 录</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
