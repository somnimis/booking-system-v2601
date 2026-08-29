@extends('layouts.admin')

@section('title', 'Booking Dashboard')

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

        <!-- Stat Cards -->
        <div class="row g-3 mb-4" id="statsRow">
          <div class="col-md-3 col-6">
            <div class="stat-card" onclick="redirectToTab('pending')">
              <i class="bi bi-chevron-right stat-arrow"></i>
              <div class="stat-value" id="pendingCount">0</div>
              <div class="stat-label">Pending Approval</div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stat-card" onclick="redirectToTab('awaiting')">
              <i class="bi bi-chevron-right stat-arrow"></i>
              <div class="stat-value" id="awaitingPaymentCount">0</div>
              <div class="stat-label">Awaiting Payment</div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stat-card" onclick="redirectToTab('payment-submitted')">
              <i class="bi bi-chevron-right stat-arrow"></i>
              <div class="stat-value" id="paymentSubmittedCount">0</div>
              <div class="stat-label">Payment Submitted</div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stat-card" onclick="redirectToTab('reserved')">
              <i class="bi bi-chevron-right stat-arrow"></i>
              <div class="stat-value" id="reservedCount">0</div>
              <div class="stat-label">Reserved</div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Left Column -->
          <div class="col-lg-6">

            <!-- Pending Approvals -->
            <div class="section-card">
              <div class="section-header">
                <h6 class="section-title">
                  <i class="bi bi-clock-history"></i> Pending Approvals
                </h6>
                <a href="{{ url('/admin/pending-requests') }}" class="view-all-link">View all <i
                    class="bi bi-arrow-right"></i></a>
              </div>

              <div class="section-body" id="pendingApprovalsList">
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

    // ========== TODAY'S EVENTS RENDERING (with sample data fallback) ==========
    function renderTodayEvents() {
      const container = document.getElementById('reservationsList');

      // Get real events from API
      let events = todayEventsData?.data || [];

      // 🔵 SAMPLE DATA - TEMPORARY PLACEHOLDER FOR PRESENTATION (REMOVE IN PRODUCTION)
      // These only show when there are 0 real events
      const SAMPLE_EVENTS = [
        { request_id: 9999991, requester_name: '✨ Sample Event ✨', event_title: 'University General Assembly', time: '09:00 AM - 05:00 PM', locations: ['Main Auditorium'], is_sample: true },
        { request_id: 9999992, requester_name: '📋 Sample Event 📋', event_title: 'Student Leadership Workshop', time: '01:00 PM - 04:00 PM', locations: ['Conference Room A'], is_sample: true },
        { request_id: 9999993, requester_name: '🎭 Sample Event 🎭', event_title: 'Cultural Presentation', time: '02:00 PM - 06:00 PM', locations: ['Performing Arts Hall'], is_sample: true },
        { request_id: 9999994, requester_name: '🏆 Sample Event 🏆', event_title: 'Awards Ceremony', time: '03:00 PM - 07:00 PM', locations: ['University Center'], is_sample: true },
        { request_id: 9999995, requester_name: '📚 Sample Event 📚', event_title: 'Faculty Meeting', time: '10:00 AM - 12:00 PM', locations: ['Conference Room B'], is_sample: true },
        { request_id: 9999996, requester_name: '🎪 Sample Event 🎪', event_title: 'Organization Fair', time: '11:00 AM - 04:00 PM', locations: ['Student Plaza'], is_sample: true }
      ];

      const showSamples = events.length === 0;
      let displayEvents = [];
      let totalPages = 1;
      let itemsPerPage = 5;

      if (showSamples) {
        totalPages = Math.ceil(SAMPLE_EVENTS.length / itemsPerPage);
        const start = (currentTodayEventsPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        displayEvents = SAMPLE_EVENTS.slice(start, end);
      } else {
        displayEvents = events;
        totalPages = totalTodayEventsPages;
      }

      if (displayEvents.length === 0) {
        container.innerHTML = `<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No reservations today</p></div>`;
        document.getElementById('todayEventsPagination').style.display = 'none';
        return;
      }

      // Show pagination if needed
      const showPagination = showSamples ? SAMPLE_EVENTS.length > itemsPerPage : totalTodayEventsPages > 1;

      if (showPagination) {
        const paginationContainer = document.getElementById('todayEventsPagination');
        const infoSpan = document.getElementById('todayEventsInfo');
        const prevBtn = document.getElementById('todayEventsPrevBtn');
        const nextBtn = document.getElementById('todayEventsNextBtn');

        if (infoSpan) {
          if (showSamples) {
            infoSpan.textContent = `Page ${currentTodayEventsPage} of ${totalPages} (${SAMPLE_EVENTS.length} demo items)`;
          } else {
            infoSpan.textContent = `Page ${currentTodayEventsPage} of ${totalTodayEventsPages} (${totalTodayEventsItems} total)`;
          }
        }

        if (prevBtn) {
          prevBtn.disabled = currentTodayEventsPage === 1;
          // Replace the button to remove any existing listeners
          const newPrevBtn = prevBtn.cloneNode(true);
          prevBtn.parentNode.replaceChild(newPrevBtn, prevBtn);
          newPrevBtn.onclick = (e) => {
            e.stopPropagation();
            e.preventDefault();
            if (currentTodayEventsPage > 1) {
              if (showSamples) {
                currentTodayEventsPage--;
                renderTodayEvents();
              } else {
                loadTodayEventsPrevPage();
              }
            }
            return false;
          };
        }

        if (nextBtn) {
          nextBtn.disabled = currentTodayEventsPage === totalPages;
          // Replace the button to remove any existing listeners
          const newNextBtn = nextBtn.cloneNode(true);
          nextBtn.parentNode.replaceChild(newNextBtn, nextBtn);
          newNextBtn.onclick = (e) => {
            e.stopPropagation();
            e.preventDefault();
            if (currentTodayEventsPage < totalPages) {
              if (showSamples) {
                currentTodayEventsPage++;
                renderTodayEvents();
              } else {
                loadTodayEventsNextPage();
              }
            }
            return false;
          };
        }

        if (paginationContainer) paginationContainer.style.display = 'flex';
      } else {
        document.getElementById('todayEventsPagination').style.display = 'none';
      }

      // Render events
      container.innerHTML = displayEvents.map(r => `
      <div class="reservation-item" onclick="handleEventClick(${r.request_id}, ${r.is_sample || false})">
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

    // Handle click on events (with sample detection)
    function handleEventClick(requestId, isSample) {
      if (isSample) {
        alert('Sample event only. Please submit a real reservation form with today\'s date.');
      } else {
        window.location.href = `/admin/requisition/${requestId}`;
      }
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
      document.getElementById('pendingCount').textContent = dashboardData.stats.pending_count || 0;
      document.getElementById('awaitingPaymentCount').textContent = dashboardData.stats.awaiting_payment_count || 0;
      document.getElementById('paymentSubmittedCount').textContent = dashboardData.stats.payment_submitted_count || 0;
      document.getElementById('reservedCount').textContent = dashboardData.stats.reserved_count || 0;

      const today = new Date();
      document.getElementById('todayDate').textContent = today.toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
      });

      renderPendingApprovals();
      renderFeedback();
    }

    function renderPendingApprovals() {
      const container = document.getElementById('pendingApprovalsList');
      const approvals = dashboardData.pending_approvals || [];

      if (approvals.length === 0) {
        container.innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <p>No pending approvals</p>
                                <small>All caught up!</small>
                            </div>`;
        return;
      }

      container.innerHTML = approvals.map(a => `
                        <div class="pending-item" onclick="goToRequest(${a.request_id})">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <div class="item-name">${escapeHtml(a.requester_name)}</div>
                                    <div class="item-sub">${escapeHtml(a.event_title)}</div>
                                    <div class="item-meta">
                                        <i class="bi bi-building"></i>${escapeHtml(a.organization)}
                                        <span class="text-light">·</span>
                                        <i class="bi bi-calendar3"></i>${a.start_date}
                                    </div>
                                </div>
                            </div>
                        </div>`).join('');
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

    function redirectToTab(tab) {
      window.location.href = `/admin/pending-requests?tab=${tab}`;
    }
  </script>
@endsection