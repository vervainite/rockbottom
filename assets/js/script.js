/* RockBottom interactions — no inline scripts, CSP friendly. */
(function () {
  'use strict';

  var peso = function (value) {
    return '\u20B1' + Number(value || 0).toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  onReady(function () {
    /* ---- Sticky header state ------------------------------------------- */
    var header = document.getElementById('siteHeader');
    if (header) {
      var updateHeader = function () {
        header.classList.toggle('is-scrolled', window.scrollY > 8);
      };
      updateHeader();
      window.addEventListener('scroll', updateHeader, { passive: true });
    }

    /* ---- Flash alerts -------------------------------------------------- */
    document.addEventListener('click', function (event) {
      var dismiss = event.target.closest('[data-dismiss-alert]');
      if (!dismiss) return;
      var alert = dismiss.closest('.rb-alert');
      if (alert) alert.remove();
    });

    /* ---- Confirmation dialogs ----------------------------------------- */
    document.addEventListener('submit', function (event) {
      var form = event.target;
      var trigger = event.submitter || form.querySelector('[data-confirm]');
      if (!trigger || !trigger.dataset.confirm) return;
      if (!window.confirm(trigger.dataset.confirm)) {
        event.preventDefault();
      }
    });

    /* ---- Quantity steppers -------------------------------------------- */
    document.addEventListener('click', function (event) {
      var step = event.target.closest('[data-step]');
      if (!step) return;
      var stepper = step.closest('[data-stepper]');
      var input = stepper && stepper.querySelector('input[type="number"]');
      if (!input || input.disabled) return;
      event.preventDefault();
      var min = parseInt(input.min, 10);
      var max = parseInt(input.max, 10);
      if (!Number.isFinite(min)) min = 1;
      if (!Number.isFinite(max) || max < min) max = min;
      var next = (parseInt(input.value, 10) || min) + parseInt(step.dataset.step, 10);
      next = Math.max(min, Math.min(max, next));
      input.value = String(next);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    document.addEventListener('change', function (event) {
      var input = event.target;
      if (!input.matches('[data-stepper] input[type="number"]')) return;
      var min = parseInt(input.min, 10);
      var max = parseInt(input.max, 10);
      if (!Number.isFinite(min)) min = 1;
      if (!Number.isFinite(max) || max < min) max = min;
      var value = parseInt(input.value, 10);
      if (!Number.isFinite(value)) value = min;
      input.value = String(Math.max(min, Math.min(max, value)));
    });

    /* ---- Search trigger ------------------------------------------------ */
    document.querySelectorAll('[data-search-trigger]').forEach(function (trigger) {
      trigger.addEventListener('click', function (event) {
        var search = document.getElementById('catalogSearch');
        if (!search) return;
        event.preventDefault();
        var shop = document.getElementById('shop');
        if (shop) shop.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(function () {
          search.focus({ preventScroll: true });
        }, 350);
      });
    });

    /* ---- Catalog ------------------------------------------------------- */
    (function initCatalog() {
      var shop = document.getElementById('shop');
      var grid = document.getElementById('productGrid');
      if (!shop || !grid) return;

      var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-product-card]'));
      var search = document.getElementById('catalogSearch');
      var category = document.getElementById('catalogCategory');
      var sort = document.getElementById('catalogSort');
      var reset = document.getElementById('catalogReset');
      var resetEmpty = document.getElementById('catalogResetEmpty');
      var empty = document.getElementById('catalogEmpty');
      var count = document.getElementById('catalogCount');

      var products = [];
      try {
        products = JSON.parse(shop.dataset.products || '[]');
      } catch (error) {
        products = [];
      }
      var byId = new Map();
      products.forEach(function (product) {
        byId.set(Number(product.id), product);
      });
      window.rbProducts = products;
      window.rbProductMap = byId;

      function apply() {
        var query = search ? search.value.trim().toLowerCase() : '';
        var selected = category ? category.value : '';
        var order = sort ? sort.value : 'newest';
        var visible = 0;

        cards.forEach(function (card) {
          var matchesSearch = !query || (card.dataset.search || '').indexOf(query) !== -1;
          var matchesCategory = !selected || card.dataset.category === selected;
          var show = matchesSearch && matchesCategory;
          card.classList.toggle('is-filtered', !show);
          if (show) visible += 1;
        });

        var sorted = cards.slice().sort(function (a, b) {
          if (order === 'price-asc') {
            return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
          }
          if (order === 'price-desc') {
            return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
          }
          return parseInt(b.dataset.newest, 10) - parseInt(a.dataset.newest, 10);
        });
        var fragment = document.createDocumentFragment();
        sorted.forEach(function (card) { fragment.appendChild(card); });
        grid.appendChild(fragment);

        if (count) {
          count.textContent = visible === 1
            ? 'Showing 1 treasure'
            : 'Showing ' + visible + ' treasures';
        }
        if (empty) empty.hidden = visible !== 0;
        if (reset) {
          reset.hidden = !(query || selected || order !== 'newest');
        }
      }

      function clear() {
        if (search) search.value = '';
        if (category) category.value = '';
        if (sort) sort.value = 'newest';
        apply();
        if (search) search.focus({ preventScroll: true });
      }

      var debounce;
      if (search) {
        search.addEventListener('input', function () {
          window.clearTimeout(debounce);
          debounce = window.setTimeout(apply, 90);
        });
      }
      if (category) category.addEventListener('change', apply);
      if (sort) sort.addEventListener('change', apply);
      if (reset) reset.addEventListener('click', clear);
      if (resetEmpty) resetEmpty.addEventListener('click', clear);

      apply();

      /* ---- Product details modal -------------------------------------- */
      var modalEl = document.getElementById('productModal');
      if (!modalEl || !window.bootstrap) return;

      var modalImage = document.getElementById('modalImage');
      var modalCategory = document.getElementById('modalCategory');
      var modalTitle = document.getElementById('productModalTitle');
      var modalDescription = document.getElementById('modalDescription');
      var modalPrice = document.getElementById('modalPrice');
      var modalStock = document.getElementById('modalStock');
      var modalProductId = document.getElementById('modalProductId');
      var modalQty = document.getElementById('modalQty');
      var modalAdd = document.getElementById('modalAdd');

      modalEl.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) return;
        var product = byId.get(Number(trigger.dataset.productId));
        if (!product) return;

        var stock = Math.max(0, parseInt(product.stock, 10) || 0);
        if (modalImage) {
          modalImage.src = product.image;
          modalImage.alt = product.name + ' collectible';
        }
        if (modalCategory) modalCategory.textContent = product.category || 'Collectible';
        if (modalTitle) modalTitle.textContent = product.name;
        if (modalDescription) modalDescription.textContent = product.description;
        if (modalPrice) modalPrice.textContent = peso(product.price);
        if (modalStock) {
          modalStock.textContent = stock > 0
            ? 'In stock \u00B7 ' + stock + ' available'
            : 'Sold out \u00B7 restocking soon';
        }
        if (modalProductId) modalProductId.value = String(product.id);
        if (modalQty) {
          modalQty.max = String(Math.max(1, Math.min(stock || 1, 10)));
          modalQty.value = '1';
          modalQty.disabled = stock < 1;
        }
        if (modalAdd) {
          modalAdd.disabled = stock < 1;
          modalAdd.textContent = stock < 1 ? 'Sold out' : 'Add to bag';
        }
      });
    })();

    /* ---- Reveal on scroll --------------------------------------------- */
    var reveals = document.querySelectorAll('.reveal');
    if (reveals.length) {
      if (!('IntersectionObserver' in window)) {
        reveals.forEach(function (el) { el.classList.add('is-visible'); });
      } else {
        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              observer.unobserve(entry.target);
            }
          });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px' });
        reveals.forEach(function (el) { observer.observe(el); });
      }
    }

    /* ---- Admin sidebar ------------------------------------------------- */
    var adminSidebar = document.getElementById('adminSidebar');
    var adminBackdrop = document.querySelector('.admin-backdrop');
    var adminOpeners = document.querySelectorAll('[data-admin-menu]');
    if (adminSidebar && adminOpeners.length) {
      var setAdminOpen = function (open) {
        adminSidebar.classList.toggle('is-open', open);
        if (adminBackdrop) adminBackdrop.hidden = !open;
        adminOpeners.forEach(function (button) {
          if (button.hasAttribute('aria-expanded')) {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
          }
        });
      };
      adminOpeners.forEach(function (button) {
        button.addEventListener('click', function () {
          setAdminOpen(!adminSidebar.classList.contains('is-open'));
        });
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && adminSidebar.classList.contains('is-open')) {
          setAdminOpen(false);
        }
      });
      window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) setAdminOpen(false);
      });
    }

    /* ---- Payment option highlight ------------------------------------- */
    document.querySelectorAll('.payment-option input[type="radio"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        document.querySelectorAll('.payment-option').forEach(function (option) {
          option.classList.toggle('is-selected', !!option.querySelector('input:checked'));
        });
      });
    });
  });
})();
