<?php
$badge = [
    'INSERT' => 'success', 'UPDATE' => 'primary', 'DELETE' => 'danger',
    'LOGIN' => 'secondary', 'LOGOUT' => 'secondary', 'LOGIN_FAILED' => 'warning',
];
$pageUrl = fn(int $p): string => crud_url($filters + ['page' => $p]);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Audit trail</h1>
  <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearModal">Clear entries</button>
</div>

<?php if ($loadError): ?>
  <div class="alert alert-danger">The audit trail could not be loaded. Check that the audit_log table exists.</div>
<?php endif; ?>

<form method="get" action="<?= e($self) ?>" class="panel mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-12 col-md">
      <label class="form-label small" for="user">User</label>
      <select class="form-select" id="user" name="user">
        <option value="">All</option>
        <?php foreach ($opts['users'] as $u): ?>
          <option value="<?= e($u) ?>"<?= $u === $filters['user'] ? ' selected' : '' ?>><?= e($u) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md">
      <label class="form-label small" for="action">Action</label>
      <select class="form-select" id="action" name="action">
        <option value="">All</option>
        <?php foreach (AUDIT_ACTIONS as $code => $name): ?>
          <option value="<?= e($code) ?>"<?= $code === $filters['action'] ? ' selected' : '' ?>><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md">
      <label class="form-label small" for="module">Module</label>
      <select class="form-select" id="module" name="module">
        <option value="">All</option>
        <?php foreach ($opts['modules'] as $m): ?>
          <option value="<?= e($m) ?>"<?= $m === $filters['module'] ? ' selected' : '' ?>><?= e(AUDIT_MODULES[$m] ?? $m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="row g-2 align-items-end mt-1">
    <div class="col-6 col-md-3">
      <label class="form-label small" for="from">From</label>
      <input type="date" class="form-control" id="from" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small" for="to">To</label>
      <input type="date" class="form-control" id="to" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <div class="col-12 col-md-auto d-flex gap-2">
      <button type="submit" class="btn btn-field">Filter</button>
      <a href="<?= e($self) ?>" class="btn btn-signout">Reset</a>
    </div>
  </div>
</form>

<section class="panel">
  <?php if (!$rows): ?>
    <p class="text-secondary mb-0">No audit entries match.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-fixed align-middle mb-0">
        <thead>
          <tr>
            <th style="width: 11rem;">When</th>
            <th style="width: 8rem;">User</th>
            <th style="width: 8.5rem;">Action</th>
            <th style="width: 7.5rem;">Module</th>
            <th>Record</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
              $act = $r['action'];
              $changes = $r['details'] ? json_decode($r['details'], true) : null; ?>
            <tr>
              <td><?= e(date('M j, Y g:i:s A', strtotime($r['created_at']))) ?></td>
              <td><?= e($r['username']) ?></td>
              <td><span class="badge text-bg-<?= e($badge[$act] ?? 'secondary') ?>"><?= e(AUDIT_ACTIONS[$act] ?? $act) ?></span></td>
              <td><?= e(AUDIT_MODULES[$r['table_name']] ?? $r['table_name']) ?></td>
              <td><?= e($r['record_label'] ?? '') ?></td>
              <td>
                <?php if (is_array($changes) && $changes): ?>
                  <details class="audit-details">
                    <summary class="small"><?= count($changes) ?> <?= count($changes) === 1 ? 'field' : 'fields' ?></summary>
                    <ul class="small mb-0 ps-3 mt-1">
                      <?php foreach ($changes as [$label, $old, $new]): ?>
                        <li>
                          <strong><?= e($label) ?>:</strong>
                          <?php if ($act === 'UPDATE'): ?>
                            <?= e($old === '' ? '(empty)' : $old) ?> &rarr; <?= e($new === '' ? '(empty)' : $new) ?>
                          <?php elseif ($act === 'DELETE'): ?>
                            <?= e($old) ?>
                          <?php else: ?>
                            <?= e($new) ?>
                          <?php endif; ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </details>
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
            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e($pageUrl($page - 1)) ?>">Previous</a></li>
            <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
              <li class="page-item<?= $i === $page ? ' active' : '' ?>"><a class="page-link" href="<?= e($pageUrl($i)) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>"><a class="page-link" href="<?= e($pageUrl($page + 1)) ?>">Next</a></li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<p class="small text-secondary mt-3 mb-0">
  Entries cannot be edited. Failed sign-ins are removed automatically after <?= (int) AUDIT_KEEP_DAYS_FAILED ?> days
  and all other entries after <?= (int) AUDIT_KEEP_DAYS ?> days.
</p>

<div class="modal fade" id="clearModal" tabindex="-1" aria-labelledby="clearTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= e($self) ?>" class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="clearTitle">Clear audit entries?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="op" value="clear">
        <p class="small text-secondary">Choose what to remove. Everything that does not match stays.</p>

        <div class="mb-3">
          <label class="form-label" for="clear_module">Module</label>
          <select class="form-select" id="clear_module" name="module">
            <option value="">All modules</option>
            <?php foreach (AUDIT_MODULES as $code => $name): ?>
              <option value="<?= e($code) ?>"><?= e($name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label" id="clearTypesLabel">Entry types</label>
          <div class="dropdown">
            <button type="button" class="form-select text-start" id="clearTypesBtn" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside" aria-expanded="false"
                    aria-labelledby="clearTypesLabel clearTypesBtn">Failed sign-in</button>
            <div class="dropdown-menu w-100 p-2" id="clearTypesMenu">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="clearTypesAll">
                <label class="form-check-label fw-semibold" for="clearTypesAll">All types</label>
              </div>
              <hr class="dropdown-divider">
              <?php foreach (AUDIT_ACTIONS as $code => $name): ?>
                <div class="form-check" data-code="<?= e($code) ?>">
                  <input class="form-check-input" type="checkbox" name="types[]" value="<?= e($code) ?>"
                         id="clear_type_<?= e($code) ?>" data-label="<?= e($name) ?>"<?= $code === 'LOGIN_FAILED' ? ' checked' : '' ?>>
                  <label class="form-check-label" for="clear_type_<?= e($code) ?>"><?= e($name) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div>
          <label class="form-label" for="clear_age">Age</label>
          <select class="form-select" id="clear_age" name="age">
            <option value="0">Any age</option>
            <option value="30">Older than 30 days</option>
            <option value="90">Older than 90 days</option>
            <option value="180">Older than 180 days</option>
          </select>
        </div>

        <p class="small text-secondary mt-3 mb-0">
          This cannot be undone. A record of the clean-up (who, what and how many) is added. Earlier clean-up records can be removed, but the most recent one is always kept.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-signout" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Clear entries</button>
      </div>
    </form>
  </div>
</div>

<?php
$typeMap = ['' => audit_actions_for('')];
foreach (array_keys(AUDIT_MODULES) as $m) {
    $typeMap[$m] = audit_actions_for($m);
}
?>
<script>
  (function () {
    var allowedTypes = <?= json_encode($typeMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var moduleSel = document.getElementById('clear_module');
    var btn = document.getElementById('clearTypesBtn');
    var menu = document.getElementById('clearTypesMenu');
    var all = document.getElementById('clearTypesAll');
    if (!moduleSel || !btn || !menu || !all) { return; }
    var rows = Array.prototype.slice.call(menu.querySelectorAll('[data-code]'));

    function visibleBoxes() {
      return rows.filter(function (r) { return r.style.display !== 'none'; })
                 .map(function (r) { return r.querySelector('input'); });
    }

    function refresh() {
      var vis = visibleBoxes();
      var on = vis.filter(function (b) { return b.checked; });
      all.checked = vis.length > 0 && on.length === vis.length;
      if (on.length === 0) {
        btn.textContent = 'Choose types...';
      } else if (on.length === vis.length && vis.length > 1) {
        btn.textContent = 'All types';
      } else if (on.length <= 2) {
        btn.textContent = on.map(function (b) { return b.getAttribute('data-label'); }).join(', ');
      } else {
        btn.textContent = on.length + ' types selected';
      }
    }

    function applyModule() {
      var allowed = allowedTypes[moduleSel.value] || allowedTypes[''];
      rows.forEach(function (r) {
        var show = allowed.indexOf(r.getAttribute('data-code')) !== -1;
        r.style.display = show ? '' : 'none';
        if (!show) { r.querySelector('input').checked = false; }
      });
      refresh();
    }

    all.addEventListener('change', function () {
      visibleBoxes().forEach(function (b) { b.checked = all.checked; });
      refresh();
    });
    rows.forEach(function (r) { r.querySelector('input').addEventListener('change', refresh); });
    moduleSel.addEventListener('change', applyModule);
    applyModule();
  })();
</script>