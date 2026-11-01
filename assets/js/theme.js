/* Theme preference — loaded early so the page paints in the right mode. */
(function () {
  'use strict';

  var root = document.documentElement;
  var key = 'rb-theme';
  var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function read() {
    try {
      return window.localStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function store(mode) {
    try {
      window.localStorage.setItem(key, mode);
    } catch (error) {
      /* Storage can be unavailable in private modes; the toggle still works. */
    }
  }

  function current() {
    return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }

  function metaColor(mode) {
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', mode === 'dark' ? '#1c141b' : '#30202D');
  }

  function paintButtons() {
    var dark = current() === 'dark';
    var buttons = document.querySelectorAll('[data-theme-toggle]');
    for (var i = 0; i < buttons.length; i += 1) {
      buttons[i].setAttribute('aria-pressed', dark ? 'true' : 'false');
      buttons[i].setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
      var label = buttons[i].querySelector('[data-theme-label]');
      if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
    }
  }

  function apply(mode, remember) {
    root.setAttribute('data-theme', mode);
    if (remember) store(mode);
    metaColor(mode);
    paintButtons();
  }

  var saved = read();
  apply(saved ? saved : (media && media.matches ? 'dark' : 'light'), false);

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-theme-toggle]');
    if (!button) return;
    apply(current() === 'dark' ? 'light' : 'dark', true);
  });

  if (media) {
    var systemChange = function (event) {
      if (!read()) apply(event.matches ? 'dark' : 'light', false);
    };
    if (media.addEventListener) media.addEventListener('change', systemChange);
    else if (media.addListener) media.addListener(systemChange);
  }

  document.addEventListener('DOMContentLoaded', paintButtons);
})();
