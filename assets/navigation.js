(() => {
  'use strict';

  const initialized = new WeakSet();
  const mobile = window.matchMedia('(max-width: 782px)');

  function initialize(navigation) {
    if (initialized.has(navigation)) return;
    const button = navigation.querySelector(':scope > .nl-layout-menu-toggle');
    const menu = navigation.querySelector(':scope > .nl-layout-menu');
    if (!button || !menu) return;

    initialized.add(navigation);
    const setExpanded = (expanded) => button.setAttribute('aria-expanded', String(expanded));
    setExpanded(!mobile.matches);
    button.hidden = false;
    navigation.dataset.nlEnhanced = '';

    button.addEventListener('click', () => {
      if (mobile.matches) setExpanded(button.getAttribute('aria-expanded') !== 'true');
    });
    navigation.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && mobile.matches && button.getAttribute('aria-expanded') === 'true') {
        setExpanded(false);
        button.focus();
        event.preventDefault();
      }
    });
  }

  function scan(root) {
    if (!(root instanceof Element || root instanceof Document)) return;
    if (root instanceof Element && root.matches('[data-nl-navigation]')) initialize(root);
    root.querySelectorAll('[data-nl-navigation]').forEach(initialize);
  }

  function start() {
    scan(document);
    mobile.addEventListener('change', () => {
      document.querySelectorAll('[data-nl-enhanced] > .nl-layout-menu-toggle').forEach((button) => {
        button.setAttribute('aria-expanded', String(!mobile.matches));
      });
    });
    // Elementor replaces widget markup during editing; initialize each new menu once.
    new MutationObserver((records) => {
      records.forEach((record) => record.addedNodes.forEach(scan));
    }).observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();
