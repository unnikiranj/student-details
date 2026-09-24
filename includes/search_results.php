<!-- ======== SEARCH RESULTS ======== -->
<div id="searchResults" style="display:none" class="mt-2">
  <div class="container-fluid py-2 px-2 px-md-3">
    
    <!-- Screen Toolbar -->
    <div class="search-results-top-bar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 bg-white p-3 rounded-4 shadow-sm border">
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="resetToSearchHome()">
          <i class="fas fa-arrow-left me-1"></i> New Search
        </button>
        <span class="text-muted d-none d-sm-inline">|</span>
        <span class="text-muted small fw-semibold d-none d-sm-inline">
          <i class="fas fa-id-card me-1 text-primary"></i> Full Student Academic Dossier
        </span>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm" onclick="downloadStudentReportPdf()" id="downloadFullPdfBtn" title="Download Complete PDF Report">
          <i class="fas fa-file-pdf me-1"></i> Download Report PDF
        </button>
        <button type="button" class="btn btn-light btn-sm rounded-pill border px-3" onclick="shareStudentProfile()" id="shareProfileBtn" title="Copy shareable link">
          <i class="fas fa-share-nodes me-1 text-primary"></i> <span class="d-none d-md-inline">Share</span>
        </button>
      </div>
    </div>

    <div id="studentInfo"></div>
  </div>
</div>