(function () {
  'use strict';

  var el = document.getElementById('crudModal');
  if (!el) { return; }

  var dialog = el.querySelector('.modal-dialog');
  var loadingHtml = dialog.innerHTML;
  var headers = { 'X-Requested-With': 'XMLHttpRequest' };
  var modal = null;

  function getModal() {
    if (!modal) { modal = bootstrap.Modal.getOrCreateInstance(el); }
    return modal;
  }

  function setContent(html) {
    dialog.innerHTML = html;
    var first = dialog.querySelector('input:not([type=hidden]), select');
    if (first) { first.focus(); }
  }

  function showMessage(text) {
    var box = document.createElement('div');
    box.className = 'modal-content';
    box.innerHTML =
      '<div class="modal-header"><h2 class="modal-title h5">Notice</h2>' +
      '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
      '<div class="modal-body"><div class="alert alert-warning mb-0"></div></div>' +
      '<div class="modal-footer"><button type="button" class="btn btn-signout" data-bs-dismiss="modal">Close</button></div>';
    box.querySelector('.alert').textContent = text;
    dialog.innerHTML = '';
    dialog.appendChild(box);
  }

  function open(url) {
    dialog.innerHTML = loadingHtml;
    getModal().show();
    fetch(url, { headers: headers, credentials: 'same-origin', cache: 'no-store' })
      .then(function (res) {
        if (res.redirected) { window.location.href = res.url; return; }
        return res.text().then(function (text) {
          if (res.ok) { setContent(text); } else { showMessage(text || 'Something went wrong. Please try again.'); }
        });
      })
      .catch(function () { showMessage('Could not load this window. Check your connection and try again.'); });
  }

  document.addEventListener('click', function (ev) {
    var link = ev.target.closest('a[data-crud-modal]');
    if (!link) { return; }
    if (ev.button !== 0 || ev.ctrlKey || ev.metaKey || ev.shiftKey) { return; }
    ev.preventDefault();
    open(link.href);
  });

  dialog.addEventListener('submit', function (ev) {
    var form = ev.target.closest('form[data-crud-form]');
    if (!form) { return; }
    ev.preventDefault();

    var btn = form.querySelector('button[type=submit]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving...'; }

    fetch(form.action, { method: 'POST', body: new FormData(form), headers: headers, credentials: 'same-origin' })
      .then(function (res) {
        if (res.redirected) { window.location.href = res.url; return; }
        if (res.ok) { window.location.reload(); return; }
        if (res.status === 403) { window.location.reload(); return; }
        return res.text().then(function (text) {
          if (res.status === 422) { setContent(text); }
          else { showMessage(text || 'The record could not be saved. Please try again.'); }
        });
      })
      .catch(function () { showMessage('Could not save. Check your connection and try again.'); });
  });

  el.addEventListener('hidden.bs.modal', function () { dialog.innerHTML = loadingHtml; });
})();