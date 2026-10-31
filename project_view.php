<?php
/** 密码库详情：展示密码库内账号及可修改字段 */
$page_title = '密码库详情';
$require_auth = true;
include __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$project = find_owned_project($id);
if (!$project) {
    flash('error', '密码库不存在或无权访问。');
    redirect('dashboard.php');
}
ensure_credential_image_column();

$stmt = db()->prepare('SELECT * FROM credentials WHERE project_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$creds = $stmt->fetchAll();

// 一次性取出该密码库全部账号的可修改字段，按账号分组
$fields = [];
$stmt = db()->prepare('SELECT cf.* FROM credential_fields cf
                       JOIN credentials c ON c.id = cf.credential_id
                       WHERE c.project_id = ? ORDER BY cf.id');
$stmt->execute([$id]);
foreach ($stmt->fetchAll() as $f) {
    $fields[$f['credential_id']][] = $f;
}
?>
<div class="page-head">
  <div class="head-left">
    <?php if ($project['image']): ?>
      <img class="head-thumb" src="<?= e(site_url($project['image'])) ?>" alt="">
    <?php endif; ?>
    <h2><?= e($project['name']) ?></h2>
  </div>
  <div class="head-actions">
    <a class="btn" href="<?= e(site_url('account_add.php?project_id=' . $id)) ?>">＋ 添加账号</a>
    <a class="btn btn-ghost" href="<?= e(site_url('project_edit.php?id=' . $id)) ?>">编辑密码库 / 更换图片</a>
    <a class="btn btn-ghost" href="<?= e(site_url('dashboard.php')) ?>">返回</a>
  </div>
</div>

<?php if (!$creds): ?>
  <div class="empty">该密码库还没有账号，点击「添加账号」开始录入。</div>
<?php else: ?>
  <div class="cred-list">
    <?php foreach ($creds as $c):
        $cFields = $fields[$c['id']] ?? [];
    ?>
      <div class="card cred-card">
        <div class="cred-head">
          <h3><?= e($c['name']) ?></h3>
          <div class="cred-actions">
            <a class="btn btn-small" href="<?= e(site_url('account_edit.php?id=' . (int)$c['id'])) ?>">编辑</a>
            <form method="post" action="<?= e(site_url('account_delete.php')) ?>"
                  onsubmit="return confirm('确定删除该账号吗？')">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="project_id" value="<?= $id ?>">
              <button class="btn btn-small btn-danger" type="submit">删除</button>
            </form>
          </div>
        </div>

        <dl class="cred-body">
          <div class="row">
            <dt>账号</dt>
            <dd><?= e($c['username']) ?></dd>
          </div>
          <div class="row">
            <dt>密码</dt>
            <dd class="pw-cell">
              <span class="pw-mask" data-reveal-url="<?= e(site_url('account_reveal.php')) ?>"
                    data-id="<?= (int)$c['id'] ?>">••••••••</span>
              <button class="btn btn-small btn-ghost pw-toggle" type="button">显示</button>
            </dd>
          </div>
          <?php if ($cFields || $c['image']): ?>
            <details class="custom-fields">
              <summary class="cf-head">其他信息 <span class="cf-toggle">展开</span></summary>
              <div class="cf-body">
                <?php foreach ($cFields as $f):
                    $fv = (string)$f['field_value'];
                    $isUrl = (bool)preg_match('#^https?://#i', $fv);
                ?>
                  <div class="row">
                    <dt><?= e($f['field_name']) ?></dt>
                    <dd><?php if ($isUrl): ?><a class="cred-link" href="<?= e($fv) ?>" target="_blank" rel="noopener noreferrer"><?= e($fv) ?></a><?php else: ?><?= e($fv) ?><?php endif; ?></dd>
                  </div>
                <?php endforeach; ?>
                <?php if ($c['image']): ?>
                  <div class="row cred-img-row">
                    <dt>图片</dt>
                    <dd>
                      <a class="cred-img-link" href="<?= e(site_url($c['image'])) ?>" data-lightbox>
                        <img class="cred-img-thumb" src="<?= e(site_url($c['image'])) ?>" alt="账号图片">
                      </a>
                    </dd>
                  </div>
                <?php endif; ?>
              </div>
            </details>
          <?php endif; ?>
        </dl>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
