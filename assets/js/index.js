/* ==================== LOGIN ==================== */

// Export displayStudentInfo for use in other modules

function showLogin() {
  const loginModalEl = document.getElementById('loginModal');
  if (loginModalEl) {
    try {
      const loginModal = new bootstrap.Modal(loginModalEl);
      loginModal.show();
    } catch (e) {
      // Fallback to jQuery if Bootstrap modal fails
      if (typeof $ !== 'undefined') {
        $('#loginModal').modal('show');
      }
    }
  }
}

async function handleLogin() {
  try {
    const email = document.getElementById('email')?.value;
    const password = document.getElementById('password')?.value;

    if (!email || !password) {
      const errorMsg = document.getElementById('errorMessage');
      if (errorMsg) {
        errorMsg.classList.remove('d-none');
        errorMsg.textContent = 'Please enter email and password';
      }
      return;
    }

    const body = new URLSearchParams();
    body.set('email', email);
    body.set('password', password);

    const res = await fetch('login_process.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    const data = await res.json();

    if (data.success) {
      window.location = data.redirect || 'dashboard.php';
    } else {
      const errorMsg = document.getElementById('errorMessage');
      if (errorMsg) {
        errorMsg.classList.remove('d-none');
        errorMsg.textContent = data.message || 'Login failed';
      }
    }
  } catch (error) {
    const errorMsg = document.getElementById('errorMessage');
    if (errorMsg) {
      errorMsg.classList.remove('d-none');
      errorMsg.textContent = 'Login request failed. Please try again.';
    }
  }
}

// Make functions globally available immediately (not in DOMContentLoaded)
window.showLogin = showLogin;
window.displayStudentInfo = displayStudentInfo;
window.performSearch = performSearch;
window.openStudentOffcanvas = openStudentOffcanvas;
window.generateStudentPDF = generateStudentPDF;
window.toggleTranspose = toggleTranspose;
window.srSwitchMonth = srSwitchMonth;
window.mobileOpenSearch = mobileOpenSearch;
window.mobileCloseSearch = mobileCloseSearch;
window.mobileGoHome = mobileGoHome;
window.selectStudent = selectStudent;

document.addEventListener('DOMContentLoaded', () => {

  // Initialize login form
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      e.preventDefault();
      handleLogin();
    });
  }

  // Load today's birthdays
  loadTodayBirthdays();

  // Initialize attendance animations
  if (typeof animateAttendanceVisuals === 'function') {
    animateAttendanceVisuals();
  }
});

/* ==================== LOAD TODAY'S BIRTHDAYS ==================== */
async function loadTodayBirthdays() {
  try {
    const res = await fetch('today_birthdays.php');
    const data = await res.json();

    if (data.success && data.count > 0) {
      const section = document.getElementById('todayBirthdaysSection');
      const list = document.getElementById('birthdaysList');
      const dateBadge = document.getElementById('birthdayDate');

      if (section && list && dateBadge) {
        dateBadge.textContent = data.date;

        list.innerHTML = data.data.map(student => `
          <div class="birthday-badge" style="
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
            border: 1px solid #e91e63;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
          ">
            <span style="font-size: 16px;">🎂</span>
            <div>
              <div style="font-weight: 600; color: #c2185b;">${student.student_name}</div>
              <div style="font-size: 11px; color: #880e4f;">${student.roll_no}</div>
            </div>
          </div>
        `).join('');

        section.style.display = 'block';
      }
    }
  } catch (error) {
    console.error('Error loading birthdays:', error);
  }
}

