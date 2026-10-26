'use strict';
// ============================================================
// SUNN Clinic — Live Typeahead Search for Nurse Form Toolbar
// Wires all form search inputs (id="search-*" or id="*_search")
// to show a live dropdown of matching students as the nurse types.
// Clicking a result auto-fills all form fields and updates preview.
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  // Wait a tick so nurse.js and nurse-forms.js are fully initialized
  setTimeout(initFormSearchTypeahead, 100);
});

function initFormSearchTypeahead() {
  // Find all form search inputs. Two naming conventions are used in the app:
  //   • "search-medcert"      (dash, used by some form toolbars)
  //   • "medcert_search"      (underscore, used by the medcert toolbar)
  const inputs = [
    ...document.querySelectorAll('input[id^="search-"]'),
    ...document.querySelectorAll('input[id$="_search"]'),
    ...document.querySelectorAll('#vitals_patient_id'),
  ];

  inputs.forEach(input => {
    // Derive the form type from the input id.
    //   search-medcert   → "medcert"
    //   medcert_search   → "medcert"
    let formType;
    if (input.id === 'vitals_patient_id') {
      formType = 'vitals';
    } else if (input.id.startsWith('search-')) {
      formType = input.id.replace('search-', '');
    } else {
      formType = input.id.replace(/_search$/, '');
    }
    const formTypeAliases = {
      prescription_dentist: 'prescription-dentist',
      referral_dentist: 'referral-dentist',
    };
    formType = formTypeAliases[formType] || formType;

    // The search-toolbar needs position:relative so the dropdown anchors below it
    const toolbar = input.closest('.search-toolbar');
    if (!toolbar) return;
    toolbar.style.position = 'relative';

    // Create dropdown container
    const dropdown = document.createElement('div');
    dropdown.className = 'typeahead-dropdown';
    dropdown.style.cssText = [
      'position:absolute',
      'top:calc(100% + 2px)',
      'left:0',
      'right:0',
      'z-index:99999',
      'background:#fff',
      'border:1px solid #d4e6df',
      'border-radius:0 0 10px 10px',
      'box-shadow:0 8px 24px rgba(0,0,0,0.18)',
      'max-height:260px',
      'overflow-y:auto',
      'display:none',
      'font-family:Poppins,sans-serif',
    ].join(';');
    toolbar.appendChild(dropdown);

    let debounce = null;
    let searchRequest = 0;
    input._cancelNurseTypeaheadDebounce = () => clearTimeout(debounce);

    // — type to search —
    input.addEventListener('input', () => {
      clearTimeout(debounce);
      searchRequest += 1;
      const requestId = searchRequest;
      const q = input.value.trim();
      input._nurseTypeaheadSearchCache = null;
      if (input.id === 'vitals_patient_id') {
        const selected = document.getElementById('vitals-selected-student');
        if (selected) { selected.innerHTML = ''; }
      }

      if (q.length < 2) {
        closeDropdown(dropdown);
        return;
      }

      dropdown.innerHTML = '<div style="padding:11px 14px;color:#888;font-size:0.8rem">Searching...</div>';
      dropdown.style.display = 'block';

      debounce = setTimeout(async () => {
        let searchPromise;
        try {
          // apiFetch is defined in nurse.js — available globally
          searchPromise = apiFetch('search_student&q=' + encodeURIComponent(q));
          input._nurseTypeaheadSearchRequest = { query: q, promise: searchPromise };
          const res = await searchPromise;
          if (requestId !== searchRequest || input.value.trim() !== q) { return; }
          const students = res.students || [];
          input._nurseTypeaheadSearchCache = { query: q, students };
          renderDropdown(dropdown, students, formType, input);
        } catch {
          if (requestId === searchRequest) { closeDropdown(dropdown); }
        } finally {
          if (
            searchPromise &&
            input._nurseTypeaheadSearchRequest?.promise === searchPromise
          ) {
            delete input._nurseTypeaheadSearchRequest;
          }
        }
      }, 280);
    });

    // — enter key triggers Load Patient —
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { closeDropdown(dropdown); return; }
      if (e.key === 'Enter') {
        // Let the existing onkeydown handler in HTML fire first
        closeDropdown(dropdown);
      }
    });

    // — close on blur (small delay lets click register) —
    input.addEventListener('blur', () => {
      setTimeout(() => closeDropdown(dropdown), 180);
    });

    // — reopen on focus if query exists —
    input.addEventListener('focus', () => {
      if (input.value.trim().length >= 2 && dropdown.innerHTML &&
          !dropdown.innerHTML.includes('Searching')) {
        dropdown.style.display = 'block';
      }
    });
  });

  // Close all dropdowns when clicking outside
  document.addEventListener('mousedown', (e) => {
    if (!e.target.closest('.search-toolbar')) {
      document.querySelectorAll('.typeahead-dropdown').forEach(d => {
        d.style.display = 'none';
      });
    }
  });
}

function closeDropdown(dropdown) {
  if (dropdown) dropdown.style.display = 'none';
}

function renderDropdown(dropdown, students, formType, input) {
  if (!students.length) {
    dropdown.innerHTML = '<div style="padding:11px 14px;color:#888;font-size:0.8rem">No students found</div>';
    return;
  }

  dropdown.innerHTML = students.slice(0, 8).map(s => `
    <div class="ta-item"
      data-sid="${s.student_id}"
      style="display:flex;align-items:center;gap:10px;padding:9px 14px;
             cursor:pointer;border-bottom:1px solid #f0f5f3;transition:background 0.1s;"
      onmouseover="this.style.background='#f0f8f4'"
      onmouseout="this.style.background='#fff'">
      <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;
                  background:linear-gradient(135deg,#1B4332,#2a7a6f);
                  color:#fff;display:flex;align-items:center;justify-content:center;
                  font-size:0.8rem;font-weight:700;">
        ${(s.first_name || s.last_name || '?')[0].toUpperCase()}
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:0.83rem;color:#111;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          ${s.first_name || ''} ${s.last_name || ''}
        </div>
        <div style="font-size:0.7rem;color:#666;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          ${s.student_id} &bull; ${s.department || s.course_year || ''}
        </div>
      </div>
      <span style="font-size:0.68rem;color:#2a7a6f;font-weight:600;flex-shrink:0;">Select ›</span>
    </div>`).join('');

  // Wire click on each result
  dropdown.querySelectorAll('.ta-item').forEach(item => {
    item.addEventListener('mousedown', async (e) => {
      e.preventDefault(); // prevent blur before click
      const studentId = item.dataset.sid;

      // Close and update input immediately
      closeDropdown(dropdown);
      input.value = studentId;
      input.placeholder = 'Loading...';

      try {
        const res = await apiFetch('get_student_for_form&student_id=' + encodeURIComponent(studentId));
        if (res.student) {
          if (formType === 'vitals') {
            input.value = res.student.student_id;
            const selected = document.getElementById('vitals-selected-student');
            if (selected) {
              selected.textContent = 'Selected: ' +
                [res.student.first_name, res.student.last_name].filter(Boolean).join(' ') +
                ' (' + res.student.student_id + ')';
            }
          } else {
            // The shared form saver looks up the selected student by generated form type.
            setCurrentStudentForForm(formType, res.student);
            autoFillForm(formType, res.student, res.medications || [], res.appointments || []);
          }
          showToast('✓ ' + res.student.first_name + ' ' + res.student.last_name + ' loaded', 'success');
        } else {
          showToast(res.error || 'Student not found', 'error');
        }
      } catch (err) {
        showToast('Error loading student', 'error');
      } finally {
        input.placeholder = 'Search student by ID or name...';
      }
    });
  });
}
