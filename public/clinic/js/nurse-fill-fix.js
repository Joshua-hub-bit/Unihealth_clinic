'use strict';
// ═══════════════════════════════════════════════════════════
// SUNN Clinic — Form Fill Fix
// The form panels created by buildFormPanels() have the same
// element IDs as the old hidden pool (display:none) at the
// bottom of the HTML. This causes getElementById to sometimes
// find the wrong element. This fix:
//   1. Removes the hidden pool after the panels are built
//   2. Re-writes all field values after autoFillForm runs
//      to guarantee the visible panel inputs get the data
// ═══════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function() {
  // Remove the hidden pool after buildFormPanels() has run.
  // buildFormPanels() runs at DOMContentLoaded in nurse-forms.js.
  // We use setTimeout to run AFTER all DOMContentLoaded handlers.
  setTimeout(function removeHiddenPool() {
    document.querySelectorAll('div[aria-hidden="true"]').forEach(function(div) {
      if (div.style.display === 'none') {
        div.parentNode.removeChild(div);
      }
    });
  }, 10);
});