/* ==================== DISPLAY STUDENT ==================== */
function displayStudentInfo(data, targetSelector = '#studentInfo') {
  try {
    const targetEl = document.querySelector(targetSelector);
    if (!targetEl) return;


    /* ---- ATTENDANCE CARD ---- */
    const renderAttendanceCard = (group, groupKey, midmarksByCode, subjectGradesByCode, subjectCreditsByCode, studentResultsBySemester, subjectNameMap = {}) => {
      const allRecords = group.allRecords || [];
      const attendancePivotKey = `${groupKey}-attendance-pivot`;
      let sessionGroups = {};
      allRecords.forEach((record) => {
        const src = record && record.source_table ? String(record.source_table).toLowerCase() : '';
        const isSummary = src === 'attendance_summary'; const isSubjects = src === 'attendance_subjects';
        if (!isSummary && !isSubjects) return;
        const session = (record && record.session_name) ? String(record.session_name).trim() : 'Unknown Session';
        if (!sessionGroups[session]) {
          sessionGroups[session] = {
            summary: [],
            subjects: [],
            semester: record.semester || null,
            academic_year: record.academic_year || null
          };
        }
        if (isSubjects) sessionGroups[session].subjects.push(record);
        else sessionGroups[session].summary.push(record);
      });
      const percentageToClass = (p) => { const n = Number(p); if (!Number.isFinite(n)) return ''; if (n >= 75) return 'text-success'; if (n >= 65) return 'text-warning'; return 'text-danger'; };
      const attendancePrediction = (pctStr) => { const n = Number(pctStr); if (!Number.isFinite(n)) return { pct: null, label: '-', cls: 'secondary' }; if (n >= 75) return { pct: n, label: 'Safe', cls: 'success' }; if (n >= 65) return { pct: n, label: 'Condonation', cls: 'warning' }; return { pct: n, label: 'Detention', cls: 'danger' }; };
      const attendancePredictionCell = (p) => {
        if (!p || p.label === '-') return `<div class="d-flex align-items-center gap-2"><span class="mm-pie" data-value="0"></span><span class="badge bg-secondary">-</span></div>`;
        const pct = Math.max(0, Math.min(100, Number(p.pct)));
        const pieCls = (p.cls === 'warning') ? 'warning' : (p.cls || 'secondary');
        const badge = (p.cls === 'warning') ? `<span class="badge bg-warning text-dark">${p.label}</span>` : `<span class="badge bg-${p.cls}">${p.label}</span>`;
        return `<div class="d-flex align-items-center gap-2"><span class="mm-pie ${pieCls}" data-value="${pct}" title="${pct.toFixed(2)}%"></span>${badge}</div>`;
      };
      const midToNum = (v) => { if (v === null || v === undefined) return null; const s = String(v).trim(); if (s === '') return null; const n = Number(s); return Number.isFinite(n) ? n : null; };
      const midPrediction = (totalOutOf30) => { const tRaw = midToNum(totalOutOf30); if (tRaw === null) return { pct: null, label: '-', cls: 'secondary' }; const t = Math.max(0, Math.min(30, tRaw)); const pct = Math.round((t / 30) * 100); if (t >= 27) return { pct, label: 'S', cls: 'success' }; if (t >= 24) return { pct, label: 'A', cls: 'success' }; if (t >= 21) return { pct, label: 'B', cls: 'warning' }; if (t >= 18) return { pct, label: 'C', cls: 'warning' }; if (t >= 15) return { pct, label: 'D', cls: 'orange' }; if (t >= 12) return { pct, label: 'E', cls: 'orange' }; return { pct, label: 'F', cls: 'danger' }; };
      const midBadge = (p) => { if (!p || p.label === '-') return '<span class="badge bg-secondary">-</span>'; if (p.cls === 'orange') return `<span class="badge" style="background:var(--orange)">${p.label}</span>`; if (p.cls === 'warning') return `<span class="badge bg-warning text-dark">${p.label}</span>`; return `<span class="badge bg-${p.cls}">${p.label}</span>`; };
      const toPct = (present, total, fallback) => {
        if (fallback !== undefined && fallback !== null && String(fallback).trim() !== '') return String(fallback).trim();
        const p = Number(present), t = Number(total);
        if (Number.isFinite(p) && Number.isFinite(t) && t > 0) return ((p / t) * 100).toFixed(2);
        return '';
      };
      let sessions = Object.keys(sessionGroups);
      const sessionToKey = (s) => {
        const str = String(s || '').trim().toLowerCase();
        const monthMap = { jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6, jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12 };
        const monthMatches = [...str.matchAll(/jan|feb|mar|apr|may|jun|jul|aug|sept|sep|oct|nov|dec/g)];
        const mon = monthMatches.length ? monthMatches[monthMatches.length - 1][0].replace('sept', 'sep') : null;
        const m = mon ? (monthMap[mon] || 0) : 0; if (m === 0) return null;
        const fullYears = [...str.matchAll(/20\d{2}/g)].map(match => Number(match[0]));
        const shortYear = str.match(/(?:^|[_-])(\d{2})$/);
        const year = fullYears.length ? fullYears[fullYears.length - 1] : (shortYear ? 2000 + Number(shortYear[1]) : null);
        if (!Number.isFinite(year) || year < 2000) return null;
        return (year * 100) + m;
      };
      const attendanceMonthLabel = (session) => {
        const value = String(session || '').trim();
        const encode = text => String(text ?? '').replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
        const names = { jan: 'January', feb: 'February', mar: 'March', apr: 'April', may: 'May', jun: 'June', jul: 'July', aug: 'August', sep: 'September', sept: 'September', oct: 'October', nov: 'November', dec: 'December' };
        const tokens = value.toLowerCase().split(/[^a-z0-9]+/).filter(Boolean);
        const monthCodes = [...value.toLowerCase().matchAll(/jan|feb|mar|apr|may|jun|jul|aug|sept|sep|oct|nov|dec/g)].map(match => match[0]);
        const months = monthCodes.map(code => names[code]);
        const yearMatch = value.match(/(20\d{2}|(?:^|[_-])(\d{2})(?:$|[_-]))/);
        const year = yearMatch ? (yearMatch[1].length === 4 ? yearMatch[1] : `20${yearMatch[2]}`) : '';
        if (!months.length) return encode(value || '-');
        const monthText = months.length > 1 ? `${months[0]}–${months[months.length - 1]}` : months[0];
        return encode(year ? `${monthText} ${year}` : monthText);
      };
      const sessionToAcademicYearLabel = (s) => {
        const str = String(s || '').trim().toLowerCase();
        const yr = str.match(/(20\d{2})\s*[_-]\s*(\d{2}|20\d{2})/);
        if (yr) { const y1 = parseInt(yr[1], 10); const y2raw = yr[2]; const y2 = (String(y2raw).length === 2) ? (2000 + parseInt(y2raw, 10)) : parseInt(y2raw, 10); if (Number.isFinite(y1) && Number.isFinite(y2)) return `${y1}-${String(y2).slice(-2)}`; }
        const ySingle = str.match(/(20\d{2})/);
        if (ySingle) { const y1 = parseInt(ySingle[1], 10); if (Number.isFinite(y1)) return `${y1}-${String(y1 + 1).slice(-2)}`; }
        return 'Unknown';
      };
      sessions.sort((a, b) => { const ak = sessionToKey(a), bk = sessionToKey(b); if (ak !== null && bk !== null) return bk - ak; if (ak !== null && bk === null) return -1; if (ak === null && bk !== null) return 1; return String(b).localeCompare(String(a)); });
      const subjectMap = new Map();
      sessions.forEach((session) => { (sessionGroups[session].subjects || []).forEach((r) => { const code = (r.subject_code !== undefined && r.subject_code !== null) ? String(r.subject_code).trim() : ''; if (!code) return; const name = subjectNameMap[code] || ((r.subject_name !== undefined && r.subject_name !== null) ? String(r.subject_name).trim() : ''); if (!subjectMap.has(code)) subjectMap.set(code, name); if (subjectMap.has(code) && !subjectMap.get(code) && name) subjectMap.set(code, name); }); });
      const subjects = Array.from(subjectMap.keys()).sort((a, b) => a.localeCompare(b));

      let cardHtml = `<div class="card data-card mb-3 attendance-card result-dashboard-card" id="attendance-${String(groupKey).replace(/[^a-zA-Z0-9_-]/g, '_')}"><div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #000 !important; border: 1px solid #000; border-bottom: 2px solid #000; font-size: 12px; padding: 12px 16px; font-weight: 600;"><div class="d-flex align-items-center gap-2" style="color: #000 !important;"><i class="fas fa-calendar-check" style="color: #000 !important;"></i><span class="fw-bold" style="color: #000 !important;">ATTENDANCE</span></div><div class="result-card-meta" style="color: #000 !important;">${sessions.length} month(s)</div></div><div class="card-body"><div class="status-legend mb-3"><span class="attendance-badge safe"><i class="fas fa-check-circle"></i> Safe >= 75%</span><span class="attendance-badge condonation"><i class="fas fa-exclamation-triangle"></i> Condonation 65-75%</span><span class="attendance-badge detention"><i class="fas fa-times-circle"></i> Detention < 65%</span></div>`;
      if (!sessions.length) { cardHtml += `<div class="text-muted">No attendance records found.</div></div></div>`; return cardHtml; }

      const overallRowsAll = [];
      let overallTotalClassesSum = 0, highestPctNum = null, highestPctLabel = '', highestPctPresent = '', highestPctTotal = '';
      sessions.forEach((session) => {
        const summaryRows = sessionGroups[session].summary || [];
        if (!summaryRows.length) { overallRowsAll.push(`<tr><td>${session}</td><td>-</td><td>-</td><td>-</td><td>${attendancePredictionCell({ pct: null, label: '-', cls: 'secondary' })}</td></tr>`); return; }
        summaryRows.forEach((r, idx) => {
          const pct = toPct(r.total_present, r.total_classes, r.percentage);
          const pctClass = percentageToClass(pct);
          const pred = attendancePrediction(pct);
          const label = (idx === 0 ? session : `${session} (${idx + 1})`);
          const yearLabel = sessionToAcademicYearLabel(session);
          const tc = Number(r.total_classes); if (Number.isFinite(tc)) overallTotalClassesSum += tc;
          const pctNum = Number(pct);
          if (Number.isFinite(pctNum)) { if (highestPctNum === null || pctNum > highestPctNum) { highestPctNum = pctNum; highestPctLabel = label; highestPctPresent = (r.total_present !== undefined && r.total_present !== null && String(r.total_present).trim() !== '') ? String(r.total_present).trim() : ''; highestPctTotal = (r.total_classes !== undefined && r.total_classes !== null && String(r.total_classes).trim() !== '') ? String(r.total_classes).trim() : ''; } }
          overallRowsAll.push(`<tr data-year="${yearLabel}"><td>${label}</td><td>${(r.total_present !== undefined && r.total_present !== null && String(r.total_present).trim() !== '') ? r.total_present : '-'}</td><td>${(r.total_classes !== undefined && r.total_classes !== null && String(r.total_classes).trim() !== '') ? r.total_classes : '-'}</td><td>${pct ? `<span class="fw-bold ${pctClass}">${pct}%</span>` : '-'}</td></tr>`);
        });
      });

      const attTabKey = `${groupKey}-overall-att`;
      const attendanceAnchorId = `attendance-${String(groupKey).replace(/[^a-zA-Z0-9_-]/g, '_')}`;

      /* Subject-wise pivot - Group by semester */
      const bySession = {};
      sessions.forEach((session) => { const subjRows = sessionGroups[session].subjects || []; const byCode = {}; subjRows.forEach((r) => { const code = (r.subject_code !== undefined && r.subject_code !== null) ? String(r.subject_code).trim() : ''; if (!code) return; byCode[code] = r; }); bySession[session] = byCode; });

      /* Group sessions by semester */
      const sessionsBySemester = {};
      sessions.forEach((session) => {
        const group = sessionGroups[session];
        if (!group) return;

        // Use semester from group structure (set by groupAttendanceData)
        const semester = group.semester ? String(group.semester).trim() : 'Unknown';
        const academicYear = group.academic_year ? String(group.academic_year).trim() : '';

        const key = semester;
        if (!sessionsBySemester[key]) { sessionsBySemester[key] = { semester, academicYear, sessions: [] }; }
        sessionsBySemester[key].sessions.push(session);
      });

      cardHtml += `<div class="mb-2"><div class="d-flex justify-content-between align-items-center mb-2"><div class="section-kicker mb-0"><i class="fas fa-book"></i><span>Subject-wise Attendance</span></div></div>`;

      /* Render separate table for each semester */
      const semesterKeys = Object.keys(sessionsBySemester).sort().reverse();
      semesterKeys.forEach((semKey, semIdx) => {
        const semData = sessionsBySemester[semKey];
        const semSessions = semData.sessions;
        const semLabel = semData.semester !== 'Unknown' ? `Semester ${semData.semester}` : 'Unknown Semester';
        const ayLabel = semData.academicYear ? `${semData.academicYear}` : '';
        const semPivotKey = `${attendancePivotKey}-sem-${semIdx}`;

        /* Filter subjects that have actual attendance data in this semester */
        const activeSubjectsForSemester = subjects.filter(code => {
          // Check if this subject has any non-empty attendance data across all sessions in this semester
          return semSessions.some(session => {
            const r = (bySession[session] || {})[code];
            if (!r) return false;
            const present = (r.present !== undefined && r.present !== null && String(r.present).trim() !== '') ? r.present : null;
            const total = (r.total !== undefined && r.total !== null && String(r.total).trim() !== '') ? r.total : null;
            return present !== null && total !== null;
          });
        });

        /* If no active subjects for this semester, skip rendering */
        if (activeSubjectsForSemester.length === 0) return;

        /* Get semester results data using semester_info and exam_info */
        /* Match attendance semester/academic_year with student_results */
        const attendanceSem = semData.semester || 'Unknown';
        const attendanceAY = semData.academicYear || '';

        /* Create mapping from attendance semester format to student_results semester_info format */
        const semMapping = {
          '1-1': 'I Yr I Sem',
          '1-2': 'I Yr II Sem',
          '2-1': 'II Yr I Sem',
          '2-2': 'II Yr II Sem',
          '3-1': 'III Yr I Sem',
          '3-2': 'III Yr II Sem',
          '4-1': 'IV Yr I Sem',
          '4-2': 'IV Yr II Sem'
        };

        /* Try to find matching student_results based on semester pattern */
        let matchedResults = null;
        const expectedSemInfo = semMapping[attendanceSem] || attendanceSem;

        Object.keys(studentResultsBySemester).forEach(key => {
          const sr = studentResultsBySemester[key];
          const srSemInfo = String(sr.semesterInfo || '').toLowerCase();
          const expectedLower = String(expectedSemInfo).toLowerCase();

          /* Check if semester_info matches the expected pattern */
          if (srSemInfo.includes(expectedLower) || expectedLower.includes(srSemInfo)) {
            matchedResults = sr;
          }
        });

        const semResults = matchedResults || { cgpa: null, sgpa: null, scr: null, tcr: null, backlog: 0 };
        const cgpaVal = semResults.cgpa !== null ? Number(semResults.cgpa) : null;
        const sgpaVal = semResults.sgpa !== null ? Number(semResults.sgpa) : null;
        const parseFraction = (v) => {
          if (v === null || v === undefined) return null;
          const s = String(v).trim();
          if (s === '') return null;
          if (s.includes('/')) {
            const parts = s.split('/');
            if (parts.length === 2) {
              const num = Number(parts[0].trim());
              const den = Number(parts[1].trim());
              if (Number.isFinite(num)) return num;
            }
          }
          const n = Number(s);
          return Number.isFinite(n) ? n : null;
        };
        const scrVal = parseFraction(semResults.scr);
        const tcrVal = parseFraction(semResults.tcr);
        const scrDisplay = semResults.scr !== null && semResults.scr !== undefined ? String(semResults.scr).trim() : null;
        const tcrDisplay = semResults.tcr !== null && semResults.tcr !== undefined ? String(semResults.tcr).trim() : null;
        const backlogVal = semResults.backlog || 0;

        cardHtml += `<div class="semester-attendance-block subject-attendance-panel mb-4"><div class="semester-block-header subject-attendance-toolbar"><div><h6>${semLabel}</h6>${ayLabel ? `<small>${ayLabel}</small>` : ''}</div><div class="d-flex gap-2"><button class="btn btn-sm btn-outline-secondary" onclick="copySemesterTable('${semPivotKey}', '${semLabel}', '${ayLabel}')"><i class="fas fa-copy me-1"></i>Copy</button><button class="btn btn-sm btn-outline-secondary" onclick="toggleTranspose('${semPivotKey}')"><i class="fas fa-exchange-alt me-1"></i>Transpose</button></div></div><div class="attendance-pivot-wrap subject-attendance-table-shell"><div class="table-responsive"><div id="normal-table-${semPivotKey}" style="display:none"><table class="table table-sm table-hover attendance-pivot table-minimal subject-attendance-table"><thead><tr><th class="sticky-col">Subject</th>${semSessions.map(s => `<th>${attendanceMonthLabel(s)}</th>`).join('')}</tr></thead><tbody>`;
        // Sort subjects: by credits in descending order, then alphabetically
        const sortedSubjects = [...activeSubjectsForSemester].sort((a, b) => {
          const aCredits = subjectCreditsByCode && subjectCreditsByCode[a] !== null && subjectCreditsByCode[a] !== undefined ? Number(subjectCreditsByCode[a]) : 0;
          const bCredits = subjectCreditsByCode && subjectCreditsByCode[b] !== null && subjectCreditsByCode[b] !== undefined ? Number(subjectCreditsByCode[b]) : 0;
          // Sort by credits descending
          if (bCredits !== aCredits) return bCredits - aCredits;
          // Then alphabetically
          return a.localeCompare(b);
        });
        sortedSubjects.forEach((code) => {
          const name = subjectMap.get(code) || ''; const title = name ? `${code} - ${name}` : code;
          const credits = subjectCreditsByCode && subjectCreditsByCode[code] ? subjectCreditsByCode[code] : null;
          const creditsBadge = credits !== null ? `<div class="credits-badge">${credits} CR</div>` : '';
          const mm = midmarksByCode && midmarksByCode[code] ? midmarksByCode[code] : null;
          const mmTotalRaw = mm ? ((mm.total_marks !== null && mm.total_marks !== undefined && String(mm.total_marks).trim() !== '') ? mm.total_marks : ((mm.marks !== null && mm.marks !== undefined && String(mm.marks).trim() !== '') ? mm.marks : null)) : null;
          const mmPred = midPrediction(mmTotalRaw);
          const mmTotalTxt = (mmTotalRaw === null || mmTotalRaw === undefined || String(mmTotalRaw).trim() === '') ? '-' : String(mmTotalRaw).trim();
          const mmPctTxt = (mmPred && mmPred.pct !== null && mmPred.pct !== undefined) ? `${mmPred.pct}%` : '-';
          const mmD1 = mm && mm.descriptive_marks1 !== undefined && mm.descriptive_marks1 !== null && String(mm.descriptive_marks1).trim() !== '' ? String(mm.descriptive_marks1).trim() : '-';
          const mmO1 = mm && mm.objective_marks1 !== undefined && mm.objective_marks1 !== null && String(mm.objective_marks1).trim() !== '' ? String(mm.objective_marks1).trim() : '-';
          const mmA1 = mm && mm.assignment_marks1 !== undefined && mm.assignment_marks1 !== null && String(mm.assignment_marks1).trim() !== '' ? String(mm.assignment_marks1).trim() : '-';
          const mmD2 = mm && mm.descriptive_marks2 !== undefined && mm.descriptive_marks2 !== null && String(mm.descriptive_marks2).trim() !== '' ? String(mm.descriptive_marks2).trim() : '-';
          const mmO2 = mm && mm.objective_marks2 !== undefined && mm.objective_marks2 !== null && String(mm.objective_marks2).trim() !== '' ? String(mm.objective_marks2).trim() : '-';
          const mmA2 = mm && mm.assignment_marks2 !== undefined && mm.assignment_marks2 !== null && String(mm.assignment_marks2).trim() !== '' ? String(mm.assignment_marks2).trim() : '-';
          const mmBlock = mm ? `<div class="small text-muted mt-1" style="line-height:1.25;">
            <div>Mid: <span class="fw-semibold" style="font-family:var(--font-mono)">${mmTotalTxt}</span> <span class="ms-1">${mmPctTxt}</span> <span class="ms-1">${midBadge(mmPred)}</span></div>
            <div class="mid-details"><span title="Descriptive 1">DES1</span> ${mmD1} | <span title="Objective 1">OBJ1</span> ${mmO1} | <span title="Assignment 1">ASMT1</span> ${mmA1}</div>
            <div class="mid-details"><span title="Descriptive 2">DES2</span> ${mmD2} | <span title="Objective 2">OBJ2</span> ${mmO2} | <span title="Assignment 2">ASMT2</span> ${mmA2}</div>
          </div>`: '';
          cardHtml += `<tr><td class="sticky-col subject-cell" title="${title}"><div class="subject-code">${code}</div>${name ? `<div class="sub-name">${name}</div>` : ''}${creditsBadge}${mmBlock}</td>${semSessions.map(session => { const r = (bySession[session] || {})[code]; if (!r) return `<td><span class="empty-mark">-</span></td>`; const present = (r.present !== undefined && r.present !== null && String(r.present).trim() !== '') ? r.present : ''; const extraRaw = (r.extra !== undefined && r.extra !== null && String(r.extra).trim() !== '') ? r.extra : ''; const total = (r.total !== undefined && r.total !== null && String(r.total).trim() !== '') ? r.total : ''; const pct = toPct(present, total, r.percentage); const pctClass = percentageToClass(pct); const pred = attendancePrediction(pct); const predHtml = attendancePredictionCell(pred); const extraNum = extraRaw === '' ? 0 : Number(extraRaw); const showExtra = Number.isFinite(extraNum) && extraNum > 0; const pt = (present !== '' && total !== '') ? (showExtra ? `${present}(${extraNum}) / ${total}` : `${present} / ${total}`) : '-'; const pctPart = pct ? `<span class="${pctClass}">${pct}%</span>` : ''; const value = pctPart ? `<div class="attendance-cell-value">${pt}</div><div class="pct-line fw-bold">${pctPart}</div><div class="attendance-status mt-1">${predHtml}</div>` : `<div class="attendance-cell-value">${pt}</div>`; return `<td>${value}</td>`; }).join('')}</tr>`;
        });
        const mmCell = (code, field) => {
          const mm = midmarksByCode && midmarksByCode[code] ? midmarksByCode[code] : null; if (!mm) return '-'; if (field === 'total') { const raw = (mm.total_marks !== null && mm.total_marks !== undefined && String(mm.total_marks).trim() !== '') ? mm.total_marks : ((mm.marks !== null && mm.marks !== undefined && String(mm.marks).trim() !== '') ? mm.marks : ''); const mmTotalTxt = (raw === null || raw === undefined || String(raw).trim() === '') ? '-' : String(raw).trim(); return `<div style="font-family:var(--font-mono); font-size:12px; font-weight:700; color:var(--accent);">${mmTotalTxt}</div>`; }
          const v = mm[field]; if (v === null || v === undefined || String(v).trim() === '') return '-'; return `<span style="font-family:var(--font-mono); font-size:12px;">${String(v).trim()}</span>`;
        };
        const getSessionSummary = (session) => {
          const rows = (sessionGroups[session] && sessionGroups[session].summary) ? (sessionGroups[session].summary || []) : [];
          const r0 = (rows && rows.length) ? rows[0] : null;
          const present = r0 && r0.total_present !== undefined && r0.total_present !== null && String(r0.total_present).trim() !== '' ? String(r0.total_present).trim() : '-';
          const total = r0 && r0.total_classes !== undefined && r0.total_classes !== null && String(r0.total_classes).trim() !== '' ? String(r0.total_classes).trim() : '-';
          const pctRaw = r0 ? toPct(r0.total_present, r0.total_classes, r0.percentage) : '';
          const pct = (pctRaw && String(pctRaw).trim() !== '') ? String(pctRaw).trim() : '';
          const pctClass = percentageToClass(pct);
          const pctHtml = pct ? `<span class="fw-bold ${pctClass}">${pct}%</span>` : '-';
          const pred = attendancePrediction(pct);
          const predHtml = attendancePredictionCell(pred);
          return { present, total, pctHtml, predHtml };
        };
        /* Grades row - removed per user request */
        const gradesRowHtml = '';

        const mmRowsHtml = (midmarksByCode && Object.keys(midmarksByCode || {}).length) ? [
          { label: 'DES1', full: 'Descriptive 1', field: 'descriptive_marks1' },
          { label: 'OBJ1', full: 'Objective 1', field: 'objective_marks1' },
          { label: 'ASMT1', full: 'Assignment 1', field: 'assignment_marks1' },
          { label: 'DES2', full: 'Descriptive 2', field: 'descriptive_marks2' },
          { label: 'OBJ2', full: 'Objective 2', field: 'objective_marks2' },
          { label: 'ASMT2', full: 'Assignment 2', field: 'assignment_marks2' },
          { label: 'MID TOTAL', full: 'Mid Total', field: 'total' }
        ].filter(r => sortedSubjects.some(code => {
          const record = midmarksByCode[code];
          if (!record) return false;
          const value = r.field === 'total'
            ? ((record.total_marks !== undefined && record.total_marks !== null && String(record.total_marks).trim() !== '') ? record.total_marks : record.marks)
            : record[r.field];
          return value !== undefined && value !== null && String(value).trim() !== '';
        })).map(r => `<tr><td class="sticky-col"><span style="font-size:12px; font-weight:800;color:#000;" title="${r.full}">${r.label}</span></td>${sortedSubjects.map(code => `<td>${mmCell(code, r.field)}</td>`).join('')}<td>-</td><td>-</td><td>-</td></tr>`).join('') : '';

        cardHtml += `</tbody></table></div><div id="transpose-table-${semPivotKey}"><table class="table table-sm table-hover attendance-pivot table-minimal subject-attendance-table"><thead><tr><th class="sticky-col">Month</th>${sortedSubjects.map(code => { const name = subjectMap.get(code) || ''; const title = name ? `${code} - ${name}` : code; const credits = subjectCreditsByCode && subjectCreditsByCode[code] ? subjectCreditsByCode[code] : null; const creditsBadge = credits !== null ? `<div class="credits-badge">${credits} CR</div>` : ''; return `<th title="${title}"><div class="subject-code">${code}</div>${name ? `<div class="sub-name">${name}</div>` : ''}${creditsBadge}</th>`; }).join('')}<th class="col-narrow">TP</th><th class="col-narrow">TC</th><th class="col-narrow">%</th></tr></thead><tbody>${semSessions.map(session => { const sum = getSessionSummary(session); const sessionDisplay = attendanceMonthLabel(session); return `<tr><td class="sticky-col month-cell">${sessionDisplay}</td>${sortedSubjects.map(code => { const r = (bySession[session] || {})[code]; if (!r) return `<td><span class="empty-mark">-</span></td>`; const present = (r.present !== undefined && r.present !== null && String(r.present).trim() !== '') ? r.present : ''; const extraRaw = (r.extra !== undefined && r.extra !== null && String(r.extra).trim() !== '') ? r.extra : ''; const total = (r.total !== undefined && r.total !== null && String(r.total).trim() !== '') ? r.total : ''; const pct = toPct(present, total, r.percentage); const pctClass = percentageToClass(pct); const extraNum = extraRaw === '' ? 0 : Number(extraRaw); const showExtra = Number.isFinite(extraNum) && extraNum > 0; const pt = (present !== '' && total !== '') ? (showExtra ? `${present}(${extraNum}) / ${total}` : `${present} / ${total}`) : '-'; const pctPart = pct ? `<span class="${pctClass}">${pct}%</span>` : ''; const value = pctPart ? `<div class="attendance-cell-value">${pt}</div><div class="pct-line fw-bold">${pctPart}</div>` : `<div class="attendance-cell-value">${pt}</div>`; return `<td>${value}</td>`; }).join('')}<td class="col-narrow">${sum.present}</td><td class="col-narrow">${sum.total}</td><td class="col-narrow">${sum.pctHtml}</td></tr>`; }).join('')}${mmRowsHtml}${gradesRowHtml}</tbody></table></div></div></div>`;
      });

      sessions.forEach(session => {
        cardHtml = cardHtml.replaceAll(`>${session}<`, `>${attendanceMonthLabel(session)}<`);
      });
      cardHtml += `</div></div>`;
      return cardHtml;
    };

    /* ---- MID MARKS ---- */
    const renderMidMarksCard = (records) => {
      if (!records || !records.length) return '';
      const toNum = (v) => { if (v === null || v === undefined) return null; const s = String(v).trim(); if (s === '') return null; const n = Number(s); return Number.isFinite(n) ? n : null; };
      const getPrediction = (totalOutOf30) => { const tRaw = toNum(totalOutOf30); if (tRaw === null) return { pct: null, label: '-', cls: 'secondary' }; const t = Math.max(0, Math.min(30, tRaw)); const pct = Math.round((t / 30) * 100); if (t >= 27) return { pct, label: 'ðŸŸ¢ S (Topper level)', cls: 'success' }; if (t >= 24) return { pct, label: 'ðŸŸ¢ A', cls: 'success' }; if (t >= 21) return { pct, label: 'ðŸŸ¡ B', cls: 'warning' }; if (t >= 18) return { pct, label: 'ðŸŸ¡ C', cls: 'warning' }; if (t >= 15) return { pct, label: 'ðŸŸ  D', cls: 'orange' }; if (t >= 12) return { pct, label: 'ðŸŸ  E', cls: 'orange' }; return { pct, label: 'ðŸ”´ F (Risk)', cls: 'danger' }; };
      const predictionBadge = (p) => { if (!p || p.label === '-') return '<span class="badge bg-secondary">-</span>'; if (p.cls === 'orange') return `<span class="badge" style="background:var(--orange)">${p.label}</span>`; return `<span class="badge bg-${p.cls}">${p.label}</span>`; };
      const predictionWithPie = (p) => { if (!p || p.pct === null || p.pct === undefined) return `<div class="d-flex align-items-center gap-2"><span class="mm-pie" data-value="0"></span>${predictionBadge(p)}</div>`; const cls = (p.cls === 'orange') ? 'orange' : (p.cls || 'secondary'); const pct = Math.max(0, Math.min(100, Number(p.pct))); return `<div class="d-flex align-items-center gap-2"><span class="mm-pie ${cls}" data-value="${pct}" title="${pct}%"></span>${predictionBadge(p)}</div>`; };
      const pctPill = (pct) => { if (pct === null || pct === undefined || pct === '') return '-'; const n = Number(pct); if (!Number.isFinite(n)) return '-'; const cls = n >= 80 ? 'success' : (n >= 60 ? 'warning' : (n >= 40 ? 'orange' : 'danger')); if (cls === 'orange') return `<span class="badge" style="background:var(--orange)">${n}%</span>`; return `<span class="badge bg-${cls}">${n}%</span>`; };
      const pctToCls = (pct) => { const n = Number(pct); if (!Number.isFinite(n)) return 'secondary'; if (n >= 80) return 'success'; if (n >= 60) return 'warning'; if (n >= 40) return 'orange'; return 'danger'; };
      const groups = {};
      records.forEach(r => { const key = r.upload_id || ''; if (!groups[key]) groups[key] = { upload_id: key, semester_info: r.semester_info || '', academic_year: r.academic_year || '', file_name: r.file_name || '', subjects: [] }; groups[key].subjects.push(r); });
      let cardHtml = `<div class="card data-card mb-3 midmarks-compact"><div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #000 !important; border: 1px solid #000; border-bottom: 2px solid #000; font-size: 12px; padding: 12px 16px; font-weight: 600;"><div class="d-flex align-items-center gap-2" style="color: #000 !important;"><i class="fas fa-chart-bar" style="color: #000 !important;"></i><span style="color: #000 !important;">MID MARKS</span><span id="midmarksOverallPie" class="mm-pie mm-pie-lg" data-value="0" title="Overall %"></span></div><div class="badge bg-white text-primary" style="border: 1px solid #000; color: #000 !important;">${Object.keys(groups).length} upload(s)</div></div><div class="card-body"><div class="mb-3"><div class="small text-muted">Prediction based on <b>Mid Marks (out of 30)</b></div><div class="d-flex flex-wrap gap-2 mt-2"><span class="mid-badge success">🟢 S/A</span><span class="mid-badge warning">🟡 B/C</span><span class="mid-badge orange">🟠 D/E</span><span class="mid-badge danger">🔴 F</span></div></div>`;
      let overallCount = 0, overallSumPct = 0;
      records.forEach((r) => { const total = (r.total_marks !== null && r.total_marks !== undefined && String(r.total_marks).trim() !== '') ? r.total_marks : ((r.marks !== null && r.marks !== undefined && String(r.marks).trim() !== '') ? r.marks : null); const pred = getPrediction(total); if (pred && pred.pct !== null && pred.pct !== undefined && Number.isFinite(Number(pred.pct))) { overallCount += 1; overallSumPct += Number(pred.pct); } });
      const overallPct = overallCount ? Math.round(overallSumPct / overallCount) : null;
      const overallCls = pctToCls(overallPct);
      setTimeout(() => { const el = document.getElementById('midmarksOverallPie'); if (!el) return; const p = (overallPct === null || overallPct === undefined) ? 0 : overallPct; el.setAttribute('data-value', p); el.classList.remove('success', 'warning', 'orange', 'danger', 'secondary'); if (overallCls && overallCls !== 'secondary') el.classList.add(overallCls); el.title = overallPct === null ? 'Overall %: -' : `Overall %: ${overallPct}%`; }, 0);
      Object.values(groups).forEach(g => {
        const hdr = [];
        if (g.semester_info) hdr.push(g.semester_info);
        if (g.academic_year) hdr.push(g.academic_year);
        if (g.file_name && !g.semester_info) hdr.push(g.file_name);
        const midKey = `midmarks-${String(g.upload_id || '').replace(/[^a-zA-Z0-9_-]/g, '_')}`;

        const rows = (g.subjects || []).map(s => {
          const name = s.subject_name || s.subject_code || '';
          const d1 = (s.descriptive_marks1 !== null && s.descriptive_marks1 !== undefined && String(s.descriptive_marks1).trim() !== '') ? s.descriptive_marks1 : '-';
          const o1 = (s.objective_marks1 !== null && s.objective_marks1 !== undefined && String(s.objective_marks1).trim() !== '') ? s.objective_marks1 : '-';
          const a1 = (s.assignment_marks1 !== null && s.assignment_marks1 !== undefined && String(s.assignment_marks1).trim() !== '') ? s.assignment_marks1 : '-';
          const d2 = (s.descriptive_marks2 !== null && s.descriptive_marks2 !== undefined && String(s.descriptive_marks2).trim() !== '') ? s.descriptive_marks2 : '-';
          const o2 = (s.objective_marks2 !== null && s.objective_marks2 !== undefined && String(s.objective_marks2).trim() !== '') ? s.objective_marks2 : '-';
          const a2 = (s.assignment_marks2 !== null && s.assignment_marks2 !== undefined && String(s.assignment_marks2).trim() !== '') ? s.assignment_marks2 : '-';
          const total = (s.total_marks !== null && s.total_marks !== undefined && String(s.total_marks).trim() !== '') ? s.total_marks : ((s.marks !== null && s.marks !== undefined && String(s.marks).trim() !== '') ? s.marks : '-');
          const pred = getPrediction(total);
          return { name, d1, o1, a1, d2, o2, a2, total, pct: pred.pct, predHtml: predictionWithPie(pred) };
        });

        const normalRows = rows.map(r => `<tr><td>${r.name}</td><td class="text-end font-mono">${r.d1}</td><td class="text-end font-mono">${r.o1}</td><td class="text-end font-mono">${r.a1}</td><td class="text-end font-mono">${r.d2}</td><td class="text-end font-mono">${r.o2}</td><td class="text-end font-mono">${r.a2}</td><td class="text-end fw-bold font-mono">${r.total}</td><td class="text-end font-mono">${pctPill(r.pct)}</td><td>${r.predHtml}</td></tr>`).join('');
        const transposeHeader = `<tr><th>FIELD</th>${rows.map(r => `<th class=\"text-end\" title=\"${String(r.name).replace(/\"/g, '&quot;')}\">${r.name}</th>`).join('')}</tr>`;
        const transposeRows = [
          { k: 'd1', label: 'DES1', full: 'Descriptive 1', cls: 'text-end font-mono' },
          { k: 'o1', label: 'OBJ1', full: 'Objective 1', cls: 'text-end font-mono' },
          { k: 'a1', label: 'ASMT1', full: 'Assignment 1', cls: 'text-end font-mono' },
          { k: 'd2', label: 'DES2', full: 'Descriptive 2', cls: 'text-end font-mono' },
          { k: 'o2', label: 'OBJ2', full: 'Objective 2', cls: 'text-end font-mono' },
          { k: 'a2', label: 'ASMT2', full: 'Assignment 2', cls: 'text-end font-mono' },
          { k: 'total', label: 'TOTAL', full: '', cls: 'text-end fw-bold font-mono' },
          { k: 'pct', label: '%', full: '', cls: 'text-end font-mono' },
          { k: 'predHtml', label: 'PREDICTION', full: '', cls: '' }
        ].map(row => `<tr><td${row.full ? ` title="${row.full}"` : ''}>${row.label}</td>${rows.map(r => { if (row.k === 'pct') return `<td class=\"${row.cls}\">${pctPill(r.pct)}</td>`; if (row.k === 'predHtml') return `<td>${r.predHtml}</td>`; if (row.k === 'total') return `<td class=\"${row.cls}\">${r.total}</td>`; return `<td class=\"${row.cls}\">${r[row.k]}</td>`; }).join('')}</tr>`).join('');

        cardHtml += `<div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="small text-muted">${hdr.join(' | ')}</div>
            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="toggleTranspose('${midKey}')"><i class="fas fa-exchange-alt me-1"></i>Transpose</button>
          </div>
          <div class="table-responsive">
            <div id="normal-table-${midKey}">
              <table class="table table-sm table-striped table-hover table-minimal">
                <thead><tr><th>SUBJECT</th><th class="text-end" title="Descriptive 1">DES1</th><th class="text-end" title="Objective 1">OBJ1</th><th class="text-end" title="Assignment 1">ASMT1</th><th class="text-end" title="Descriptive 2">DES2</th><th class="text-end" title="Objective 2">OBJ2</th><th class="text-end" title="Assignment 2">ASMT2</th><th class="text-end">TOTAL</th><th class="text-end">%</th></thead>
                <tbody>${normalRows || ''}</tbody>
              </table>
            </div>
            <div id="transpose-table-${midKey}" style="display:none;">
              <table class="table table-sm table-striped table-hover table-minimal">
                <thead>${transposeHeader}</thead>
                <tbody>${transposeRows || ''}</tbody>
              </table>
            </div>
          </div>
        </div>`;
      });
      cardHtml += `</div></div>`;
      return cardHtml;
    };

    /* ---- NPTEL CERTIFICATES ---- */
    const renderNptelCertificatesCard = (records) => {
      if (!records || !records.length) return '';
      const scoreToColor = (score) => {
        const s = Number(score);
        if (s >= 90) return 'success';
        if (s >= 80) return 'primary';
        if (s >= 70) return 'info';
        if (s >= 60) return 'warning';
        return 'danger';
      };
      const scoreToBadge = (score) => {
        const s = Number(score);
        if (s >= 90) return '🏆 Elite+';
        if (s >= 75) return '🥇 Elite';
        if (s >= 60) return '🥈 Successfully Completed';
        if (s >= 50) return '🥉 Completed';
        return '❌ Audit';
      };
      let cardHtml = `<div class="card data-card mb-3 nptel-certificates"><div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #000 !important; border: 1px solid #000; border-bottom: 2px solid #000; font-size: 12px; padding: 12px 16px; font-weight: 600;"><div class="d-flex align-items-center gap-2" style="color: #000 !important;"><i class="fas fa-certificate" style="color: #000 !important;"></i><span style="color: #000 !important;">NPTEL CERTIFICATES</span></div><div class="badge bg-white text-success" style="border: 1px solid #000; color: #000 !important;">${records.length} Certificate(s)</div></div><div class="card-body"><div class="row row-cols-1 row-cols-md-2 g-3">`;
      records.forEach(c => {
        const score = c.score_percentage || 0;
        const color = scoreToColor(score);
        const badge = scoreToBadge(score);
        const assignments = c.assignments_score || '-';
        const proctored = c.proctored_score || '-';
        const duration = c.duration || '-';
        const credits = c.credits || 0;
        const grade = c.grade_label || '-';
        cardHtml += `<div class="col"><div class="nptel-card border rounded p-3" style="border-left: 4px solid var(--${color});">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="nptel-course-name fw-bold" style="font-size:14px; color:var(--ink);">${c.course_name || 'Unknown Course'}</div>
            <span class="badge bg-${color}" style="font-size:11px;">${badge}</span>
          </div>
          <div class="mb-2"><span class="text-muted" style="font-size:12px;">Score:</span> <strong class="text-${color}" style="font-size:18px; font-family:var(--font-mono);">${score}%</strong></div>
          <div class="row g-1 text-muted" style="font-size:11px;">
            <div class="col-6"><span class="fw-medium">Assignments:</span> ${assignments}</div>
            <div class="col-6"><span class="fw-medium">Proctored:</span> ${proctored}</div>
            <div class="col-6"><span class="fw-medium">Duration:</span> ${duration}</div>
            <div class="col-6"><span class="fw-medium">Credits:</span> ${credits}</div>
            <div class="col-12"><span class="fw-medium">Grade:</span> <span class="badge bg-secondary">${grade}</span></div>
          </div>
          ${c.certificate_no ? `<div class="mt-2 pt-2 border-top"><small class="text-muted">Certificate No: ${c.certificate_no}</small></div>` : ''}
        </div></div>`;
      });
      cardHtml += `</div></div></div>`;
      return cardHtml;
    };

    /* ---- SUBJECT RESULTS / GRADES ---- */
    const renderSubjectResultsCard = (records, subjectNameMap = {}, studentResultsBySemester = {}, studentName = '', rollNo = '') => {
      if (!records || !records.length) return '';

      // Merge course info from course_codes table
      const courseInfoMap = data.subject_results_course_info || {};

      // Group by semester first, then by subject code within each semester
      const semesterGroups = {};
      records.forEach(r => {
        const semesterInfo = r.semester_info || 'Unknown Semester';
        const examInfo = r.exam_info || 'Unknown Exam';
        const code = r.subject_code || '';
        if (!code) return;

        const grade = r.grade || '-';
        // Use credits from course_codes table if available, otherwise use subject_results
        const credits = (courseInfoMap[code] && courseInfoMap[code].credits !== null) ? courseInfoMap[code].credits : (r.credits || 0);
        // Use course name from course_codes table if available, otherwise use subject_results or subjectNameMap
        const subjectName = (courseInfoMap[code] && courseInfoMap[code].course_name) ? courseInfoMap[code].course_name : (subjectNameMap[code] || r.subject_name || '');

        const key = `${semesterInfo}|${examInfo}`;
        const semesterData = studentResultsBySemester[key] || {};
        const cgpa = semesterData.cgpa || '-';
        const sgpa = semesterData.sgpa || '-';

        if (!semesterGroups[semesterInfo]) {
          semesterGroups[semesterInfo] = {
            examInfo,
            cgpa,
            sgpa,
            subjects: {},
            totalCredits: 0,
            failedCredits: 0
          };
        }

        if (!semesterGroups[semesterInfo].subjects[code]) {
          semesterGroups[semesterInfo].subjects[code] = {
            subjectName,
            attempts: []
          };
        }

        semesterGroups[semesterInfo].subjects[code].attempts.push({
          examInfo,
          grade,
          credits
        });
      });

      // Calculate total credits per semester (excluding failed subjects)
      Object.keys(semesterGroups).forEach(semesterInfo => {
        const semester = semesterGroups[semesterInfo];
        Object.keys(semester.subjects).forEach(code => {
          const subject = semester.subjects[code];
          // Get the latest attempt (last one in array)
          const latestAttempt = subject.attempts[subject.attempts.length - 1];
          if (latestAttempt) {
            const gradeUpper = latestAttempt.grade.toUpperCase();
            const isFail = gradeUpper.startsWith('F');
            const creditValue = parseFloat(latestAttempt.credits) || 0;

            if (isFail) {
              semester.failedCredits += creditValue;
            } else {
              semester.totalCredits += creditValue;
            }
          }
        });
      });

      let cardHtml = `<div class="card data-card mb-3 subject-results-card" id="subjectGradesCard"><div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #000 !important; border: 1px solid #000; border-bottom: 2px solid #000; font-size: 12px; padding: 12px 16px; font-weight: 600;"><div class="d-flex align-items-center gap-2" style="color: #000 !important;"><i class="fas fa-graduation-cap" style="color: #000 !important;"></i><span class="fw-bold" style="color: #000 !important;">SUBJECT GRADES</span></div><div class="d-flex flex-column align-items-end gap-1"><div class="d-flex gap-2 align-items-center"><div class="badge bg-white text-info fw-bold" style="border: 1px solid #000; color: #000 !important;">${Object.keys(semesterGroups).length} Semester(s)</div><button onclick="copySubjectGrades()" class="btn btn-sm btn-light text-info" style="padding: 4px 8px; font-size: 11px; border: 1px solid #000; color: #000 !important;" title="Copy Grades"><i class="fas fa-copy" style="color: #000 !important;"></i></button></div>${studentName || rollNo ? `<div class="small text-muted" style="font-size:10px; color: #000 !important;">${studentName ? studentName : ''}${studentName && rollNo ? ' | ' : ''}${rollNo ? rollNo : ''}</div>` : ''}</div></div><div class="card-body p-3">`;

      Object.keys(semesterGroups).sort().forEach(semesterInfo => {
        const semester = semesterGroups[semesterInfo];
        const subjectCodes = Object.keys(semester.subjects).sort();

        cardHtml += `
          <div class="mb-4 p-3 p-md-4 rounded" style="background: #f8f9fa; border: 1px solid #e9ecef;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
              <div class="section-kicker mb-0"><i class="fas fa-book me-2"></i><span class="fw-bold text-primary">${semesterInfo}</span></div>
              <div class="d-flex gap-2 flex-wrap">
                ${semester.sgpa !== '-' ? `<span class="badge px-3 py-2" style="font-size:13px; background: #fff; color: #000; border: 1px solid #000;">SGPA: <strong>${semester.sgpa}</strong></span>` : ''}
                ${semester.cgpa !== '-' ? `<span class="badge px-3 py-2" style="font-size:13px; background: #fff; color: #000; border: 1px solid #000;">CGPA: <strong>${semester.cgpa}</strong></span>` : ''}
                <span class="badge px-3 py-2" style="font-size:13px; background: #fff; color: #000; border: 1px solid #000;">Credits: <strong>${semester.totalCredits}</strong></span>
                ${semester.failedCredits > 0 ? `<span class="badge px-3 py-2" style="font-size:13px; background: #fff; color: #dc3545; border: 1px solid #dc3545;">Failed Credits: <strong>${semester.failedCredits}</strong></span>` : ''}
              </div>
            </div>
            <div class="small text-muted mb-3 fst-italic">${semester.examInfo}</div>
            <div class="table-responsive" style="border-radius: 8px; overflow-x: auto; -webkit-overflow-scrolling: touch; border: 1px solid #000;">
              <table class="table table-bordered mb-0" style="margin-bottom: 0; font-size: 12px; min-width: 600px; border: 1px solid #000;">
                <thead style="background: #fff; color: #000; border: 1px solid #000;">
                  <tr style="border: 1px solid #000;">
                    <th class="d-none d-md-table-cell" style="position: sticky; left: 0; background: #fff; z-index: 10; min-width: 120px; font-weight: 600; border: 1px solid #000;">Exam</th>
                    <th class="d-md-none" style="min-width: 80px; font-weight: 600; font-size: 11px; border: 1px solid #000;">Exam</th>
        `;

        // Add subject codes as column headers
        subjectCodes.forEach(code => {
          const subject = semester.subjects[code];
          cardHtml += `<th style="min-width: 90px; font-weight: 600; font-size: 11px; border: 1px solid #000;">${code}<br><small style="font-weight: 400; font-size: 10px;">${subject.subjectName}</small></th>`;
        });

        cardHtml += `
                    <th style="min-width: 70px; font-weight: 600; background: #fff; font-size: 11px; border: 1px solid #000;">SGPA</th>
                    <th style="min-width: 70px; font-weight: 600; background: #fff; font-size: 11px; border: 1px solid #000;">CGPA</th>
                  </tr>
                </thead>
                <tbody>
        `;

        // Get all unique exams from all subjects
        const allExams = new Set();
        subjectCodes.forEach(code => {
          semester.subjects[code].attempts.forEach(attempt => {
            allExams.add(attempt.examInfo);
          });
        });

        // Create rows for each exam
        Array.from(allExams).sort().forEach((examInfo, idx) => {
          cardHtml += `<tr><td class="d-none d-md-table-cell" style="position: sticky; left: 0; background: #fff; z-index: 5; font-weight: 500; border: 1px solid #000;">${examInfo}</td><td class="d-md-none" style="font-weight: 500; font-size: 11px; border: 1px solid #000;">${examInfo.substring(0, 15)}${examInfo.length > 15 ? '...' : ''}</td>`;

          subjectCodes.forEach(code => {
            const subject = semester.subjects[code];
            const attempt = subject.attempts.find(a => a.examInfo === examInfo);

            if (attempt) {
              const gradeUpper = attempt.grade.toUpperCase();
              let bgStyle = '';
              let textColor = '#000';
              let borderColor = '#000';

              if (gradeUpper === 'S' || gradeUpper === 'A+') {
                bgStyle = 'background: #fff;';
                textColor = '#28a745';
                borderColor = '#28a745';
              } else if (gradeUpper.startsWith('F')) {
                bgStyle = 'background: #fff;';
                textColor = '#dc3545';
                borderColor = '#dc3545';
              } else {
                bgStyle = 'background: #fff;';
                textColor = '#000';
                borderColor = '#000';
              }

              cardHtml += `<td style="text-align: center; vertical-align: middle; padding: 8px 4px; border: 1px solid #000;"><span class="badge" style="font-size:11px; font-weight: 700; padding: 6px 8px; ${bgStyle} color: ${textColor}; border: 1px solid ${borderColor};">${gradeUpper}</span><br><small class="text-muted mt-1 d-block" style="font-size:10px;">${attempt.credits} CR</small></td>`;
            } else {
              cardHtml += `<td style="text-align: center; vertical-align: middle; color: #adb5bd; font-style: italic; padding: 8px 4px; border: 1px solid #000;">—</td>`;
            }
          });

          // Add SGPA and CGPA for this exam
          const key = `${semesterInfo}|${examInfo}`;
          const examData = studentResultsBySemester[key] || {};
          const sgpaValue = examData.sgpa || '-';
          const cgpaValue = examData.cgpa || '-';

          cardHtml += `<td style="text-align: center; vertical-align: middle; font-weight: 600; background: #fff; border: 1px solid #000; color: #000; padding: 8px 4px; font-size: 11px;">${sgpaValue}</td>`;
          cardHtml += `<td style="text-align: center; vertical-align: middle; font-weight: 600; background: #fff; border: 1px solid #000; color: #000; padding: 8px 4px; font-size: 11px;">${cgpaValue}</td>`;

          cardHtml += `</tr>`;
        });

        cardHtml += `
                </tbody>
              </table>
            </div>
          </div>
        `;
      });

      cardHtml += `</div></div>`;
      return cardHtml;
    };

    /* ---- JOB OFFERS ---- */
    const renderJobOffersCompact = (records, rankData, cgpaData) => {
      if (!records || !records.length) return '';
      const formatCurrency = (amount) => {
        if (!amount || amount === null || amount === undefined) return 'N/A';
        const num = Number(amount);
        if (!Number.isFinite(num)) return 'N/A';
        return `₹${num.toLocaleString('en-IN')}L`;
      };

      // Find highest package
      let highestPackage = 0;
      records.forEach(job => {
        const pkg = Number(job.package);
        if (Number.isFinite(pkg) && pkg > highestPackage) {
          highestPackage = pkg;
        }
      });

      let html = `<div class="job-offers-compact">`;

      // Add ranking badge if available
      if (rankData && rankData.rank) {
        const rank = rankData.rank;
        const total = rankData.total;
        let rankIcon = '';
        let rankClass = 'bg-primary';

        if (rank === 1) {
          rankIcon = '🥇';
          rankClass = 'bg-warning text-dark';
        } else if (rank === 2) {
          rankIcon = '🥈';
          rankClass = 'bg-secondary';
        } else if (rank === 3) {
          rankIcon = '🥉';
          rankClass = 'bg-info text-dark';
        }

        html += `<div class="mb-2"><span class="badge ${rankClass}" style="font-size:11px; padding:6px 10px;">${rankIcon} Rank #${rank} of ${total} students</span></div>`;
      }

      html += `<div class="d-flex flex-wrap gap-2 align-items-center">`;
      records.forEach(job => {
        const companyName = job.company_name || 'Unknown';
        const packageAmount = formatCurrency(job.package);
        const pkg = Number(job.package);
        const isHighest = Number.isFinite(pkg) && pkg === highestPackage;
        const badgeClass = isHighest ? 'bg-success' : 'bg-secondary';
        const icon = isHighest ? '<i class="fas fa-trophy me-1"></i>' : '<i class="fas fa-briefcase me-1"></i>';
        html += `<span class="badge ${badgeClass}" style="font-size:11px; padding:6px 10px;">${icon}${companyName} - ${packageAmount}</span>`;
      });
      html += `</div></div>`;
      return html;
    };

    /* ---- CGPA RANK ---- */
    const renderCGPARank = (rankData) => {
      if (!rankData) return '';

      let html = `<div class="cgpa-rank-compact d-flex flex-wrap gap-2 align-items-center">`;

      // Overall CGPA ranking
      if (rankData.overall_rank && rankData.cgpa) {
        const overallRank = rankData.overall_rank;
        const overallTotal = rankData.overall_total;
        const cgpa = rankData.cgpa.toFixed(2);
        let rankIcon = '';
        let rankClass = 'bg-primary';

        if (overallRank === 1) {
          rankIcon = '🥇';
          rankClass = 'bg-warning text-dark';
        } else if (overallRank === 2) {
          rankIcon = '🥈';
          rankClass = 'bg-secondary';
        } else if (overallRank === 3) {
          rankIcon = '🥉';
          rankClass = 'bg-info text-dark';
        }

        html += `<span class="badge ${rankClass}" style="font-size:11px; padding:6px 10px; cursor:pointer; text-decoration:none; transition:all 0.2s ease;" onclick="showRankingModal('overall')" title="Click to view full ranking">${rankIcon} CGPA ${cgpa} - Overall #${overallRank}/${overallTotal}</span>`;
      }

      // Semester-wise ranking
      if (rankData.semester_rank && rankData.cgpa && rankData.semester_info) {
        const semesterRank = rankData.semester_rank;
        const semesterTotal = rankData.semester_total;
        const cgpa = rankData.cgpa.toFixed(2);
        const sgpa = rankData.sgpa ? rankData.sgpa.toFixed(2) : null;
        const semesterInfo = rankData.semester_info;
        let rankIcon = '';
        let rankClass = 'bg-primary';

        if (semesterRank === 1) {
          rankIcon = '🥇';
          rankClass = 'bg-warning text-dark';
        } else if (semesterRank === 2) {
          rankIcon = '🥈';
          rankClass = 'bg-secondary';
        } else if (semesterRank === 3) {
          rankIcon = '🥉';
          rankClass = 'bg-info text-dark';
        }

        const sgpaText = sgpa ? ` | SGPA ${sgpa}` : '';
        html += `<span class="badge ${rankClass}" style="font-size:11px; padding:6px 10px; cursor:pointer; text-decoration:none; transition:all 0.2s ease;" onclick="showRankingModal('semester')" title="Click to view full ranking">${rankIcon} CGPA ${cgpa}${sgpaText} (${semesterInfo}) - #${semesterRank}/${semesterTotal}</span>`;
      }

      html += `</div>`;
      return html;
    };

    /* ---- SMART SCORE ---- */
    const renderSmartScore = (smartScoreData) => {
      if (!smartScoreData || !smartScoreData.overall_score) return '';

      const score = smartScoreData.overall_score.toFixed(2);
      const rank = smartScoreData.rank;
      const globalRank = smartScoreData.global_rank;
      let rankIcon = '';
      let rankClass = 'bg-warning text-dark';

      if (rank === 1) {
        rankIcon = '🥇';
        rankClass = 'bg-warning text-dark';
      } else if (rank === 2) {
        rankIcon = '🥈';
        rankClass = 'bg-secondary';
      } else if (rank === 3) {
        rankIcon = '🥉';
        rankClass = 'bg-info text-dark';
      }

      return `<div class="smart-score-compact d-flex flex-wrap gap-2 align-items-center mt-2">
        <span class="badge ${rankClass}" style="font-size:11px; padding:6px 10px; cursor:pointer; text-decoration:none; transition:all 0.2s ease;" onclick="window.location.href='leaderboard.php?ranking=weighted'" title="Click to view smart score leaderboard">${rankIcon} Smart Score ${score} - Rank #${rank}</span>
      </div>`;
    };

    /* ---- TEST ASSESSMENT RESULTS ---- */
    const renderTestAssessmentResults = (records) => {
      if (!records || !records.length) return '';

      let html = `<div class="d-flex flex-wrap gap-2 align-items-center">`;

      records.forEach(test => {
        let testName = (test.test_name || 'Unknown Test').toUpperCase();
        // Replace RANDA with R/A
        testName = testName.replace(/RANDA/g, 'R/A');
        const testDate = test.test_date ? new Date(test.test_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
        const marks = test.max_marks || 0; // Using max_marks as obtained marks based on data
        const percentage = Number(test.percentage) || 0;
        const companyName = test.company_name || 'TALENT SHINE INDIA PVT LTD';

        let badgeClass = 'bg-secondary';
        if (percentage >= 80) badgeClass = 'bg-success';
        else if (percentage >= 60) badgeClass = 'bg-primary';
        else if (percentage >= 40) badgeClass = 'bg-warning text-dark';
        else badgeClass = 'bg-danger';

        html += `<span class="badge ${badgeClass}" style="font-size:11px; padding:6px 10px;" title="${companyName} | ${testDate}">
          <i class="fas fa-clipboard-check me-1"></i>${testName}: ${marks} (${percentage.toFixed(1)}%)
        </span>`;
      });

      html += `</div>`;
      return html;
    };

    /* ---- ACHIEVEMENT TOAST SYSTEM ---- */
    let achievementCounter = 0;
    const achievementQueue = [];
    const MAX_VISIBLE_TOASTS = 2;

    const showAchievement = (title, subtitle = "Achievement Unlocked", icon = "🏆") => {
      if (achievementCounter >= MAX_VISIBLE_TOASTS) {
        achievementQueue.push({ title, subtitle, icon });
        return;
      }
      renderAchievementToast(title, subtitle, icon);
    };

    const renderAchievementToast = (title, subtitle, icon) => {
      const toast = document.createElement('div');
      toast.className = 'achievement-toast';
      toast.style.top = `${24 + (achievementCounter * 90)}px`;
      toast.innerHTML = `
        <span class="achievement-icon">${icon}</span>
        <div class="achievement-text">
          <strong>${title}</strong>
          <small>${subtitle}</small>
        </div>
      `;
      document.body.appendChild(toast);

      // Confetti puff behind the icon (small celebration)
      requestAnimationFrame(() => {
        const iconEl = toast.querySelector('.achievement-icon');
        if (!iconEl) return;
        const rect = iconEl.getBoundingClientRect();
        const puffColors = ['#FFD700', '#FF6B6B', '#4ECDC4', '#A78BFA', '#38BDF8'];
        for (let i = 0; i < 14; i++) {
          const dot = document.createElement('div');
          const color = puffColors[Math.floor(Math.random() * puffColors.length)];
          const angle = Math.random() * Math.PI * 2;
          const dist = 20 + Math.random() * 30;
          const tx = Math.cos(angle) * dist;
          const ty = Math.sin(angle) * dist;
          const size = 3 + Math.random() * 3;
          dot.style.cssText = `
            position:fixed; left:${rect.left + rect.width / 2}px; top:${rect.top + rect.height / 2}px;
            width:${size}px; height:${size}px; background:${color}; border-radius:50%;
            z-index:10001; pointer-events:none;
            animation: achievementPuff .7s ease-out forwards;
            --tx:${tx}px; --ty:${ty}px;
          `;
          document.body.appendChild(dot);
          setTimeout(() => dot.remove(), 700);
        }
      });

      requestAnimationFrame(() => toast.classList.add('show'));
      achievementCounter++;

      setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        toast.addEventListener('animationend', () => {
          toast.remove();
          achievementCounter--;
          document.querySelectorAll('.achievement-toast.show').forEach((t, index) => {
            t.style.top = `${24 + (index * 90)}px`;
          });
          // Pull next queued toast if any
          if (achievementQueue.length > 0 && achievementCounter < MAX_VISIBLE_TOASTS) {
            const next = achievementQueue.shift();
            renderAchievementToast(next.title, next.subtitle, next.icon);
          }
        }, { once: true });
      }, 3000);
    };


    /* ---- CONGRATULATIONS POPUP ---- */
    const showCongratulationsPopup = (type, rank, data) => {
      let gradient = '';
      let title = '';
      let message = '';
      let icon = '';
      let badgeColor = '';
      let cgpaValue = '';

      if (type === 'cgpa_overall') {
        gradient = 'linear-gradient(135deg, #ffd700 0%, #ff8c00 50%, #ff6b35 100%)';
        badgeColor = '#ffd700';
        const rankText = rank === 1 ? '#1' : rank === 2 ? '#2' : '#3';
        const rankEmoji = rank === 1 ? '🏆' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : '⭐';
        title = `Academic Excellence`;
        message = `Ranked ${rankText} in Overall CGPA`;
        icon = rankEmoji;
        cgpaValue = data.cgpa ? data.cgpa.toFixed(2) : '';
      } else if (type === 'cgpa_semester') {
        gradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 50%, #9b59b6 100%)';
        badgeColor = '#667eea';
        const rankText = rank === 1 ? '#1' : rank === 2 ? '#2' : '#3';
        const rankEmoji = rank === 1 ? '🎯' : rank === 2 ? '🌟' : rank === 3 ? '✨' : '💫';
        title = `Semester Achievement`;
        message = `Ranked ${rankText} in ${data.semester_info}`;
        icon = rankEmoji;
        cgpaValue = data.cgpa ? data.cgpa.toFixed(2) : '';
      } else if (type === 'job_offer') {
        gradient = 'linear-gradient(135deg, #11998e 0%, #38ef7d 50%, #00b894 100%)';
        badgeColor = '#38ef7d';
        const rankText = rank === 1 ? '#1' : rank === 2 ? '#2' : '#3';
        const rankEmoji = rank === 1 ? '💎' : rank === 2 ? '🚀' : rank === 3 ? '🎁' : '🌟';
        title = `Career Milestone`;
        message = `Ranked ${rankText} in Job Offers`;
        icon = rankEmoji;
      }

      const popup = document.createElement('div');
      popup.className = 'congratulations-popup';
      popup.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        background: ${gradient};
        padding: 0;
        border-radius: 24px;
        box-shadow: 0 25px 80px rgba(0,0,0,0.4), 0 0 60px rgba(255, 215, 0, 0.3);
        z-index: 10000;
        text-align: center;
        color: white;
        min-width: 320px;
        max-width: 380px;
        animation: popupBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
        overflow: hidden;
      `;

      popup.innerHTML = `
        <div style="background: rgba(255,255,255,0.15); padding: 20px 25px 15px;">
          <div style="font-size: 48px; margin-bottom: 8px; animation: iconFloat 2s ease-in-out infinite;">${icon}</div>
          <div style="font-size: 11px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.8; margin-bottom: 4px;">Achievement Unlocked</div>
          <h2 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">${title}</h2>
        </div>
        <div style="padding: 20px 25px 25px; background: rgba(0,0,0,0.1);">
          <div style="background: rgba(255,255,255,0.2); border-radius: 16px; padding: 15px; margin-bottom: 15px; backdrop-filter: blur(10px);">
            <div style="font-size: 13px; opacity: 0.9; margin-bottom: 4px;">${message}</div>
            ${cgpaValue ? `<div style="font-size: 36px; font-weight: 800; line-height: 1; text-shadow: 0 2px 20px rgba(0,0,0,0.3);">${cgpaValue}</div>` : ''}
          </div>
          <button onclick="this.closest('.congratulations-popup').remove(); document.querySelector('.popup-overlay')?.remove();" style="
            background: white;
            color: #333;
            border: none;
            padding: 12px 32px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
          " onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)'">Continue</button>
        </div>
      `;

      document.body.appendChild(popup);

      // Add CSS animations
      const style = document.createElement('style');
      style.textContent = `
        @keyframes iconFloat {
          0%, 100% { transform: translateY(0) rotate(0deg); }
          50% { transform: translateY(-10px) rotate(5deg); }
        }
        @keyframes popupBounce {
          0% { transform: translate(-50%, -50%) scale(0) rotate(-10deg); opacity: 0; }
          50% { transform: translate(-50%, -50%) scale(1.1) rotate(2deg); }
          100% { transform: translate(-50%, -50%) scale(1) rotate(0deg); opacity: 1; }
        }
        @keyframes confettiFall {
          0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
          100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        @keyframes sparkle {
          0%, 100% { transform: scale(0) rotate(0deg); opacity: 0; }
          50% { transform: scale(1) rotate(180deg); opacity: 1; }
        }
        @keyframes glowRingPulse {
          0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.8; }
          50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.4; }
          100% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.8; }
        }
      `;
      document.head.appendChild(style);

      // Create golden confetti
      const createConfetti = () => {
        const colors = ['#FFD700', '#FFA500', '#FF6347', '#FFB6C1', '#FFF8DC', '#F0E68C'];
        for (let i = 0; i < 200; i++) {
          const confetti = document.createElement('div');
          confetti.style.cssText = `
            position: fixed;
            width: ${Math.random() * 10 + 5}px;
            height: ${Math.random() * 10 + 5}px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            left: ${Math.random() * 100}vw;
            top: -20px;
            border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
            z-index: 10001;
            animation: confettiFall ${Math.random() * 4 + 3}s linear forwards;
            animation-delay: ${Math.random() * 4}s;
          `;
          document.body.appendChild(confetti);

          // Remove confetti after animation
          setTimeout(() => confetti.remove(), 8000);
        }
      };

      // Create golden sparkles
      const createSparkles = () => {
        const sparkles = [];
        for (let i = 0; i < 30; i++) {
          const sparkle = document.createElement('div');
          sparkle.style.cssText = `
            position: fixed;
            width: 8px;
            height: 8px;
            background: #FFD700;
            border-radius: 50%;
            left: ${Math.random() * 100}vw;
            top: ${Math.random() * 100}vh;
            z-index: 10002;
            animation: sparkle ${Math.random() * 1 + 0.5}s ease-in-out infinite;
            animation-delay: ${Math.random() * 2}s;
          `;
          document.body.appendChild(sparkle);
          sparkles.push(sparkle);
        }
        return sparkles;
      };

      // Trigger confetti and sparkles
      createConfetti();
      const sparkles = createSparkles();

      // Glow ring pulse (behind popup)
      const glowRing = document.createElement('div');
      glowRing.className = 'popup-glow-ring';
      glowRing.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 400px;
        height: 400px;
        border-radius: 50%;
        z-index: 9998;
        pointer-events: none;
        background: radial-gradient(circle, ${badgeColor}40 0%, transparent 70%);
        animation: glowRingPulse 2s ease-out infinite;
      `;
      document.body.appendChild(glowRing);

      // Clean up ring and sparkles when popup closes
      const cleanupObserver = new MutationObserver(() => {
        if (!document.body.contains(popup)) {
          glowRing.remove();
          sparkles.forEach(s => s.remove());
          cleanupObserver.disconnect();
        }
      });
      cleanupObserver.observe(document.body, { childList: true });

      // Add overlay
      const overlay = document.createElement('div');
      overlay.className = 'popup-overlay';

      overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        animation: fadeIn 0.3s ease-out;
      `;
      overlay.onclick = () => {
        popup.remove();
        overlay.remove();
      };
      document.body.appendChild(overlay);
    };

    /* ---- CHECK FOR TOP PERFORMER ---- */
    const checkTopPerformer = (data) => {
      // Check CGPA rankings - trigger achievement for CGPA >= 9.0
      if (data.cgpa_rank && data.cgpa_rank.cgpa >= 8.5) {
        showAchievement(`CGPA ${data.cgpa_rank.cgpa.toFixed(2)} Achieved!`, "Outstanding Performance", "🎓");
      }

      // Check for high attendance (90%+)
      if (data.tables) {
        const attRecords = Object.values(data.tables).flat().filter(r => r && String(r.source_table || '').toLowerCase().includes('attendance'));
        if (attRecords.length > 0) {
          const summaryRecords = attRecords.filter(r => String(r.source_table || '').toLowerCase() === 'attendance_summary');
          if (summaryRecords.length > 0) {
            const latest = summaryRecords[summaryRecords.length - 1];
            const percentage = latest.percentage ? Number(latest.percentage) : 0;
            if (percentage >= 90) {
              setTimeout(() => showAchievement(`${percentage}% Attendance!`, "Perfect Attendance Streak", "📅"), 2000);
            }
          }
        }
      }

      // Check for NPTEL certificates
      if (data.tables && data.tables['nptel_certificates'] && data.tables['nptel_certificates'].length > 0) {
        setTimeout(() => showAchievement(`${data.tables['nptel_certificates'].length} NPTEL Certificate(s)!`, "Skill Development", "📜"), 2500);
      }

      // Check for job offers
      if (data.tables && data.tables['job_offers'] && data.tables['job_offers'].length > 0) {
        setTimeout(() => showAchievement(`${data.tables['job_offers'].length} Job Offer(s)!`, "Career Success", "💼"), 3000);
      }

      // Check if today is student's birthday
      if (data.student && data.student.date_of_birth) {
        const dob = new Date(data.student.date_of_birth);
        const today = new Date();
        const isBirthday = dob.getDate() === today.getDate() && dob.getMonth() === today.getMonth();

        if (isBirthday) {
          setTimeout(() => {
            showAchievement("🎂 Happy Birthday!", "Wishing you a wonderful day!", "🎉");
            triggerConfetti();
          }, 2000);
        }
      }

      // Check for high CGPA (8.0+) - now triggered by scroll instead of immediate
      // if (data.cgpa_rank && data.cgpa_rank.cgpa >= 8.0) {
      //   setTimeout(() => {
      //     showAchievement("CGPA 8.0+ Achieved!", "Excellent Academic Performance", "🎓");
      //     triggerConfetti();
      //   }, 5000);
      // }

      /* Test assessment results achievement notification removed */
    };

    /* ---- FIREWORK + CONFETTI CELEBRATION (upgraded) ---- */
    const triggerConfetti = () => {
      const fireworkColors = ['#FFD700', '#FF6B6B', '#4ECDC4', '#A78BFA', '#FF8C42', '#38BDF8', '#F472B6'];
      const confettiColors = ['#FFD700', '#FF6B6B', '#4ECDC4', '#A78BFA', '#38BDF8', '#F472B6', '#34D399'];

      // Enhanced screen flash with congratulations text
      const flash = document.createElement('div');
      flash.style.cssText = `position:fixed;inset:0;background:radial-gradient(circle at 50% 40%, rgba(255,255,255,0.85), rgba(255,215,0,0.3) 40%, transparent 70%);z-index:9998;pointer-events:none;animation:screenFlash .6s ease-out forwards;`;
      document.body.appendChild(flash);

      // Add congratulations text
      const congratsText = document.createElement('div');
      congratsText.textContent = '🎉 Congratulations! 🎉';
      const isMobile = window.innerWidth <= 768;
      congratsText.style.cssText = `
        position:fixed;
        top:50%;
        left:50%;
        transform:translate(-50%, -50%);
        font-size:${isMobile ? '2rem' : '3rem'};
        font-weight:bold;
        color:#FFD700;
        text-shadow:0 0 20px rgba(255,215,0,0.8), 0 0 40px rgba(255,215,0,0.5);
        z-index:9999;
        pointer-events:none;
        animation:congratsText 0.8s ease-out forwards;
        white-space:nowrap;
      `;
      document.body.appendChild(congratsText);

      setTimeout(() => {
        flash.remove();
        congratsText.remove();
      }, 3000);

      // Falling confetti rain (rectangles that flutter + rotate)
      const rainCount = 90;
      for (let i = 0; i < rainCount; i++) {
        setTimeout(() => {
          const piece = document.createElement('div');
          const color = confettiColors[Math.floor(Math.random() * confettiColors.length)];
          const isRibbon = Math.random() > 0.5;
          const w = isRibbon ? 6 + Math.random() * 4 : 8 + Math.random() * 6;
          const h = isRibbon ? 14 + Math.random() * 10 : w;
          const startX = Math.random() * 100;
          const duration = 2.6 + Math.random() * 1.8;
          const drift = (Math.random() - 0.5) * 160;
          const spin = 360 + Math.random() * 720 * (Math.random() > 0.5 ? 1 : -1);

          piece.style.cssText = `
            position:fixed; top:-20px; left:${startX}vw;
            width:${w}px; height:${h}px; background:${color};
            border-radius:${isRibbon ? '2px' : '3px'};
            z-index:9997; pointer-events:none; opacity:0.95;
          `;
          piece.style.animation = `confettiFlutter ${duration}s cubic-bezier(.25,.46,.45,.94) forwards`;
          piece.style.setProperty('--drift', `${drift}px`);
          piece.style.setProperty('--spin', `${spin}deg`);
          document.body.appendChild(piece);
          setTimeout(() => piece.remove(), duration * 1000 + 100);
        }, i * 18);
      }

      // Firework bursts with a launch trail
      for (let burst = 0; burst < 6; burst++) {
        setTimeout(() => {
          const centerX = 12 + Math.random() * 76;
          const centerY = 18 + Math.random() * 42;
          const color = fireworkColors[Math.floor(Math.random() * fireworkColors.length)];

          // Launch trail rising to the burst point
          const trail = document.createElement('div');
          trail.style.cssText = `
            position:fixed; left:${centerX}vw; top:100vh; bottom:auto;
            width:3px; height:3px; border-radius:50%;
            background:${color};
            z-index:9997; pointer-events:none;
            animation: fireworkTrail .55s ease-out forwards;
            --targetY:${centerY}vh;
          `;
          document.body.appendChild(trail);
          setTimeout(() => trail.remove(), 600);

          // Explosion after the trail arrives
          setTimeout(() => {
            const centerFlash = document.createElement('div');
            centerFlash.style.cssText = `
              position:fixed; width:90px; height:90px;
              background:radial-gradient(circle, white 0%, ${color}55 40%, transparent 70%);
              border-radius:50%; left:calc(${centerX}vw - 45px); top:calc(${centerY}vh - 45px);
              z-index:9998; pointer-events:none; animation:centerFlash .5s ease-out forwards;
            `;
            document.body.appendChild(centerFlash);
            setTimeout(() => centerFlash.remove(), 500);

            const particleCount = 46;
            for (let i = 0; i < particleCount; i++) {
              const particle = document.createElement('div');
              const angle = (Math.PI * 2 * i) / particleCount + Math.random() * 0.2;
              const velocity = 120 + Math.random() * 220;
              const tx = Math.cos(angle) * velocity;
              const ty = Math.sin(angle) * velocity + 60; // gravity bias downward
              const size = 4 + Math.random() * 6;
              const duration = 1.1 + Math.random() * 0.7;
              const shape = Math.random() > 0.7 ? '2px' : '50%';

              particle.style.cssText = `
                position:fixed; width:${size}px; height:${size}px;
                background:${color}; border-radius:${shape};
                left:${centerX}vw; top:${centerY}vh;
                z-index:9998; pointer-events:none;
                animation: fireworkExplode ${duration}s cubic-bezier(.15,.75,.4,1) forwards;
                --tx:${tx}px; --ty:${ty}px;
              `;
              document.body.appendChild(particle);
              setTimeout(() => particle.remove(), duration * 1000);
            }
          }, 550);
        }, burst * 260);
      }
    };


    /* ---- ATTENDANCE WARNING POPUP ---- */
    const showAttendanceWarning = (attendanceData) => {
      const percentage = attendanceData.percentage;
      const month = attendanceData.month;
      const present = attendanceData.present;
      const total = attendanceData.total;
      const fine = attendanceData.fine;

      if (percentage === '-' || percentage >= 75) return;

      const popup = document.createElement('div');
      popup.className = 'attendance-warning-popup';

      let status = '';
      let statusClass = '';
      let icon = '';
      let message = '';

      if (percentage >= 65) {
        status = 'Condonation';
        statusClass = 'warning';
        icon = '⚠️';
        message = `Your attendance is ${percentage}% which falls in the condonation range (65-75%).`;
      } else {
        status = 'Detention';
        statusClass = 'danger';
        icon = '🚨';
        message = `Your attendance is ${percentage}% which is below the required 65%.`;
      }

      let suggestions = '';
      if (percentage >= 65) {
        suggestions = `
          <div style="margin-top: 15px; text-align: left; background: rgba(0,0,0,0.15); padding: 12px; border-radius: 8px;">
            <div style="font-size: 12px; font-weight: 600; margin-bottom: 8px; opacity: 0.9;">💡 Suggestions:</div>
            <ul style="margin: 0; padding-left: 18px; font-size: 11px; opacity: 0.85; line-height: 1.6;">
              <li>Apply for condonation through proper channels</li>
              <li>Attend all remaining classes without fail</li>
              <li>Submit medical certificates for valid absences</li>
              <li>Meet with your faculty advisor for guidance</li>
            </ul>
          </div>
        `;
      } else {
        suggestions = `
          <div style="margin-top: 15px; text-align: left; background: rgba(0,0,0,0.15); padding: 12px; border-radius: 8px;">
            <div style="font-size: 12px; font-weight: 600; margin-bottom: 8px; opacity: 0.9;">💡 Suggestions:</div>
            <ul style="margin: 0; padding-left: 18px; font-size: 11px; opacity: 0.85; line-height: 1.6;">
              <li>Attend all remaining classes without exception</li>
              <li>Submit medical certificates immediately if applicable</li>
              <li>Apply for attendance improvement programs</li>
              <li>Meet with department head urgently</li>
              <li>Consider attendance recovery options</li>
            </ul>
          </div>
        `;
      }

      const bgColor = statusClass === 'warning' ? 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)' : 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)';

      popup.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        background: ${bgColor};
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        z-index: 10000;
        text-align: center;
        color: white;
        min-width: 300px;
        max-width: 90vw;
        animation: popupBounce 0.5s ease-out forwards;
      `;

      popup.innerHTML = `
        <div style="font-size: 40px; margin-bottom: 12px;">${icon}</div>
        <h2 style="margin: 0 0 10px 0; font-size: 20px;">Attendance Alert</h2>
        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 10px; margin: 15px 0;">
          <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">${month}</div>
          <div style="font-size: 36px; font-weight: 700; margin: 8px 0;">${percentage}%</div>
          <div style="font-size: 12px; opacity: 0.9;">Present: ${present}/${total} | Fine: ₹${fine}</div>
        </div>
        <div style="font-size: 16px; font-weight: 600; margin: 15px 0; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 8px;">
          Status: ${status}
        </div>
        <p style="margin: 12px 0 18px 0; font-size: 13px; opacity: 0.95; line-height: 1.4;">${message}</p>
        ${suggestions}
        <button onclick="this.closest('.attendance-warning-popup').remove(); document.querySelector('.warning-overlay')?.remove();" style="
          background: white;
          color: ${statusClass === 'warning' ? '#F59E0B' : '#EF4444'};
          border: none;
          padding: 8px 24px;
          border-radius: 20px;
          font-size: 14px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.3s ease;
        ">I Understand</button>
      `;

      document.body.appendChild(popup);

      // Add overlay
      const overlay = document.createElement('div');
      overlay.className = 'warning-overlay';
      overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        z-index: 9999;
        animation: fadeIn 0.3s ease-out;
      `;
      overlay.onclick = () => {
        popup.remove();
        overlay.remove();
      };
      document.body.appendChild(overlay);
    };

    /* ---- UNDER CONSTRUCTION MODAL ---- */
    window.showUnderConstruction = (featureName) => {
      // Create modal
      const modal = document.createElement('div');
      modal.className = 'modal fade';
      modal.id = 'underConstructionModal';
      modal.tabIndex = '-1';
      modal.setAttribute('aria-hidden', 'true');

      modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">🚧 Under Construction</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeUnderConstructionModal()"></button>
            </div>
            <div class="modal-body text-center py-4">
              <div class="mb-3">
                <i class="fas fa-tools fa-4x text-warning"></i>
              </div>
              <h4 class="mb-3">${featureName}</h4>
              <p class="text-muted">This feature is currently under development and will be available soon.</p>
              <p class="text-muted small">We're working hard to bring you the best experience!</p>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-primary" onclick="closeUnderConstructionModal()">Got it</button>
            </div>
          </div>
        </div>
      `;

      document.body.appendChild(modal);

      // Show modal
      setTimeout(() => {
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
      }, 10);
    };

    window.closeUnderConstructionModal = () => {
      const modal = document.getElementById('underConstructionModal');
      if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        setTimeout(() => {
          modal.remove();
        }, 300);
      }
    };

    /* ---- RANKING MODAL ---- */
    window.showRankingModal = (type) => {
      // Fetch ranking data via AJAX
      const rollNo = document.querySelector('.info-value-sm.font-mono')?.textContent;

      if (!rollNo) {
        alert('Could not find roll number');
        return;
      }

      const url = `public_search.php?roll_numbers=${rollNo}&ranking_type=${type}`;


      fetch(url)
        .then(response => {
          return response.json();
        })
        .then(data => {

          if (!data.success || !data.data) {
            alert('Failed to load ranking data: ' + (data.message || 'Unknown error'));
            return;
          }

          // Create modal
          const modal = document.createElement('div');
          modal.className = 'modal fade';
          modal.id = 'rankingModal';
          modal.tabIndex = '-1';
          modal.setAttribute('aria-hidden', 'true');

          const title = type === 'overall' ? 'Overall CGPA Ranking' : 'Semester-wise CGPA Ranking';

          // Build ranking table HTML
          let tableHtml = '';
          if (data.data.ranking_list && data.data.ranking_list.length) {
            tableHtml = `
              <table class="table table-striped table-hover" style="font-size:13px;">
                <thead>
                  <tr>
                    <th style="width:60px;">Rank</th>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>CGPA</th>
                    ${type === 'semester' ? '<th>SGPA</th>' : ''}
                  </tr>
                </thead>
                <tbody>
            `;
            data.data.ranking_list.forEach((student, index) => {
              const isCurrentStudent = student.college_roll_number === rollNo;
              const rowClass = isCurrentStudent ? 'table-primary' : '';
              const cgpa = Number(student.cgpa) || 0;
              const sgpa = Number(student.sgpa) || 0;
              tableHtml += `
                <tr class="${rowClass}">
                  <td><strong>${index + 1}</strong></td>
                  <td class="font-mono">${student.college_roll_number}</td>
                  <td>${student.full_name}</td>
                  <td><strong>${cgpa.toFixed(2)}</strong></td>
                  ${type === 'semester' ? `<td>${sgpa ? sgpa.toFixed(2) : '-'}</td>` : ''}
                </tr>
              `;
            });
            tableHtml += `
                </tbody>
              </table>
            `;
          } else {
            tableHtml = '<p class="text-center text-muted">No ranking data available</p>';
          }

          modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">${title}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                  ${tableHtml}
                </div>
              </div>
            </div>
          `;

          document.body.appendChild(modal);

          // Initialize and show modal
          const bootstrapModal = new bootstrap.Modal(modal);
          bootstrapModal.show();

          // Clean up on hide
          modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
          });
        })
        .catch(error => {
          alert('Error loading ranking data: ' + error.message);
        });
    };

    /* ---- CHECK ATTENDANCE WARNING ---- */
    const checkAttendanceWarning = (data) => {
      // Find latest attendance data from the rendered HTML
      const attendanceBadge = document.querySelector('.attendance-badge');
      if (attendanceBadge) {
        const attendanceMonth = attendanceBadge.querySelector('.attendance-month')?.textContent || '';
        const attendanceValue = attendanceBadge.querySelector('.stat-value')?.textContent || '';
        const attendanceP = attendanceValue.replace('%', '');
        const percentage = parseFloat(attendanceP);

        const presentEl = attendanceBadge.querySelectorAll('.stat-value')[1];
        const presentText = presentEl?.textContent || '';
        const [present, total] = presentText.split('/').map(n => parseInt(n.trim()) || 0);

        const fineEl = attendanceBadge.querySelectorAll('.stat-value')[2];
        const fineText = fineEl?.textContent.replace(/[^\d.-]/g, '') || '0';
        const fine = parseInt(fineText) || 0;

        if (!isNaN(percentage)) {
          showAttendanceWarning({
            percentage: percentage,
            month: attendanceMonth,
            present: present,
            total: total,
            fine: fine
          });
        }
      }
    };

    /* ---- MAIN RENDER ---- */
    let html = '';
    if (data.tables) {
      let allRecords = [];
      Object.keys(data.tables).forEach(table => { let records = data.tables[table]; if (!records.length) return; records.forEach(r => { r.source_table = table; allRecords.push(r); }); });

      let subjectGradesByCode = {};
      let subjectCreditsByCode = {};
      let studentResultsBySemester = {};
      try {
        allRecords.filter(r => r && String(r.source_table || '').toLowerCase() === 'subject_results').forEach(r => {
          const code = (r.subject_code !== undefined && r.subject_code !== null) ? String(r.subject_code).trim() : '';
          const grade = (r.grade !== undefined && r.grade !== null) ? String(r.grade).trim() : '';
          const credits = (r.credits !== undefined && r.credits !== null) ? r.credits : null;
          if (!code || !grade) return;
          if (!subjectGradesByCode[code]) subjectGradesByCode[code] = grade;
          if (credits !== null && credits !== '') subjectCreditsByCode[code] = credits;
        });
      } catch (e) { subjectGradesByCode = {}; subjectCreditsByCode = {}; }

      // Merge course info from course_codes table (takes precedence over attendance_subjects)
      if (data.course_info) {
        Object.keys(data.course_info).forEach(code => {
          const info = data.course_info[code];
          if (info) {
            if (info.credits !== null && info.credits !== undefined) {
              subjectCreditsByCode[code] = info.credits;
            }
          }
        });
      }

      // Build subject name mapping from course_codes table (takes precedence) and attendance_subjects
      const subjectNameMap = {};
      try {
        // First, populate from course_codes table
        if (data.course_info) {
          Object.keys(data.course_info).forEach(code => {
            const info = data.course_info[code];
            if (info && info.course_name) {
              subjectNameMap[code] = info.course_name;
            }
          });
        }

        // Then, fallback to attendance_subjects for any missing names
        const attendanceSubjectsRecords = allRecords.filter(r => r && String(r.source_table || '').toLowerCase() === 'attendance_subjects');
        attendanceSubjectsRecords.forEach(r => {
          const code = r.subject_code || '';
          const name = r.subject_name || '';
          if (code && name && !subjectNameMap[code]) {
            subjectNameMap[code] = name;
          }
        });
      } catch (e) { console.error('Error building subject name map:', e); }

      try {
        const srRecords = allRecords.filter(r => r && String(r.source_table || '').toLowerCase() === 'student_results');
        srRecords.forEach(r => {
          const semesterInfo = r.semester_info || r.sem_info || 'Unknown';
          const examInfo = r.exam_info || r.exam || 'Unknown';
          const key = `${semesterInfo}|${examInfo}`;
          if (!studentResultsBySemester[key]) {
            studentResultsBySemester[key] = {
              cgpa: r.cgpa || null,
              sgpa: r.sgpa || null,
              scr: r.scr || null,
              tcr: r.tcr || null,
              backlog: 0,
              semesterInfo: semesterInfo,
              examInfo: examInfo
            };
          }
        });
      } catch (e) { console.error('Error extracting student results:', e); studentResultsBySemester = {}; }

      try {
        allRecords.filter(r => r && String(r.source_table || '').toLowerCase() === 'subject_results').forEach(r => {
          const semesterInfo = r.semester_info || r.sem_info || 'Unknown';
          const examInfo = r.exam_info || r.exam || 'Unknown';
          const key = `${semesterInfo}|${examInfo}`;
          const grade = (r.grade !== undefined && r.grade !== null) ? String(r.grade).trim().toUpperCase() : '';
          const subjectCode = r.subject_code || r.code || '';
          const subjectName = subjectNameMap[subjectCode] || r.subject_name || r.subject || r.name || 'Unknown Subject';
          if (/^F$/.test(grade) || /^F\b/.test(grade)) {
            if (studentResultsBySemester[key]) {
              studentResultsBySemester[key].backlog = (studentResultsBySemester[key].backlog || 0) + 1;
              if (!studentResultsBySemester[key].backlogSubjects) {
                studentResultsBySemester[key].backlogSubjects = [];
              }
              studentResultsBySemester[key].backlogSubjects.push({
                name: subjectName,
                code: subjectCode
              });
            } else {
              studentResultsBySemester[key] = {
                cgpa: null,
                sgpa: null,
                backlog: 1,
                backlogSubjects: [{ name: subjectName, code: subjectCode }],
                semesterInfo: semesterInfo,
                examInfo: examInfo
              };
            }
          }
          // Track passed subjects for each semester
          if (!/^F$/.test(grade) && !/^F\b/.test(grade) && subjectCode) {
            if (!studentResultsBySemester[key]) {
              studentResultsBySemester[key] = {
                cgpa: null,
                sgpa: null,
                backlog: 0,
                passedSubjects: [],
                semesterInfo: semesterInfo,
                examInfo: examInfo
              };
            }
            if (!studentResultsBySemester[key].passedSubjects) {
              studentResultsBySemester[key].passedSubjects = [];
            }
            studentResultsBySemester[key].passedSubjects.push(subjectCode);
          }
        });
      } catch (e) { console.error('Error extracting subject results:', e); }

      // Calculate total backlogs across all semesters (unique subjects)
      let totalBacklogs = 0;
      let clearedBacklogs = 0;
      let pendingBacklogs = 0;
      let backlogSubjectsList = [];
      const subjectFailureCounts = {};

      // Collect all passed subject codes across all semesters
      const allPassedSubjectCodes = new Set();
      Object.values(studentResultsBySemester).forEach(semData => {
        if (semData.passedSubjects && semData.passedSubjects.length > 0) {
          semData.passedSubjects.forEach(code => {
            allPassedSubjectCodes.add(code);
          });
        }
      });

      Object.values(studentResultsBySemester).forEach(semData => {
        if (semData.backlogSubjects && semData.backlogSubjects.length > 0) {
          semData.backlogSubjects.forEach(subj => {
            if (!subjectFailureCounts[subj.code]) {
              subjectFailureCounts[subj.code] = { ...subj, count: 0, cleared: false };
              totalBacklogs++;
            }
            subjectFailureCounts[subj.code].count++;
            // Mark as cleared if passed in any semester
            if (allPassedSubjectCodes.has(subj.code)) {
              subjectFailureCounts[subj.code].cleared = true;
            }
          });
        }
      });
      backlogSubjectsList = Object.values(subjectFailureCounts);

      // Count cleared and pending
      backlogSubjectsList.forEach(subj => {
        if (subj.cleared) {
          clearedBacklogs++;
        } else {
          pendingBacklogs++;
        }
      });

      // Initialize student info variables
      let rollNo = 'N/A';
      let studentName = 'N/A';
      let fatherName = 'N/A';
      let studentPhoto = '';
      let studentInitials = 'ST';
      let studentAvatarHtml = '';
      let dateOfBirth = '-';

      if (allRecords.length > 0) {
        const firstRecord = allRecords[0];
        rollNo = firstRecord.roll_no || 'N/A';
        studentName = firstRecord.student_name || 'N/A';
        fatherName = firstRecord.father_name || 'N/A';
        studentPhoto = data.student ? (data.student.student_photo || data.student.photo_path || data.student.profile_photo || data.student.photo || '') : '';
        studentInitials = String(studentName || rollNo || 'ST')
          .split(/\s+/)
          .filter(Boolean)
          .slice(0, 2)
          .map(part => part.charAt(0).toUpperCase())
          .join('') || 'ST';
        studentAvatarHtml = studentPhoto
          ? `<img src="${escapeHtml(studentPhoto)}" alt="${escapeHtml(studentName)} photo" class="student-avatar-img" loading="lazy" onclick="showPhotoModal('${escapeHtml(studentPhoto)}', '${escapeHtml(rollNo)}')" style="cursor: pointer;" onerror="this.parentElement.classList.add('student-avatar-fallback'); this.remove();">`
          : `<span class="student-avatar-initials" onclick="redirectToProfileUpdate('${escapeHtml(rollNo)}')" style="cursor: pointer;">${escapeHtml(studentInitials)}</span>`;

        // Format DOB to show only day and month (confidential - hide year)
        if (data.student && data.student.date_of_birth) {
          const dob = new Date(data.student.date_of_birth);
          if (!isNaN(dob.getTime())) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            dateOfBirth = `${dob.getDate()} ${months[dob.getMonth()]}`;
          }
        }

        // Calculate overall attendance summary
        let overallAttendance = { present: 0, total: 0, percentage: '-' };
        let latestMonthAttendance = { present: 0, total: 0, percentage: '-', month: '-', fine: 0 };

        const attRecords = allRecords.filter(r => r && String(r.source_table || '').toLowerCase().includes('attendance'));
        if (attRecords.length > 0) {
          const summaryRecords = attRecords.filter(r => String(r.source_table || '').toLowerCase() === 'attendance_summary');
          if (summaryRecords.length > 0) {
            // Sort by date to find actual latest month
            const sessionToKey = (s) => {
              const str = String(s || '').trim().toLowerCase();
              if (!str) return null;
              const monthMap = { jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6, jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12 };
              // Extract month
              const monthMatch = str.match(/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i);
              if (!monthMatch) return null;
              const m = monthMap[monthMatch[1].toLowerCase()];
              if (!m) return null;
              // Extract year - look for 4-digit year
              const yearMatch = str.match(/(20\d{2})/);
              if (!yearMatch) return null;
              const year = parseInt(yearMatch[1], 10);
              if (!Number.isFinite(year) || year < 2000) return null;
              return (year * 100) + m;
            };

            summaryRecords.sort((a, b) => {
              const keyA = sessionToKey(a.session_name);
              const keyB = sessionToKey(b.session_name);
              if (keyA === null && keyB === null) return 0;
              if (keyA === null) return 1;
              if (keyB === null) return -1;
              return keyB - keyA; // Descending order (latest first)
            });

            const latest = summaryRecords[0]; // Now the actual latest by date
            overallAttendance = {
              present: latest.total_present || 0,
              total: latest.total_classes || 0,
              percentage: latest.percentage || '-'
            };

            // Latest month data
            const monthLabel = latest.session_name || '-';
            const semesterInfo = latest.semester || null;
            const academicYear = latest.academic_year || null;

            // Format month label (replicate attendanceMonthLabel logic)
            const formatMonthLabel = (session) => {
              const value = String(session || '').trim();
              const encode = text => String(text ?? '').replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
              const names = { jan: 'January', feb: 'February', mar: 'March', apr: 'April', may: 'May', jun: 'June', jul: 'July', aug: 'August', sep: 'September', sept: 'September', oct: 'October', nov: 'November', dec: 'December' };
              const tokens = value.toLowerCase().split(/[^a-z0-9]+/).filter(Boolean);
              const monthCodes = [...value.toLowerCase().matchAll(/jan|feb|mar|apr|may|jun|jul|aug|sept|sep|oct|nov|dec/g)].map(match => match[0]);
              const months = monthCodes.map(code => names[code]);
              const yearMatch = value.match(/(20\d{2}|(?:^|[_-])(\d{2})(?:$|[_-]))/);
              const year = yearMatch ? (yearMatch[1].length === 4 ? yearMatch[1] : `20${yearMatch[2]}`) : '';
              if (!months.length) return encode(value || '-');
              const monthText = months.length > 1 ? `${months[0]}-${months[months.length - 1]}` : months[0];
              return encode(year ? `${monthText} ${year}` : monthText);
            };

            // Format semester label
            const formatSemesterLabel = (sem, ay) => {
              if (!sem) return '';
              const semMapping = {
                '1-1': 'I Yr I Sem',
                '1-2': 'I Yr II Sem',
                '2-1': 'II Yr I Sem',
                '2-2': 'II Yr II Sem',
                '3-1': 'III Yr I Sem',
                '3-2': 'III Yr II Sem',
                '4-1': 'IV Yr I Sem',
                '4-2': 'IV Yr II Sem'
              };
              const semLabel = semMapping[sem] || sem;
              return ay ? `${semLabel}, ${ay}` : semLabel;
            };

            latestMonthAttendance = {
              present: latest.total_present || 0,
              total: latest.total_classes || 0,
              percentage: latest.percentage || '-',
              month: formatMonthLabel(monthLabel),
              semester: formatSemesterLabel(semesterInfo, academicYear),
              fine: Math.max(0, (latest.total_classes || 0) - (latest.total_present || 0))
            };
          }
        }

        const attPct = overallAttendance.percentage !== '-' ? Number(overallAttendance.percentage) : null;
        const attClass = attPct === null ? 'secondary' : (attPct >= 75 ? 'success' : (attPct >= 65 ? 'warning text-dark' : 'danger'));

        const latestPct = latestMonthAttendance.percentage !== '-' ? Number(latestMonthAttendance.percentage) : null;
        const latestClass = latestPct === null ? 'secondary' : (latestPct >= 75 ? 'success' : (latestPct >= 65 ? 'warning text-dark' : 'danger'));
        const latestStatus = latestPct === null ? '-' : (latestPct >= 75 ? 'Safe' : (latestPct >= 65 ? 'Condonation' : 'Detention'));

        // Get best semester ranking for prominent display
        let bestSemesterRankBadge = '';
        let overallTopperBadge = '';

        // Check if student is in top 10 overall OR has CGPA >= 9.0
        if (data.cgpa_rank && data.cgpa_rank.overall_rank && data.cgpa_rank.cgpa && (data.cgpa_rank.overall_rank <= 10 || data.cgpa_rank.cgpa >= 9.0)) {
          const overallRank = data.cgpa_rank.overall_rank;
          const overallTotal = data.cgpa_rank.overall_total;
          const cgpa = data.cgpa_rank.cgpa.toFixed(2);

          let rankIcon = '';
          let rankClass = 'bg-gradient-topper';
          let rankLabel = 'TOPPER';

          if (overallRank === 1) {
            rankIcon = '🏆';
            rankClass = 'bg-gradient-topper';
            rankLabel = 'OVERALL TOPPER';
          } else if (overallRank === 2) {
            rankIcon = '🥈';
            rankClass = 'bg-gradient-silver';
            rankLabel = '2ND RANK';
          } else if (overallRank === 3) {
            rankIcon = '🥉';
            rankClass = 'bg-gradient-bronze';
            rankLabel = '3RD RANK';
          } else if (overallRank <= 10) {
            rankIcon = '⭐';
            rankClass = 'bg-gradient-star';
            rankLabel = `TOP ${overallRank}`;
          } else if (cgpa >= 9.0) {
            rankIcon = '🌟';
            rankClass = 'bg-gradient-star';
            rankLabel = `HIGH ACHIEVER`;
          }

          overallTopperBadge = `<div class="mb-2"><span class="badge ${rankClass}" style="font-size:12px; padding:8px 14px; cursor:pointer; text-decoration:none; transition:all 0.3s ease; animation: pulseGlow 2s infinite;" onclick="showRankingModal('overall')" title="Click to view full ranking">${rankIcon} ${rankLabel} - CGPA ${cgpa} #${overallRank}/${overallTotal}</span></div>`;
        }

        if (data.cgpa_rank && data.cgpa_rank.semester_rank && data.cgpa_rank.cgpa && data.cgpa_rank.semester_info) {
          const semesterRank = data.cgpa_rank.semester_rank;
          const semesterTotal = data.cgpa_rank.semester_total;
          const cgpa = data.cgpa_rank.cgpa.toFixed(2);
          const sgpa = data.cgpa_rank.sgpa ? data.cgpa_rank.sgpa.toFixed(2) : null;
          const semesterInfo = data.cgpa_rank.semester_info;

          let rankIcon = '';
          let rankClass = 'bg-primary';
          let rankLabel = '';

          if (semesterRank === 1) {
            rankIcon = '🥇';
            rankClass = 'bg-gradient-topper';
            rankLabel = 'SEMESTER TOPPER';
          } else if (semesterRank === 2) {
            rankIcon = '🥈';
            rankClass = 'bg-gradient-silver';
            rankLabel = '2ND RANK';
          } else if (semesterRank === 3) {
            rankIcon = '🥉';
            rankClass = 'bg-gradient-bronze';
            rankLabel = '3RD RANK';
          } else if (semesterRank <= 10) {
            rankIcon = '⭐';
            rankClass = 'bg-gradient-star';
            rankLabel = `TOP ${semesterRank}`;
          } else if (cgpa >= 9.0) {
            rankIcon = '🌟';
            rankClass = 'bg-gradient-star';
            rankLabel = `HIGH ACHIEVER`;
          } else {
            rankIcon = '';
            rankClass = 'bg-primary';
            rankLabel = '';
          }

          const sgpaText = sgpa ? ` | SGPA ${sgpa}` : '';
          if (semesterRank <= 10 || cgpa >= 9.0) {
            bestSemesterRankBadge = `<div class="mb-2"><span class="badge ${rankClass}" style="font-size:11px; padding:6px 10px; cursor:pointer; text-decoration:none; transition:all 0.2s ease;" onclick="showRankingModal('semester')" title="Click to view full ranking">${rankIcon} ${rankLabel} - CGPA ${cgpa}${sgpaText} (${semesterInfo}) #${semesterRank}/${semesterTotal}</span></div>`;
          }
        }

        html += `<div class="student-info-header mb-3" data-zone="${latestPct !== null ? (latestPct >= 75 ? 'safe' : (latestPct >= 65 ? 'condonation' : 'detention')) : ''}">
          <div class="row g-3">
            <!-- Left Column: Avatar and Attendance Badge -->
            <div class="col-auto">
              <div class="d-flex flex-column gap-2">
                <div class="student-avatar">
                  ${studentAvatarHtml}
                </div>
                <div class="attendance-badge ${latestClass}">
                  <div class="attendance-badge-header">
                    <span class="attendance-month">${latestMonthAttendance.month}</span>
                    <span class="attendance-status">${latestStatus}</span>
                  </div>
                  <div class="attendance-stats">
                    <div class="attendance-stat">
                      <span class="stat-label">ATT</span>
                      <span class="stat-value">${latestMonthAttendance.percentage}%</span>
                    </div>
                    <div class="attendance-stat">
                      <span class="stat-label">P/T</span>
                      <span class="stat-value">${latestMonthAttendance.present}/${latestMonthAttendance.total}</span>
                    </div>
                    <div class="attendance-stat">
                      <span class="stat-label">FINE</span>
                      <span class="stat-value">Rs ${latestMonthAttendance.fine}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Right Column: Student Details -->
            <div class="col">
              <!-- Ranking Badges -->
              <div class="d-flex flex-wrap gap-2 mb-3">
                ${overallTopperBadge}
                ${bestSemesterRankBadge}
              </div>
              
              <!-- Student Info Grid -->
              <div class="student-info-grid">
                <div class="student-info-item">
                  <span class="info-label-sm">ROLL NO</span>
                  <span class="info-value-sm font-mono">${rollNo}</span>
                </div>
                <div class="student-info-item">
                  <span class="info-label-sm">NAME</span>
                  <span class="info-value-sm">${studentName}</span>
                </div>
                <div class="student-info-item">
                  <span class="info-label-sm">FATHER</span>
                  <span class="info-value-sm">${fatherName}</span>
                </div>
                <div class="student-info-item">
                  <span class="info-label-sm">DOB</span>
                  <span class="info-value-sm">${dateOfBirth}</span>
                </div>
                <div class="student-info-item">
                  <span class="info-label-sm">BACKLOGS</span>
                  <span class="info-value-sm">${clearedBacklogs > 0 ? `<span class="text-success">${clearedBacklogs} Cleared</span>` : ''}${clearedBacklogs > 0 && pendingBacklogs > 0 ? ', ' : ''}${pendingBacklogs > 0 ? `<span class="text-danger">${pendingBacklogs} Pending</span>` : ''}${totalBacklogs === 0 ? '<span class="text-success">0</span>' : ''}</span>
                </div>
              </div>
              
              ${backlogSubjectsList.length > 0 ? `
              <div class="mt-2">
                <div class="backlog-subjects-list">
                  <small class="text-muted" style="font-size:10px;">Backlog Subjects:</small>
                  <div class="d-flex flex-wrap gap-1 mt-1">
                    ${backlogSubjectsList.map(subj => `
                      <span class="badge ${subj.cleared ? 'bg-success' : 'bg-danger'}" style="font-size:9px; padding:2px 6px;">${escapeHtml(subj.name)}${subj.code ? ` (${escapeHtml(subj.code)})` : ''}${subj.count > 1 ? ` [${subj.count}]` : ''}${subj.cleared ? ' ✓' : ''}</span>
                    `).join('')}
                  </div>
                </div>
              </div>` : ''}
              
              <!-- Job Offers / CGPA Rank / Smart Score Tabs -->
              ${(data.tables && data.tables['job_offers'] && data.tables['job_offers'].length) || data.cgpa_rank || data.smart_score ? `
              <div class="mt-3">
                <ul class="nav nav-tabs nav-tabs-sm" id="studentInfoTabs" role="tablist" style="font-size:11px; border-bottom:1px solid var(--border);">
                  ${data.tables && data.tables['job_offers'] && data.tables['job_offers'].length ? `<li class="nav-item" role="presentation">
                    <button class="nav-link active" id="job-offers-tab" data-bs-toggle="tab" data-bs-target="#job-offers" type="button" role="tab" aria-controls="job-offers" aria-selected="true">
                      <i class="fas fa-briefcase me-1"></i>Job Offers
                    </button>
                  </li>` : ''}
                  ${data.cgpa_rank ? `<li class="nav-item" role="presentation">
                    <button class="nav-link ${!(data.tables && data.tables['job_offers'] && data.tables['job_offers'].length) ? 'active' : ''}" id="cgpa-rank-tab" data-bs-toggle="tab" data-bs-target="#cgpa-rank" type="button" role="tab" aria-controls="cgpa-rank" aria-selected="${!(data.tables && data.tables['job_offers'] && data.tables['job_offers'].length) ? 'true' : 'false'}">
                      <i class="fas fa-trophy me-1"></i>CGPA Rank
                    </button>
                  </li>` : ''}
                  ${!data.cgpa_rank && data.smart_score ? `<li class="nav-item" role="presentation">
                    <button class="nav-link active" id="smart-score-tab" data-bs-toggle="tab" data-bs-target="#smart-score-only" type="button" role="tab" aria-controls="smart-score-only" aria-selected="true">
                      <i class="fas fa-star me-1"></i>Smart Score
                    </button>
                  </li>` : ''}
                </ul>
                <div class="tab-content mt-2" id="studentInfoTabsContent">
                  ${data.tables && data.tables['job_offers'] && data.tables['job_offers'].length ? `<div class="tab-pane fade show active" id="job-offers" role="tabpanel" aria-labelledby="job-offers-tab">
                    ${renderJobOffersCompact(data.tables['job_offers'], data.job_offers_rank, data.cgpa_rank)}
                  </div>` : ''}
                  ${data.cgpa_rank ? `<div class="tab-pane fade ${!(data.tables && data.tables['job_offers'] && data.tables['job_offers'].length) ? 'show active' : ''}" id="cgpa-rank" role="tabpanel" aria-labelledby="cgpa-rank-tab">
                    ${renderCGPARank(data.cgpa_rank)}
                    ${renderSmartScore(data.smart_score)}
                  </div>` : ''}
                  ${!data.cgpa_rank && data.smart_score ? `<div class="tab-pane fade show active" id="smart-score-only" role="tabpanel">
                    ${renderSmartScore(data.smart_score)}
                  </div>` : ''}
                </div>
              </div>` : ''}
            </div>
          </div>
        </div>`;
      }

      let tableGroups = {};
      let midmarksRecords = null;
      let midmarksByCode = null;
      Object.keys(data.tables).forEach(table => {
        let records = data.tables[table];
        if (!records.length) return;
        if (table === 'midmarks_subject_marks') { midmarksRecords = records; return; }
        if (table === 'nptel_certificates') { return; }
        let cols = Object.keys(records[0]);
        let dataCols = cols.filter(col => !isHiddenColumn(col));
        const isAttendanceTable = table.includes('attendance') || /^(dec|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov)/.test(table);
        const isResultsTable = table.includes('student_results') || table.includes('subject_results');
        const category = isAttendanceTable ? 'attendance' : (isResultsTable ? 'results' : 'other');
        let matchedGroup = null;
        for (let groupKey of Object.keys(tableGroups)) {
          let existingGroup = tableGroups[groupKey];
          let existingCols = existingGroup.columns.filter(col => !isHiddenColumn(col));
          let commonCols = dataCols.filter(col => existingCols.includes(col));
          let matchRatio = commonCols.length / Math.max(dataCols.length, existingCols.length);
          let hasRollNo = dataCols.includes('roll_no') && existingCols.includes('roll_no');
          if (existingGroup.category === category && (category === 'attendance' || category === 'results' || matchRatio >= 0.5 || (hasRollNo && commonCols.length >= 2))) { matchedGroup = groupKey; break; }
        }
        if (matchedGroup) { tableGroups[matchedGroup].tables.push(table); tableGroups[matchedGroup].allRecords.push(...records); let allCols = new Set([...tableGroups[matchedGroup].columns, ...cols]); tableGroups[matchedGroup].columns = Array.from(allCols); }
        else { let dataColsSignature = dataCols.sort().join(','); tableGroups[dataColsSignature] = { tables: [table], allRecords: [...records], columns: cols, category: category }; }
      });

      /* ---- SUBJECT RESULTS / GRADES ---- */
      const subjectResultsRecords = data.tables['subject_results'] || [];
      if (subjectResultsRecords && subjectResultsRecords.length) {
        html += renderSubjectResultsCard(subjectResultsRecords, subjectNameMap, studentResultsBySemester, studentName, rollNo);
      }

      if (Object.keys(tableGroups).length > 0) {
        if (midmarksRecords && midmarksRecords.length) {
          midmarksByCode = {};
          midmarksRecords.forEach(r => {
            const code = (r && r.subject_code !== undefined && r.subject_code !== null) ? String(r.subject_code).trim() : '';
            if (!code) return;
            const up = r && r.upload_id !== undefined && r.upload_id !== null ? Number(r.upload_id) : null;
            if (!midmarksByCode[code]) { midmarksByCode[code] = r; return; }
            const prev = midmarksByCode[code];
            const prevUp = prev && prev.upload_id !== undefined && prev.upload_id !== null ? Number(prev.upload_id) : null;
            if (prevUp === null || up === null) { if (prevUp === null && up !== null) midmarksByCode[code] = r; return; }
            if (up > prevUp) midmarksByCode[code] = r;
          });
        }
        Object.keys(tableGroups).forEach(groupKey => {
          let group = tableGroups[groupKey];
          let allRecords = group.allRecords;
          let cols = group.columns;
          const collapseKey = String(groupKey).replace(/[^a-zA-Z0-9_-]/g, '_');
          if (group.category === 'attendance') {
            html += renderAttendanceCard(group, groupKey, midmarksByCode, subjectGradesByCode, subjectCreditsByCode, studentResultsBySemester, subjectNameMap);
            return;
          }
          if (group.category === 'results' || group.tables.includes('student_results') || group.tables.includes('subject_results')) {
            return;
          }
          const isStudentResultsGroup = group.tables.includes('student_results');
          let sortedTables = group.tables.sort((a, b) => {
            let aMonth, aYear, bMonth, bYear;
            if (/^(dec|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov)/.test(a)) { aMonth = a.substring(0, 3).toLowerCase(); aYear = parseInt(a.substring(a.length - 4)); }
            else { let mm = a.match(/(dec|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov)/i), ym = a.match(/(20\d{2})/); aMonth = mm ? mm[1].toLowerCase() : 'zzz'; aYear = ym ? parseInt(ym[1]) : 9999; }
            if (/^(dec|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov)/.test(b)) { bMonth = b.substring(0, 3).toLowerCase(); bYear = parseInt(b.substring(b.length - 4)); }
            else { let mm = b.match(/(dec|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov)/i), ym = b.match(/(20\d{2})/); bMonth = mm ? mm[1].toLowerCase() : 'zzz'; bYear = ym ? parseInt(ym[1]) : 9999; }
            if (aYear !== bYear) return aYear - bYear;
            let mo = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            return mo.indexOf(aMonth) - mo.indexOf(bMonth);
          });
          let tableNames = sortedTables.map(t => formatSourceTableLabel(t)).join(' + ');

          html += `<div class="card data-card mb-3"><div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #000 !important; border: 1px solid #000; border-bottom: 2px solid #000; font-size: 12px; padding: 12px 16px; font-weight: 600;"><div class="d-flex flex-column" style="color: #000 !important;"><div class="result-section-title" style="color: #000 !important;"><i class="fas fa-table me-2 text-primary" style="color: #000 !important;"></i>${tableNames}</div></div><div class="d-flex gap-2"><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#section-${collapseKey}" aria-expanded="false" style="border: 1px solid #000; color: #000 !important;"><i class="fas fa-chevron-down" style="color: #000 !important;"></i></button><button class="btn btn-sm btn-outline-secondary" onclick="toggleTranspose('${groupKey}')" style="border: 1px solid #000; color: #000 !important;"><i class="fas fa-exchange-alt me-1" style="color: #000 !important;"></i>Transpose</button></div></div><div class="collapse" id="section-${collapseKey}"><div class="table-responsive"><div id="normal-table-${groupKey}"><table class="table table-sm table-striped table-hover table-minimal"><thead><tr>${(() => { const visibleCols = cols.filter(col => !isHiddenColumn(col)); const hasRollNo = visibleCols.includes('roll_no'); const rest = visibleCols.filter(c => c !== 'roll_no'); const ordered = hasRollNo ? ['roll_no', '__month', ...rest] : ['__month', ...visibleCols]; return ordered.map(c => { if (c === '__month') return '<th>MONTH</th>'; return `<th>${c.replaceAll('_', ' ').toUpperCase()}</th>`; }).join(''); })()}</tr></thead><tbody>${allRecords.map(r => `<tr>${(() => { const visibleCols = cols.filter(col => !isHiddenColumn(col)); const hasRollNo = visibleCols.includes('roll_no'); const rest = visibleCols.filter(c => c !== 'roll_no'); const ordered = hasRollNo ? ['roll_no', '__month', ...rest] : ['__month', ...visibleCols]; return ordered.map(c => { if (c === '__month') { return `<td>${getRecordMonthLabel(r)}</td>`; } const value = r[c]; if (value && value !== 'N/A' && (typeof value === 'string' ? value.trim() : value)) { return `<td>${value}</td>`; } return `<td>-</td>`; }).join(''); })()}</tr>`).join('')}</tbody></table></div><div id="transpose-table-${groupKey}" style="display:none;"><table class="table table-sm table-striped table-hover table-minimal"><thead><tr><th>ATTRIBUTE</th>${allRecords.map((r, index) => `<th>RECORD ${index + 1}</th>`).join('')}</tr></thead><tbody><tr><td><strong>MONTH</strong></td>${allRecords.map(r => `<td>${getRecordMonthLabel(r)}</td>`).join('')}</tr>${cols.filter(col => !isHiddenColumn(col)).map(col => `<tr><td><strong>${col.replaceAll('_', ' ').toUpperCase()}</strong></td>${allRecords.map(r => { const value = r[col]; if (value && value !== 'N/A' && (typeof value === 'string' ? value.trim() : value)) { return `<td>${value}</td>`; } return `<td>-</td>`; }).join('')}</tr>`).join('')}</tbody></table></div></div></div></div>`;
        });
      } else {
        Object.keys(data.tables).forEach(table => { let records = data.tables[table]; if (!records.length) return; let cols = Object.keys(records[0]); html += `<div class="card data-card mb-4"><div class="card-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #000 !important; border: 1px solid #000; border-bottom: 2px solid #000; font-size: 12px; padding: 12px 16px; font-weight: 600;">${table.replaceAll('_', ' ').toUpperCase()}</div><div class="table-responsive"><table class="table table-sm table-striped table-hover table-minimal"><thead><tr>${cols.filter(col => !isHiddenColumn(col)).map(c => `<th>${c.replaceAll('_', ' ').toUpperCase()}</th>`).join('')}</tr></thead><tbody>${records.map(r => `<tr>${cols.filter(col => !isHiddenColumn(col)).map(c => { const value = r[c]; if (value && value !== 'N/A' && (typeof value === 'string' ? value.trim() : value)) { return `<td>${value}</td>`; } return `<td>-</td>`; }).join('')}</tr>`).join('')}</tbody></table></div></div>`; });
      }

      /* ---- NPTEL CERTIFICATES ---- */
      const nptelRecords = data.tables['nptel_certificates'] || [];
      if (nptelRecords && nptelRecords.length) {
        html += renderNptelCertificatesCard(nptelRecords);
      }
    }

    targetEl.innerHTML = maskPvp(html);

    // Initialize attendance animations after render
    if (typeof animateAttendanceVisuals === 'function') {
      animateAttendanceVisuals(targetEl);
    }

    // Initialize scroll fade hints for tables (inline to avoid module import issues)
    document.querySelectorAll('.subject-attendance-table-shell').forEach(shell => {
      const checkScroll = () => {
        const atEnd = shell.scrollLeft + shell.clientWidth >= shell.scrollWidth - 2;
        shell.classList.toggle('scrolled-end', atEnd);
      };
      shell.addEventListener('scroll', checkScroll);
      checkScroll();
    });

    // Set up intersection observer for ranking badge scroll trigger
    const rankingBadge = document.querySelector('.student-info-header .mb-2 .badge');
    if (rankingBadge && data.cgpa_rank && data.cgpa_rank.cgpa >= 8.0) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            // Trigger confetti when badge scrolls into view
            setTimeout(() => {
              showAchievement("CGPA 8.0+ Achieved!", "Excellent Academic Performance", "🎓");
              triggerConfetti();
            }, 500);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
      observer.observe(rankingBadge);
    }

    // Check for top performer and show congratulations
    checkTopPerformer(data);

    // Check attendance warning
    setTimeout(() => checkAttendanceWarning(data), 500);

    // Initialize study buddy mascot
    if (typeof initMascot === 'function') {
      initMascot(data);
    }
  } catch (error) {
    const targetEl = document.querySelector(targetSelector);
    if (targetEl) {
      targetEl.innerHTML = `<div class="alert alert-danger text-center">Error displaying student information.<br><small>${error.message}</small></div>`;
    }
  }
}

