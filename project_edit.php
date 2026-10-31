<?php
/** 编辑密码库：修改名称、更换密码库图片 */
$page_title = '编辑密码库';
$require_auth = true;
include __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$project = find_owned_project($id);
if (!$project) {
    flash('error', '密码库不存在或无权访问。');
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = '请求已过期，请重试。';
    } elseif ($action === 'update') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = '密码库名称不能为空。';
        } else {
            $newImage = $project['image']; // 默认保留原图
            if (!empty($_FILES['image']['name'])) {
                $newImage = save_project_image($_FILES['image']);
                if ($newImage === null) {
                    $error = '图片上传失败，请确认是 JPG/PNG/GIF/WebP 且不超过 2MB。';
                } else {
                    // 新图成功后再删旧图
                    delete_project_image($project['image']);
                }
            }
            if ($error === '') {
                $stmt = db()->prepare('UPDATE projects SET name = ?, image = ? WHERE id = ? AND admin_id = ?');
                $stmt->execute([mb_substr($name, 0, 100), $newImage, $id, current_admin_id()]);
                flash('success', '密码库已更新。');
                redirect('project_view.php?id=' . $id);
            }
        }
    } elseif ($action === 'remove_image') {
        delete_project_image($project['image']);
        $stmt = db()->prepare('UPDATE projects SET image = NULL WHERE id = ? AND admin_id = ?');
        $stmt->execute([$id, current_admin_id()]);
        flash('success', '密码库图片已移除。');
        redirect('project_edit.php?id=' . $id);
    } elseif ($action === 'delete') {
        // 删除密码库（连带账号与字段级联删除）
        delete_project_image($project['image']);
        $stmt = db()->prepare('DELETE FROM projects WHERE id = ? AND admin_id = ?');
        $stmt->execute([$id, current_admin_id()]);
        flash('success', '密码库已删除。');
        redirect('dashboard.php');
    }
}
?>
<div class="page-head">
  <h2>编辑密码库</h2>
</div>

<div class="card form-card">
  <?php if ($error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if ($project['image']): ?>
    <div class="current-image">
      <img src="<?= e(site_url($project['image'])) ?>" alt="密码库图片">
      <form method="post" action="<?= e(site_url('project_edit.php?id=' . $id)) ?>"
            onsubmit="return confirm('确定移除该图片吗？')">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="remove_image">
        <button class="btn btn-small btn-ghost" type="submit">移除图片</button>
      </form>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(site_url('project_edit.php?id=' . $id)) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="update">

    <label class="field">
      <span>密码库名称 *</span>
      <input type="text" name="name" value="<?= e($project['name']) ?>" required maxlength="100">
    </label>

    <div class="field">
      <span><?= $project['image'] ? '更换密码库图片（可选）' : '上传密码库图片（可选）' ?></span>
      <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
      <small class="hint">上传新图将替换原图片，支持 JPG / PNG / GIF / WebP，不超过 2MB。</small>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">保存修改</button>
      <a class="btn btn-ghost" href="<?= e(site_url('project_view.php?id=' . $id)) ?>">返回详情</a>
    </div>
  </form>

  <hr>
  <form method="post" action="<?= e(site_url('project_edit.php?id=' . $id)) ?>"
        onsubmit="return confirm('删除该密码库将同时删除其中所有账号，确定吗？')">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="delete">
    <button type="submit" class="btn btn-danger">删除该密码库</button>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
