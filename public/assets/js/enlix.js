(function () {
  'use strict';

  function isMobile() {
    return window.innerWidth < 992;
  }

  function initMobileMenu() {
    var nav = document.getElementById('enlixNav');
    if (!nav) return;

    // Prevent Bootstrap dropdown toggle from interfering on mobile
    var servicesToggle = document.querySelector('.enlix-flyout-menu > .nav-link[data-bs-toggle="dropdown"]');
    if (servicesToggle) {
      servicesToggle.addEventListener('click', function (e) {
        if (isMobile()) e.stopImmediatePropagation();
      }, true);
    }

    // Accordion: tap a category row to expand/collapse its sub-links
    nav.addEventListener('click', function (e) {
      if (!isMobile()) return;

      var flyoutLink = e.target.closest('.flyout-link');
      if (!flyoutLink) return;

      var flyoutItem = flyoutLink.closest('.flyout-item');
      if (!flyoutItem || !flyoutItem.querySelector('.flyout-sub')) return;

      e.preventDefault();

      var isOpen = flyoutItem.classList.contains('is-open');

      // Close all open items (one open at a time)
      nav.querySelectorAll('.flyout-item.is-open').forEach(function (el) {
        el.classList.remove('is-open');
      });

      if (!isOpen) {
        flyoutItem.classList.add('is-open');
        // Scroll the opened item into view
        setTimeout(function () {
          flyoutItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 50);
      }
    });

    // Reset accordions when hamburger closes the nav
    document.getElementById('enlixNav').addEventListener('hide.bs.collapse', function () {
      nav.querySelectorAll('.flyout-item.is-open').forEach(function (el) {
        el.classList.remove('is-open');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenu);
  } else {
    initMobileMenu();
  }
})();
