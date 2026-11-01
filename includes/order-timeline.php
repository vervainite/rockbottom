<?php
/** @var array $order */
$timelineStatus = (string) $order['status'];
$timelineFlow = rb_status_flow();
$timelineIndex = array_search($timelineStatus, $timelineFlow, true);
$timelineNotes = [
    'Pending'    => 'Order received',
    'Processing' => 'Packing your treasures',
    'Shipped'    => 'On its way to you',
    'Delivered'  => 'Treasure delivered',
];
?>
<?php if ($timelineStatus === 'Cancelled'): ?>
  <div class="status-cancelled" role="status">
    <span aria-hidden="true">×</span>
    <div><strong>This order was cancelled.</strong><p>Any reserved stock has been returned to the collection. Payment was not collected.</p></div>
  </div>
<?php else: ?>
  <ol class="order-timeline" aria-label="Order status: <?= e($timelineStatus) ?>">
    <?php foreach ($timelineFlow as $i => $step): ?>
      <?php
      $isDone = $timelineIndex !== false && $i < $timelineIndex;
      $isCurrent = $timelineIndex !== false && $i === $timelineIndex;
      ?>
      <li class="<?= $isDone ? 'is-done' : '' ?> <?= $isCurrent ? 'is-current' : '' ?>">
        <span class="order-timeline__dot" aria-hidden="true"><?= $isDone ? '✓' : ($i + 1) ?></span>
        <div>
          <strong><?= e($step) ?></strong>
          <small><?= e($timelineNotes[$step]) ?></small>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