// Photo modal function
function showPhotoModal(photoPath, rollNumber) {
  const timestamp = new Date().getTime();
  document.getElementById('modalPhoto').src = photoPath + '?t=' + timestamp;

  // Set update profile link with roll number
  if (rollNumber) {
    document.getElementById('updateProfileBtn').href = 'student_profile.php?roll=' + encodeURIComponent(rollNumber);
    document.getElementById('downloadPdfBtn').href = 'api/download_pdf.php?roll=' + encodeURIComponent(rollNumber);
  }

  try {
    const photoModal = new bootstrap.Modal(document.getElementById('photoModal'));
    photoModal.show();
  } catch (e) {
    if (typeof $ !== 'undefined') {
      $('#photoModal').modal('show');
    }
  }
}

// Redirect to profile update page
function redirectToProfileUpdate(rollNumber) {
  if (rollNumber) {
    window.location.href = 'student_profile.php?roll=' + encodeURIComponent(rollNumber);
  } else {
    window.location.href = 'student_profile.php';
  }
}

// Copy semester table to clipboard
function copySemesterTable(tableKey, semesterLabel, academicYear) {
  // Find the table by traversing from the button's semester block
  const btn = event.target.closest('button');
  const semesterBlock = btn.closest('.semester-attendance-block');

  if (!semesterBlock) {
    alert('Table not found. Please try again.');
    return;
  }

  // Find the normal table (hidden) which contains GRADE and midmarks rows
  // The tableKey might be complex, so try to find by ending with the key
  let normalDiv = semesterBlock.querySelector('#normal-table-' + tableKey);
  let table = null;
  let wasHidden = false;

  // If exact match not found, try finding by ending with the key
  if (!normalDiv) {
    const allNormalDivs = semesterBlock.querySelectorAll('[id^="normal-table-"]');
    for (let div of allNormalDivs) {
      if (div.id.endsWith(tableKey)) {
        normalDiv = div;
        break;
      }
    }
  }

  // If normal div exists, temporarily show it to access the table
  if (normalDiv) {
    wasHidden = normalDiv.style.display === 'none' || normalDiv.style.display === '';
    if (wasHidden) {
      normalDiv.style.display = 'block';
    }
    table = normalDiv.querySelector('table');
  }

  // If still not found, try transpose table
  if (!table) {
    table = semesterBlock.querySelector('#transpose-table-' + tableKey + ' table');
  }

  // If still not found, try any table in the block
  if (!table) {
    table = semesterBlock.querySelector('table');
  }

  if (!table) {
    alert('Table not found. Please try again.');
    return;
  }

  // Extract table data
  let text = `${semesterLabel}`;
  if (academicYear) {
    text += ` (${academicYear})`;
  }
  text += '\n\n';

  // Get headers
  const headers = table.querySelectorAll('thead th');
  const headerTexts = Array.from(headers).map(th => th.textContent.trim());
  text += headerTexts.join('\t') + '\n';

  // Get rows
  const rows = table.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    const firstCell = cells[0];

    // Check if this is a GRADE or midmarks row (by checking first cell)
    const labelSpan = firstCell?.querySelector('span[style*="font-weight:800"]');
    const rowLabel = labelSpan?.textContent.trim() || '';

    let cellTexts = [];

    if (rowLabel === 'GRADE') {
      // For GRADE row, extract badge content from each cell
      cellTexts = Array.from(cells).map(td => {
        const badge = td.querySelector('.badge')?.textContent.trim() || '-';
        return badge;
      });
    } else if (['DES1', 'OBJ1', 'ASMT1', 'DES2', 'OBJ2', 'ASMT2', 'MID TOTAL'].includes(rowLabel)) {
      // For midmarks rows, extract cell content from each cell
      cellTexts = Array.from(cells).map(td => {
        return td.textContent.trim() || '-';
      });
    } else {
      // Regular attendance row - extract subject info and midmarks from first cell
      cellTexts = Array.from(cells).map((td, index) => {
        if (index === 0) {
          // First column - extract subject code, name, midmarks, and grade
          const subjectCode = td.querySelector('.subject-code')?.textContent.trim() || '';
          const subjectName = td.querySelector('.sub-name')?.textContent.trim() || '';

          // Extract midmarks and grade from the cell text
          const cellText = td.textContent.trim();

          // Extract grade (look for single letter grade like A, B, C, etc.)
          const gradeMatch = cellText.match(/\b([A-Z])\b/);
          const grade = gradeMatch ? gradeMatch[1] : '';

          // Extract mid total
          const midMatch = cellText.match(/Mid:\s*([\d.]+)/);
          const midTotal = midMatch ? midMatch[1] : '';

          // Extract mid percentage
          const midPctMatch = cellText.match(/Mid:[\d.]+\s*(\d+)%/);
          const midPct = midPctMatch ? midPctMatch[1] : '';

          // Extract DES1, OBJ1, ASMT1, DES2, OBJ2, ASMT2
          const des1Match = cellText.match(/DES1\s*([\d.]+)/);
          const des1 = des1Match ? des1Match[1] : '';

          const obj1Match = cellText.match(/OBJ1\s*([\d.]+)/);
          const obj1 = obj1Match ? obj1Match[1] : '';

          const asmt1Match = cellText.match(/ASMT1\s*([\d.]+)/);
          const asmt1 = asmt1Match ? asmt1Match[1] : '';

          const des2Match = cellText.match(/DES2\s*([\d.]+)/);
          const des2 = des2Match ? des2Match[1] : '';

          const obj2Match = cellText.match(/OBJ2\s*([\d.]+)/);
          const obj2 = obj2Match ? obj2Match[1] : '';

          const asmt2Match = cellText.match(/ASMT2\s*([\d.]+)/);
          const asmt2 = asmt2Match ? asmt2Match[1] : '';

          let subjectInfo = `${subjectCode} - ${subjectName}`;
          if (grade) subjectInfo += ` | Grade: ${grade}`;
          if (midTotal) subjectInfo += ` | Mid: ${midTotal}`;
          if (midPct) subjectInfo += ` (${midPct}%)`;

          // Add midmarks details if available
          const midDetails = [];
          if (des1) midDetails.push(`DES1:${des1}`);
          if (obj1) midDetails.push(`OBJ1:${obj1}`);
          if (asmt1) midDetails.push(`ASMT1:${asmt1}`);
          if (des2) midDetails.push(`DES2:${des2}`);
          if (obj2) midDetails.push(`OBJ2:${obj2}`);
          if (asmt2) midDetails.push(`ASMT2:${asmt2}`);

          if (midDetails.length > 0) {
            subjectInfo += ` | ${midDetails.join(' | ')}`;
          }

          return subjectInfo;
        }

        // Other columns - extract attendance value and percentage
        const attendanceValue = td.querySelector('.attendance-cell-value')?.textContent.trim() || '';
        const percentage = td.querySelector('.pct-line')?.textContent.trim() || '';

        if (attendanceValue && percentage) {
          return `${attendanceValue} (${percentage})`;
        }
        return td.textContent.trim().replace(/\s+/g, ' ');
      });
    }

    text += cellTexts.join('\t') + '\n';
  });

  // Copy to clipboard
  navigator.clipboard.writeText(text).then(() => {
    // Restore hidden state if we temporarily showed the table
    if (wasHidden && normalDiv) {
      normalDiv.style.display = 'none';
    }

    // Show success message
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-success');

    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.classList.remove('btn-success');
      btn.classList.add('btn-outline-secondary');
    }, 2000);
  }).catch(err => {
    // Restore hidden state on error
    if (wasHidden && normalDiv) {
      normalDiv.style.display = 'none';
    }
    console.error('Failed to copy:', err);
    alert('Failed to copy table to clipboard');
  });
}

