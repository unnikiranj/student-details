<!-- ======== HERO ======== -->
<div class="hero" id="heroSection">
  <div class="hero-card">

    <!-- Accent ambient glow rendered by ::before -->
    <div class="hero-top">
      <div class="hero-copy">
        <div class="hero-kicker">
          <i class="fas fa-shield-halved text-primary"></i> Real-time Academic Lookup
        </div>
        <div class="hero-icon">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <h1 class="hero-title">Student Details<br><span>Portal</span></h1>
        <p class="hero-subtitle">Instant student records, live attendance, mid marks, university grades, placements & verified certificates.</p>
      </div>

      <div class="hero-panel">
        <form id="heroSearchForm" class="position-relative" autocomplete="off">
          <div class="hero-search-box">
            <i class="fas fa-search hero-search-icon"></i>
            <label for="heroSearchInput" class="visually-hidden">Roll number or student name</label>
            <input 
              id="heroSearchInput" 
              placeholder="Enter roll number (e.g. 21501A0501) or name…" 
              autocomplete="off" 
              autocapitalize="characters" 
              spellcheck="false"
              aria-label="Search by roll number or student name"
            >
            <button type="button" class="search-clear-btn d-none" id="searchClearBtn" title="Clear search" aria-label="Clear search">
              <i class="fas fa-times"></i>
            </button>
            <div class="search-shortcut-hint" title="Press Ctrl+K or / to search">
              <span class="kbd-badge">Ctrl</span><span class="kbd-badge">K</span>
            </div>
            <button type="submit" class="hero-submit-btn">
              <i class="fas fa-arrow-right me-1"></i><span>Search</span>
            </button>
          </div>

          <!-- Live Recent Searches & Suggestions Dropdown -->
          <div class="search-suggestions-dropdown d-none" id="searchSuggestionsDropdown">
            <div class="dropdown-header d-flex justify-content-between align-items-center">
              <span><i class="fas fa-clock-rotate-left me-1"></i> Recent Searches</span>
              <button type="button" class="btn-clear-history" id="clearSearchHistoryBtn">Clear all</button>
            </div>
            <div class="recent-searches-list" id="recentSearchesList"></div>
            <div class="dropdown-footer">
              <span><i class="fas fa-keyboard me-1"></i> Press <kbd>Enter</kbd> to search or <kbd>Esc</kbd> to close</span>
            </div>
          </div>
        </form>

        <!-- Quick Sample Search Chips -->
        <div class="quick-chips-wrap mt-2">
          <span class="quick-chip-label"><i class="fas fa-bolt text-warning me-1"></i>Quick:</span>
          <div class="d-flex flex-wrap gap-1 align-items-center" id="quickSearchChips">
            <button type="button" class="quick-chip" data-query="21501A0501">
              <i class="fas fa-user-graduate"></i> Demo Student
            </button>
            <a href="leaderboard.php" class="quick-chip">
              <i class="fas fa-trophy text-warning"></i> Top Rankers
            </a>
            <a href="leaderboard.php?ranking=weighted" class="quick-chip">
              <i class="fas fa-star text-warning"></i> SmartScore
            </a>
            <a href="placement.php" class="quick-chip">
              <i class="fas fa-briefcase text-success"></i> Placements
            </a>
          </div>
        </div>

        <div class="hero-metrics">
          <div class="hero-metric">
            <strong><i class="fas fa-bolt text-primary me-1"></i>Instant</strong>
            <span>Roll / Name search</span>
          </div>
          <div class="hero-metric">
            <strong><i class="fas fa-chart-line text-success me-1"></i>Live</strong>
            <span>Attendance &amp; Marks</span>
          </div>
          <div class="hero-metric">
            <strong><i class="fas fa-file-pdf text-danger me-1"></i>Export</strong>
            <span>PDF Grade Report</span>
          </div>
        </div>
      </div>
    </div>

    <div class="feature-pills">
      <div class="pill"><i class="fas fa-bolt"></i> Instant Results</div>
      <div class="pill"><i class="fas fa-calendar-check"></i> Attendance Tracking</div>
      <div class="pill"><i class="fas fa-chart-bar"></i> Mid Marks Breakdown</div>
      <div class="pill"><i class="fas fa-graduation-cap"></i> SGPA/CGPA Analysis</div>
      <div class="pill"><i class="fas fa-filter"></i> Smart Multi-Filters</div>
      <div class="pill"><i class="fas fa-file-pdf"></i> Official PDF Export</div>
    </div>

    <!-- Quick Access Section Cards -->
    <div class="row g-3 mt-2">
      <!-- Top CGPA Quick Access -->
      <div class="col-12 col-md-6">
        <div class="quick-access-card top-cgpa-card h-100">
          <div class="card-inner">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="card-title-highlight text-primary mb-0">
                <i class="fas fa-trophy me-2 text-warning"></i>Top CGPA Students
              </h6>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Academic</span>
            </div>
            <p class="quick-card-desc">Quickly browse university high-achievers and overall semester toppers.</p>
            <div class="d-flex flex-wrap gap-2">
              <a href="leaderboard.php?limit=10" class="btn btn-sm btn-quick-pill">
                <i class="fas fa-medal me-1 text-warning"></i>Top 10
              </a>
              <a href="leaderboard.php?limit=20" class="btn btn-sm btn-quick-pill">
                <i class="fas fa-medal me-1 text-secondary"></i>Top 20
              </a>
              <a href="leaderboard.php?limit=50" class="btn btn-sm btn-quick-pill">
                <i class="fas fa-medal me-1 text-warning"></i>Top 50
              </a>
              <a href="leaderboard.php" class="btn btn-sm btn-quick-pill btn-quick-pill-primary">
                <i class="fas fa-arrow-right me-1"></i>View All
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Smart Score Quick Access -->
      <div class="col-12 col-md-6">
        <div class="quick-access-card top-smartscore-card h-100">
          <div class="card-inner">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="card-title-highlight text-warning mb-0">
                <i class="fas fa-star me-2 text-warning"></i>Smart Score Ranking
              </h6>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle">60% Smart + 40% CGPA</span>
            </div>
            <p class="quick-card-desc">Weighted intelligence ranking combining academics with technical skills.</p>
            <div class="d-flex flex-wrap gap-2">
              <a href="leaderboard.php?ranking=weighted&limit=10" class="btn btn-sm btn-quick-pill btn-quick-pill-warning">
                <i class="fas fa-medal me-1"></i>Top 10
              </a>
              <a href="leaderboard.php?ranking=weighted&limit=20" class="btn btn-sm btn-quick-pill btn-quick-pill-warning">
                <i class="fas fa-medal me-1"></i>Top 20
              </a>
              <a href="leaderboard.php?ranking=weighted&limit=50" class="btn btn-sm btn-quick-pill btn-quick-pill-warning">
                <i class="fas fa-medal me-1"></i>Top 50
              </a>
              <a href="leaderboard.php?ranking=weighted" class="btn btn-sm btn-quick-pill btn-quick-pill-warning-solid">
                <i class="fas fa-list-ol me-1"></i>View All
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Birthdays -->
    <div class="today-birthdays-section mt-3" id="todayBirthdaysSection" style="display:none;">
      <div class="celebration-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="mb-0 text-pink fw-bold">
            <span class="celebrate-emoji">🎂</span> Today's Birthdays
          </h6>
          <span class="badge bg-pink text-white" id="birthdayDate"></span>
        </div>
        <div id="birthdaysList" class="d-flex flex-wrap gap-2"></div>
      </div>
    </div>

    <!-- Home Tabs (Search / Multi-Filters) -->
    <div class="home-tabs-wrap mt-4">
      <ul class="nav nav-tabs custom-nav-tabs" id="homeTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-search" data-bs-toggle="tab" data-bs-target="#tabPaneSearch" type="button" role="tab">
            <i class="fas fa-magnifying-glass me-1"></i>Direct Search
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-filters" data-bs-toggle="tab" data-bs-target="#tabPaneFilters" type="button" role="tab">
            <i class="fas fa-sliders me-1"></i>Smart Multi-Filters
          </button>
        </li>
      </ul>
      <div class="tab-content tab-surface">
        <div class="tab-pane fade show active" id="tabPaneSearch" role="tabpanel">
          <div class="search-instructions-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="small text-muted">
              <i class="fas fa-info-circle text-primary me-1"></i>
              Enter any student roll number or full/partial name in the search bar above.
            </span>
            <span class="badge bg-light text-dark border">
              <i class="fas fa-keyboard me-1 text-muted"></i> Shortcut: <kbd>/</kbd> or <kbd>Ctrl+K</kbd>
            </span>
          </div>
        </div>
        
        <div class="tab-pane fade" id="tabPaneFilters" role="tabpanel">
          <form id="filterForm" class="row g-3 filter-form">
            <div class="col-12 col-md-6">
              <label for="filterSemester" class="form-label fw-semibold small">Semester Info</label>
              <select class="form-select" id="filterSemester" required><option value="">Loading semesters…</option></select>
            </div>
            <div class="col-12 col-md-6">
              <label for="filterDepartment" class="form-label fw-semibold small">Department</label>
              <select class="form-select" id="filterDepartment" disabled><option value="">Select semester first</option></select>
            </div>
            <div class="col-6 col-md-3">
              <label for="filterCgpaMin" class="form-label fw-semibold small">CGPA Min</label>
              <input type="number" step="0.01" min="0" max="10" class="form-control" id="filterCgpaMin" placeholder="e.g. 7.5" autocomplete="off">
            </div>
            <div class="col-6 col-md-3">
              <label for="filterCgpaMax" class="form-label fw-semibold small">CGPA Max</label>
              <input type="number" step="0.01" min="0" max="10" class="form-control" id="filterCgpaMax" placeholder="e.g. 10.0" autocomplete="off">
            </div>
            <div class="col-6 col-md-3">
              <label for="filterSgpaMin" class="form-label fw-semibold small">SGPA Min</label>
              <input type="number" step="0.01" min="0" max="10" class="form-control" id="filterSgpaMin" placeholder="e.g. 7.0" autocomplete="off">
            </div>
            <div class="col-6 col-md-3">
              <label for="filterSgpaMax" class="form-label fw-semibold small">SGPA Max</label>
              <input type="number" step="0.01" min="0" max="10" class="form-control" id="filterSgpaMax" placeholder="e.g. 10.0" autocomplete="off">
            </div>
            <div class="col-12 col-md-6">
              <label for="filterFCount" class="form-label fw-semibold small">Backlogs / F Count</label>
              <select class="form-select" id="filterFCount">
                <option value="">Any Backlog Status</option>
                <option value="0">0 (All Clear ✨)</option>
                <option value="1">1 Backlog</option>
                <option value="2">2 Backlogs</option>
                <option value="3plus">3+ Backlogs</option>
              </select>
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end gap-2">
              <button type="submit" class="btn-apply-filter flex-grow-1">
                <i class="fas fa-filter me-1"></i> Apply Filters
              </button>
              <button type="reset" class="btn btn-outline-secondary" id="resetFilterBtn">
                <i class="fas fa-rotate-left"></i>
              </button>
            </div>
          </form>
          <div id="filterError" class="alert alert-danger mt-3 d-none"></div>
          <div id="filterResults" class="mt-3" style="display:none;">
            <div id="filterCount" class="small text-muted mb-2 fw-semibold"></div>
            <div class="table-responsive">
              <table class="table table-sm table-striped table-hover mb-0 table-minimal">
                <thead>
                  <tr>
                    <th>Roll no</th><th>Name</th><th>Dept</th><th>Semester</th>
                    <th class="text-end">SGPA</th><th class="text-end">CGPA</th>
                    <th class="text-end">F count</th><th></th>
                  </tr>
                </thead>
                <tbody id="filterResultsBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>