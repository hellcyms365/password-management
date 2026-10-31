<?php
/** 添加密码库（可上传密码库图片） */
$page_title = '添加密码库';
$require_auth = true;
include __DIR__ . '/includes/header.php';

$error = '';
$name = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = '请求已过期，请重试。';
    } else {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = '密码库名称不能为空。';
        } else {
            $name = mb_substr($name, 0, 100);
            $image = null;
            if (!empty($_FILES['image']['name'])) {
                $image = save_project_image($_FILES['image']);
                if ($image === null) {
                    $error = '图片上传失败，请确认是 JPG/PNG/GIF/WebP 且不超过 2MB。';
                }
            }
            if ($error === '') {
                $stmt = db()->prepare('INSERT INTO projects (admin_id, name, image) VALUES (?, ?, ?)');
                $stmt->execute([current_admin_id(), $name, $image]);
                flash('success', '密码库创建成功。');
                redirect('project_view.php?id=' . (int)db()->lastInsertId());
            }
        }
    }
}
?>
<div class="page-head">
  <h2>添加密码库</h2>
</div>

<div class="card form-card">
  <?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(site_url('project_add.php')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label class="field">
      <span>密码库名称 *</span>
      <input type="text" name="name" value="<?= e($name) ?>" required maxlength="100">
    </label>

    <div class="field">
      <span>密码库图片（可选）</span>
      <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
      <small class="hint">支持 JPG / PNG / GIF / WebP，不超过 2MB。可在编辑页随时更换。</small>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">保存</button>
      <a class="btn btn-ghost" href="<?= e(site_url('dashboard.php')) ?>">返回</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
