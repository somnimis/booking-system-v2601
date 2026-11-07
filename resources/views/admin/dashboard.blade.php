@extends('layouts.admin')

@section('title', 'Booking Dashboard')
<style>
  /* Signatory / System Admin stat cards — flat, no animation */
  .rstat-card {
    position: relative;
    background: #fff;
    border: 1px solid var(--bs-border-color, #e2e8f0);
    border-radius: 10px;
    padding: 16px 14px;
    cursor: pointer;
    overflow: hidden;
  }

  .rstat-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: #2563eb;
    opacity: 0.85;
  }

  .rstat-card.rstat-subset::before {
    background: #f59e0b;
    opacity: 0.7;
  }

  .rstat-card.rstat-subset.rstat-overdue::before {
    background: #ef4444;
    opacity: 0.6;
  }

  .rstat-card.rstat-success::before {
    background: #10b981;
  }

  .rstat-card.rstat-system::before {
    background: #6366f1;
  }

  .rstat-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
    color: #1e293b;
    margin-bottom: 6px;
  }

  @media (min-width: 768px) {
    .rstat-value {
      font-size: 2rem;
    }
  }

  .rstat-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 3px;
  }

  .rstat-subtext {
    font-size: 0.68rem;
    color: #94a3b8;
    line-height: 1.25;
  }
