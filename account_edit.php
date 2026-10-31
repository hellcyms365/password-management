<?php
/** 编辑账号：修改账号名/账号/密码及可修改字段 */
$page_title = '编辑账号';
$require_auth = true;
include __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$cred = find_owned_credential($id);
if (!$cred) {
    flash('error', '账号不存在或无权访问。');
    redirect('dashboard.php');
}
$projectId = (int)$cred['project_id'];
ensure_credential_image_column();

$stmt = db()->prepare('SELECT * FROM credential_fields WHERE credential_id = ? ORDER BY id');
$stmt->execute([$id]);
$existingFields = $stmt->fetchAll();

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

            // 账号图片：上传新图则替换并删除旧图；勾选「移除」则删除旧图并置空
            $newImage = $cred['image'];
            if (!empty($_FILES['image']['name'])) {
                $newImage = save_credential_image($_FILES['image']);
                if ($newImage === null) {
                    $error = '账号图片上传失败，请确认是 JPG/PNG/GIF/WebP 且不超过 2MB。';
                } else {
                    delete_credential_image($cred['image']);
                }
            }
            if (!empty($_POST['remove_image']) && $cred['image'] !== null) {
                delete_credential_image($cred['image']);
                $newImage = null;
            }
            if ($error === '') {
                $stmt = db()->prepare('UPDATE credentials SET name = ?, username = ?, password_enc = ?, image = ? WHERE id = ?');
                $stmt->execute([$name, $username, encrypt_password($password), $newImage, $id]);

                // 删除旧字段并重插
                db()->prepare('DELETE FROM credential_fields WHERE credential_id = ?')->execute([$id]);
                $ins = db()->prepare('INSERT INTO credential_fields (credential_id, field_name, field_value) VALUES (?, ?, ?)');
                foreach ($fields as $f) {
                    $ins->execute([$id, $f['name'], $f['value']]);
                }
                flash('success', '账号已更新。');
                redirect('project_view.php?id=' . $projectId);
            }
        }
    }
}
?>
<div class="page-head">
  <h2>编辑账号</h2>
</div>

<div class="card form-card">
  <?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(site_url('account_edit.php?id=' . $id)) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label class="field">
      <span>账号名称 *</span>
      <input type="text" name="name" value="<?= e($cred['name']) ?>" required maxlength="100">
    </label>
    <label class="field">
      <span>账号</span>
      <input type="text" name="username" value="<?= e($cred['username']) ?>" maxlength="255">
    </label>
    <label class="field">
      <span>密码</span>
      <input type="text" name="password" class="pw-input" value="<?= e(decrypt_password($cred['password_enc']) ?? '') ?>">
      <button type="button" class="btn btn-small btn-ghost gen-pw">生成随机密码</button>
    </label>

    <div class="field">
      <span><?= $cred['image'] ? '更换账号图片（可选）' : '上传账号图片（可选）' ?></span>
      <?php if ($cred['image']): ?>
        <img class="current-cred-img" src="<?= e(site_url($cred['image'])) ?>" alt="账号图片">
      <?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
      <small class="hint">上传新图将替换原图片；支持 JPG / PNG / GIF / WebP，不超过 2MB。</small>
      <?php if ($cred['image']): ?>
        <label class="inline-check"><input type="checkbox" name="remove_image" value="1"> 移除当前图片</label>
      <?php endif; ?>
    </div>

    <div class="field">
      <span>可修改字段（可添加多条）</span>
      <div class="custom-fields-editor" id="customFieldsEditor"
           data-fields="<?= e(json_encode(array_map(function ($f) {
               return ['name' => $f['field_name'], 'value' => $f['field_value']];
           }, $existingFields))) ?>"></div>
      <button type="button" class="btn btn-small btn-ghost add-custom-field">＋ 添加字段</button>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">保存修改</button>
      <a class="btn btn-ghost" href="<?= e(site_url('project_view.php?id=' . $projectId)) ?>">返回</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
