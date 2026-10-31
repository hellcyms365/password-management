/* 密码管理器 前端脚本 */
(function () {
  'use strict';

  function escAttr(s) {
    return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;')
      .replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  /* ============ 自定义字段编辑器 ============ */
  function addCustomField(name, value) {
    var editor = document.getElementById('customFieldsEditor');
    if (!editor) return;
    var row = document.createElement('div');
    row.className = 'cf-row';
    row.innerHTML =
      '<input type="text" name="field_names[]" placeholder="字段名，如 邮箱 / 手机 / 备注" value="' + escAttr(name || '') + '">' +
      '<input type="text" name="field_values[]" placeholder="字段值" value="' + escAttr(value || '') + '">' +
      '<button type="button" class="btn btn-small btn-danger cf-remove" title="删除该字段">×</button>';
    editor.appendChild(row);
  }

  // 编辑页：回显已有自定义字段
  (function initFields() {
    var editor = document.getElementById('customFieldsEditor');
    if (!editor) return;
    var dataAttr = editor.getAttribute('data-fields');
    var list = [];
    if (dataAttr) {
      try { list = JSON.parse(dataAttr); } catch (e) { list = []; }
    }
    list.forEach(function (f) { addCustomField(f.name, f.value); });
  })();

  // 添加字段按钮
  var addBtn = document.querySelector('.add-custom-field');
  if (addBtn) {
    addBtn.addEventListener('click', function () { addCustomField('', ''); });
  }

  // 删除字段（事件委托）
  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t && t.classList.contains('cf-remove')) {
      var row = t.closest('.cf-row');
      if (row) row.parentNode.removeChild(row);
    }
  });

  /* ============ 密码显示 / 隐藏 ============ */
  document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var cell = btn.closest('.pw-cell');
      if (!cell) return;
      var mask = cell.querySelector('.pw-mask');
      if (!mask) return;
      if (btn.classList.contains('revealed')) {
        mask.textContent = '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022';
        btn.textContent = '显示';
        btn.classList.remove('revealed');
        return;
      }
      var url = mask.getAttribute('data-reveal-url');
      var id = mask.getAttribute('data-id');
      fetch(url + '?id=' + id, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.ok) {
            mask.textContent = d.password || '';
            btn.textContent = '隐藏';
            btn.classList.add('revealed');
          }
        })
        .catch(function () { mask.textContent = '获取失败'; });
    });
  });

  /* ============ 生成随机密码 ============ */
  function randomPassword(len) {
    len = len || 16;
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
    var arr = new Uint32Array(len);
    if (window.crypto && crypto.getRandomValues) {
      crypto.getRandomValues(arr);
    } else {
      for (var i = 0; i < len; i++) arr[i] = Math.floor(Math.random() * 0xFFFFFFFF);
    }
    var out = '';
    for (var i = 0; i < len; i++) out += chars[arr[i] % chars.length];
    return out;
  }

  document.querySelectorAll('.gen-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var field = btn.closest('.field');
      if (!field) return;
      var input = field.querySelector('.pw-input');
      if (input) input.value = randomPassword();
    });
  });

  /* ============ 账号卡片：可修改字段折叠提示 ============ */
  document.addEventListener('toggle', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('custom-fields')) {
      var span = e.target.querySelector('.cf-toggle');
      if (span) span.textContent = e.target.open ? '收起' : '展开';
    }
  });

  /* ============ 账号图片：点击打开预览（灯箱） ============ */
  var lightbox = null;
  document.addEventListener('click', function (e) {
    var link = e.target && e.target.closest ? e.target.closest('[data-lightbox]') : null;
    if (!link) return;
    e.preventDefault();
    var src = link.getAttribute('href');
    if (!lightbox) {
      lightbox = document.createElement('div');
      lightbox.className = 'lightbox';
      lightbox.innerHTML = '<div class="lightbox-inner"><img alt="预览"></div>';
      document.body.appendChild(lightbox);
      lightbox.addEventListener('click', function () {
        lightbox.classList.remove('open');
      });
    }
    lightbox.querySelector('img').src = src;
    lightbox.classList.add('open');
  });
})();
