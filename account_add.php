<?php
/** 在指定密码库内添加账号（支持多条可修改字段） */
$page_title = '添加账号';
$require_auth = true;
include __DIR__ . '/includes/header.php';

$projectId = (int)($_GET['project_id'] ?? ($_POST['project_id'] ?? 0));
$project = find_owned_project($projectId);
if (!$project) {
    flash('error', '密码库不存在或无权访问。');
    redirect('dashboard.php');
}
ensure_credential_image_column();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = '请求已过期，请重试。';
    } else {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($name === '') {
            $error = '账号名称不能为空。';
        } else {
            $name = mb_substr($name, 0, 100);
            $username = mb_substr($username, 0, 255);
            $fields = collect_custom_fields($_POST['field_names'] ?? [], $_POST['field_values'] ?? []);

            $image = '';
            if (!empty($_FILES['image']['name'])) {
                $image = save_credential_image($_FILES['image']);
                if ($image === null) {
                    $error = '账号图片上传失败，请确认是 JPG/PNG/GIF/WebP 且不超过 2MB。';
                }
            }
            if ($error === '') {
                $stmt = db()->prepare('INSERT INTO credentials (project_id, name, username, password_enc, image) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$projectId, $name, $username, encrypt_password($password), $image]);
                $credId = (int)db()->lastInsertId();

                $ins = db()->prepare('INSERT INTO credential_fields (credential_id, field_name, field_value) VALUES (?, ?, ?)');
                foreach ($fields as $f) {
                    $ins->execute([$credId, $f['name'], $f['value']]);
                }
                flash('success', '账号已添加。');
                redirect('project_view.php?id=' . $projectId);
            }
        }
    }
}
?>
<div class="page-head">
  <h2>添加账号 · <?= e($project['name']) ?></h2>
</div>

<div class="card form-card">
  <?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(site_url('account_add.php')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">

    <label class="field">
      <span>账号名称 *</span>
      <input type="text" name="name" required maxlength="100">
    </label>
    <label class="field">
      <span>账号</span>
      <input type="text" name="username" maxlength="255">
    </label>
    <label class="field">
      <span>密码</span>
      <input type="text" name="password" class="pw-input">
      <button type="button" class="btn btn-small btn-ghost gen-pw">生成随机密码</button>
    </label>

    <div class="field">
      <span>账号图片（可选）</span>
      <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
      <small class="hint">支持 JPG / PNG / GIF / WebP，不超过 2MB。</small>
    </div>

    <div class="field">
      <span>可修改字段（可添加多条）</span>
      <div class="custom-fields-editor" id="customFieldsEditor"></div>
      <button type="button" class="btn btn-small btn-ghost add-custom-field">＋ 添加字段</button>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">保存</button>
      <a class="btn btn-ghost" href="<?= e(site_url('project_view.php?id=' . $projectId)) ?>">返回</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
