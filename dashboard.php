<?php
/** 后台首页：我的密码库列表 */
$page_title = '我的密码库';
$require_auth = true;
include __DIR__ . '/includes/header.php';

$adminId = current_admin_id();

$stmt = db()->prepare('SELECT p.*,
       (SELECT COUNT(*) FROM credentials c WHERE c.project_id = p.id) AS cred_count
       FROM projects p WHERE p.admin_id = ? ORDER BY p.updated_at DESC, p.id DESC');
$stmt->execute([$adminId]);
$projects = $stmt->fetchAll();
?>
<div class="page-head">
  <h2>我的密码库</h2>
  <a class="btn" href="<?= e(site_url('project_add.php')) ?>">＋ 添加密码库</a>
</div>

<?php if (!$projects): ?>
  <div class="empty">还没有密码库，点击右上角「添加密码库」创建第一个吧。</div>
<?php else: ?>
  <div class="grid projects">
    <?php foreach ($projects as $p): ?>
      <a class="project-card" href="<?= e(site_url('project_view.php?id=' . (int)$p['id'])) ?>">
        <div class="project-cover">
          <?php if ($p['image']): ?>
            <img src="<?= e(site_url($p['image'])) ?>" alt="">
          <?php else: ?>
            <div class="cover-placeholder">🔒</div>
          <?php endif; ?>
        </div>
        <div class="project-info">
          <h3><?= e($p['name']) ?></h3>
          <span><?= (int)$p['cred_count'] ?> 条账号</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
