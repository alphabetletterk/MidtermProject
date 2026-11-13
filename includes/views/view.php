<?php
$details = [];
foreach ($cols as $key => $c) {
    $details[] = [$c[0], crud_fmt($record[$key] ?? null, $c[2] ?? null)];
}
foreach ($cfg['view_extra'] ?? [] as $key => $c) {
    $details[] = [$c[0], crud_fmt($record[$key] ?? null, $c[1] ?? null)];
}
if (!isset($cols['created_at'])) {
    $details[] = ['Created', crud_fmt($stamps['created_at'] ?? null, 'datetime')];
    $details[] = ['Last updated', crud_fmt($stamps['updated_at'] ?? null, 'datetime')];
}
$title = ucfirst($one) . ' details';
$editUrl = crud_url(['action' => 'edit', 'id' => $id]);
?>
<?php if ($modal): ?>
<div class="modal-content">
  <div class="modal-header">
    <h2 class="modal-title h5"><?= e($title) ?></h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  </div>
  <div class="modal-body">
<?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?= e($title) ?></h1>
  <div class="d-flex gap-2">
    <a href="<?= e($editUrl) ?>" class="btn btn-signout">Edit</a>
    <a href="<?= e($self) ?>" class="btn btn-signout">Back to list</a>
  </div>
</div>

<section class="panel">
<?php endif; ?>
  <dl class="row mb-0">
    <?php foreach ($details as [$label, $value]): ?>
      <dt class="col-sm-4 col-lg-3 text-secondary fw-semibold"><?= e($label) ?></dt>
      <dd class="col-sm-8 col-lg-9"><?= $value === '' ? '<span class="text-secondary">&mdash;</span>' : e($value) ?></dd>
    <?php endforeach; ?>
  </dl>
<?php if ($modal): ?>
  </div>
  <div class="modal-footer">
    <a href="<?= e($editUrl) ?>" class="btn btn-signout" data-crud-modal>Edit</a>
    <button type="button" class="btn btn-field" data-bs-dismiss="modal">Close</button>
  </div>
</div>
<?php else: ?>
</section>
<?php endif; ?>