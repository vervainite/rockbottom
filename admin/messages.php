<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $messageId = (int) ($_POST['message_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($messageId > 0 && in_array($action, ['read', 'unread', 'delete'], true)) {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$messageId]);
            flash('success', 'Message deleted.');
        } else {
            $status = $action === 'read' ? 'read' : 'new';
            $pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$status, $messageId]);
            flash('success', $status === 'read' ? 'Marked as read.' : 'Marked as unread.');
        }
    } else {
        flash('error', 'That message action was not valid.');
    }
    go(url('admin/messages.php'));
}

$filter = (string) ($_GET['status'] ?? 'all');
if (!in_array($filter, ['all', 'new', 'read'], true)) {
    $filter = 'all';
}
$whereSql = $filter === 'all' ? '' : 'WHERE status = ?';
$stmt = $pdo->prepare("SELECT * FROM contact_messages $whereSql ORDER BY created_at DESC, id DESC LIMIT 200");
$stmt->execute($filter === 'all' ? [] : [$filter]);
$messages = $stmt->fetchAll();

$counts = ['all' => 0, 'new' => 0, 'read' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM contact_messages GROUP BY status') as $row) {
    $counts[$row['status']] = (int) $row['total'];
    $counts['all'] += (int) $row['total'];
}

$admin_title = 'Messages';
$adminActive = 'messages';
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">From the surface</p>
    <h1>THE <em>INBOX.</em></h1>
    <p><?= $counts['new'] ?> unread message<?= $counts['new'] === 1 ? '' : 's' ?> · <?= $counts['all'] ?> total</p>
  </div>
  <a class="btn btn-outline-plum" href="mailto:hello@rockbottom.ph">Open mail app <span aria-hidden="true">↗</span></a>
</div>

<div class="admin-toolbar">
  <nav class="admin-tabs" aria-label="Filter messages">
    <a class="<?= $filter === 'all' ? 'is-active' : '' ?>" href="<?= e(url('admin/messages.php')) ?>">All <span><?= $counts['all'] ?></span></a>
    <a class="<?= $filter === 'new' ? 'is-active' : '' ?>" href="<?= e(url('admin/messages.php?status=new')) ?>">Unread <span><?= $counts['new'] ?></span></a>
    <a class="<?= $filter === 'read' ? 'is-active' : '' ?>" href="<?= e(url('admin/messages.php?status=read')) ?>">Read <span><?= $counts['read'] ?></span></a>
  </nav>
</div>

<?php if ($messages): ?>
  <div class="message-grid">
    <?php foreach ($messages as $message): ?>
      <article class="message-card <?= $message['status'] === 'new' ? 'is-unread' : '' ?>">
        <header class="message-card__head">
          <span class="avatar-mini"><?= e(rb_initials((string) $message['name'])) ?></span>
          <div>
            <strong><?= e($message['name']) ?></strong>
            <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>
          </div>
          <time datetime="<?= e($message['created_at']) ?>"><?= e(rb_date($message['created_at'], 'M j, Y · g:i A')) ?></time>
        </header>
        <p class="message-card__body"><?= nl2br(e($message['message'])) ?></p>
        <footer class="message-card__foot">
          <span class="status-pill status-pill--<?= $message['status'] === 'new' ? 'lime' : 'muted' ?>"><?= $message['status'] === 'new' ? 'Unread' : 'Read' ?></span>
          <div class="message-card__actions">
            <a class="text-link" href="mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode('Re: your note to RockBottom') ?>">Reply ↗</a>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
              <?php if ($message['status'] === 'new'): ?>
                <button class="table-link" type="submit" name="action" value="read">Mark read</button>
              <?php else: ?>
                <button class="table-link" type="submit" name="action" value="unread">Mark unread</button>
              <?php endif; ?>
            </form>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
              <button class="table-link table-link--danger" type="submit" name="action" value="delete" data-confirm="Delete the message from <?= e($message['name']) ?>? This cannot be undone.">Delete</button>
            </form>
          </div>
        </footer>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <section class="admin-panel">
    <div class="admin-empty">
      <p><?= $filter === 'new' ? 'Nothing unread. The depths are quiet.' : 'No messages here yet.' ?></p>
    </div>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