</style>
@section('content')
  <link rel="stylesheet" href="{{ asset('css/public/dashboard.css') }}">
  <main id="main">
    <div class="dashboard-wrap">

      <!-- ── Dashboard Header ── -->
      <div class="dashboard-header">
        <div class="dashboard-header-inner">
          <div>
            <h1 class="dashboard-header-title">Your Dashboard</h1>
            <p class="dashboard-header-sub">Manage Reservations & Resources</p>
          </div>
          <a href="/admin/reservations/create" class="btn btn-light btn-sm fw-semibold">
            <i class="bi bi-plus-circle"></i> Add Form
          </a>
        </div>
      </div>

      <!-- ── Skeleton State ── -->
      <div id="skeletonState">
        <div class="row g-3 mb-4">
          <div class="col-md-3 col-6">
            <div class="skeleton skeleton-stat"></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="skeleton skeleton-stat"></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="skeleton skeleton-stat"></div>
          </div>
          <div class="col-md-3 col-6">
            <div class="skeleton skeleton-stat"></div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-6">
            <div class="section-card p-3">
              <div class="skeleton skeleton-title"></div>
              <div class="skeleton skeleton-item"></div>
              <div class="skeleton skeleton-item"></div>
              <div class="skeleton skeleton-item"></div>
            </div>
            <div class="section-card p-3">
              <div class="skeleton skeleton-title"></div>
              <div class="skeleton skeleton-feedback"></div>
              <div class="skeleton skeleton-feedback"></div>
              <div class="skeleton skeleton-feedback"></div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="section-card p-3">
              <div class="skeleton skeleton-title"></div>
              <div class="skeleton skeleton-item"></div>
              <div class="skeleton skeleton-item"></div>
              <div class="skeleton skeleton-item"></div>
            </div>
            <div class="section-card p-3">
              <div class="skeleton skeleton-title"></div>
              <div class="skeleton skeleton-activity"></div>
              <div class="skeleton skeleton-activity"></div>
              <div class="skeleton skeleton-activity"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Dashboard Content ── -->
      <div id="dashboardContent" style="display: none;">

        {{-- Scope clarifier — the stat cards below are personal to this admin --}}
        <div id="statsHeader" class="mb-2" style="display: none;">
          <h6 class="mb-0 fw-semibold" style="color:#1e293b;">Your Workload</h6>
          <small class="text-muted" style="font-size:0.72rem;">Counts reflect requests assigned to you only.</small>
        </div>

        {{-- Stat cards — role-aware. Populated by renderInitialDashboard() in JS. --}}
        <div class="row g-3 mb-4" id="statsRow" style="display: none;">
          {{-- Rendered dynamically based on data.role_stats in JS --}}
        </div>

        <div class="row">
          <!-- Left Column -->
          <div class="col-lg-6">

            <!-- Needs My Action -->
            <div class="section-card">
              <div class="section-header">
                <h6 class="section-title">
                  <i class="bi bi-lightning-charge"></i> Needs My Action
                </h6>
                <a href="{{ url('/admin/actionable-requests') }}" class="view-all-link">View all <i
                    class="bi bi-arrow-right"></i></a>
              </div>

              <div class="section-body" id="actionablePreviewList">
                <!-- Dynamic content -->
              </div>
            </div>

            <!-- Activity Timeline (moved to left column) -->
            <div class="section-card">
              <div class="section-header">
                <h6 class="section-title"><i class="bi bi-activity"></i> Activity Timeline</h6>
                <span class="small text-muted" id="activityTotal" style="font-size:0.75rem;"></span>
              </div>
              <div class="section-body" id="activityTimelineList">
                <!-- Dynamic content -->
              </div>

              <!-- Pagination -->
              <div id="activityPagination" class="section-footer" style="display: none;">
                <div class="d-flex align-items-center gap-3 flex-wrap w-100">
                  <span class="small text-muted bg-light px-3 py-1 rounded" id="activityInfo"
                    style="font-size:0.72rem;"></span>
                  <div class="d-flex gap-2 ms-auto">
                    <button class="btn btn-sm btn-primary" id="activityPrevBtn" onclick="loadActivityPrevPage()" disabled>
                      <i class="bi bi-chevron-left"></i> Prev
                    </button>
                    <button class="btn btn-sm btn-primary" id="activityNextBtn" onclick="loadActivityNextPage()">
                      Next <i class="bi bi-chevron-right"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Right Column -->
          <div class="col-lg-6">

            <!-- Today's Events -->
            <div class="section-card">
              <div class="section-header">
                <h6 class="section-title"><i class="bi bi-calendar-event"></i> Today's Events</h6>
                <span class="small text-muted" id="todayDate" style="font-size:0.75rem;"></span>
              </div>
              <div class="section-body" id="reservationsList">
                <!-- Dynamic content -->
              </div>

              <!-- Today's Events Pagination -->
              <div id="todayEventsPagination" class="section-footer" style="display: none;">
                <div class="d-flex align-items-center gap-3 flex-wrap w-100">
                  <span class="small text-muted bg-light px-3 py-1 rounded" id="todayEventsInfo"
                    style="font-size:0.72rem;"></span>
                  <div class="d-flex gap-2 ms-auto">
                    <button class="btn btn-sm btn-primary" id="todayEventsPrevBtn" onclick="loadTodayEventsPrevPage()"
                      disabled>
                      <i class="bi bi-chevron-left"></i> Prev
                    </button>
                    <button class="btn btn-sm btn-primary" id="todayEventsNextBtn" onclick="loadTodayEventsNextPage()">
                      Next <i class="bi bi-chevron-right"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Latest Feedback (moved to right column) -->
            <div class="section-card">
              <div class="section-header">
                <h6 class="section-title"><i class="bi bi-chat-dots"></i> Latest User Feedback</h6>
                <a href="{{ url('/admin/user-feedback') }}" class="view-all-link">View all <i
                    class="bi bi-arrow-right"></i></a>
              </div>
              <div class="section-body" id="feedbackList">
                <!-- Dynamic content -->
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </main>
@endsection

