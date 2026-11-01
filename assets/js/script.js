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

    /* ---- Nav location indicator ---------------------------------------- */
    (function initNavSpy() {
      var nav = document.querySelector('.main-nav');
      if (!nav) return;
      var links = Array.prototype.slice.call(nav.querySelectorAll('a'));
      var sections = ['shop', 'about', 'contact'].map(function (id) {
        return { id: id, el: document.getElementById(id) };
      }).filter(function (section) { return section.el; });
      if (!sections.length) return;

      var lockedId = '';
      var lockedUntil = 0;

      var setActive = function (id) {
        links.forEach(function (link) {
          var target = (link.getAttribute('href') || '').split('#')[1] || 'home';
          link.classList.toggle('is-active', target === id);
        });
      };

      var update = function () {
        if (lockedId && Date.now() < lockedUntil) {
          setActive(lockedId);
          return;
        }
        lockedId = '';
        var offset = (header ? header.offsetHeight : 80) + 24;
        var atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
        var current = 'home';
        sections.forEach(function (section) {
          var rect = section.el.getBoundingClientRect();
          if (rect.top <= offset && rect.bottom > offset) current = section.id;
        });
        if (atBottom) current = sections[sections.length - 1].id;
        setActive(current);
      };

      var closeDrawer = function () {
        var drawer = document.getElementById('mobileNav');
        if (!drawer || !drawer.classList.contains('show') || !window.bootstrap) return;
        var instance = window.bootstrap.Offcanvas.getInstance(drawer);
        if (instance) instance.hide();
      };

      var onNavClick = function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        var link = event.currentTarget;
        var href = link.getAttribute('href') || '';
        var hash = href.split('#')[1] || '';
        var target = hash ? document.getElementById(hash) : null;
        if (hash && !target) return;
        event.preventDefault();
        lockedId = hash || 'home';
        lockedUntil = Date.now() + 1400;
        setActive(lockedId);
        if (history.pushState) history.pushState(null, '', href);
        closeDrawer();
        if (target) {
          target.scrollIntoView({ block: 'start' });
        } else {
          window.scrollTo({ top: 0 });
        }
      };

      var navLinks = links.concat(Array.prototype.slice.call(document.querySelectorAll('.mobile-nav__links a')));
      navLinks.forEach(function (link) { link.addEventListener('click', onNavClick); });

      update();
      window.addEventListener('scroll', update, { passive: true });
      window.addEventListener('resize', update);
      window.addEventListener('hashchange', update);
      window.addEventListener('popstate', update);
    })();

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

    /* ---- Collector card ------------------------------------------------ */
    (function initCollectorCard() {
      var card = document.querySelector('[data-collector-card]');
      if (!card) return;

      var flip = document.querySelector('[data-card-flip]');
      var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
      var finePoint = window.matchMedia('(hover: hover) and (pointer: fine)');
      var tiltFrame = 0;

      function setFlipped(on) {
        card.classList.toggle('is-flipped', on);
        card.style.removeProperty('--tilt-x');
        card.style.removeProperty('--tilt-y');
        if (flip) {
          flip.setAttribute('aria-pressed', on ? 'true' : 'false');
          flip.textContent = on ? 'Flip back' : 'Flip card';
        }
      }

      function toggle() {
        setFlipped(!card.classList.contains('is-flipped'));
      }

      if (flip) flip.addEventListener('click', toggle);
      card.addEventListener('click', function (event) {
        if (event.target.closest('a, button')) return;
        toggle();
      });

      var resetTilt = function () {
        window.cancelAnimationFrame(tiltFrame);
        card.style.removeProperty('--tilt-x');
        card.style.removeProperty('--tilt-y');
      };

      card.addEventListener('pointermove', function (event) {
        if (reduce.matches || !finePoint.matches || card.classList.contains('is-flipped')) return;
        var rect = card.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        var px = (event.clientX - rect.left) / rect.width;
        var py = (event.clientY - rect.top) / rect.height;
        window.cancelAnimationFrame(tiltFrame);
        tiltFrame = window.requestAnimationFrame(function () {
          card.style.setProperty('--tilt-y', ((px - 0.5) * 12).toFixed(2) + 'deg');
          card.style.setProperty('--tilt-x', ((0.5 - py) * 10).toFixed(2) + 'deg');
          card.style.setProperty('--shine-x', (px * 100).toFixed(1) + '%');
          card.style.setProperty('--shine-y', (py * 100).toFixed(1) + '%');
        });
      });
      card.addEventListener('pointerleave', resetTilt);
      card.addEventListener('blur', resetTilt);

      var skins = ['reef', 'dune', 'moon', 'scrap', 'bubble'];
      var styleInputs = Array.prototype.slice.call(document.querySelectorAll('[data-card-style]'));
      var styleName = document.querySelector('[data-card-style-name]');
      var styleLabel = document.querySelector('[data-card-style-label]');

      function applyStyle(input) {
        styleInputs.forEach(function (radio) {
          var swatch = radio.closest('.card-swatch');
          if (swatch) swatch.classList.toggle('is-selected', radio.checked);
        });
        skins.forEach(function (skin) {
          card.classList.toggle('is-' + skin, input.value === skin);
        });
        var swatch = input.closest('.card-swatch');
        var labelNode = swatch ? swatch.querySelector('strong') : null;
        var label = labelNode ? labelNode.textContent : input.value;
        if (styleName) styleName.textContent = label;
        if (styleLabel) styleLabel.textContent = label;
      }

      styleInputs.forEach(function (input) {
        input.addEventListener('change', function () {
          if (input.checked) applyStyle(input);
        });
      });
      var checkedSkin = document.querySelector('[data-card-style]:checked');
      if (checkedSkin) applyStyle(checkedSkin);

      var nameInput = document.querySelector('[data-card-name-input]');
      var nameNode = document.querySelector('[data-card-name]');
      var bioInput = document.querySelector('[data-card-bio-input]');
      var bioNode = document.querySelector('[data-card-bio]');
      var bioCount = document.querySelector('[data-bio-count]');
      var initialsNode = document.querySelector('[data-card-initials]');
      var houseBio = 'Collecting small strange things from the bottom of the pond.';

      function initialsOf(value) {
        var parts = String(value || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        var first = parts[0].charAt(0);
        var last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';
        return (first + last).toUpperCase();
      }

      if (nameInput) {
        nameInput.addEventListener('input', function () {
          var value = nameInput.value.trim();
          if (nameNode) nameNode.textContent = value || 'Your name';
          if (initialsNode && !initialsNode.hidden) initialsNode.textContent = initialsOf(value);
        });
      }

      if (bioInput) {
        bioInput.addEventListener('input', function () {
          var value = bioInput.value.trim();
          if (bioNode) bioNode.textContent = value || houseBio;
          if (bioCount) bioCount.textContent = String(bioInput.value.length);
        });
      }

      var avatarImage = document.querySelector('[data-card-avatar]');
      var avatarThumbImage = document.querySelector('[data-avatar-thumb-img]');
      var avatarThumbInitials = document.querySelector('[data-avatar-thumb-initials]');
      var avatarInput = document.querySelector('[data-avatar-input]');
      var avatarRemove = document.querySelector('[data-avatar-remove]');
      var originalAvatar = avatarImage && avatarImage.getAttribute('src') ? avatarImage.getAttribute('src') : '';

      function readPreview(file, done) {
        var reader = new FileReader();
        reader.onload = function () {
          if (typeof reader.result === 'string') done(reader.result);
        };
        reader.readAsDataURL(file);
      }

      function showAvatar(src) {
        var has = !!src;
        if (avatarImage) {
          if (has) {
            avatarImage.src = src;
            avatarImage.hidden = false;
          } else {
            avatarImage.hidden = true;
            avatarImage.removeAttribute('src');
          }
        }
        if (initialsNode) initialsNode.hidden = has;
        if (avatarThumbImage) {
          if (has) {
            avatarThumbImage.src = src;
            avatarThumbImage.hidden = false;
          } else {
            avatarThumbImage.hidden = true;
            avatarThumbImage.removeAttribute('src');
          }
        }
        if (avatarThumbInitials) avatarThumbInitials.hidden = has;
      }

      if (avatarInput) {
        avatarInput.addEventListener('change', function () {
          var file = avatarInput.files && avatarInput.files[0];
          if (!file) {
            showAvatar(avatarRemove && avatarRemove.checked ? '' : originalAvatar);
            return;
          }
          readPreview(file, showAvatar);
          if (avatarRemove) avatarRemove.checked = false;
        });
      }
      if (avatarRemove) {
        avatarRemove.addEventListener('change', function () {
          if (avatarRemove.checked) {
            if (avatarInput) avatarInput.value = '';
            showAvatar('');
          } else if (avatarInput && !avatarInput.files.length) {
            showAvatar(originalAvatar);
          }
        });
      }

      var cover = document.querySelector('[data-card-cover]');
      var coverThumb = document.querySelector('[data-cover-thumb]');
      var coverInput = document.querySelector('[data-cover-input]');
      var coverRemove = document.querySelector('[data-cover-remove]');
      var originalCover = '';
      if (cover) {
        var currentCover = cover.style.backgroundImage || '';
        var coverMatch = currentCover.match(/^url\(["']?(.*?)["']?\)$/);
        if (coverMatch) originalCover = coverMatch[1];
      }
      function showCover(url) {
        var value = url ? 'url("' + url.replace(/"/g, '%22') + '")' : '';
        if (cover) cover.style.backgroundImage = value;
        if (coverThumb) coverThumb.style.backgroundImage = value;
      }

      if (coverInput) {
        coverInput.addEventListener('change', function () {
          var file = coverInput.files && coverInput.files[0];
          if (!file) {
            showCover(coverRemove && coverRemove.checked ? '' : originalCover);
            return;
          }
          readPreview(file, showCover);
          if (coverRemove) coverRemove.checked = false;
        });
      }
      if (coverRemove) {
        coverRemove.addEventListener('change', function () {
          if (coverRemove.checked) {
            if (coverInput) coverInput.value = '';
            showCover('');
          } else if (coverInput && !coverInput.files.length) {
            showCover(originalCover);
          }
        });
      }
    })();

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
