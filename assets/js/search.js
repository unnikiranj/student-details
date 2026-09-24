/* ==================== RECENT SEARCHES MANAGER ==================== */
const RECENT_SEARCHES_KEY = 'student_portal_recent_searches';
const MAX_RECENT_SEARCHES = 6;

function getRecentSearches() {
  try {
    const raw = localStorage.getItem(RECENT_SEARCHES_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    return [];
  }
}

function saveRecentSearch(query, details = {}) {
  if (!query || typeof query !== 'string') return;
  const q = query.trim().toUpperCase();
  if (!q) return;

  let list = getRecentSearches().filter(item => {
    const itemQuery = typeof item === 'string' ? item : item.query;
    return itemQuery.toUpperCase() !== q;
  });

  list.unshift({
    query: q,
    name: details.name || '',
    timestamp: Date.now()
  });

  if (list.length > MAX_RECENT_SEARCHES) {
    list = list.slice(0, MAX_RECENT_SEARCHES);
  }

  try {
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(list));
  } catch (e) {
    console.warn('Could not save recent search:', e);
  }
}

function removeRecentSearch(query) {
  if (!query) return;
  const q = query.trim().toUpperCase();
  let list = getRecentSearches().filter(item => {
    const itemQuery = typeof item === 'string' ? item : item.query;
    return itemQuery.toUpperCase() !== q;
  });
  try {
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(list));
  } catch (e) {}
  renderRecentSearchesDropdown();
}

function clearAllRecentSearches() {
  try {
    localStorage.removeItem(RECENT_SEARCHES_KEY);
  } catch (e) {}
  renderRecentSearchesDropdown();
}

