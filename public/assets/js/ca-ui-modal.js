/**
 * Modales CleanAvis — remplace alert() natif (accessibilité + style).
 * API : showCaModal({ variant, title, message, listItems?, hint? }), closeCaModal()
 */
(function (global) {
  'use strict';

  function escapeHtml(text) {
    if (text == null) return '';
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
  }

  var ICONS = {
    error:
      '<svg class="ca-modal__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
    warning:
      '<svg class="ca-modal__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
    info:
      '<svg class="ca-modal__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    success:
      '<svg class="ca-modal__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  };

  function ensureModal() {
    if (document.getElementById('ca-modal-overlay')) return;

    var wrap = document.createElement('div');
    wrap.id = 'ca-modal-root';
    wrap.innerHTML =
      '<div class="ca-modal-overlay" id="ca-modal-overlay" hidden>' +
      '<div class="ca-modal" role="dialog" aria-modal="true" aria-labelledby="ca-modal-title">' +
      '<button type="button" class="ca-modal__close" id="ca-modal-close-x" aria-label="Fermer">&times;</button>' +
      '<div class="ca-modal__visual" id="ca-modal-visual" aria-hidden="true"></div>' +
      '<h2 class="ca-modal__title" id="ca-modal-title"></h2>' +
      '<div class="ca-modal__body" id="ca-modal-body"></div>' +
      '<div class="ca-modal__footer">' +
      '<button type="button" class="ca-modal__btn ca-modal__btn--primary" id="ca-modal-ok">OK</button>' +
      '</div></div></div>';

    document.body.appendChild(wrap);

    var ov = document.getElementById('ca-modal-overlay');
    ov.addEventListener('click', function (e) {
      if (e.target === ov) global.closeCaModal();
    });
    document.getElementById('ca-modal-ok').addEventListener('click', global.closeCaModal);
    document.getElementById('ca-modal-close-x').addEventListener('click', global.closeCaModal);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && ov && !ov.hidden) {
        e.preventDefault();
        global.closeCaModal();
      }
    });
  }

  global.showCaModal = function (opts) {
    if (!opts || typeof opts !== 'object') opts = {};
    ensureModal();

    var variant = opts.variant || 'info';
    if (['error', 'warning', 'info', 'success'].indexOf(variant) === -1) variant = 'info';

    var ov = document.getElementById('ca-modal-overlay');
    var modal = ov.querySelector('.ca-modal');
    modal.className = 'ca-modal ca-modal--' + variant;

    document.getElementById('ca-modal-title').textContent = opts.title || 'Information';

    var html = '';
    if (opts.message) {
      html +=
        '<p class="ca-modal__text">' +
        escapeHtml(opts.message).replace(/\r\n|\r|\n/g, '<br>') +
        '</p>';
    }
    if (opts.listItems && opts.listItems.length) {
      html += '<ul class="ca-modal__list">';
      for (var i = 0; i < opts.listItems.length; i++) {
        html += '<li>' + escapeHtml(opts.listItems[i]) + '</li>';
      }
      html += '</ul>';
    }
    if (opts.hint) {
      html += '<p class="ca-modal__hint">' + escapeHtml(opts.hint) + '</p>';
    }
    document.getElementById('ca-modal-body').innerHTML = html || '<p class="ca-modal__text">—</p>';

    document.getElementById('ca-modal-visual').innerHTML = ICONS[variant] || ICONS.info;

    ov.hidden = false;
    document.body.style.overflow = 'hidden';

    requestAnimationFrame(function () {
      var btn = document.getElementById('ca-modal-ok');
      if (btn) btn.focus();
    });
  };

  global.closeCaModal = function () {
    var ov = document.getElementById('ca-modal-overlay');
    if (ov) {
      ov.hidden = true;
      document.body.style.overflow = '';
    }
  };
})(typeof window !== 'undefined' ? window : globalThis);
