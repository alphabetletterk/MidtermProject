<?php
?>
<?php if (!empty($flash)): ?>
<div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
  <?= e($flash['msg']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if (!empty($message)): ?>
<div class="alert alert-<?= e($type ?? 'danger') ?>"><?= e($message) ?></div>
<?php endif; ?>