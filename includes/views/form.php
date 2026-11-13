<?php
$title = ucfirst($action) . ' ' . $one;
?>
<?php if ($modal): ?>
<form method="post" action="<?= e($self) ?>" class="modal-content" data-crud-form>
  <div class="modal-header">
    <h2 class="modal-title h5"><?= e($title) ?></h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  </div>
  <div class="modal-body">
<?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?= e($title) ?></h1>
  <a href="<?= e($self) ?>" class="btn btn-signout">Back to list</a>
</div>

<section class="panel">
  <form method="post" action="<?= e($self) ?>">
<?php endif; ?>
    <?php if (!empty($errors['_form'])): ?>
      <div class="alert alert-danger"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="op" value="save">
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-3">
      <?php foreach ($fields as $f):
          $n = $f['name'];
          $isSelect = $f['type'] === 'select';
          $isPassword = $f['type'] === 'password';
          $val = $isPassword ? '' : ($formData[$n] ?? '');
          $cls = ($isSelect ? 'form-select' : 'form-control') . (isset($errors[$n]) ? ' is-invalid' : '');
          $attr = (!empty($f['required']) ? ' required' : '')
                . (isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '')
                . (isset($f['min']) ? ' min="' . e($f['min']) . '"' : '')
                . (isset($f['maxv']) ? ' max="' . e($f['maxv']) . '"' : '')
                . (isset($f['step']) ? ' step="' . e($f['step']) . '"' : '')
                . ($isPassword ? ' autocomplete="new-password"' : ''); ?>
        <div class="col-md-6">
          <label class="form-label" for="f_<?= e($n) ?>"><?= e($f['label']) ?></label>
          <?php if ($isSelect): ?>
            <select class="<?= $cls ?>" id="f_<?= e($n) ?>" name="<?= e($n) ?>"<?= $attr ?>>
              <option value="">Choose...</option>
              <?php foreach ($f['opts'] as $k => $label): ?>
                <option value="<?= e($k) ?>"<?= (string) $k === (string) $val ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="<?= e($f['type']) ?>" class="<?= $cls ?>" id="f_<?= e($n) ?>" name="<?= e($n) ?>"
                   value="<?= e($val) ?>"<?= $attr ?>>
          <?php endif; ?>
          <?php if (isset($errors[$n])): ?>
            <div class="invalid-feedback"><?= e($errors[$n]) ?></div>
          <?php endif; ?>
          <?php if (!empty($f['hint'])): ?>
            <div class="form-text"><?= e($f['hint']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
<?php if ($modal): ?>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-signout" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-field px-4">Save</button>
  </div>
</form>
<?php else: ?>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-field px-4">Save</button>
      <a href="<?= e($self) ?>" class="btn btn-signout">Cancel</a>
    </div>
  </form>
</section>
<?php endif; ?>