function renderRecentSearchesDropdown() {
  const dropdown = document.getElementById('searchSuggestionsDropdown');
  const listEl = document.getElementById('recentSearchesList');
  if (!dropdown || !listEl) return;

  const items = getRecentSearches();
  if (!items || items.length === 0) {
    dropdown.classList.add('d-none');
    return;
  }

  listEl.innerHTML = items.map(item => {
    const q = typeof item === 'string' ? item : item.query;
    const name = (typeof item === 'object' && item.name) ? item.name : '';
    return `
      <div class="recent-search-item" onclick="handleRecentSearchClick('${escapeHtml(q)}')">
        <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
          <i class="fas fa-history text-muted flex-shrink-0"></i>
          <span class="recent-query font-mono fw-semibold">${escapeHtml(q)}</span>
          ${name ? `<span class="recent-name text-muted text-truncate">(${escapeHtml(name)})</span>` : ''}
        </div>
        <button type="button" class="btn-remove-recent" onclick="event.stopPropagation(); removeRecentSearch('${escapeHtml(q)}');" title="Remove">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
  }).join('');
}

function handleRecentSearchClick(query) {
  const heroInput = document.getElementById('heroSearchInput');
  if (heroInput) heroInput.value = query;
  hideRecentSearchesDropdown();
  performSearch(query);
}

function showRecentSearchesDropdown() {
  const dropdown = document.getElementById('searchSuggestionsDropdown');
  const items = getRecentSearches();
  if (dropdown && items.length > 0) {
    renderRecentSearchesDropdown();
    dropdown.classList.remove('d-none');
  }
}

function hideRecentSearchesDropdown() {
  const dropdown = document.getElementById('searchSuggestionsDropdown');
  if (dropdown) dropdown.classList.add('d-none');
}

/* ==================== STUDENT SELECTION UI (MULTI MATCH) ==================== */
function showStudentSelection(students, searchQuery) {
  const studentInfo = document.getElementById('studentInfo');
  const heroSection = document.getElementById('heroSection');
  const searchResults = document.getElementById('searchResults');

  if (heroSection) heroSection.style.display = 'none';
  if (searchResults) searchResults.style.display = '';

  const studentGridHtml = students.map(student => {
    const roll = escapeHtml(student.roll_no || '');
    const name = escapeHtml(student.student_name || 'Unknown');
    const photo = student.student_photo ? escapeHtml(student.student_photo) : null;
    const initial = name.charAt(0).toUpperCase();
    const dept = student.department ? escapeHtml(student.department) : (roll.length >= 8 ? roll.substring(6, 8) : '');

    return `
      <div class="student-selection-card" onclick="selectStudent('${roll}')">
        <div class="student-selection-avatar">
          ${photo
            ? `<img src="${photo}" alt="${name}" class="student-selection-photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
               <div class="student-selection-fallback">${initial}</div>`
            : `<div class="student-selection-fallback">${initial}</div>`
          }
        </div>
        <div class="student-selection-details">
          <div class="student-selection-name">${name}</div>
          <div class="d-flex align-items-center gap-2">
            <span class="student-selection-roll font-mono">${roll}</span>
            ${dept ? `<span class="badge bg-light text-dark border">${dept}</span>` : ''}
          </div>
        </div>
        <div class="student-selection-action">
          <button class="btn btn-primary btn-sm rounded-pill px-3">
            <span>View</span> <i class="fas fa-arrow-right ms-1"></i>
          </button>
        </div>
      </div>
    `;
  }).join('');

  if (studentInfo) {
    studentInfo.innerHTML = `
      <div class="card data-card shadow-sm border-0 mb-4">
        <div class="card-header bg-surface d-flex justify-content-between align-items-center py-3">
          <div class="d-flex align-items-center gap-2">
            <div class="badge-icon-wrap bg-primary-subtle text-primary p-2 rounded-circle">
              <i class="fas fa-users"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold">Multiple Matches Found</h5>
              <small class="text-muted">Select a student from the list below to view full details</small>
            </div>
          </div>
          <button class="btn btn-outline-secondary btn-sm" onclick="resetToSearchHome()">
            <i class="fas fa-search me-1"></i> New Search
          </button>
        </div>
        <div class="card-body">
          <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
            <i class="fas fa-circle-info text-primary flex-shrink-0"></i>
            <div>Found <strong>${students.length}</strong> students matching "<strong>${escapeHtml(searchQuery)}</strong>".</div>
          </div>
          <div class="student-selection-grid">
            ${studentGridHtml}
          </div>
        </div>
      </div>
    `;
  }
}

function selectStudent(roll) {
  performSearch(roll);
}

function resetToSearchHome() {
  const heroSection = document.getElementById('heroSection');
  const searchResults = document.getElementById('searchResults');
  const studentInfo = document.getElementById('studentInfo');
  const heroInput = document.getElementById('heroSearchInput');

  if (searchResults) searchResults.style.display = 'none';
  if (heroSection) heroSection.style.display = '';
  if (studentInfo) studentInfo.innerHTML = '';
  if (heroInput) {
    heroInput.focus();
    heroInput.select();
  }
  
  // Clean URL query
  if (window.history && window.history.pushState) {
    const url = window.location.pathname;
    window.history.pushState({}, '', url);
  }
}

/* ==================== SKELETON LOADING STATE ==================== */
function renderSkeletonLoading(targetSelector = '#studentInfo') {
  const target = document.querySelector(targetSelector);
  if (!target) return;
  target.innerHTML = `
    <div class="student-skeleton-wrap mb-4">
      <div class="skeleton-header-card p-4 rounded-4 mb-3">
        <div class="d-flex align-items-center gap-3">
          <div class="skeleton skeleton-avatar"></div>
          <div class="flex-grow-1">
            <div class="skeleton skeleton-text mb-2" style="width:35%; height:24px;"></div>
            <div class="skeleton skeleton-text mb-2" style="width:20%; height:16px;"></div>
            <div class="skeleton skeleton-text" style="width:50%; height:14px;"></div>
          </div>
        </div>
        <div class="row g-2 mt-3">
          <div class="col-6 col-md-3"><div class="skeleton skeleton-metric"></div></div>
          <div class="col-6 col-md-3"><div class="skeleton skeleton-metric"></div></div>
          <div class="col-6 col-md-3"><div class="skeleton skeleton-metric"></div></div>
          <div class="col-6 col-md-3"><div class="skeleton skeleton-metric"></div></div>
        </div>
      </div>
      <div class="skeleton skeleton-card mb-3" style="height: 220px; border-radius: 16px;"></div>
      <div class="skeleton skeleton-card mb-3" style="height: 300px; border-radius: 16px;"></div>
    </div>
  `;
}

/* ==================== SEARCH INITIALIZATION ==================== */
document.addEventListener('DOMContentLoaded', () => {
  const navbarForm = document.getElementById('navbarSearchForm');
  const heroForm = document.getElementById('heroSearchForm');
  const heroInput = document.getElementById('heroSearchInput');
  const clearBtn = document.getElementById('searchClearBtn');
  const clearHistoryBtn = document.getElementById('clearSearchHistoryBtn');

  // Input typing handlers for clear button & uppercase formatting
  if (heroInput) {
    heroInput.addEventListener('input', () => {
      const val = heroInput.value.trim();
      if (clearBtn) {
        clearBtn.classList.toggle('d-none', !val);
      }
      if (!val) {
        showRecentSearchesDropdown();
      }
    });

    heroInput.addEventListener('focus', () => {
      if (!heroInput.value.trim()) {
        showRecentSearchesDropdown();
      }
    });

    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        heroInput.value = '';
        clearBtn.classList.add('d-none');
        heroInput.focus();
        showRecentSearchesDropdown();
      });
    }
  }

  // Clear history button
  if (clearHistoryBtn) {
    clearHistoryBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      clearAllRecentSearches();
    });
  }

  // Close dropdown on outside click
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#heroSearchForm')) {
      hideRecentSearchesDropdown();
    }
  });

  // Global Keyboard Shortcuts
  document.addEventListener('keydown', (e) => {
    // Focus search on '/' or 'Ctrl+K' / 'Cmd+K'
    if ((e.key === '/' && document.activeElement !== heroInput && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') ||
        ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')) {
      e.preventDefault();
      if (heroInput) {
        heroInput.focus();
        heroInput.select();
        showRecentSearchesDropdown();
      }
    }
    // Escape to close dropdown or blur
    if (e.key === 'Escape') {
      hideRecentSearchesDropdown();
      if (document.activeElement === heroInput) {
        heroInput.blur();
      }
    }
  });

  // Quick search sample chips
  const quickChipsWrap = document.getElementById('quickSearchChips');
  if (quickChipsWrap) {
    quickChipsWrap.addEventListener('click', (e) => {
      const btn = e.target.closest('.quick-chip[data-query]');
      if (btn) {
        e.preventDefault();
        const query = btn.getAttribute('data-query');
        if (query) {
          if (heroInput) heroInput.value = query;
          performSearch(query);
        }
      }
    });
  }

  // Form Submissions
  if (navbarForm) {
    navbarForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const roll = document.getElementById('navbarSearch')?.value?.trim();
      if (roll) performSearch(roll);
    });
  }

  if (heroForm) {
    heroForm.addEventListener('submit', (e) => {
      e.preventDefault();
      hideRecentSearchesDropdown();
      const roll = heroInput?.value?.trim();
      if (roll) performSearch(roll);
    });
  }

  // Auto Search from URL Query Parameter (?roll=... or ?q=...)
  const urlParams = new URLSearchParams(window.location.search);
  const initialRoll = urlParams.get('roll') || urlParams.get('q') || urlParams.get('roll_no');
  if (initialRoll) {
    if (heroInput) heroInput.value = initialRoll;
    performSearch(initialRoll, false); // false = don't push state again
  }
});

/* ==================== PERFORM SEARCH ==================== */
async function performSearch(roll, updateUrl = true) {
  try {
    if (!roll) {
      alert("Please enter a roll number or student name");
      return;
    }

    const rollUpper = validateRollNumber(roll);
    if (!rollUpper) {
      alert("Please enter a valid roll number or name");
      return;
    }

    const heroSection = document.getElementById('heroSection');
    const searchResults = document.getElementById('searchResults');
    const studentInfo = document.getElementById('studentInfo');
    const heroInput = document.getElementById('heroSearchInput');
    const navbarInput = document.getElementById('navbarSearch');

    // Synchronize inputs
    if (heroInput && heroInput.value !== rollUpper) heroInput.value = rollUpper;
    if (navbarInput && navbarInput.value !== rollUpper) navbarInput.value = rollUpper;

    // Render skeleton loading state
    renderSkeletonLoading();

    if (heroSection) heroSection.style.display = 'none';
    if (searchResults) searchResults.style.display = '';

    const searchResultsEl = document.getElementById('searchResults');
    if (searchResultsEl) {
      try {
        searchResultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } catch (e) {
        window.scrollTo(0, 0);
      }
    }

    const body = new URLSearchParams();
    body.set('roll_number', rollUpper);

    const res = await fetchAPI('public_search.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    if (res.multiple_matches && res.students) {
      // Save search in recent
      saveRecentSearch(rollUpper);
      showStudentSelection(res.students, res.search.query);
      return;
    }

    if (res.success && typeof window.displayStudentInfo === 'function') {
      window.currentActiveRollNumber = rollUpper;
      const studentName = res.data?.student_info?.student_name || '';
      saveRecentSearch(rollUpper, { name: studentName });

      // Update URL query parameter
      if (updateUrl && window.history && window.history.pushState) {
        const newUrl = `${window.location.pathname}?roll=${encodeURIComponent(rollUpper)}`;
        window.history.pushState({ roll: rollUpper }, '', newUrl);
      }

      window.displayStudentInfo(res.data);
    } else {
      if (heroSection) heroSection.style.display = '';
      if (searchResults) searchResults.style.display = 'none';
      if (studentInfo) {
        studentInfo.innerHTML = `
          <div class="alert alert-warning text-center shadow-sm p-4 rounded-4">
            <i class="fas fa-triangle-exclamation fa-2x text-warning mb-2"></i>
            <h5 class="fw-bold">No Records Found</h5>
            <p class="text-muted mb-3">We couldn't find any student details matching "<strong>${escapeHtml(roll)}</strong>".</p>
            <div class="d-flex justify-content-center gap-2">
              <button class="btn btn-primary btn-sm px-3" onclick="resetToSearchHome()">
                <i class="fas fa-arrow-left me-1"></i> Try Another Search
              </button>
            </div>
          </div>
        `;
      }
    }
  } catch (error) {
    const studentInfo = document.getElementById('studentInfo');
    const heroSection = document.getElementById('heroSection');
    const searchResults = document.getElementById('searchResults');

    let msg = 'Error occurred while searching. Please try again.';
    if (error.message && error.message.includes('timed out')) msg = 'Search timed out. Please check your connection and try again.';

    if (studentInfo) {
      studentInfo.innerHTML = `
        <div class="alert alert-danger text-center shadow-sm p-4 rounded-4">
          <i class="fas fa-circle-exclamation fa-2x text-danger mb-2"></i>
          <h5 class="fw-bold">Search Failed</h5>
          <p class="text-muted mb-3">${msg}</p>
          <button class="btn btn-outline-danger btn-sm px-3" onclick="resetToSearchHome()">
            <i class="fas fa-rotate-left me-1"></i> Back to Search
          </button>
        </div>
      `;
    }
    if (heroSection) heroSection.style.display = '';
    if (searchResults) searchResults.style.display = 'none';
  }
}

/* ==================== OFFCANVAS ==================== */
async function openStudentOffcanvas(roll) {
  try {
    const el = document.getElementById('studentOffcanvas');
    if (!el || !window.bootstrap || !bootstrap.Offcanvas) {
      performSearch(roll);
      return;
    }

    const off = bootstrap.Offcanvas.getOrCreateInstance(el);
    off.show();

    const rollUpper = validateRollNumber(roll);
    if (!rollUpper) {
      document.getElementById('studentOffcanvasContent').innerHTML = `<div class="alert alert-danger text-center mb-0">Invalid roll number</div>`;
      return;
    }

    const content = document.getElementById('studentOffcanvasContent');
    if (content) {
      content.innerHTML = `<div class="loading text-center py-5"><div class="spinner-border text-primary"></div><h6 class="mt-3 mb-0">Loading Student Data…</h6></div>`;
    }

    const body = new URLSearchParams();
    body.set('roll_number', rollUpper);

    const res = await fetchAPI('public_search.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    if (res && res.success && typeof window.displayStudentInfo === 'function') {
      window.displayStudentInfo(res.data, '#studentOffcanvasContent');
    } else {
      if (content) {
        content.innerHTML = `<div class="alert alert-warning text-center mb-0">No data found for <b>${rollUpper}</b></div>`;
      }
    }
  } catch (error) {
    const content = document.getElementById('studentOffcanvasContent');
    if (content) {
      content.innerHTML = `<div class="alert alert-danger text-center mb-0">Error occurred.<br><small>${error.message}</small></div>`;
    }
  }
}

/* ==================== PDF GENERATION ==================== */
async function generateStudentPDF(rollNo) {
  const btn = document.getElementById('generatePdfBtn');
  if (!btn) {
    alert('PDF button not found. Please search again.');
    return;
  }

  try {
    const originalText = btn.innerHTML;
    setButtonLoading(btn, true);

    const container = document.getElementById('studentInfo');
    if (!container) {
      alert('Results container not found.');
      setButtonLoading(btn, false, originalText);
      return;
    }

    const clone = container.cloneNode(true);
    const btnWrap = clone.querySelector('#generatePdfBtn')?.closest('div');
    if (btnWrap) btnWrap.remove();
    const srCard = clone.querySelector('.sr-card');
    if (srCard) srCard.remove();

    const htmlToExport = clone.innerHTML;
    const body = new URLSearchParams();
    body.set('roll_no', rollNo);
    body.set('html', htmlToExport);

    const blob = await fetchAPI('generate_pdf.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body
    });

    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = `student_report_${rollNo}_${new Date().toISOString().split('T')[0]}.pdf`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);

    setButtonLoading(btn, false, originalText);
  } catch (error) {
    alert(`Failed to generate PDF: ${error.message}`);
    setButtonLoading(btn, false);
  }
}

/* ==================== SHARE PROFILE ==================== */
function shareStudentProfile() {
  const shareBtn = document.getElementById('shareProfileBtn');
  const currentUrl = window.location.href;
  
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(currentUrl).then(() => {
      if (shareBtn) {
        const orig = shareBtn.innerHTML;
        shareBtn.innerHTML = `<i class="fas fa-check text-success me-1"></i> <span class="d-none d-md-inline text-success">Copied Link!</span>`;
        setTimeout(() => {
          shareBtn.innerHTML = orig;
        }, 2500);
      }
    }).catch(() => {
      prompt('Copy this student profile link:', currentUrl);
    });
  } else {
    prompt('Copy this student profile link:', currentUrl);
  }
}

/* ==================== FULL REPORT DOWNLOAD & PRINT ==================== */
function downloadStudentReportPdf(customRoll = null) {
  const roll = customRoll || window.currentActiveRollNumber || document.getElementById('heroSearchInput')?.value?.trim();
  if (!roll) {
    alert('Please search for a student first before downloading the report.');
    return;
  }
  
  const btn = document.getElementById('downloadFullPdfBtn');
  if (btn) {
    const orig = btn.innerHTML;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Generating PDF…`;
    btn.disabled = true;
    setTimeout(() => {
      btn.innerHTML = orig;
      btn.disabled = false;
    }, 4000);
  }

  window.location.href = `api/download_pdf.php?roll=${encodeURIComponent(roll)}`;
}

function printFullStudentReport() {
  // 1. Expand all collapsible accordions in studentInfo
  const collapses = document.querySelectorAll('#studentInfo .collapse');
  collapses.forEach(el => {
    el.classList.add('show');
  });

  // 2. Unhide normal tables
  const hiddenTables = document.querySelectorAll('#studentInfo [id^="normal-table-"]');
  hiddenTables.forEach(tbl => {
    tbl.style.display = 'block';
  });

  // 3. Short timeout to allow browser layout calculation then trigger print
  setTimeout(() => {
    window.print();
  }, 150);
}

// Make functions globally available
window.performSearch = performSearch;
window.selectStudent = selectStudent;
window.resetToSearchHome = resetToSearchHome;
window.openStudentOffcanvas = openStudentOffcanvas;
window.generateStudentPDF = generateStudentPDF;
window.downloadStudentReportPdf = downloadStudentReportPdf;
window.printFullStudentReport = printFullStudentReport;
window.shareStudentProfile = shareStudentProfile;
window.removeRecentSearch = removeRecentSearch;
window.clearAllRecentSearches = clearAllRecentSearches;
window.handleRecentSearchClick = handleRecentSearchClick;