@section('scripts')
  <script>
    // Dashboard static data
    let dashboardData = null;

    // Activity Timeline pagination state
    let currentActivityPage = 1;
    let totalActivityPages = 1;
    let totalActivityItems = 0;
    let activityTimelineData = null;

    // Today's Events pagination state
    let currentTodayEventsPage = 1;
    let totalTodayEventsPages = 1;
    let totalTodayEventsItems = 0;
    let todayEventsData = null;

    document.addEventListener('DOMContentLoaded', function () {
      // Load initial static data FIRST (fast)
      loadInitialDashboardData();

      // Then lazy load the paginated sections
      loadTodayEventsPage(1);
      loadActivityTimelinePage(1);
      loadActionablePreview();
    });

    // ========== INITIAL LOAD (Static content only - FAST) ==========
    function loadInitialDashboardData() {
      const token = localStorage.getItem('adminToken');

      if (!token) {
        console.error('No authentication token found');
        return;
      }

      fetch(`/api/admin/dashboard-data`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        credentials: 'include'
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            dashboardData = data.data;
            renderInitialDashboard();
            document.getElementById('skeletonState').style.display = 'none';
            document.getElementById('dashboardContent').style.display = 'block';
          } else {
            showError('Failed to load dashboard data');
          }
        })
        .catch(error => {
          console.error('Error loading dashboard:', error);
          showError('Network error occurred');
        });
    }

    // ========== TODAY'S EVENTS (Lazy Loaded with Pagination) ==========
    function loadTodayEventsPage(page) {
      const token = localStorage.getItem('adminToken');

      if (!token) return;

      const container = document.getElementById('reservationsList');

      // Show loading state only on first load or when manually refreshing
      if (!todayEventsData || page !== currentTodayEventsPage) {
        container.innerHTML = `
                                            <div class="empty-state">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                <p class="mt-2">Loading events...</p>
                                            </div>`;
      }

      fetch(`/api/admin/today-events?page=${page}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        credentials: 'include'
      })
        .then(response => response.json())
        .then(response => {
          if (response.success) {
            todayEventsData = response.data;
            currentTodayEventsPage = todayEventsData.current_page;
            totalTodayEventsPages = todayEventsData.last_page;
            totalTodayEventsItems = todayEventsData.total;
            renderTodayEvents();
          } else {
            console.error('Failed to load today\'s events:', response.message);
            container.innerHTML = `
                                                    <div class="empty-state">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        <p>Failed to load events</p>
                                                        <button class="btn btn-sm btn-primary mt-2" onclick="loadTodayEventsPage(1)">Retry</button>
                                                    </div>`;
          }
        })
        .catch(error => {
          console.error('Error loading today\'s events:', error);
          container.innerHTML = `
                                                <div class="empty-state">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <p>Network error loading events</p>
                                                    <button class="btn btn-sm btn-primary mt-2" onclick="loadTodayEventsPage(1)">Retry</button>
                                                </div>`;
        });
    }

    // ========== TODAY'S EVENTS RENDERING ==========
    function renderTodayEvents() {
      const container = document.getElementById('reservationsList');

      // Get real events from API
      const events = todayEventsData?.data || [];

      if (events.length === 0) {
        container.innerHTML = `<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No reservations today</p></div>`;
        document.getElementById('todayEventsPagination').style.display = 'none';
        return;
      }

      // Show pagination if needed
      if (totalTodayEventsPages > 1) {
        const paginationContainer = document.getElementById('todayEventsPagination');
        const infoSpan = document.getElementById('todayEventsInfo');
        const prevBtn = document.getElementById('todayEventsPrevBtn');
        const nextBtn = document.getElementById('todayEventsNextBtn');

        if (infoSpan) {
          infoSpan.textContent = `Page ${currentTodayEventsPage} of ${totalTodayEventsPages} (${totalTodayEventsItems} total)`;
        }

        if (prevBtn) {
          prevBtn.disabled = currentTodayEventsPage === 1;
        }

        if (nextBtn) {
          nextBtn.disabled = currentTodayEventsPage === totalTodayEventsPages;
        }

        if (paginationContainer) paginationContainer.style.display = 'flex';
      } else {
        document.getElementById('todayEventsPagination').style.display = 'none';
      }

      // Render events
      container.innerHTML = events.map(r => `
                  <div class="reservation-item" onclick="window.location.href='/admin/requisition/${r.request_id}'">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <div class="flex-grow-1">
                        <div class="item-name">${escapeHtml(r.requester_name)}</div>
                        <div class="item-sub">${escapeHtml(r.event_title)}</div>
                        <div class="item-meta">
                          <i class="bi bi-clock"></i>${r.time}
                          <span class="text-light">·</span>
                          <i class="bi bi-geo-alt"></i>
                          ${r.locations.map(loc => `<span class="location-chip">${escapeHtml(loc)}</span>`).join(' ')}
                        </div>
                      </div>
                      <i class="bi bi-chevron-right text-primary align-self-center" style="font-size:0.8rem; opacity:0.5;"></i>
                    </div>
                  </div>
                `).join('');
    }

    function loadTodayEventsNextPage() {
      if (currentTodayEventsPage < totalTodayEventsPages) {
        loadTodayEventsPage(currentTodayEventsPage + 1);
      }
    }

    function loadTodayEventsPrevPage() {
      if (currentTodayEventsPage > 1) {
        loadTodayEventsPage(currentTodayEventsPage - 1);
      }
    }

    // ========== ACTIVITY TIMELINE (Lazy Loaded with Pagination) ==========
    function loadActivityTimelinePage(page) {
      const token = localStorage.getItem('adminToken');

      if (!token) return;

      const container = document.getElementById('activityTimelineList');

      // Show loading state only on first load or when manually refreshing
      if (!activityTimelineData || page !== currentActivityPage) {
        container.innerHTML = `
                                            <div class="empty-state">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                                <p class="mt-2">Loading activities...</p>
                                            </div>`;
      }

      fetch(`/api/admin/activity-timeline?page=${page}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        credentials: 'include'
      })
        .then(response => response.json())
        .then(response => {
          if (response.success) {
            activityTimelineData = response.data;
            currentActivityPage = activityTimelineData.current_page;
            totalActivityPages = activityTimelineData.last_page;
            totalActivityItems = activityTimelineData.total;
            renderActivityTimeline();
          } else {
            console.error('Failed to load activity timeline:', response.message);
            container.innerHTML = `
                                                    <div class="empty-state">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        <p>Failed to load activities</p>
                                                        <button class="btn btn-sm btn-primary mt-2" onclick="loadActivityTimelinePage(1)">Retry</button>
                                                    </div>`;
          }
        })
        .catch(error => {
          console.error('Error loading activity timeline:', error);
          container.innerHTML = `
                                                <div class="empty-state">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <p>Network error loading activities</p>
                                                    <button class="btn btn-sm btn-primary mt-2" onclick="loadActivityTimelinePage(1)">Retry</button>
                                                </div>`;
        });
    }

    // ========== NEEDS MY ACTION PREVIEW (Lazy Loaded) ==========
    /**
     * Fetch up to 3 actionable requisitions for this admin.
     * Uses the same endpoint as /admin/actionable-requests so both surfaces
     * always display identical data.
     */
    function loadActionablePreview() {
      const token = localStorage.getItem('adminToken');
      const container = document.getElementById('actionablePreviewList');

      if (!token || !container) return;

      container.innerHTML = `
                      <div class="empty-state">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <p class="mt-2">Loading...</p>
                      </div>`;

      fetch(`/api/admin/requisitions/actionable?per_page=3&sort_order=asc`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        credentials: 'include'
      })
        .then(response => response.json())
        .then(response => {
          if (response.success) {
            renderActionablePreview(response.data || []);
          } else {
            container.innerHTML = `
                            <div class="empty-state">
                              <i class="bi bi-exclamation-triangle"></i>
                              <p>Failed to load</p>
                              <button class="btn btn-sm btn-primary mt-2" onclick="loadActionablePreview()">Retry</button>
                            </div>`;
          }
        })
        .catch(error => {
          console.error('Error loading actionable preview:', error);
          container.innerHTML = `
                          <div class="empty-state">
                            <i class="bi bi-exclamation-triangle"></i>
                            <p>Network error</p>
                            <button class="btn btn-sm btn-primary mt-2" onclick="loadActionablePreview()">Retry</button>
                          </div>`;
        });
    }

    function renderActionablePreview(items) {
      const container = document.getElementById('actionablePreviewList');

      if (!items || items.length === 0) {
        container.innerHTML = `
                        <div class="empty-state">
                          <i class="bi bi-check-circle"></i>
                          <p>Nothing needs your action</p>
                          <small>You're all caught up!</small>
                        </div>`;
        return;
      }

      container.innerHTML = items.map(item => {
        const requesterName = escapeHtml(item.requester?.name || 'Unknown');
        const organization = escapeHtml(item.requester?.organization || '');
        const eventTitle = escapeHtml(item.event_title || 'No Title');
        const scheduleStart = escapeHtml(item.schedule?.start_date || '');

        return `
                        <div class="pending-item" onclick="goToRequest(${item.request_id})">
                          <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                              <div class="item-name">${requesterName}</div>
                              <div class="item-sub">${eventTitle}</div>
                              <div class="item-meta">
                                <i class="bi bi-building"></i>${organization}
                                <span class="text-light">·</span>
                                <i class="bi bi-calendar3"></i>${scheduleStart}
                              </div>
                            </div>
                          </div>
                        </div>`;
      }).join('');
    }

    function renderActivityTimeline() {
      const container = document.getElementById('activityTimelineList');

      if (!activityTimelineData || activityTimelineData.data.length === 0) {
        container.innerHTML = `
                                            <div class="empty-state">
                                                <i class="bi bi-activity"></i>
                                                <p>No recent activity</p>
                                                <small>Comments will appear here</small>
                                            </div>`;
        document.getElementById('activityPagination').style.display = 'none';
        return;
      }

      // Update pagination UI
      document.getElementById('activityTotal').textContent = `(${totalActivityItems} total)`;
      document.getElementById('activityInfo').textContent = `Page ${currentActivityPage} of ${totalActivityPages}`;
      document.getElementById('activityPrevBtn').disabled = currentActivityPage === 1;
      document.getElementById('activityNextBtn').disabled = currentActivityPage === totalActivityPages;
      document.getElementById('activityPagination').style.display = totalActivityPages > 1 ? 'flex' : 'none';

      container.innerHTML = activityTimelineData.data.map(activity => `
                                        <div class="activity-item" onclick="goToRequest(${activity.request_id})">
                                            <div class="d-flex gap-2">
                                                <div class="activity-icon"><i class="bi bi-chat-dots"></i></div>
                                                <div class="flex-grow-1">
                                                    <div class="activity-text">
                                                        <strong>${escapeHtml(activity.admin_name)}</strong>
                                                        ${activity.action_type} in
                                                        <strong class="request-link">Request #${activity.request_number}</strong>
                                                        <div class="item-sub mt-1">${escapeHtml(activity.event_title)}</div>
                                                    </div>
                                                    <div class="activity-comment">
                                                        <i class="bi bi-quote me-1"></i>${escapeHtml(activity.comment)}
                                                    </div>
                                                    <div class="activity-time"><i class="bi bi-clock me-1"></i>${activity.time_ago}</div>
                                                </div>
                                                <i class="bi bi-chevron-right align-self-center" style="color:var(--text-light); font-size:0.78rem;"></i>
                                            </div>
                                        </div>`).join('');
    }

    function loadActivityNextPage() {
      if (currentActivityPage < totalActivityPages) {
        loadActivityTimelinePage(currentActivityPage + 1);
      }
    }

    function loadActivityPrevPage() {
      if (currentActivityPage > 1) {
        loadActivityTimelinePage(currentActivityPage - 1);
      }
    }

    // ========== INITIAL RENDER (Static content only) ==========
    function renderInitialDashboard() {
      const ops = dashboardData.ops_stats || {};
      const roleStats = dashboardData.role_stats;

      // Role-aware stat cards
      renderRoleStatCards(roleStats);

      // Satisfaction card data (used only in system-admin set)
      const today = new Date();
      document.getElementById('todayDate').textContent = today.toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
      });

      renderFeedback();
    }

    /**
     * Render the top stat row based on the admin's role.
     * role_stats is null for Inventory Manager (role 4) → row stays hidden.
     */
    function renderRoleStatCards(roleStats) {

      const row = document.getElementById('statsRow');
      if (!roleStats) { row.style.display = 'none'; return; }

      // Detect which shape we got (signatory vs system admin)
      const isSystemAdmin = roleStats.hasOwnProperty('verifying_payment');


      // Show the "Your Workload" header only for signatories (personal scope).
      // System admin sees org-wide counts, so the header doesn't apply.
      // Matches the inline style="display: none;" on #statsHeader in HTML.
      const header = document.getElementById('statsHeader');
      if (header) {
        header.style.display = isSystemAdmin ? 'none' : 'block';
      }


      // href values map to routes in routes/web.php.
      // - System admin cards deep-link into pending-requests tabs; ?tab= is
      //   read by pending-requests.blade.php's handleUrlTabParameterAndLoad().
      // - Signatory cards (roles 2/3/5) all funnel to their personal queue.
      const cards = isSystemAdmin
        ? [
          { value: roleStats.today_bookings, label: "Today's Bookings", subtext: 'active events today', cls: 'rstat-system', href: '/admin/pending-requests?tab=reserved' },
          { value: roleStats.pending_this_week, label: 'Pending This Week', subtext: 'new intake · 7 days', cls: 'rstat-system', href: '/admin/pending-requests?tab=pending' },
          { value: roleStats.awaiting_finalization, label: 'Awaiting Final Approval', subtext: 'stage 2 · fee not yet locked', cls: 'rstat-system', href: '/admin/pending-requests?tab=pending' },
          { value: roleStats.verifying_payment, label: 'Verifying Payment', subtext: 'signatories checking receipts', cls: 'rstat-system', href: '/admin/pending-requests?tab=payment-submitted' },
        ]
        : [
          { value: roleStats.needs_review, label: 'Needs My Review', subtext: 'awaiting your action', cls: '', href: '/admin/actionable-requests' },
          { value: roleStats.due_this_week, label: 'Due This Week', subtext: 'event within 7 days', cls: 'rstat-subset', href: '/admin/actionable-requests' },
          { value: roleStats.overdue_tasks, label: 'Overdue Tasks', subtext: 'waiting over 3 days', cls: 'rstat-subset rstat-overdue', href: '/admin/actionable-requests' },
          { value: roleStats.approved_this_week, label: 'Approved This Week', subtext: 'completed by you', cls: 'rstat-success', href: '/admin/actionable-requests' },
        ];
      row.innerHTML = cards.map(c => `
                    <div class="col-md-3 col-6">
                      <div class="rstat-card ${c.cls}" onclick="window.location.href='${c.href}'">
                        <div class="rstat-value">${c.value ?? 0}</div>
                        <div class="rstat-label">${escapeHtml(c.label)}</div>
                        <div class="rstat-subtext">${escapeHtml(c.subtext)}</div>
                      </div>
                    </div>
                  `).join('');

      row.style.display = 'flex';
    }
    function renderFeedback() {
      const container = document.getElementById('feedbackList');
      const feedbacks = dashboardData.latest_feedback || [];

      if (feedbacks.length === 0) {
        container.innerHTML = `
                                            <div class="empty-state">
                                                <i class="bi bi-chat-square-text"></i>
                                                <p>No feedback yet</p>
                                                <small>Responses will appear here</small>
                                            </div>`;
        return;
      }

      container.innerHTML = feedbacks.map(f => `
                                        <div class="feedback-item" onclick="goToRequest(${f.request_id})">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div class="flex-grow-1">
                                                    <div class="item-name">${escapeHtml(f.requester_name)}</div>
                                                    <div class="item-sub">${escapeHtml(f.ratings_summary)}</div>
                                                    ${f.additional_feedback ? `<div class="item-meta fst-italic">"${escapeHtml(f.additional_feedback.substring(0, 80))}${f.additional_feedback.length > 80 ? '…' : ''}"</div>` : ''}
                                                    <div class="item-meta"><i class="bi bi-clock"></i>${f.created_at}</div>
                                                </div>
                                                <i class="bi bi-chat-dots align-self-start" style="color:var(--navy); font-size:0.85rem; opacity:0.6;"></i>
                                            </div>
                                        </div>`).join('');
    }

    // ========== UTILITY FUNCTIONS ==========
    function goToRequest(requestId) {
      if (requestId) window.location.href = `/admin/requisition/${requestId}`;
    }

    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function showError(message) {
      const skeletonState = document.getElementById('skeletonState');
      if (skeletonState) {
        skeletonState.innerHTML = `
                                            <div class="empty-state py-5">
                                                <i class="bi bi-exclamation-triangle" style="color:var(--danger);font-size:2rem;"></i>
                                                <p class="text-danger mt-2">${message}</p>
                                                <button class="btn btn-primary btn-sm mt-1" onclick="location.reload()">
                                                    <i class="bi bi-arrow-clockwise me-1"></i> Retry
                                                </button>
                                            </div>`;
      }
    }

  </script>
@endsection