<?php
/** 管理器账户页：查看、添加、改密、调整角色、删除管理器账户（仅超级管理员） */
require_once __DIR__ . '/includes/functions.php';
$page_title = '管理器账户';
require_super();
include __DIR__ . '/includes/header.php';

$message = '';
$messageType = 'success';

function super_count(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM admins WHERE role = "super"')->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $message = '请求已过期，请重试。';
        $messageType = 'error';
    } elseif ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? 'manager') === 'super' ? 'super' : 'manager';
        if ($username === '' || mb_strlen($username) > 50) {
            $message = '用户名不能为空且不超过 50 个字符。';
            $messageType = 'error';
        } elseif (mb_strlen($password) < 6) {
            $message = '密码至少 6 位。';
            $messageType = 'error';
        } else {
            $exists = db()->prepare('SELECT id FROM admins WHERE username = ?');
            $exists->execute([$username]);
            if ($exists->fetch()) {
                $message = '用户名已存在。';
                $messageType = 'error';
            } else {
                db()->prepare('INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
                $message = '账户已创建。';
            }
        }
    } elseif ($action === 'update') {
        $uid = (int)($_POST['id'] ?? 0);
        $role = ($_POST['role'] ?? 'manager') === 'super' ? 'super' : 'manager';
        $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$uid]);
        $target = $stmt->fetch();
        if (!$target) {
            $message = '账户不存在。';
            $messageType = 'error';
        } else {
            if ($target['role'] === 'super' && $role !== 'super' && super_count() <= 1) {
                $message = '不能降级最后一个超级管理员。';
                $messageType = 'error';
            } else {
                db()->prepare('UPDATE admins SET role = ? WHERE id = ?')->execute([$role, $uid]);
                $message = '账户角色已更新。';
            }
        }
    } elseif ($action === 'password') {
        $uid = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $stmt = db()->prepare('SELECT id FROM admins WHERE id = ?');
        $stmt->execute([$uid]);
        if (!$stmt->fetch()) {
            $message = '账户不存在。';
            $messageType = 'error';
        } elseif (mb_strlen($password) < 6) {
            $message = '密码至少 6 位。';
            $messageType = 'error';
        } else {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $uid]);
            $message = '密码已重置。';
        }
    } elseif ($action === 'delete') {
        $uid = (int)($_POST['id'] ?? 0);
        $me = current_admin_id();
        if ($uid === $me) {
            $message = '不能删除当前登录的账户。';
            $messageType = 'error';
        } else {
            $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
            $stmt->execute([$uid]);
            $target = $stmt->fetch();
            if (!$target) {
                $message = '账户不存在。';
                $messageType = 'error';
            } elseif ($target['role'] === 'super' && super_count() <= 1) {
                $message = '不能删除最后一个超级管理员。';
                $messageType = 'error';
            } else {
                db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$uid]);
                $message = '账户已删除。';
            }
        }
    }
}

$admins = db()->query('SELECT a.id, a.username, a.role, a.created_at,
                       (SELECT COUNT(*) FROM projects p WHERE p.admin_id = a.id) AS proj_count
                       FROM admins a ORDER BY a.id')->fetchAll();
?>
<div class="page-head">
  <h2>管理器账户</h2>
</div>

<?php if ($message): ?>
  <div class="flash flash-<?= $messageType === 'error' ? 'error' : 'success' ?>"><?= e($message) ?></div>
<?php endif; ?>

<div class="card form-card">
  <h3>添加管理器账户</h3>
  <form method="post" action="<?= e(site_url('managers.php')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <div class="form-row">
      <label class="field">
        <span>用户名</span>
        <input type="text" name="username" required maxlength="50">
      </label>
      <label class="field">
        <span>密码</span>
        <input type="password" name="password" required minlength="6">
      </label>
      <label class="field">
        <span>角色</span>
        <select name="role">
          <option value="manager">普通管理器</option>
          <option value="super">超级管理员</option>
        </select>
      </label>
      <div class="form-actions">
        <button type="submit" class="btn">添加</button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>用户名</th>
        <th>角色</th>
        <th>密码库数</th>
        <th>创建时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($admins as $a): ?>
        <tr>
          <td><?= (int)$a['id'] ?></td>
          <td><?= e($a['username']) ?></td>
          <td>
            <form method="post" action="<?= e(site_url('managers.php')) ?>">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <select name="role" class="inline-select">
                <option value="super" <?= $a['role'] === 'super' ? 'selected' : '' ?>>超级管理员</option>
                <option value="manager" <?= $a['role'] === 'manager' ? 'selected' : '' ?>>普通管理器</option>
              </select>
              <button class="btn btn-small" type="submit">保存角色</button>
            </form>
          </td>
          <td><?= (int)$a['proj_count'] ?></td>
          <td><?= e($a['created_at']) ?></td>
          <td>
            <details class="inline-details">
              <summary class="btn btn-small btn-ghost">重置密码</summary>
              <form method="post" action="<?= e(site_url('managers.php')) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="password">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <input type="password" name="password" minlength="6" placeholder="新密码（至少6位）">
                <button class="btn btn-small" type="submit">确定</button>
              </form>
            </details>
            <?php if ((int)$a['id'] !== current_admin_id()): ?>
              <form method="post" action="<?= e(site_url('managers.php')) ?>"
                    onsubmit="return confirm('确定删除该管理器账户吗？')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="btn btn-small btn-danger" type="submit">删除</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
