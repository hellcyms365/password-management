<?php
/** 系统设置页：主题、页面背景、登录页自定义（仅超级管理员） */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/settings.php';
$page_title = '系统设置';
require_super();
include __DIR__ . '/includes/header.php';

// 处理设置图片上传/移除
function handle_setting_image(string $key, string $fileField, string $prefix): ?string
{
    $old = setting($key, '');
    if (!empty($_FILES[$fileField]['name'])) {
        $img = save_uploaded_image($_FILES[$fileField], $prefix);
        if ($img === null) {
            return '图片上传失败（仅支持 JPG/PNG/GIF/WebP，且不超过 2MB）。';
        }
        if ($old) {
            delete_stored_file($old);
        }
        save_setting($key, $img);
    } elseif (!empty($_POST[$fileField . '_remove'])) {
        if ($old) {
            delete_stored_file($old);
        }
        save_setting($key, '');
    }
    return null;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors[] = '请求已过期，请重试。';
    } else {
        // ---- 主题 ----
        $mode = ($_POST['theme_mode'] ?? 'light') === 'dark' ? 'dark' : 'light';
        save_setting('theme_mode', $mode);

        $color = trim($_POST['theme_color'] ?? '');
        if (is_hex_color($color)) {
            save_setting('theme_color', $color);
        }
        $navOpacity = max(0, min(100, (int)($_POST['nav_opacity'] ?? 90)));
        save_setting('nav_opacity', (string)$navOpacity);
        $cardOpacity = max(0, min(100, (int)($_POST['card_opacity'] ?? 90)));
        save_setting('card_opacity', (string)$cardOpacity);

        // ---- 页面背景 ----
        $pageBg = trim($_POST['page_bg_color'] ?? '');
        if (is_hex_color($pageBg)) {
            save_setting('page_bg_color', $pageBg);
        }
        if ($err = handle_setting_image('page_bg_image', 'page_bg_image', 'bg')) {
            $errors[] = $err;
        }

        // ---- 登录页自定义 ----
        $loginBg = trim($_POST['login_bg_color'] ?? '');
        if (is_hex_color($loginBg)) {
            save_setting('login_bg_color', $loginBg);
        }
        save_setting('login_title', mb_substr(trim($_POST['login_title'] ?? ''), 0, 60));
        save_setting('login_subtitle', mb_substr(trim($_POST['login_subtitle'] ?? ''), 0, 120));
        if ($err = handle_setting_image('login_logo', 'login_logo', 'logo')) {
            $errors[] = $err;
        }
        if ($err = handle_setting_image('login_bg_image', 'login_bg_image', 'loginbg')) {
            $errors[] = $err;
        }

        if (!$errors) {
            flash('success', '设置已保存。');
        } else {
            flash('error', implode(' ', $errors));
        }
        redirect('settings.php');
    }
}

$s = settings();
$mode     = ($s['theme_mode'] ?? 'light') === 'dark' ? 'dark' : 'light';
$tColor   = is_hex_color($s['theme_color'] ?? '') ? $s['theme_color'] : '#2563eb';
$navOp    = (int)($s['nav_opacity'] ?? 90);
$cardOp   = (int)($s['card_opacity'] ?? 90);
$pBg      = is_hex_color($s['page_bg_color'] ?? '') ? $s['page_bg_color'] : '#f4f6f9';
$lBg      = is_hex_color($s['login_bg_color'] ?? '') ? $s['login_bg_color'] : '#f4f6f9';
$lTitle   = $s['login_title'] ?? '密码管理器';
$lSubtitle= $s['login_subtitle'] ?? '';
$pBgImg   = $s['page_bg_image'] ?? '';
$lBgImg   = $s['login_bg_image'] ?? '';
$lLogo    = $s['login_logo'] ?? '';
?>
<div class="page-head"><h2>系统设置</h2></div>

