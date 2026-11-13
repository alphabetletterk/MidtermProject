<?php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?= e($cfg['title']) ?></h1>
  <a href="<?= e(crud_url(['action' => 'add'])) ?>" class="btn btn-field" data-crud-modal>Add <?= e($one) ?></a>
</div>

<?php if ($loadError): ?>
  <div class="alert alert-danger">The records could not be loaded. Check that the database tables exist.</div>
<?php endif; ?>

<form method="get" action="<?= e($self) ?>" class="panel mb-3">
  <div class="row g-2 align-items-end">
    <div class="col">
      <label class="form-label small" for="q">Search</label>
      <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Search <?= e(strtolower($cfg['title'])) ?>">
    </div>
    <div class="col-auto d-flex gap-2">
      <button type="submit" class="btn btn-field">Search</button>
      <a href="<?= e($self) ?>" class="btn btn-signout">Reset</a>
    </div>
  </div>
  <?php if (!empty($cfg['filters'])): ?>
    <div class="row g-2 mt-1">
      <?php foreach ($cfg['filters'] as $k => $flt): ?>
        <div class="col-12 col-md">
          <label class="form-label small" for="flt_<?= e($k) ?>"><?= e($flt['label']) ?></label>
          <select class="form-select" id="flt_<?= e($k) ?>" name="<?= e($k) ?>">
            <option value="">All</option>
            <?php foreach ($fopts[$k] ?? [] as $ov => $ol): ?>
              <option value="<?= e($ov) ?>"<?= (string) $ov === ($keep[$k] ?? '') ? ' selected' : '' ?>><?= e($ol) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</form>

<section class="panel">
  <?php if (!$rows): ?>
    <p class="text-secondary mb-0">No records found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-fixed align-middle mb-0">
        <thead>
          <tr>
            <?php foreach ($cols as $key => $c):
                $next = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
                $arrow = $sort === $key ? ($dir === 'asc' ? ' ↑' : ' ↓') : ''; ?>
              <th>
                <a href="<?= e(crud_url($keep + ['sort' => $key, 'dir' => $next])) ?>"><?= e($c[0] . $arrow) ?></a>
              </th>
            <?php endforeach; ?>
            <th class="text-end" style="width: 12.5rem;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php foreach ($cols as $key => $c): ?>
                <td><?= e(crud_fmt($r[$key], $c[2] ?? null)) ?></td>
              <?php endforeach; ?>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-signout btn-action" href="<?= e(crud_url(['action' => 'view', 'id' => $r[$pk]])) ?>" data-crud-modal>View</a>
                <a class="btn btn-sm btn-signout btn-action" href="<?= e(crud_url(['action' => 'edit', 'id' => $r[$pk]])) ?>" data-crud-modal>Edit</a>
                <?php if ($isAdmin): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger btn-action" data-bs-toggle="modal"
                          data-bs-target="#deleteModal" data-id="<?= e($r[$pk]) ?>"
                          data-name="<?= e($r[$cfg['label_col']]) ?>">Delete</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
      <span class="small text-secondary">Showing <?= ($page - 1) * $perPage + 1 ?>–<?= ($page - 1) * $perPage + count($rows) ?> of <?= $total ?></span>
      <?php if ($pages > 1): ?>
        <nav aria-label="Pagination">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(crud_url($keep + ['sort' => $sort, 'dir' => $dir, 'page' => $page - 1])) ?>">Previous</a></li>
            <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
              <li class="page-item<?= $i === $page ? ' active' : '' ?>"><a class="page-link" href="<?= e(crud_url($keep + ['sort' => $sort, 'dir' => $dir, 'page' => $i])) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(crud_url($keep + ['sort' => $sort, 'dir' => $dir, 'page' => $page + 1])) ?>">Next</a></li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php if ($isAdmin): ?>
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form method="post" action="<?= e($self) ?>" class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title h5" id="deleteTitle">Delete <?= e($one) ?>?</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete <strong id="deleteName"></strong>? This cannot be undone.
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="op" value="delete">
          <input type="hidden" name="id" id="deleteId" value="">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-signout" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function (ev) {
      document.getElementById('deleteId').value = ev.relatedTarget.dataset.id;
      document.getElementById('deleteName').textContent = ev.relatedTarget.dataset.name;
    });
  </script>
<?php endif; ?>

<div class="modal fade" id="crudModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-body text-center text-secondary py-5">
        <span class="spinner-border spinner-border-sm text-success me-2" aria-hidden="true"></span>Loading...
      </div>
    </div>
  </div>
</div>
<script src="<?= e($basePath) ?>assets/js/crud-modal.js" defer></script>