/* ---- COPY SUBJECT GRADES ---- */
const copySubjectGrades = () => {
  const card = document.getElementById('subjectGradesCard');
  if (!card) {
    alert('Subject grades card not found');
    return;
  }

  // Extract text content from the card
  let text = '';

  // Get student info from header
  const headerText = card.querySelector('.card-header')?.textContent || '';
  text += headerText.trim() + '\n\n';

  // Get each semester section
  const semesterSections = card.querySelectorAll('.mb-4');
  semesterSections.forEach(section => {
    const semesterTitle = section.querySelector('.section-kicker')?.textContent || '';
    text += semesterTitle.trim() + '\n';

    // Get badges (SGPA, CGPA, Credits)
    const badges = section.querySelectorAll('.badge');
    badges.forEach(badge => {
      text += badge.textContent.trim() + ' ';
    });
    text += '\n';

    // Get table data
    const table = section.querySelector('table');
    if (table) {
      const rows = table.querySelectorAll('tr');
      rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const cellTexts = Array.from(cells).map(cell => {
          // Get grade and credits
          const grade = cell.querySelector('.badge')?.textContent || '';
          const credits = cell.querySelector('small')?.textContent || '';
          const cellContent = cell.textContent.trim();

          if (grade) {
            return `${grade} ${credits}`.trim();
          }
          return cellContent;
        });
        text += cellTexts.join('\t') + '\n';
      });
    }
    text += '\n';
  });

  // Copy to clipboard
  navigator.clipboard.writeText(text).then(() => {
    alert('Subject grades copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy:', err);
    alert('Failed to copy grades to clipboard');
  });
};