<div class="card form-card">
  <form method="post" action="<?= e(site_url('settings.php')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <!-- 主题 -->
    <h3 class="sec-title">主题</h3>
    <div class="form-row">
      <label class="field">
        <span>主题模式</span>
        <select name="theme_mode">
          <option value="light" <?= $mode === 'light' ? 'selected' : '' ?>>浅色</option>
          <option value="dark" <?= $mode === 'dark' ? 'selected' : '' ?>>深色</option>
        </select>
      </label>
      <label class="field">
        <span>主题色（强调色）</span>
        <input type="color" name="theme_color" value="<?= e($tColor) ?>">
      </label>
    </div>
    <div class="field">
      <span>顶部导航透明度（越低越透明）</span>
      <input type="range" name="nav_opacity" min="0" max="100" value="<?= $navOp ?>"
             oninput="document.getElementById('navOpacityVal').textContent=this.value+'%'">
      <small class="hint">当前 <span id="navOpacityVal"><?= $navOp ?>%</span> · 配合背景图使用毛玻璃效果更佳</small>
    </div>
    <div class="field">
      <span>账号卡片透明度（密码库内账号管理卡片，越低越透明）</span>
      <input type="range" name="card_opacity" min="0" max="100" value="<?= $cardOp ?>"
             oninput="document.getElementById('cardOpacityVal').textContent=this.value+'%'">
      <small class="hint">当前 <span id="cardOpacityVal"><?= $cardOp ?>%</span> · 配合背景图使用毛玻璃效果更佳</small>
    </div>

    <!-- 页面背景 -->
    <h3 class="sec-title">页面背景</h3>
    <div class="form-row">
      <label class="field">
        <span>背景颜色</span>
        <input type="color" name="page_bg_color" value="<?= e($pBg) ?>">
      </label>
      <div class="field">
        <span>背景图片（可选，将覆盖背景颜色）</span>
        <input type="file" name="page_bg_image" accept="image/jpeg,image/png,image/gif,image/webp">
        <?php if ($pBgImg): ?>
          <div class="setting-preview">
            <img src="<?= e(site_url($pBgImg)) ?>" alt="页面背景">
            <label class="remove-check"><input type="checkbox" name="page_bg_image_remove" value="1"> 移除当前背景图</label>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 登录页自定义 -->
    <h3 class="sec-title">登录页自定义</h3>
    <label class="field">
      <span>登录标题</span>
      <input type="text" name="login_title" value="<?= e($lTitle) ?>" maxlength="60">
    </label>
    <label class="field">
      <span>登录副标题</span>
      <input type="text" name="login_subtitle" value="<?= e($lSubtitle) ?>" maxlength="120">
    </label>
    <div class="field">
      <span>登录 Logo（可选）</span>
      <input type="file" name="login_logo" accept="image/jpeg,image/png,image/gif,image/webp">
      <?php if ($lLogo): ?>
        <div class="setting-preview">
          <img src="<?= e(site_url($lLogo)) ?>" alt="登录 Logo">
          <label class="remove-check"><input type="checkbox" name="login_logo_remove" value="1"> 移除当前 Logo</label>
        </div>
      <?php endif; ?>
    </div>
    <div class="form-row">
      <label class="field">
        <span>登录页背景颜色</span>
        <input type="color" name="login_bg_color" value="<?= e($lBg) ?>">
      </label>
      <div class="field">
        <span>登录页背景图片（可选）</span>
        <input type="file" name="login_bg_image" accept="image/jpeg,image/png,image/gif,image/webp">
        <?php if ($lBgImg): ?>
          <div class="setting-preview">
            <img src="<?= e(site_url($lBgImg)) ?>" alt="登录背景">
            <label class="remove-check"><input type="checkbox" name="login_bg_image_remove" value="1"> 移除当前登录背景</label>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">保存设置</button>
      <a class="btn btn-ghost" href="<?= e(site_url('dashboard.php')) ?>">返回</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
