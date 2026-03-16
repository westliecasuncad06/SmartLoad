// ===================================================
// SmartLoad - Faculty Scheduling System
// Main Application JavaScript
// ===================================================

// --------------------------------------------------
// Sidebar Toggle (Mobile/Tablet)
// --------------------------------------------------
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isOpen = sidebar.classList.contains('sidebar-open');
    if (isOpen) {
        closeSidebar();
    } else {
        sidebar.classList.add('sidebar-open');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('sidebar-open');
    overlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function toggleMobileSearch() {
    const bar = document.getElementById('mobileSearchBar');
    bar.classList.toggle('hidden');
}

// Auto-close sidebar on resize to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
        closeSidebar();
    }
});

// --------------------------------------------------
// Page Switching / Navigation
// --------------------------------------------------
function switchPage(pageName) {
    // Close sidebar on mobile
    if (window.innerWidth < 1024) {
        closeSidebar();
    }

    // Hide all pages
    document.querySelectorAll('.page-content').forEach(page => {
        page.classList.add('hidden');
    });

    // Show selected page
    const selectedPage = document.getElementById('page-' + pageName);
    if (selectedPage) {
        selectedPage.classList.remove('hidden');
    }

    // Load Reports - Predictive Analytics chart should render only when visible
    if (pageName === 'loadreports') {
        requestAnimationFrame(() => {
            if (typeof loadPredictiveAnalytics === 'function') {
                loadPredictiveAnalytics();
            }
        });
    }

    // Update navigation active state
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('bg-indigo-600', 'text-white', 'font-medium');
        link.classList.add('text-slate-300', 'hover:bg-slate-800', 'hover:text-white');
    });

    const activeLink = document.querySelector('.nav-link[data-page="' + pageName + '"]');
    if (activeLink) {
        activeLink.classList.add('bg-indigo-600', 'text-white', 'font-medium');
        activeLink.classList.remove('text-slate-300', 'hover:bg-slate-800', 'hover:text-white');
    }

    // Update breadcrumbs
    const pageNames = {
        'dashboard': 'Dashboard',
        'teachers': 'Teachers',
        'subjects': 'Subjects',
        'schedules': 'Schedules',
        'loadreports': 'Load Reports',
        'audittrail': 'Audit Trail'
    };
    const title = pageNames[pageName] || 'Dashboard';
    document.getElementById('breadcrumbTitle').textContent = title;
    const mobileBreadcrumb = document.getElementById('mobileBreadcrumb');
    if (mobileBreadcrumb) mobileBreadcrumb.textContent = title;
}

// --------------------------------------------------
// Modal Controls
// --------------------------------------------------
let currentAssignmentId = null;

// Upload conflict modal state
let conflictModalState = {
    type: null,
    file: null,
    conflicts: [],
    rowsInserted: 0,
};

// When enabled by a feature (e.g. Teachers CSV import), resolving conflicts will reload the page.
let reloadAfterConflictResolve = false;

function openModal(assignmentId) {
    currentAssignmentId = assignmentId || null;
    document.getElementById('overrideModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('overrideModal').classList.add('hidden');
    currentAssignmentId = null;
}

function openSettingsModal() {
    document.getElementById('settingsModal').classList.remove('hidden');
    loadPolicySettings();
}

function closeSettingsModal() {
    document.getElementById('settingsModal').classList.add('hidden');
}

function openAuditDetailModal() {
    const modal = document.getElementById('auditDetailModal');
    if (!modal) return;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAuditDetailModal() {
    const modal = document.getElementById('auditDetailModal');
    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function initAuditTrail() {
    const listEl = document.getElementById('auditLogList');
    const applyBtn = document.getElementById('auditApplyFiltersBtn');
    const dateFromEl = document.getElementById('auditFilterDateFrom');
    const dateToEl = document.getElementById('auditFilterDateTo');
    const typeEl = document.getElementById('auditFilterType');
    const userEl = document.getElementById('auditFilterUser');
    const modal = document.getElementById('auditDetailModal');
    const dataEl = document.getElementById('auditLogData');

    if (!listEl || !applyBtn || !dateFromEl || !dateToEl || !typeEl || !userEl || !dataEl) return;

    let logs = [];
    try {
        logs = JSON.parse(dataEl.textContent || '[]');
    } catch (_) {
        logs = [];
    }

    const items = Array.from(listEl.querySelectorAll('.audit-log-item'));

    const normalizeActivityType = (value) => {
        const normalized = String(value || '').trim().toLowerCase();
        if (!normalized || normalized === 'all activity types') return '';
        if (normalized === 'schedule generated') return 'schedule generation';
        if (normalized === 'warnings') return 'overload warning';
        return normalized;
    };

    const setDetailText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    };

    const openAuditLogDetail = (index) => {
        const log = logs[index];
        if (!log) return;

        const createdAt = log.created_at ? new Date(log.created_at) : null;
        const formattedDate = createdAt && !Number.isNaN(createdAt.getTime())
            ? createdAt.toLocaleString()
            : '—';

        setDetailText('auditDetailTitle', log.action_type || 'Audit Entry');
        setDetailText('auditDetailActionType', log.action_type || '—');
        setDetailText('auditDetailStatus', log.action_type || 'Activity');
        setDetailText('auditDetailUser', log.user || 'System');
        setDetailText('auditDetailDate', formattedDate);
        setDetailText('auditDetailDescription', log.description || 'No description available.');
        setDetailText('auditDetailReference', log.id ? 'Log ID #' + log.id : 'No reference available.');
        openAuditDetailModal();
    };

    items.forEach((item, index) => {
        item.dataset.logIndex = String(index);
        const btn = item.querySelector('.audit-detail-btn');
        if (!btn) return;

        btn.addEventListener('click', () => openAuditLogDetail(index));
    });

    const applyAuditFilters = () => {
        const fromDate = dateFromEl.value ? new Date(dateFromEl.value + 'T00:00:00') : null;
        const toDate = dateToEl.value ? new Date(dateToEl.value + 'T23:59:59') : null;
        const actionFilter = normalizeActivityType(typeEl.value);
        const userFilter = String(userEl.value || '').trim().toLowerCase();
        const matchAllUsers = !userFilter || userFilter === 'all users';

        items.forEach((item) => {
            const createdAtValue = item.dataset.createdAt || '';
            const itemDate = createdAtValue ? new Date(createdAtValue.replace(' ', 'T')) : null;
            const itemAction = String(item.dataset.actionType || '').trim().toLowerCase();
            const itemUser = String(item.dataset.user || '').trim().toLowerCase();

            const matchesFromDate = !fromDate || (itemDate && itemDate >= fromDate);
            const matchesToDate = !toDate || (itemDate && itemDate <= toDate);
            const matchesType = !actionFilter || itemAction === actionFilter;
            const matchesUser = matchAllUsers || itemUser === userFilter;

            item.dataset.filterHidden = matchesFromDate && matchesToDate && matchesType && matchesUser ? '0' : '1';
        });

        refreshSmartPagination('audit');
    };

    applyBtn.addEventListener('click', applyAuditFilters);

    [dateFromEl, dateToEl, typeEl, userEl].forEach((el) => {
        el.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyAuditFilters();
            }
        });
    });

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeAuditDetailModal();
            }
        });
    }
}

function initPolicySettingsForm() {
    const expertiseSlider = document.getElementById('policyExpertiseWeight');
    const availabilitySlider = document.getElementById('policyAvailabilityWeight');

    if (!expertiseSlider || !availabilitySlider) return;

    const syncLabels = () => {
        const expertise = Number(expertiseSlider.value || 0);
        const availability = Number(availabilitySlider.value || 0);

        const eLabel = document.getElementById('policyExpertiseWeightLabel');
        const aLabel = document.getElementById('policyAvailabilityWeightLabel');

        if (eLabel) eLabel.textContent = expertise + '%';
        if (aLabel) aLabel.textContent = availability + '%';
    };

    expertiseSlider.addEventListener('input', () => {
        const expertise = Number(expertiseSlider.value || 0);
        availabilitySlider.value = String(100 - expertise);
        syncLabels();
    });

    availabilitySlider.addEventListener('input', () => {
        const availability = Number(availabilitySlider.value || 0);
        expertiseSlider.value = String(100 - availability);
        syncLabels();
    });

    syncLabels();
}

function setPolicySettingsStatus(message, isError) {
    const status = document.getElementById('policySettingsStatus');
    if (!status) return;

    if (!message) {
        status.classList.add('hidden');
        status.textContent = '';
        status.classList.remove('text-green-600', 'text-red-600');
        return;
    }

    status.classList.remove('hidden');
    status.textContent = message;
    status.classList.toggle('text-red-600', !!isError);
    status.classList.toggle('text-green-600', !isError);
}

function applyPolicySettingsToForm(settings) {
    const maxLoadInput = document.getElementById('policyMaxLoad');
    const expertiseSlider = document.getElementById('policyExpertiseWeight');
    const availabilitySlider = document.getElementById('policyAvailabilityWeight');
    const detectOverlaps = document.getElementById('policyDetectScheduleOverlaps');
    const flagOverload = document.getElementById('policyFlagOverloadTeachers');
    const checkPrereq = document.getElementById('policyCheckPrerequisites');

    if (maxLoadInput) maxLoadInput.value = String(Number(settings.max_teaching_load || 18));
    if (expertiseSlider) expertiseSlider.value = String(Number(settings.expertise_weight || 70));
    if (availabilitySlider) availabilitySlider.value = String(Number(settings.availability_weight || 30));
    if (detectOverlaps) detectOverlaps.checked = Number(settings.detect_schedule_overlaps || 0) === 1;
    if (flagOverload) flagOverload.checked = Number(settings.flag_overload_teachers || 0) === 1;
    if (checkPrereq) checkPrereq.checked = Number(settings.check_prerequisites || 0) === 1;

    const eLabel = document.getElementById('policyExpertiseWeightLabel');
    const aLabel = document.getElementById('policyAvailabilityWeightLabel');
    if (eLabel && expertiseSlider) eLabel.textContent = expertiseSlider.value + '%';
    if (aLabel && availabilitySlider) aLabel.textContent = availabilitySlider.value + '%';
}

async function loadPolicySettings() {
    try {
        setPolicySettingsStatus('Loading settings...', false);
        const response = await fetch('api/policy_settings.php', { method: 'GET' });
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status !== 'success' || !data.settings) {
            throw new Error((data && data.message) ? data.message : 'Failed to load policy settings.');
        }

        applyPolicySettingsToForm(data.settings);
        setPolicySettingsStatus('', false);
    } catch (err) {
        setPolicySettingsStatus('Failed to load settings: ' + (err && err.message ? err.message : String(err)), true);
    }
}

async function savePolicySettings() {
    const maxLoadInput = document.getElementById('policyMaxLoad');
    const expertiseSlider = document.getElementById('policyExpertiseWeight');
    const availabilitySlider = document.getElementById('policyAvailabilityWeight');
    const detectOverlaps = document.getElementById('policyDetectScheduleOverlaps');
    const flagOverload = document.getElementById('policyFlagOverloadTeachers');
    const checkPrereq = document.getElementById('policyCheckPrerequisites');

    const maxLoad = Number(maxLoadInput ? maxLoadInput.value : 18);
    const expertise = Number(expertiseSlider ? expertiseSlider.value : 70);
    const availability = Number(availabilitySlider ? availabilitySlider.value : 30);

    if (!Number.isFinite(maxLoad) || maxLoad < 1) {
        setPolicySettingsStatus('Maximum teaching load must be at least 1.', true);
        return;
    }

    if ((expertise + availability) !== 100) {
        setPolicySettingsStatus('Expertise and availability weights must total 100%.', true);
        return;
    }

    const payload = {
        max_teaching_load: maxLoad,
        expertise_weight: expertise,
        availability_weight: availability,
        detect_schedule_overlaps: detectOverlaps && detectOverlaps.checked ? 1 : 0,
        flag_overload_teachers: flagOverload && flagOverload.checked ? 1 : 0,
        check_prerequisites: checkPrereq && checkPrereq.checked ? 1 : 0,
    };

    try {
        setPolicySettingsStatus('Saving settings...', false);
        const data = await postJson('api/policy_settings.php', payload);
        if (!data || data.status !== 'success') {
            throw new Error((data && data.message) ? data.message : 'Failed to save policy settings.');
        }

        if (data.settings) {
            applyPolicySettingsToForm(data.settings);
        }

        setPolicySettingsStatus('Settings saved successfully.', false);
        setTimeout(() => {
            setPolicySettingsStatus('', false);
            closeSettingsModal();
        }, 650);
    } catch (err) {
        setPolicySettingsStatus('Failed to save settings: ' + (err && err.message ? err.message : String(err)), true);
    }
}

function openHistoryModal() {
    document.getElementById('historyModal').classList.remove('hidden');
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

function openUploadConflictModal() {
    document.getElementById('uploadConflictModal').classList.remove('hidden');
}

function closeUploadConflictModal() {
    const modal = document.getElementById('uploadConflictModal');
    if (modal) modal.classList.add('hidden');

    conflictModalState = { type: null, file: null, conflicts: [], rowsInserted: 0 };
    reloadAfterConflictResolve = false;
    const tbody = document.getElementById('uploadConflictTableBody');
    if (tbody) tbody.innerHTML = '';
}

// Success-with-duplicates modal (new)
function closeConflictModal() {
    const modal = document.getElementById('conflictModal');
    if (modal) modal.classList.add('hidden');

    const summary = document.getElementById('conflictSummary');
    if (summary) summary.textContent = '';

    const list = document.getElementById('conflictList');
    if (list) list.innerHTML = '';
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function () {
    [
        'overrideModal',
        'settingsModal',
        'historyModal',
        'uploadConflictModal',
        'conflictModal',
        'dashboardTeacherLoadModal',
        'teacherImportModal',
        'teacherAddModal',
        'teacherViewModal',
        'teacherEditModal',
        'subjectImportModal',
        'subjectAddModal',
        'subjectViewModal',
        'subjectEditModal',
    ].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    if (modalId === 'uploadConflictModal') {
                        closeUploadConflictModal();
                    } else if (modalId === 'conflictModal') {
                        closeConflictModal();
                    } else {
                        this.classList.add('hidden');
                    }
                }
            });
        }
    });

    // Initialize file upload zones
    initFileUploads();

    // Initialize Teachers page actions/modals
    initTeachersPage();

    // Initialize Subjects page actions/modals
    initSubjectsPage();

    // Initialize Policy Settings controls
    initPolicySettingsForm();

    // Initialize filter controls (Teachers & Subjects)
    initTeacherFilters();
    initSubjectFilters();

    // Initialize schedule filters (Schedules)
    initScheduleFilters();

    // Initialize dashboard controls (filters/export/actions)
    initDashboardReport();

    // Initialize pagination (Dashboard / Teachers / Subjects / Audit Trail)
    initSmartPagination();

    // Initialize audit trail actions (details + filters)
    initAuditTrail();

    // Initialize Predictive HR Insights (Load Reports)
    initPredictiveHrInsights();
});

// --------------------------------------------------
// Schedules - Filters (Teacher/Room)
// --------------------------------------------------
function initScheduleFilters() {
    const page = document.getElementById('page-schedules');
    const teacherSearchEl = document.getElementById('scheduleTeacherSearch');
    const teacherSelectEl = document.getElementById('scheduleTeacherFilter');
    const roomSelectEl = document.getElementById('scheduleRoomFilter');

    const semesterSelectEl = document.getElementById('scheduleSemesterSelect');
    const printBtn = document.getElementById('schedulePrintBtn');

    const weeklyViewEl = document.getElementById('scheduleWeeklyView');
    const dailyViewEl = document.getElementById('scheduleDailyView');
    const listViewEl = document.getElementById('scheduleListView');
    const legendEl = document.getElementById('scheduleLegend');
    const daySelectEl = document.getElementById('scheduleDayFilter');
    const dailyListEl = document.getElementById('scheduleDailyList');
    const listBodyEl = document.getElementById('scheduleListBody');

    const btnWeekly = document.getElementById('scheduleViewWeekly');
    const btnDaily = document.getElementById('scheduleViewDaily');
    const btnList = document.getElementById('scheduleViewList');

    const printSemesterEl = document.getElementById('schedulePrintSemester');
    const printFiltersEl = document.getElementById('schedulePrintFilters');

    if (!page || (!teacherSearchEl && !teacherSelectEl && !roomSelectEl)) return;

    const isAll = (value, prefix) => {
        const v = String(value || '').trim().toLowerCase();
        if (!v) return true;
        if (v === 'all') return true;
        if (prefix && v === prefix.toLowerCase()) return true;
        if (v.startsWith('all ')) return true;
        return false;
    };

    const normalize = (value) => String(value || '').trim().toLowerCase();

    const getActiveTeacherRoomFilters = () => {
        const teacherValue = teacherSelectEl ? teacherSelectEl.value : 'All Teachers';
        const roomValue = roomSelectEl ? roomSelectEl.value : 'All Rooms';

        const teacherFilter = isAll(teacherValue, 'All Teachers') ? '' : normalize(teacherValue);
        const roomFilter = isAll(roomValue, 'All Rooms') ? '' : normalize(roomValue);

        return { teacherFilter, roomFilter, teacherValue, roomValue };
    };

    const scheduleEntries = () => {
        const cards = Array.from(page.querySelectorAll('.schedule-class-card'));
        return cards.map(card => {
            return {
                card,
                teacher: String(card.dataset.teacher || '').trim(),
                room: String(card.dataset.room || '').trim(),
                day: String(card.dataset.day || '').trim(),
                time: String(card.dataset.time || '').trim(),
                course: String(card.dataset.course || '').trim(),
                subject: String(card.dataset.subject || '').trim(),
            };
        });
    };

    const matchesCurrentFilters = (entry) => {
        const { teacherFilter, roomFilter } = getActiveTeacherRoomFilters();
        const t = normalize(entry.teacher);
        const r = normalize(entry.room);

        const matchesTeacher = !teacherFilter || t === teacherFilter;
        const matchesRoom = !roomFilter || r === roomFilter;
        return matchesTeacher && matchesRoom;
    };

    const applyFilters = () => {
        const { teacherFilter, roomFilter } = getActiveTeacherRoomFilters();
        page.querySelectorAll('.schedule-class-card').forEach(card => {
            const cardTeacher = normalize(card.dataset.teacher || '');
            const cardRoom = normalize(card.dataset.room || '');

            const matchesTeacher = !teacherFilter || cardTeacher === teacherFilter;
            const matchesRoom = !roomFilter || cardRoom === roomFilter;
            const visible = matchesTeacher && matchesRoom;

            card.classList.toggle('hidden', !visible);
        });

        // Keep derived views in sync
        rebuildDerivedViews();
        syncPrintHeader();
    };

    const filterTeacherOptions = () => {
        if (!teacherSearchEl || !teacherSelectEl) return;
        const q = normalize(teacherSearchEl.value);

        const options = Array.from(teacherSelectEl.options);
        options.forEach((opt, idx) => {
            if (idx === 0) {
                opt.hidden = false; // All Teachers
                return;
            }
            if (!q) {
                opt.hidden = false;
                return;
            }
            opt.hidden = normalize(opt.value).indexOf(q) === -1;
        });
    };

    const filterTeacherOptionsDebounced = debounce(filterTeacherOptions, 120);
    if (teacherSearchEl) teacherSearchEl.addEventListener('keyup', filterTeacherOptionsDebounced);
    if (teacherSelectEl) teacherSelectEl.addEventListener('change', applyFilters);
    if (roomSelectEl) roomSelectEl.addEventListener('change', applyFilters);

    // ---------------------------
    // View switching (Weekly/Daily/List)
    // ---------------------------
    const VIEW_KEY = 'smartload.schedule.view';
    const SEM_KEY = 'smartload.schedule.semester';

    const setActiveButton = (activeBtn) => {
        [btnWeekly, btnDaily, btnList].filter(Boolean).forEach(btn => {
            const isActive = btn === activeBtn;
            btn.classList.toggle('bg-white', isActive);
            btn.classList.toggle('text-slate-900', isActive);
            btn.classList.toggle('shadow-sm', isActive);
            btn.classList.toggle('text-slate-600', !isActive);
        });
    };

    const showView = (view) => {
        const v = String(view || 'weekly');
        if (weeklyViewEl) weeklyViewEl.classList.toggle('hidden', v !== 'weekly');
        if (dailyViewEl) dailyViewEl.classList.toggle('hidden', v !== 'daily');
        if (listViewEl) listViewEl.classList.toggle('hidden', v !== 'list');
        if (legendEl) legendEl.classList.toggle('hidden', v !== 'weekly');

        if (v === 'weekly') setActiveButton(btnWeekly);
        if (v === 'daily') setActiveButton(btnDaily);
        if (v === 'list') setActiveButton(btnList);

        try { localStorage.setItem(VIEW_KEY, v); } catch (_) {}

        rebuildDerivedViews();
        syncPrintHeader();
    };

    const rebuildDailyView = () => {
        if (!dailyListEl) return;
        if (!daySelectEl) {
            dailyListEl.innerHTML = '';
            return;
        }

        const selectedDay = String(daySelectEl.value || '').trim();
        const entries = scheduleEntries()
            .filter(e => e.day === selectedDay)
            .filter(matchesCurrentFilters)
            .sort((a, b) => a.time.localeCompare(b.time) || a.course.localeCompare(b.course));

        if (!entries.length) {
            dailyListEl.innerHTML = '<div class="text-sm text-slate-500">No classes found for this day.</div>';
            return;
        }

        dailyListEl.innerHTML = '';
        entries.forEach(e => {
            const row = document.createElement('div');
            row.className = 'flex items-start gap-3';

            const time = document.createElement('div');
            time.className = 'w-24 shrink-0 text-slate-500 text-xs font-medium pt-1';
            time.textContent = e.time || '—';

            const cardClone = e.card.cloneNode(true);
            cardClone.classList.remove('hidden');

            row.appendChild(time);
            row.appendChild(cardClone);
            dailyListEl.appendChild(row);
        });
    };

    const rebuildListView = () => {
        if (!listBodyEl) return;
        const entries = scheduleEntries()
            .filter(matchesCurrentFilters)
            .sort((a, b) => a.day.localeCompare(b.day) || a.time.localeCompare(b.time) || a.course.localeCompare(b.course));

        if (!entries.length) {
            listBodyEl.innerHTML = '<tr><td class="px-4 py-4 text-slate-500" colspan="6">No classes found.</td></tr>';
            return;
        }

        listBodyEl.innerHTML = entries.map(e => {
            return `
<tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
  <td class="px-4 py-3 text-slate-700">${escapeHtml(e.day || '—')}</td>
  <td class="px-4 py-3 text-slate-600">${escapeHtml(e.time || '—')}</td>
  <td class="px-4 py-3 font-medium text-indigo-600">${escapeHtml(e.course || '—')}</td>
  <td class="px-4 py-3 text-slate-700">${escapeHtml(e.subject || '—')}</td>
  <td class="px-4 py-3 text-slate-700">${escapeHtml(e.teacher || 'Unassigned')}</td>
  <td class="px-4 py-3 text-slate-600">${escapeHtml(e.room || '—')}</td>
</tr>`;
        }).join('');
    };

    function rebuildDerivedViews() {
        const view = (function () {
            try { return localStorage.getItem(VIEW_KEY) || 'weekly'; } catch (_) { return 'weekly'; }
        })();

        if (view === 'daily') rebuildDailyView();
        if (view === 'list') rebuildListView();
    }

    function syncPrintHeader() {
        if (!printSemesterEl && !printFiltersEl) return;

        const semester = semesterSelectEl ? String(semesterSelectEl.value || '').trim() : '';
        if (printSemesterEl) {
            printSemesterEl.textContent = semester ? semester : '';
        }

        if (printFiltersEl) {
            const { teacherValue, roomValue } = getActiveTeacherRoomFilters();
            const t = String(teacherValue || 'All Teachers');
            const r = String(roomValue || 'All Rooms');
            printFiltersEl.innerHTML = `${escapeHtml(t)}<br/>${escapeHtml(r)}`;
        }
    }

    if (btnWeekly) btnWeekly.addEventListener('click', () => showView('weekly'));
    if (btnDaily) btnDaily.addEventListener('click', () => showView('daily'));
    if (btnList) btnList.addEventListener('click', () => showView('list'));
    if (daySelectEl) daySelectEl.addEventListener('change', () => { rebuildDailyView(); syncPrintHeader(); });

    // ---------------------------
    // Semester persistence
    // ---------------------------
    if (semesterSelectEl) {
        try {
            const saved = localStorage.getItem(SEM_KEY);
            if (saved) semesterSelectEl.value = saved;
        } catch (_) {}

        semesterSelectEl.addEventListener('change', () => {
            try { localStorage.setItem(SEM_KEY, String(semesterSelectEl.value || '')); } catch (_) {}
            syncPrintHeader();
        });
    }

    // ---------------------------
    // Print schedule
    // ---------------------------
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            rebuildDerivedViews();
            syncPrintHeader();
            window.print();
        });
    }

    // Initial state
    filterTeacherOptions();
    // Restore saved view
    let initialView = 'weekly';
    try { initialView = localStorage.getItem(VIEW_KEY) || 'weekly'; } catch (_) {}
    if (!['weekly', 'daily', 'list'].includes(initialView)) initialView = 'weekly';

    applyFilters();
    showView(initialView);
    syncPrintHeader();
}

// --------------------------------------------------
// Load Reports - Predictive HR Insights
// --------------------------------------------------
let predictiveChartInstance = null;

async function loadPredictiveAnalytics() {
    const canvas = document.getElementById('predictiveChart');
    const insightEl = document.getElementById('predictiveInsight');

    if (!canvas || !insightEl) return;

    insightEl.innerHTML = 'Loading predictions...';

    if (typeof Chart === 'undefined') {
        insightEl.innerHTML = 'Chart.js is not loaded.';
        return;
    }

    try {
        const url = 'api/predict_shortages.php?v=' + Date.now();
        const response = await fetch(url, { method: 'GET', cache: 'no-store' });
        const payload = await response.json().catch(() => null);

        if (!response.ok || (payload && payload.status === 'error')) {
            const msg = payload && payload.message ? payload.message : 'Failed to load predictive analytics data.';
            throw new Error(msg);
        }

        if (!Array.isArray(payload) || payload.length === 0) {
            throw new Error('No predictive analytics data available.');
        }

        // Use the first subject for now
        const subject = payload[0] || {};
        const subjectName = String(subject.subject_name || 'Unknown Subject');
        const history = Array.isArray(subject.history) ? subject.history.map(v => Number(v)) : [];
        const predicted = Number(subject.predicted || 0);
        const capacity = Number(subject.capacity || 0);
        const hiringRequired = subject.hiring_required === true;

        const labels = ['2024', '2025', '2026', '2027 (Predicted)'];
        const hist3 = [history[0] ?? 0, history[1] ?? 0, history[2] ?? 0].map(v => Number(v) || 0);
        const bars = [...hist3, Number(predicted) || 0];

        const indigo = 'rgba(99, 102, 241, 0.85)';
        const amber = 'rgba(245, 158, 11, 0.85)';
        const red = 'rgba(239, 68, 68, 0.9)';
        const barColors = [indigo, indigo, indigo, amber];

        if (predictiveChartInstance) {
            predictiveChartInstance.destroy();
            predictiveChartInstance = null;
        }

        const ctx = canvas.getContext('2d');
        predictiveChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: subjectName + ' Units',
                        data: bars,
                        backgroundColor: barColors,
                        borderColor: barColors,
                        borderWidth: 1,
                    },
                    {
                        type: 'line',
                        label: 'Capacity',
                        data: [capacity, capacity, capacity, capacity],
                        borderColor: red,
                        borderWidth: 2,
                        borderDash: [6, 6],
                        pointRadius: 0,
                        fill: false,
                        tension: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                    tooltip: { enabled: true },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                    }
                }
            }
        });

        if (hiringRequired) {
            const msg = `⚠️ Shortage Alert: ${subjectName} will require ${predicted} units next year, but you only have ${capacity} units of capacity. Prepare to hire!`;
            insightEl.innerHTML = '<strong>' + escapeHtml(msg) + '</strong>';
        } else {
            const msg = `${subjectName} is within capacity based on current forecast.`;
            insightEl.innerHTML = escapeHtml(msg);
        }
    } catch (err) {
        if (predictiveChartInstance) {
            predictiveChartInstance.destroy();
            predictiveChartInstance = null;
        }

        insightEl.innerHTML = escapeHtml(err && err.message ? err.message : 'Unable to load predictions.');
    }
}

function initPredictiveHrInsights() {
    const container = document.getElementById('predictiveHrInsights');
    if (!container) return;

    const statusEl = document.getElementById('predictiveHrInsightsStatus');

    const setStatus = (message) => {
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.classList.remove('hidden');
        }
    };

    const clearStatus = () => {
        if (statusEl) statusEl.classList.add('hidden');
    };

    const renderEmpty = () => {
        container.innerHTML =
            '<div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-sm text-slate-700">'
            + 'No shortage risks detected based on current data.'
            + '</div>';
    };

    const renderError = (message) => {
        container.innerHTML =
            '<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-900">'
            + (message || 'Unable to load forecast data.')
            + '</div>';
    };

    const renderShortages = (shortages) => {
        if (!Array.isArray(shortages) || shortages.length === 0) {
            renderEmpty();
            return;
        }

        const cards = shortages.map((row) => {
            const subjectName = row.subject_name || 'Unknown Subject';
            const projectedUnits = Number(row.projected_units_needed || 0);
            const totalCapacity = Number(row.total_faculty_capacity || 0);
            const unitShortage = Number(
                row.unit_shortage != null
                    ? row.unit_shortage
                    : Math.max(0, projectedUnits - totalCapacity)
            );

            const recommendedAction = unitShortage <= 12
                ? 'Hire 1 Part-Time Instructor'
                : 'Hire 1 Full-Time Instructor';

            const message = `Shortage Risk: ${subjectName}. Projected demand is ${projectedUnits} units, but current specialized faculty capacity is only ${totalCapacity} units. Shortfall: ${unitShortage} units.`;

            return (
                '<div class="bg-rose-50 border border-rose-200 rounded-lg p-4 text-rose-900">'
                + '  <div class="flex items-start justify-between gap-3">'
                + '    <p class="text-sm leading-relaxed">' + escapeHtml(message) + '</p>'
                + '    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">Recommended Action: ' + escapeHtml(recommendedAction) + '</span>'
                + '  </div>'
                + '</div>'
            );
        }).join('');

        container.innerHTML = cards;
    };

    setStatus('Loading forecast…');

    const url = 'api/predict_shortages.php?v=' + Date.now();
    fetch(url, { method: 'GET', cache: 'no-store' })
        .then(async (res) => {
            const data = await res.json().catch(() => null);
            if (!res.ok || (data && data.status === 'error')) {
                const msg = data && data.message ? data.message : 'Failed to load forecast.';
                throw new Error(msg);
            }
            return data;
        })
        .then((data) => {
            clearStatus();
            if (!Array.isArray(data)) {
                const msg = (data && data.message) ? data.message : 'Unexpected response from forecast endpoint.';
                renderError(msg);
                return;
            }

            const shortages = data.filter((row) => row && row.hiring_required === true);
            renderShortages(shortages);
        })
        .catch((err) => {
            clearStatus();
            renderError(err && err.message ? err.message : 'Unable to load forecast data.');
        });
}

// --------------------------------------------------
// Dashboard - Load Assignment Report
// --------------------------------------------------
function openDashboardTeacherLoadModal() {
    const modal = document.getElementById('dashboardTeacherLoadModal');
    if (modal) modal.classList.remove('hidden');
}

function closeDashboardTeacherLoadModal() {
    const modal = document.getElementById('dashboardTeacherLoadModal');
    if (modal) modal.classList.add('hidden');
}

function normalizeDashboardStatus(value) {
    const v = String(value || '').trim().toLowerCase();
    if (v === 'manual override') return 'manual';
    if (v === 'all status') return 'all';
    return v;
}

function applyDashboardStatusFilter(tbody, status) {
    const wanted = normalizeDashboardStatus(status);
    tbody.querySelectorAll('tr').forEach(tr => {
        const rowStatus = normalizeDashboardStatus(tr.dataset.status || '');
        const show = (wanted === 'all' || wanted === '' || rowStatus === wanted);
        tr.dataset.filterHidden = show ? '0' : '1';
    });

    // Let pagination decide what to show.
    refreshSmartPagination('dashboard');
}

function downloadBlob(filename, blob) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function exportDashboardCsv(tbody) {
    const headers = ['Teacher', 'Expertise', 'Assigned Subjects', 'Schedule', 'Total Units', 'Rationale', 'Status'];
    const rows = [headers];

    tbody.querySelectorAll('tr').forEach(tr => {
        if (tr.style.display === 'none') return;
        const tds = Array.from(tr.querySelectorAll('td'));
        if (tds.length < 9) return;

        const teacher = tds[1]?.innerText?.trim() || '';
        const expertise = tds[2]?.innerText?.trim().replace(/\s+/g, ' ') || '';
        const assigned = tds[3]?.innerText?.trim().replace(/\s*\n\s*/g, '; ') || '';
        const schedule = tds[4]?.innerText?.trim().replace(/\s*\n\s*/g, '; ') || '';
        const totalUnits = tds[5]?.innerText?.trim().replace(/\s+/g, ' ') || '';
        const rationale = tds[6]?.innerText?.trim().replace(/\s+/g, ' ') || '';
        const status = tds[7]?.innerText?.trim().replace(/\s+/g, ' ') || '';

        rows.push([teacher, expertise, assigned, schedule, totalUnits, rationale, status]);
    });

    const csvLine = (fields) => fields
        .map(v => {
            const s = String(v ?? '');
            return /[",\n]/.test(s) ? '"' + s.replaceAll('"', '""') + '"' : s;
        })
        .join(',');

    const csv = rows.map(csvLine).join('\n') + '\n';
    downloadBlob('load_assignment_report.csv', new Blob([csv], { type: 'text/csv;charset=utf-8' }));
}

function exportDashboardPdfPrint(tbody) {
    const visibleRows = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.style.display !== 'none');
    const htmlRows = visibleRows.map(tr => {
        const tds = Array.from(tr.querySelectorAll('td'));
        if (tds.length < 9) return '';
        const get = (i) => (tds[i]?.innerText || '').trim();
        return `<tr>
            <td>${escapeHtml(get(1))}</td>
            <td>${escapeHtml(get(3).replace(/\s*\n\s*/g, '; '))}</td>
            <td>${escapeHtml(get(5))}</td>
            <td>${escapeHtml(get(7))}</td>
        </tr>`;
    }).join('');

    const w = window.open('', '_blank');
    if (!w) {
        alert('Popup blocked. Please allow popups to export PDF.');
        return;
    }

    w.document.open();
    w.document.write(`<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Load Assignment Report</title>
  <style>
    body{font-family:Arial, sans-serif; padding:24px;}
    h1{font-size:18px; margin:0 0 12px;}
    table{width:100%; border-collapse:collapse; font-size:12px;}
    th,td{border:1px solid #ddd; padding:8px; vertical-align:top;}
    th{background:#f5f5f5; text-align:left;}
  </style>
</head>
<body>
  <h1>Load Assignment Report</h1>
  <table>
    <thead><tr><th>Teacher</th><th>Assigned Subjects</th><th>Total Units</th><th>Status</th></tr></thead>
    <tbody>${htmlRows}</tbody>
  </table>
</body>
</html>`);
    w.document.close();
    w.focus();
    w.print();
}

async function fetchTeacherLoad(teacherId) {
    const res = await fetch('api/get_teacher_load.php?teacher_id=' + encodeURIComponent(String(teacherId)));
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data || data.status !== 'success') {
        throw new Error((data && data.message) ? data.message : 'Failed to load teacher details.');
    }
    return data;
}

function initDashboardReport() {
    const tbody = document.getElementById('dashboardReportTbody');
    const statusFilter = document.getElementById('statusFilter');
    const btnCsv = document.getElementById('btnDashboardExportCsv');
    const btnPdf = document.getElementById('btnDashboardExportPdf');

    if (!tbody || !statusFilter) return;

    const apply = () => applyDashboardStatusFilter(tbody, statusFilter.value);
    statusFilter.addEventListener('change', apply);
    apply();

    document.querySelectorAll('.dashboard-quick-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            const v = btn.dataset.status || 'all';
            statusFilter.value = v;
            apply();
        });
    });

    if (btnCsv) {
        btnCsv.addEventListener('click', () => exportDashboardCsv(tbody));
    }
    if (btnPdf) {
        btnPdf.addEventListener('click', () => exportDashboardPdfPrint(tbody));
    }

    const closeBtn = document.getElementById('btnDashboardTeacherLoadClose');
    const closeBtn2 = document.getElementById('btnDashboardTeacherLoadCancel');
    if (closeBtn) closeBtn.addEventListener('click', closeDashboardTeacherLoadModal);
    if (closeBtn2) closeBtn2.addEventListener('click', closeDashboardTeacherLoadModal);

    const sendBtn = document.getElementById('btnDashboardTeacherSendPdf');
    if (sendBtn) {
        sendBtn.addEventListener('click', async () => {
            const teacherId = Number(sendBtn.dataset.teacherId || 0);
            if (!teacherId) return;

            sendBtn.disabled = true;
            const prev = sendBtn.textContent;
            sendBtn.textContent = 'Sending...';
            try {
                const data = await postJson('api/send_teacher_load_pdf.php', { teacher_id: teacherId });
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to send PDF.');
                }
                alert('PDF sent successfully.');
            } catch (err) {
                alert('Send failed: ' + (err && err.message ? err.message : String(err)));
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = prev;
            }
        });
    }

    tbody.addEventListener('click', async (e) => {
        const target = e.target;
        const btn = target && target.closest ? target.closest('.dashboard-action-view') : null;
        if (!btn) return;

        const teacherId = Number(btn.dataset.teacherId || 0);
        if (!teacherId) return;

        try {
            const data = await fetchTeacherLoad(teacherId);
            const teacher = data.teacher || {};
            const subjects = Array.isArray(data.subjects) ? data.subjects : [];

            const nameEl = document.getElementById('dashTeacherName');
            const emailEl = document.getElementById('dashTeacherEmail');
            const unitsEl = document.getElementById('dashTeacherUnits');
            const typeEl = document.getElementById('dashTeacherType');
            const bodyEl = document.getElementById('dashTeacherSubjectsBody');

            if (nameEl) nameEl.textContent = teacher.name || btn.dataset.teacherName || '—';
            if (emailEl) emailEl.textContent = teacher.email || '—';
            if (unitsEl) unitsEl.textContent = (teacher.current_units !== undefined && teacher.max_units !== undefined)
                ? (String(teacher.current_units) + ' / ' + String(teacher.max_units) + ' units')
                : '—';
            if (typeEl) typeEl.textContent = (teacher.type ? String(teacher.type) + ' Faculty' : '—');

            if (bodyEl) {
                if (!subjects.length) {
                    bodyEl.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No assigned subjects.</td></tr>`;
                } else {
                    bodyEl.innerHTML = subjects.map(s => {
                        const sched = Array.isArray(s.schedule_lines) && s.schedule_lines.length
                            ? s.schedule_lines.join('; ')
                            : '—';
                        return `<tr class="border-b border-slate-100">
                            <td class="px-4 py-2 font-medium text-indigo-600">${escapeHtml(s.course_code || '')}</td>
                            <td class="px-4 py-2 text-slate-900">${escapeHtml(s.subject_name || '')}</td>
                            <td class="px-4 py-2 text-slate-700">${escapeHtml(s.subject_units ?? '')}</td>
                            <td class="px-4 py-2 text-slate-700 text-xs">${escapeHtml(sched)}</td>
                            <td class="px-4 py-2 text-slate-700">${escapeHtml(s.assignment_status || '')}</td>
                        </tr>`;
                    }).join('');
                }
            }

            if (sendBtn) {
                sendBtn.dataset.teacherId = String(teacherId);
            }

            openDashboardTeacherLoadModal();
        } catch (err) {
            alert('Failed to load details: ' + (err && err.message ? err.message : String(err)));
        }
    });
}

// --------------------------------------------------
// Pagination (Dashboard / Teachers / Subjects / Audit)
// --------------------------------------------------
const __smartPaginators = {};

function refreshSmartPagination(key) {
    const p = __smartPaginators[key];
    if (p && typeof p.refresh === 'function') {
        p.refresh();
    }
}

function parsePageSizeFromSelect(selectEl, fallback) {
    if (!selectEl) return fallback;
    const opt = selectEl.options && selectEl.selectedIndex >= 0 ? selectEl.options[selectEl.selectedIndex] : null;
    const text = (opt && opt.textContent) ? opt.textContent : String(selectEl.value || '');
    const m = text.match(/\d+/);
    const n = m ? Number(m[0]) : NaN;
    return Number.isFinite(n) && n > 0 ? n : fallback;
}

function buildPaginationModel(currentPage, totalPages) {
    const page = Math.max(1, Math.min(totalPages, currentPage));
    const pages = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
        return { page, pages };
    }

    const windowSize = 1; // show current +/- 1
    const left = Math.max(2, page - windowSize);
    const right = Math.min(totalPages - 1, page + windowSize);

    pages.push(1);
    if (left > 2) pages.push(null);
    for (let i = left; i <= right; i++) pages.push(i);
    if (right < totalPages - 1) pages.push(null);
    pages.push(totalPages);

    return { page, pages };
}

function renderPaginationControls(paginationEl, currentPage, totalPages, onPageChange) {
    if (!paginationEl) return;
    paginationEl.innerHTML = '';

    const makeBtn = ({ label, html, disabled, active, onClick }) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.disabled = !!disabled;
        if (active) {
            btn.className = 'px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg';
        } else {
            btn.className = 'px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors disabled:opacity-50';
        }
        if (label) btn.setAttribute('aria-label', label);
        if (html) btn.innerHTML = html;
        if (onClick) btn.addEventListener('click', onClick);
        return btn;
    };

    // Prev
    paginationEl.appendChild(makeBtn({
        label: 'Previous page',
        html: '<i class="fas fa-chevron-left text-xs"></i>',
        disabled: currentPage <= 1,
        active: false,
        onClick: () => onPageChange(currentPage - 1),
    }));

    const model = buildPaginationModel(currentPage, totalPages);
    model.pages.forEach(p => {
        if (p === null) {
            const span = document.createElement('span');
            span.className = 'px-2 text-slate-400';
            span.textContent = '...';
            paginationEl.appendChild(span);
            return;
        }
        paginationEl.appendChild(makeBtn({
            label: 'Page ' + p,
            html: String(p),
            disabled: false,
            active: p === model.page,
            onClick: () => onPageChange(p),
        }));
    });

    // Next
    paginationEl.appendChild(makeBtn({
        label: 'Next page',
        html: '<i class="fas fa-chevron-right text-xs"></i>',
        disabled: currentPage >= totalPages,
        active: false,
        onClick: () => onPageChange(currentPage + 1),
    }));
}

function setupListPaginator(key, options) {
    const {
        containerEl,
        itemSelector,
        paginationEl,
        showingEl,
        pageSizeEl,
        defaultPageSize,
        label,
        isItemFilteredOut,
    } = options;

    if (!containerEl || !paginationEl) return;

    const state = {
        page: 1,
        pageSize: defaultPageSize,
    };

    const getItems = () => Array.from(containerEl.querySelectorAll(itemSelector));
    const isFilteredOut = (el) => (isItemFilteredOut ? !!isItemFilteredOut(el) : false);

    const refresh = () => {
        if (pageSizeEl) {
            state.pageSize = parsePageSizeFromSelect(pageSizeEl, state.pageSize);
        }

        const items = getItems();
        const eligible = items.filter(el => !isFilteredOut(el));
        const total = eligible.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.page = Math.max(1, Math.min(state.page, totalPages));

        const startIdx = (state.page - 1) * state.pageSize;
        const endIdx = startIdx + state.pageSize;

        // Hide all filtered-out items.
        items.forEach(el => {
            if (isFilteredOut(el)) el.style.display = 'none';
        });

        // Page eligible items.
        eligible.forEach((el, idx) => {
            const show = idx >= startIdx && idx < endIdx;
            el.style.display = show ? '' : 'none';
        });

        if (showingEl) {
            if (total === 0) {
                showingEl.textContent = 'Showing 0 ' + label;
            } else {
                const from = startIdx + 1;
                const to = Math.min(endIdx, total);
                showingEl.textContent = `Showing ${from}-${to} of ${total} ${label}`;
            }
        }

        renderPaginationControls(paginationEl, state.page, totalPages, (nextPage) => {
            state.page = nextPage;
            refresh();
        });
    };

    if (pageSizeEl) {
        pageSizeEl.addEventListener('change', () => {
            state.page = 1;
            refresh();
        });
    }

    __smartPaginators[key] = { refresh };
    refresh();
}

function initSmartPagination() {
    // Dashboard table pagination (works with status filter via data-filter-hidden)
    setupListPaginator('dashboard', {
        containerEl: document.getElementById('dashboardReportTbody'),
        itemSelector: 'tr',
        paginationEl: document.getElementById('dashboardPagination'),
        showingEl: document.getElementById('dashboardShowing'),
        pageSizeEl: document.getElementById('dashboardPageSize'),
        defaultPageSize: 10,
        label: 'entries',
        isItemFilteredOut: (tr) => String(tr.dataset.filterHidden || '0') === '1',
    });

    // Teachers table pagination
    setupListPaginator('teachers', {
        containerEl: document.getElementById('teacherTableBody'),
        itemSelector: 'tr',
        paginationEl: document.getElementById('teacherPagination'),
        showingEl: document.getElementById('teacherShowing'),
        pageSizeEl: null,
        defaultPageSize: 10,
        label: 'teachers',
        isItemFilteredOut: null,
    });

    // Subjects table pagination
    setupListPaginator('subjects', {
        containerEl: document.getElementById('subjectTableBody'),
        itemSelector: 'tr',
        paginationEl: document.getElementById('subjectPagination'),
        showingEl: document.getElementById('subjectShowing'),
        pageSizeEl: null,
        defaultPageSize: 10,
        label: 'subjects',
        isItemFilteredOut: null,
    });

    // Audit trail pagination (list items)
    setupListPaginator('audit', {
        containerEl: document.getElementById('auditLogList'),
        itemSelector: '.audit-log-item',
        paginationEl: document.getElementById('auditPagination'),
        showingEl: document.getElementById('auditShowing'),
        pageSizeEl: null,
        defaultPageSize: 10,
        label: 'entries',
        isItemFilteredOut: (item) => String(item.dataset.filterHidden || '0') === '1',
    });
}

// --------------------------------------------------
// Filters (Teachers & Subjects)
// --------------------------------------------------
function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function initialsFromName(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '—';
    return parts.map(p => p.charAt(0).toUpperCase()).join('').slice(0, 3);
}

function debounce(fn, delayMs) {
    let timer = null;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delayMs);
    };
}

function buildTeacherRowHtml(t) {
    const id = Number(t.id || 0);
    const name = String(t.name || '');
    const email = String(t.email || '');
    const type = String(t.type || '');
    const maxUnits = Number(t.max_units || 0);
    const currentUnits = Number(t.current_units || 0);
    const expertise = String(t.expertise_tags || '');

    const isOverloaded = currentUnits > maxUnits;
    const rowClass = 'border-b border-slate-100 hover:bg-slate-50 transition-colors' + (isOverloaded ? ' bg-red-50/30' : '');
    const initials = initialsFromName(name);

    const tagsHtml = expertise
        ? expertise.split(',').map(s => s.trim()).filter(Boolean)
            .map(tag => `<span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">${escapeHtml(tag)}</span>`)
            .join('')
        : '';

    const employmentHtml = (type === 'Full-time')
        ? '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Full-time</span>'
        : '<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Part-time</span>';

    const overloadIcon = isOverloaded ? '<i class="fas fa-triangle-exclamation text-red-500 text-xs"></i>' : '';
    const currentUnitsClass = isOverloaded ? 'text-red-600' : 'text-slate-900';

    return `
<tr class="${rowClass}">
  <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
  <td class="px-6 py-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-medium text-sm">${escapeHtml(initials)}</div>
      <div>
        <p class="font-medium text-slate-900">${escapeHtml(name)}</p>
        <p class="text-xs text-slate-500">${escapeHtml(email)}</p>
      </div>
    </div>
  </td>
  <td class="px-6 py-4">
    <div class="flex flex-wrap gap-1">${tagsHtml}</div>
  </td>
  <td class="px-6 py-4">${employmentHtml}</td>
  <td class="px-6 py-4">
    <div class="flex items-center gap-2">
      <span class="font-semibold ${currentUnitsClass}">${escapeHtml(currentUnits)}</span>
      <span class="text-slate-400">/</span>
      <span class="text-slate-500">${escapeHtml(maxUnits)} units</span>
      ${overloadIcon}
    </div>
  </td>
  <td class="px-6 py-4">
    <div class="flex items-center justify-center gap-2">
      <button
        type="button"
        class="teacher-action-view p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
        title="View"
        data-teacher-id="${escapeHtml(id)}"
        data-teacher-name="${escapeHtml(name)}"
        data-teacher-email="${escapeHtml(email)}"
        data-teacher-type="${escapeHtml(type)}"
        data-teacher-max-units="${escapeHtml(maxUnits)}"
        data-teacher-current-units="${escapeHtml(currentUnits)}"
        data-teacher-expertise-tags="${escapeHtml(expertise)}"
      ><i class="fas fa-eye text-sm"></i></button>
      <button
        type="button"
        class="teacher-action-edit p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
        title="Edit"
        data-teacher-id="${escapeHtml(id)}"
        data-teacher-name="${escapeHtml(name)}"
        data-teacher-email="${escapeHtml(email)}"
        data-teacher-type="${escapeHtml(type)}"
        data-teacher-max-units="${escapeHtml(maxUnits)}"
        data-teacher-current-units="${escapeHtml(currentUnits)}"
        data-teacher-expertise-tags="${escapeHtml(expertise)}"
      ><i class="fas fa-pen text-sm"></i></button>
      <button
        type="button"
        class="teacher-action-archive p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded transition-colors"
        title="Archive"
        data-teacher-id="${escapeHtml(id)}"
        data-teacher-name="${escapeHtml(name)}"
      ><i class="fas fa-box-archive text-sm"></i></button>
    </div>
  </td>
</tr>`;
}

function wireTeacherRowButtons(container) {
    if (!container) return;

    container.querySelectorAll('.teacher-action-view').forEach(btn => {
        if (btn.dataset.wired === '1') return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', () => {
            const t = getTeacherFromDataset(btn);
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            set('viewTeacherName', t.name || '—');
            set('viewTeacherEmail', t.email || '—');
            set('viewTeacherType', t.type || '—');
            set('viewTeacherMaxUnits', String(t.max_units));
            set('viewTeacherCurrentUnits', String(t.current_units));
            set('viewTeacherExpertise', (t.expertise_tags || '').trim() || '—');
            openTeacherModal('teacherViewModal');
        });
    });

    container.querySelectorAll('.teacher-action-edit').forEach(btn => {
        if (btn.dataset.wired === '1') return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', () => {
            const t = getTeacherFromDataset(btn);
            const idEl = document.getElementById('editTeacherId');
            const nameEl = document.getElementById('editTeacherName');
            const emailEl = document.getElementById('editTeacherEmail');
            const typeEl = document.getElementById('editTeacherType');
            const maxEl = document.getElementById('editTeacherMaxUnits');
            const expEl = document.getElementById('editTeacherExpertise');
            if (idEl) idEl.value = String(t.id);
            if (nameEl) nameEl.value = t.name || '';
            if (emailEl) emailEl.value = t.email || '';
            if (typeEl) typeEl.value = t.type || 'Full-time';
            if (maxEl) maxEl.value = String(t.max_units || 0);
            if (expEl) expEl.value = t.expertise_tags || '';
            openTeacherModal('teacherEditModal');
        });
    });

    container.querySelectorAll('.teacher-action-archive').forEach(btn => {
        if (btn.dataset.wired === '1') return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', async () => {
            const id = Number(btn.dataset.teacherId || 0);
            const name = btn.dataset.teacherName || '';
            if (!id) return;
            if (!confirm('Archive this teacher?')) return;
            try {
                const data = await postJson('api/archive_teacher.php', { id });
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to archive teacher.');
                }
                alert((name ? name + ' ' : '') + 'archived successfully.');
                location.reload();
            } catch (err) {
                alert('Archive failed: ' + (err && err.message ? err.message : String(err)));
            }
        });
    });
}

function initTeacherFilters() {
    const page = document.getElementById('page-teachers');
    const tbody = document.getElementById('teacherTableBody');
    const searchEl = document.getElementById('teacherSearch');
    const deptEl = document.getElementById('teacherDepartmentFilter');
    const typeEl = document.getElementById('teacherTypeFilter');

    if (!page || !tbody || (!searchEl && !deptEl && !typeEl)) return;

    let abortController = null;

    const run = async () => {
        const search = (searchEl ? searchEl.value : '').trim();
        const department = deptEl ? String(deptEl.value || 'All') : 'All';
        const type = typeEl ? String(typeEl.value || 'All') : 'All';

        const qs = new URLSearchParams();
        qs.set('search', search);
        qs.set('department', department);
        qs.set('type', type);

        if (abortController) abortController.abort();
        abortController = new AbortController();

        try {
            const res = await fetch('api/filter_teachers.php?' + qs.toString(), { signal: abortController.signal });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data || data.status !== 'success') {
                throw new Error((data && data.message) ? data.message : 'Failed to filter teachers.');
            }

            const teachers = Array.isArray(data.teachers) ? data.teachers : [];
            tbody.innerHTML = teachers.map(buildTeacherRowHtml).join('');
            wireTeacherRowButtons(tbody);
            refreshSmartPagination('teachers');
        } catch (err) {
            if (err && err.name === 'AbortError') return;
            console.error(err);
        }
    };

    const runDebounced = debounce(run, 200);
    if (searchEl) searchEl.addEventListener('keyup', runDebounced);
    if (deptEl) deptEl.addEventListener('change', run);
    if (typeEl) typeEl.addEventListener('change', run);
}

function buildSubjectRowHtml(s) {
    const id = Number(s.id || 0);
    const courseCode = String(s.course_code || '');
    const name = String(s.name || '');
    const program = String(s.program || '');
    const units = Number(s.units || 0);
    const prerequisites = String(s.prerequisites || '');

    const isAssigned = String(s.is_assigned || '0') === '1';
    const assignedTo = String(s.assigned_teacher_name || '').trim();

    const prereqLabel = (prerequisites || '').trim() ? prerequisites : 'None';

    return `
<tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
  <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
  <td class="px-6 py-4 font-medium text-indigo-600">${escapeHtml(courseCode)}</td>
  <td class="px-6 py-4 font-medium text-slate-900">${escapeHtml(name)}</td>
  <td class="px-6 py-4 text-slate-600">${escapeHtml(program)}</td>
  <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium">${escapeHtml(units)}</span></td>
  <td class="px-6 py-4 text-slate-500 text-xs">${escapeHtml(prereqLabel)}</td>
    <td class="px-6 py-4 ${isAssigned ? 'text-slate-700' : 'text-slate-400 italic'}">${isAssigned ? escapeHtml(assignedTo || '—') : '—'}</td>
    <td class="px-6 py-4">${isAssigned
                ? '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Assigned</span>'
                : '<span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Unassigned</span>'}
    </td>
  <td class="px-6 py-4">
    <div class="flex items-center justify-center gap-2">
      <button
        type="button"
        class="subject-action-view p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
        title="View"
        data-subject-id="${escapeHtml(id)}"
        data-subject-course-code="${escapeHtml(courseCode)}"
        data-subject-name="${escapeHtml(name)}"
        data-subject-program="${escapeHtml(program)}"
        data-subject-units="${escapeHtml(units)}"
        data-subject-prerequisites="${escapeHtml(prerequisites)}"
      ><i class="fas fa-eye text-sm"></i></button>
      <button
        type="button"
        class="subject-action-edit p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
        title="Edit"
        data-subject-id="${escapeHtml(id)}"
        data-subject-course-code="${escapeHtml(courseCode)}"
        data-subject-name="${escapeHtml(name)}"
        data-subject-program="${escapeHtml(program)}"
        data-subject-units="${escapeHtml(units)}"
        data-subject-prerequisites="${escapeHtml(prerequisites)}"
      ><i class="fas fa-pen text-sm"></i></button>
      <button
        type="button"
        class="subject-action-archive p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded transition-colors"
        title="Archive"
        data-subject-id="${escapeHtml(id)}"
        data-subject-course-code="${escapeHtml(courseCode)}"
      ><i class="fas fa-box-archive text-sm"></i></button>
    </div>
  </td>
</tr>`;
}

function wireSubjectRowButtons(container) {
    if (!container) return;

    container.querySelectorAll('.subject-action-view').forEach(btn => {
        if (btn.dataset.wired === '1') return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', () => {
            const s = getSubjectFromDataset(btn);
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            set('viewSubjectCourseCode', s.course_code || '—');
            set('viewSubjectName', s.name || '—');
            set('viewSubjectProgram', s.program || '—');
            set('viewSubjectUnits', String(s.units));
            set('viewSubjectPrerequisites', (s.prerequisites || '').trim() || 'None');
            openSubjectModal('subjectViewModal');
        });
    });

    container.querySelectorAll('.subject-action-edit').forEach(btn => {
        if (btn.dataset.wired === '1') return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', () => {
            const s = getSubjectFromDataset(btn);
            const idEl = document.getElementById('editSubjectId');
            const codeEl = document.getElementById('editSubjectCourseCode');
            const nameEl = document.getElementById('editSubjectName');
            const programEl = document.getElementById('editSubjectProgram');
            const unitsEl = document.getElementById('editSubjectUnits');
            const prereqEl = document.getElementById('editSubjectPrerequisites');
            if (idEl) idEl.value = String(s.id);
            if (codeEl) codeEl.value = s.course_code || '';
            if (nameEl) nameEl.value = s.name || '';
            if (programEl) programEl.value = s.program || '';
            if (unitsEl) unitsEl.value = String(s.units || 0);
            if (prereqEl) prereqEl.value = s.prerequisites || '';
            openSubjectModal('subjectEditModal');
        });
    });

    container.querySelectorAll('.subject-action-archive').forEach(btn => {
        if (btn.dataset.wired === '1') return;
        btn.dataset.wired = '1';
        btn.addEventListener('click', async () => {
            const id = Number(btn.dataset.subjectId || 0);
            const code = btn.dataset.subjectCourseCode || '';
            if (!id) return;
            if (!confirm('Archive this subject?')) return;
            try {
                const data = await postJson('api/archive_subject.php', { id });
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to archive subject.');
                }
                alert((code ? code + ' ' : '') + 'archived successfully.');
                location.reload();
            } catch (err) {
                alert('Archive failed: ' + (err && err.message ? err.message : String(err)));
            }
        });
    });
}

function initSubjectFilters() {
    const page = document.getElementById('page-subjects');
    const tbody = document.getElementById('subjectTableBody');
    const searchEl = document.getElementById('subjectSearch');
    const programEl = document.getElementById('subjectProgramFilter');
    const statusEl = document.getElementById('subjectStatusFilter');

    if (!page || !tbody || (!searchEl && !programEl && !statusEl)) return;

    let abortController = null;

    const run = async () => {
        const search = (searchEl ? searchEl.value : '').trim();
        const program = programEl ? String(programEl.value || 'All') : 'All';
        const status = statusEl ? String(statusEl.value || 'all') : 'all';

        const qs = new URLSearchParams();
        qs.set('search', search);
        qs.set('program', program);
        qs.set('status', status);

        if (abortController) abortController.abort();
        abortController = new AbortController();

        try {
            const res = await fetch('api/filter_subjects.php?' + qs.toString(), { signal: abortController.signal });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data || data.status !== 'success') {
                throw new Error((data && data.message) ? data.message : 'Failed to filter subjects.');
            }

            const subjects = Array.isArray(data.subjects) ? data.subjects : [];
            tbody.innerHTML = subjects.map(buildSubjectRowHtml).join('');
            wireSubjectRowButtons(tbody);
            refreshSmartPagination('subjects');
        } catch (err) {
            if (err && err.name === 'AbortError') return;
            console.error(err);
        }
    };

    const runDebounced = debounce(run, 200);
    if (searchEl) searchEl.addEventListener('keyup', runDebounced);
    if (programEl) programEl.addEventListener('change', run);
    if (statusEl) statusEl.addEventListener('change', run);
}

// --------------------------------------------------
// Subjects Page - Modals & Actions
// --------------------------------------------------
function openSubjectModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('hidden');
}

function closeSubjectModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('hidden');
}

function getSubjectFromDataset(el) {
    const d = el && el.dataset ? el.dataset : {};
    return {
        id: d.subjectId ? Number(d.subjectId) : 0,
        course_code: d.subjectCourseCode || '',
        name: d.subjectName || '',
        program: d.subjectProgram || '',
        units: d.subjectUnits !== undefined ? Number(d.subjectUnits) : 0,
        prerequisites: d.subjectPrerequisites || '',
    };
}

function initSubjectsPage() {
    const btnImport = document.getElementById('btnSubjectImport');
    const btnAdd = document.getElementById('btnSubjectAdd');

    if (!btnImport && !btnAdd && !document.getElementById('page-subjects')) return;

    // -------- Import CSV Modal --------
    if (btnImport) {
        btnImport.addEventListener('click', () => openSubjectModal('subjectImportModal'));
    }

    ['btnSubjectImportClose', 'btnSubjectImportCancel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeSubjectModal('subjectImportModal'));
    });

    const importUploadBtn = document.getElementById('btnSubjectImportUpload');
    if (importUploadBtn) {
        importUploadBtn.addEventListener('click', async () => {
            const input = document.getElementById('subjectCsvFileInput');
            if (!input || !input.files || !input.files.length) {
                alert('Please select a CSV file to upload.');
                return;
            }
            const file = input.files[0];
            importUploadBtn.disabled = true;
            importUploadBtn.textContent = 'Uploading...';
            try {
                const data = await uploadFile(file, 'subject');
                closeSubjectModal('subjectImportModal');

                if (data && data.status === 'conflict') {
                    reloadAfterConflictResolve = true;
                    showUploadConflicts('subject', file, data);
                    return;
                }

                location.reload();
            } catch (err) {
                alert('Upload failed: ' + (err && err.message ? err.message : String(err)));
            } finally {
                importUploadBtn.disabled = false;
                importUploadBtn.textContent = 'Upload';
            }
        });
    }

    // -------- Add Subject Modal --------
    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            const form = document.getElementById('subjectAddForm');
            if (form) form.reset();
            openSubjectModal('subjectAddModal');
        });
    }

    ['btnSubjectAddClose', 'btnSubjectAddCancel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeSubjectModal('subjectAddModal'));
    });

    const addForm = document.getElementById('subjectAddForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                course_code: (document.getElementById('addSubjectCourseCode') || {}).value || '',
                name: (document.getElementById('addSubjectName') || {}).value || '',
                program: (document.getElementById('addSubjectProgram') || {}).value || '',
                units: Number((document.getElementById('addSubjectUnits') || {}).value || 0),
                prerequisites: (document.getElementById('addSubjectPrerequisites') || {}).value || '',
            };

            if (!payload.course_code.trim() || !payload.name.trim() || !payload.program.trim()) {
                alert('Course Code, Subject Name, and Program are required.');
                return;
            }

            const saveBtn = document.getElementById('btnSubjectAddSave');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }

            try {
                const data = await postJson('api/add_subject.php', payload);
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to add subject.');
                }
                closeSubjectModal('subjectAddModal');
                location.reload();
            } catch (err) {
                alert('Add failed: ' + (err && err.message ? err.message : String(err)));
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
            }
        });
    }

    // -------- View / Edit / Archive Row Actions --------
    document.querySelectorAll('.subject-action-view').forEach(btn => {
        btn.addEventListener('click', () => {
            const s = getSubjectFromDataset(btn);
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            set('viewSubjectCourseCode', s.course_code || '—');
            set('viewSubjectName', s.name || '—');
            set('viewSubjectProgram', s.program || '—');
            set('viewSubjectUnits', String(s.units));
            set('viewSubjectPrerequisites', (s.prerequisites || '').trim() || 'None');
            openSubjectModal('subjectViewModal');
        });
    });

    ['btnSubjectViewClose', 'btnSubjectViewOk'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeSubjectModal('subjectViewModal'));
    });

    document.querySelectorAll('.subject-action-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const s = getSubjectFromDataset(btn);
            const idEl = document.getElementById('editSubjectId');
            const codeEl = document.getElementById('editSubjectCourseCode');
            const nameEl = document.getElementById('editSubjectName');
            const programEl = document.getElementById('editSubjectProgram');
            const unitsEl = document.getElementById('editSubjectUnits');
            const prereqEl = document.getElementById('editSubjectPrerequisites');
            if (idEl) idEl.value = String(s.id);
            if (codeEl) codeEl.value = s.course_code || '';
            if (nameEl) nameEl.value = s.name || '';
            if (programEl) programEl.value = s.program || '';
            if (unitsEl) unitsEl.value = String(s.units || 0);
            if (prereqEl) prereqEl.value = s.prerequisites || '';
            openSubjectModal('subjectEditModal');
        });
    });

    ['btnSubjectEditClose', 'btnSubjectEditCancel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeSubjectModal('subjectEditModal'));
    });

    const editForm = document.getElementById('subjectEditForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                id: Number((document.getElementById('editSubjectId') || {}).value || 0),
                course_code: (document.getElementById('editSubjectCourseCode') || {}).value || '',
                name: (document.getElementById('editSubjectName') || {}).value || '',
                program: (document.getElementById('editSubjectProgram') || {}).value || '',
                units: Number((document.getElementById('editSubjectUnits') || {}).value || 0),
                prerequisites: (document.getElementById('editSubjectPrerequisites') || {}).value || '',
            };

            if (!payload.id || !payload.course_code.trim() || !payload.name.trim() || !payload.program.trim()) {
                alert('ID, Course Code, Subject Name, and Program are required.');
                return;
            }

            const saveBtn = document.getElementById('btnSubjectEditSave');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }

            try {
                const data = await postJson('api/update_subject.php', payload);
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to update subject.');
                }
                closeSubjectModal('subjectEditModal');
                location.reload();
            } catch (err) {
                alert('Update failed: ' + (err && err.message ? err.message : String(err)));
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
            }
        });
    }

    document.querySelectorAll('.subject-action-archive').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = Number(btn.dataset.subjectId || 0);
            const code = btn.dataset.subjectCourseCode || '';
            if (!id) return;
            if (!confirm('Archive this subject?')) return;
            try {
                const data = await postJson('api/archive_subject.php', { id });
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to archive subject.');
                }
                alert((code ? code + ' ' : '') + 'archived successfully.');
                location.reload();
            } catch (err) {
                alert('Archive failed: ' + (err && err.message ? err.message : String(err)));
            }
        });
    });
}

// --------------------------------------------------
// Teachers Page - Modals & Actions
// --------------------------------------------------
function openTeacherModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('hidden');
}

function closeTeacherModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('hidden');
}

function getTeacherFromDataset(el) {
    const d = el && el.dataset ? el.dataset : {};
    return {
        id: d.teacherId ? Number(d.teacherId) : 0,
        name: d.teacherName || '',
        email: d.teacherEmail || '',
        type: d.teacherType || '',
        max_units: d.teacherMaxUnits !== undefined ? Number(d.teacherMaxUnits) : 0,
        current_units: d.teacherCurrentUnits !== undefined ? Number(d.teacherCurrentUnits) : 0,
        expertise_tags: d.teacherExpertiseTags || '',
    };
}

function teacherAvailabilityDays() {
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
}

function dayKey(day) {
    return String(day || '').replace(/\s+/g, '');
}

function resetTeacherAvailability(prefix) {
    teacherAvailabilityDays().forEach(day => {
        const k = dayKey(day);
        const enabled = document.getElementById(prefix + 'TeacherAvail' + k + 'Enabled');
        const start = document.getElementById(prefix + 'TeacherAvail' + k + 'Start');
        const end = document.getElementById(prefix + 'TeacherAvail' + k + 'End');
        if (enabled) enabled.checked = false;
        if (start) start.value = '';
        if (end) end.value = '';
    });
}

function collectTeacherAvailability(prefix) {
    const rows = [];
    for (const day of teacherAvailabilityDays()) {
        const k = dayKey(day);
        const enabledEl = document.getElementById(prefix + 'TeacherAvail' + k + 'Enabled');
        const startEl = document.getElementById(prefix + 'TeacherAvail' + k + 'Start');
        const endEl = document.getElementById(prefix + 'TeacherAvail' + k + 'End');

        const enabled = !!(enabledEl && enabledEl.checked);
        const start = startEl ? String(startEl.value || '').trim() : '';
        const end = endEl ? String(endEl.value || '').trim() : '';

        if (!enabled) continue;

        if (!start || !end) {
            throw new Error('Please set start and end time for ' + day + '.');
        }

        // Time inputs are HH:MM. Simple string compare works.
        if (start >= end) {
            throw new Error('End time must be after start time for ' + day + '.');
        }

        rows.push({ day_of_week: day, start_time: start, end_time: end });
    }
    return rows;
}

function setTeacherAvailability(prefix, availabilityRows) {
    resetTeacherAvailability(prefix);
    const byDay = {};
    (Array.isArray(availabilityRows) ? availabilityRows : []).forEach(r => {
        const day = String(r.day_of_week || '').trim();
        if (!day) return;
        // Only support one range per day in the UI.
        if (byDay[day]) return;
        byDay[day] = {
            start_time: String(r.start_time || '').slice(0, 5),
            end_time: String(r.end_time || '').slice(0, 5),
        };
    });

    teacherAvailabilityDays().forEach(day => {
        const v = byDay[day];
        const k = dayKey(day);
        const enabled = document.getElementById(prefix + 'TeacherAvail' + k + 'Enabled');
        const start = document.getElementById(prefix + 'TeacherAvail' + k + 'Start');
        const end = document.getElementById(prefix + 'TeacherAvail' + k + 'End');
        if (!v) return;
        if (enabled) enabled.checked = true;
        if (start) start.value = v.start_time || '';
        if (end) end.value = v.end_time || '';
    });
}

async function fetchTeacherAvailability(teacherId) {
    const id = Number(teacherId || 0);
    if (!id) return [];
    const res = await fetch('api/get_teacher_availability.php?teacher_id=' + encodeURIComponent(String(id)));
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data || data.status !== 'success') {
        throw new Error((data && data.message) ? data.message : 'Failed to load availability.');
    }
    return Array.isArray(data.availability) ? data.availability : [];
}

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload || {}),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error((data && data.message) ? data.message : 'Request failed.');
    }
    return data;
}

function initTeachersPage() {
    const btnImport = document.getElementById('btnTeacherImport');
    const btnAdd = document.getElementById('btnTeacherAdd');

    // If page isn't present (e.g. standalone pages), do nothing.
    if (!btnImport && !btnAdd && !document.getElementById('page-teachers')) return;

    // -------- Import CSV Modal --------
    if (btnImport) {
        btnImport.addEventListener('click', () => openTeacherModal('teacherImportModal'));
    }

    ['btnTeacherImportClose', 'btnTeacherImportCancel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeTeacherModal('teacherImportModal'));
    });

    const importUploadBtn = document.getElementById('btnTeacherImportUpload');
    if (importUploadBtn) {
        importUploadBtn.addEventListener('click', async () => {
            const input = document.getElementById('teacherCsvFileInput');
            if (!input || !input.files || !input.files.length) {
                alert('Please select a CSV file to upload.');
                return;
            }
            const file = input.files[0];
            importUploadBtn.disabled = true;
            importUploadBtn.textContent = 'Uploading...';
            try {
                const data = await uploadFile(file, 'teacher');
                closeTeacherModal('teacherImportModal');

                if (data && data.status === 'conflict') {
                    reloadAfterConflictResolve = true;
                    showUploadConflicts('teacher', file, data);
                    return;
                }

                location.reload();
            } catch (err) {
                alert('Upload failed: ' + (err && err.message ? err.message : String(err)));
            } finally {
                importUploadBtn.disabled = false;
                importUploadBtn.textContent = 'Upload';
            }
        });
    }

    // -------- Add Teacher Modal --------
    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            const form = document.getElementById('teacherAddForm');
            if (form) form.reset();
            resetTeacherAvailability('add');
            openTeacherModal('teacherAddModal');
        });
    }

    ['btnTeacherAddClose', 'btnTeacherAddCancel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeTeacherModal('teacherAddModal'));
    });

    const addForm = document.getElementById('teacherAddForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            let availability = [];
            try {
                availability = collectTeacherAvailability('add');
            } catch (err) {
                alert((err && err.message) ? err.message : String(err));
                return;
            }

            const payload = {
                name: (document.getElementById('addTeacherName') || {}).value || '',
                email: (document.getElementById('addTeacherEmail') || {}).value || '',
                type: (document.getElementById('addTeacherType') || {}).value || 'Full-time',
                max_units: Number((document.getElementById('addTeacherMaxUnits') || {}).value || 0),
                expertise_tags: (document.getElementById('addTeacherExpertise') || {}).value || '',
                availability,
            };

            if (!payload.name.trim() || !payload.email.trim()) {
                alert('Name and Email are required.');
                return;
            }

            const saveBtn = document.getElementById('btnTeacherAddSave');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }

            try {
                const data = await postJson('api/add_teacher.php', payload);
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to add teacher.');
                }
                closeTeacherModal('teacherAddModal');
                location.reload();
            } catch (err) {
                alert('Add failed: ' + (err && err.message ? err.message : String(err)));
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
            }
        });
    }

    // -------- View / Edit / Archive Row Actions --------
    document.querySelectorAll('.teacher-action-view').forEach(btn => {
        btn.addEventListener('click', () => {
            const t = getTeacherFromDataset(btn);
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            set('viewTeacherName', t.name || '—');
            set('viewTeacherEmail', t.email || '—');
            set('viewTeacherType', t.type || '—');
            set('viewTeacherMaxUnits', String(t.max_units));
            set('viewTeacherCurrentUnits', String(t.current_units));
            set('viewTeacherExpertise', (t.expertise_tags || '').trim() || '—');
            openTeacherModal('teacherViewModal');
        });
    });

    ['btnTeacherViewClose', 'btnTeacherViewOk'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeTeacherModal('teacherViewModal'));
    });

    document.querySelectorAll('.teacher-action-edit').forEach(btn => {
        btn.addEventListener('click', async () => {
            const t = getTeacherFromDataset(btn);
            const idEl = document.getElementById('editTeacherId');
            const nameEl = document.getElementById('editTeacherName');
            const emailEl = document.getElementById('editTeacherEmail');
            const typeEl = document.getElementById('editTeacherType');
            const maxEl = document.getElementById('editTeacherMaxUnits');
            const expEl = document.getElementById('editTeacherExpertise');
            if (idEl) idEl.value = String(t.id);
            if (nameEl) nameEl.value = t.name || '';
            if (emailEl) emailEl.value = t.email || '';
            if (typeEl) typeEl.value = t.type || 'Full-time';
            if (maxEl) maxEl.value = String(t.max_units || 0);
            if (expEl) expEl.value = t.expertise_tags || '';

            // Default availability to empty, then fetch and populate.
            resetTeacherAvailability('edit');
            openTeacherModal('teacherEditModal');

            try {
                const rows = await fetchTeacherAvailability(t.id);
                setTeacherAvailability('edit', rows);
            } catch (err) {
                // Don't block editing if availability can't be loaded.
                console.warn(err);
            }
        });
    });

    ['btnTeacherEditClose', 'btnTeacherEditCancel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeTeacherModal('teacherEditModal'));
    });

    const editForm = document.getElementById('teacherEditForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            let availability = [];
            try {
                availability = collectTeacherAvailability('edit');
            } catch (err) {
                alert((err && err.message) ? err.message : String(err));
                return;
            }

            const payload = {
                id: Number((document.getElementById('editTeacherId') || {}).value || 0),
                name: (document.getElementById('editTeacherName') || {}).value || '',
                email: (document.getElementById('editTeacherEmail') || {}).value || '',
                type: (document.getElementById('editTeacherType') || {}).value || 'Full-time',
                max_units: Number((document.getElementById('editTeacherMaxUnits') || {}).value || 0),
                expertise_tags: (document.getElementById('editTeacherExpertise') || {}).value || '',
                availability,
            };

            if (!payload.id || !payload.name.trim() || !payload.email.trim()) {
                alert('ID, Name and Email are required.');
                return;
            }

            const saveBtn = document.getElementById('btnTeacherEditSave');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }

            try {
                const data = await postJson('api/update_teacher.php', payload);
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to update teacher.');
                }
                closeTeacherModal('teacherEditModal');
                location.reload();
            } catch (err) {
                alert('Update failed: ' + (err && err.message ? err.message : String(err)));
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
            }
        });
    }

    document.querySelectorAll('.teacher-action-archive').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = Number(btn.dataset.teacherId || 0);
            const name = btn.dataset.teacherName || '';
            if (!id) return;
            if (!confirm('Archive this teacher?')) return;
            try {
                const data = await postJson('api/archive_teacher.php', { id });
                if (!data || data.status !== 'success') {
                    throw new Error((data && data.message) ? data.message : 'Failed to archive teacher.');
                }
                alert((name ? name + ' ' : '') + 'archived successfully.');
                location.reload();
            } catch (err) {
                alert('Archive failed: ' + (err && err.message ? err.message : String(err)));
            }
        });
    });
}

// --------------------------------------------------
// File Upload Handling
// --------------------------------------------------
const uploadZones = ['teacher', 'subject', 'schedule'];
let uploadedFiles = { teacher: false, subject: false, schedule: false };

function initUploadDatasetScopeControls() {
    const toggle = document.getElementById('uploadPreviousToggle');
    const ayInput = document.getElementById('uploadAcademicYear');
    const semSelect = document.getElementById('uploadSemester');
    if (!toggle || !ayInput || !semSelect) return;

    const sync = () => {
        const isPrev = !!toggle.checked;
        ayInput.disabled = !isPrev;
        semSelect.disabled = !isPrev;

        if (!isPrev) {
            ayInput.value = '';
            semSelect.value = '';
        }
    };

    toggle.addEventListener('change', sync);
    sync();
}

function initFileUploads() {
    initUploadDatasetScopeControls();

    uploadZones.forEach(type => {
        const zone = document.getElementById(type + 'Upload');
        const input = document.getElementById(type + 'FileInput');

        if (!zone || !input) return;

        zone.addEventListener('click', () => input.click());

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('drag-over');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                handleFile(type, e.dataTransfer.files[0]);
            }
        });

        input.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFile(type, e.target.files[0]);
            }
        });
    });
}

function handleFile(type, file) {
    if (!file) return;

    const zone = document.getElementById(type + 'Upload');
    const fileInfo = document.getElementById(type + 'FileInfo');
    const fileName = document.getElementById(type + 'FileName');
    const status = document.getElementById(type + 'Status');

    // Show file info in UI immediately
    fileInfo.classList.remove('hidden');
    fileName.textContent = file.name;

    // Upload to backend
    uploadFile(file, type)
        .then((data) => {
            zone.classList.add('uploaded');
            status.classList.remove('bg-slate-300');
            status.classList.add('bg-green-500');
            uploadedFiles[type] = true;
            updateUploadSummary();

            if (data && data.status === 'conflict') {
                showUploadConflicts(type, file, data);
            }
        })
        .catch(err => {
            // Revert UI on failure
            fileInfo.classList.add('hidden');
            alert('Upload failed for ' + type + ': ' + err.message);
        });
}

/**
 * Uploads a file to api/upload.php via FormData.
 * @param {File} file - The file to upload.
 * @param {string} type - One of 'teacher', 'subject', 'schedule'.
 * @returns {Promise<object>} The parsed JSON response.
 */
async function uploadFile(file, type) {
    let conflictAction = 'detect';
    if (arguments.length >= 3 && arguments[2] && typeof arguments[2] === 'object') {
        conflictAction = arguments[2].conflict_action || 'detect';
    }

    const previousToggle = document.getElementById('uploadPreviousToggle');
    const datasetScope = (previousToggle && previousToggle.checked) ? 'previous' : 'current';

    const academicYearInput = document.getElementById('uploadAcademicYear');
    const semesterSelect = document.getElementById('uploadSemester');
    const academicYear = academicYearInput ? String(academicYearInput.value || '').trim() : '';
    const semester = semesterSelect ? String(semesterSelect.value || '').trim() : '';

    if (datasetScope === 'previous') {
        if (!academicYear) {
            throw new Error('Please enter the Academic Year for the previous dataset (e.g., 2025-2026).');
        }
        if (!semester) {
            throw new Error('Please select the Semester for the previous dataset.');
        }
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    formData.append('conflict_action', conflictAction);
    formData.append('dataset_scope', datasetScope);

    if (datasetScope === 'previous') {
        formData.append('academic_year', academicYear);
        formData.append('semester', semester);
    }

    const response = await fetch('api/upload.php', {
        method: 'POST',
        body: formData,
    });

    const data = await response.json();

    // Some endpoints may return non-2xx for conflict-style responses.
    // Treat known statuses as valid outcomes and let the caller decide UI.
    if (data && data.status === 'conflict') {
        return data;
    }

    if (!response.ok) {
        throw new Error((data && data.message) ? data.message : 'Upload failed.');
    }

    if (data.status === 'success') {
        if (data.saved_only) {
            alert(data.message || 'Saved as historical dataset for forecasting (not imported into current scheduling data).');
            return data;
        }

        const updatedMsg = (data.rows_updated && data.rows_updated > 0)
            ? (' ' + data.rows_updated + ' rows updated.')
            : '';

        if (data.duplicates && Array.isArray(data.duplicates) && data.duplicates.length > 0) {
            const summary = document.getElementById('conflictSummary');
            const list = document.getElementById('conflictList');
            const modal = document.getElementById('conflictModal');

            if (summary) {
                summary.textContent = `Successfully inserted ${data.rows_inserted} new records. However, the following entries were skipped because they already exist in the system:`;
            }

            if (list) {
                list.innerHTML = '';
                data.duplicates.forEach((name) => {
                    const li = document.createElement('li');
                    li.className = 'text-red-600 text-sm border-b border-red-200 py-1 last:border-b-0';
                    li.textContent = String(name);
                    list.appendChild(li);
                });
            }

            if (modal) {
                modal.classList.remove('hidden');
            }
        } else {
            alert('Successfully inserted ' + data.rows_inserted + ' rows!' + updatedMsg);
        }
        return data;
    }

    throw new Error(data.message || 'Upload failed.');
}

function stringifyMini(obj, keys) {
    if (!obj || typeof obj !== 'object') return '';
    const parts = [];
    keys.forEach(k => {
        if (obj[k] !== undefined && obj[k] !== null && String(obj[k]).trim() !== '') {
            parts.push(k + ': ' + obj[k]);
        }
    });
    return parts.join(' • ');
}

function showUploadConflicts(type, file, data) {
    conflictModalState.type = type;
    conflictModalState.file = file;
    conflictModalState.conflicts = Array.isArray(data.conflicts) ? data.conflicts : [];
    conflictModalState.rowsInserted = data.rows_inserted || 0;

    const subtitle = document.getElementById('uploadConflictSubtitle');
    const summary = document.getElementById('uploadConflictSummary');
    const tbody = document.getElementById('uploadConflictTableBody');
    if (!subtitle || !summary || !tbody) return;

    const label = type.charAt(0).toUpperCase() + type.slice(1);
    subtitle.textContent = label + ' upload found duplicate keys already in the database.';
    summary.textContent = (conflictModalState.conflicts.length) + ' duplicates • ' + conflictModalState.rowsInserted + ' inserted as new';

    tbody.innerHTML = '';

    const existingKeysByType = {
        teacher: ['name', 'email', 'type', 'max_units', 'expertise_tags'],
        subject: ['course_code', 'name', 'program', 'units', 'prerequisites'],
    };

    const keys = existingKeysByType[type] || [];

    conflictModalState.conflicts.slice(0, 200).forEach((c) => {
        const tr = document.createElement('tr');
        tr.className = 'border-b border-slate-100 hover:bg-slate-50';

        const tdKey = document.createElement('td');
        tdKey.className = 'px-4 py-3 font-medium text-slate-900 whitespace-nowrap';
        tdKey.textContent = (c.key || 'key') + '=' + (c.value || '');

        const tdExisting = document.createElement('td');
        tdExisting.className = 'px-4 py-3 text-slate-700';
        tdExisting.textContent = stringifyMini(c.existing, keys);

        const tdIncoming = document.createElement('td');
        tdIncoming.className = 'px-4 py-3 text-slate-700';
        tdIncoming.textContent = stringifyMini(c.incoming, keys);

        tr.appendChild(tdKey);
        tr.appendChild(tdExisting);
        tr.appendChild(tdIncoming);
        tbody.appendChild(tr);
    });

    openUploadConflictModal();
}

async function resolveConflictUpdate() {
    if (!conflictModalState.file || !conflictModalState.type) return;

    const btnUpdate = document.getElementById('uploadConflictUpdateBtn');
    const btnKeep = document.getElementById('uploadConflictKeepBtn');
    if (btnUpdate) btnUpdate.disabled = true;
    if (btnKeep) btnKeep.disabled = true;

    try {
        const data = await uploadFile(conflictModalState.file, conflictModalState.type, { conflict_action: 'update' });
        const shouldReload = reloadAfterConflictResolve;
        closeUploadConflictModal();
        if (shouldReload) {
            location.reload();
        }
        // Success alert already shown by uploadFile
        return data;
    } catch (err) {
        alert('Update failed: ' + (err && err.message ? err.message : String(err)));
    } finally {
        if (btnUpdate) btnUpdate.disabled = false;
        if (btnKeep) btnKeep.disabled = false;
    }
}

function resolveConflictKeep() {
    // Keep existing records; we already inserted the non-duplicates.
    const shouldReload = reloadAfterConflictResolve;
    closeUploadConflictModal();
    if (shouldReload) {
        location.reload();
    }
}

function removeFile(type) {
    const zone = document.getElementById(type + 'Upload');
    const fileInfo = document.getElementById(type + 'FileInfo');
    const input = document.getElementById(type + 'FileInput');
    const status = document.getElementById(type + 'Status');

    zone.classList.remove('uploaded');
    fileInfo.classList.add('hidden');
    input.value = '';
    status.classList.remove('bg-green-500');
    status.classList.add('bg-slate-300');

    uploadedFiles[type] = false;
    updateUploadSummary();
    event.stopPropagation();
}

function updateUploadSummary() {
    const count = Object.values(uploadedFiles).filter(v => v).length;
    document.getElementById('uploadSummary').textContent = count + ' of 3 files uploaded';
}

// --------------------------------------------------
// Generate Schedule (real API call)
// --------------------------------------------------
async function generateSchedule() {
    const btn = document.getElementById('generateBtn');
    const indicator = document.getElementById('generatingIndicator');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Generating...';
    indicator.classList.remove('hidden');
    indicator.classList.add('flex');

    try {
        const response = await fetch('api/generate_schedule.php', {
            method: 'POST',
        });

        const data = await response.json();

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bolt"></i> Generate Schedule';
        indicator.classList.add('hidden');
        indicator.classList.remove('flex');

        if (!response.ok || data.status !== 'success') {
            alert('Error: ' + (data.message || 'Schedule generation failed.'));
            return;
        }

        if (data.status === 'success') {
            if (data.ai_enabled === true) {
                alert(
                    '✨ Schedule Generated Successfully!\n\n' +
                    `• Assigned: ${data.assigned_count}\n` +
                    `• Unassigned: ${data.unassigned_count}\n` +
                    `• Gemini AI Evaluations: ${data.ai_calls} calls made.`
                );
            } else {
                alert(
                    '⚠️ Schedule Generated via Fallback Logic.\n' +
                    '(Gemini API Key missing or invalid. AI was NOT used).'
                );
            }
        }

        // Reload to reflect new data in the tables
        location.reload();

    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bolt"></i> Generate Schedule';
        indicator.classList.add('hidden');
        indicator.classList.remove('flex');

        alert('Network error: ' + err.message);
    }
}

// --------------------------------------------------
// Manual Override (real API call)
// --------------------------------------------------
async function submitOverride(assignmentId) {
    const newTeacherSelect = document.getElementById('overrideTeacherSelect');
    const reasonTextarea = document.getElementById('overrideReason');

    const newTeacherId = newTeacherSelect ? newTeacherSelect.value : '';
    const reason = reasonTextarea ? reasonTextarea.value.trim() : '';

    if (!newTeacherId) {
        alert('Please select a teacher to reassign to.');
        return;
    }
    if (!reason) {
        alert('Please provide a reason for the override.');
        return;
    }

    const id = assignmentId || currentAssignmentId;
    if (!id) {
        alert('No assignment selected for override.');
        return;
    }

    try {
        const response = await fetch('api/override.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                assignment_id: Number(id),
                new_teacher_id: Number(newTeacherId),
                reason: reason,
            }),
        });

        const data = await response.json();

        if (!response.ok || data.status !== 'success') {
            alert('Override failed: ' + (data.message || 'Unknown error.'));
            return;
        }

        alert('Override saved successfully!');
        closeModal();
        location.reload();

    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// --------------------------------------------------
// Global Search Functionality - FULLY FUNCTIONAL
// --------------------------------------------------
let searchAbortController = null;
let searchTimeout = null;
let currentSearchResults = [];
let selectedResultIndex = -1;

function initializeGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    const dropdown = document.getElementById('searchResultsDropdown');
    const resultsList = document.getElementById('searchResultsList');
    const loadingEl = document.getElementById('searchLoading');
    const noResultsEl = document.getElementById('searchNoResults');

    if (!searchInput || !dropdown || !resultsList || !loadingEl || !noResultsEl) {
        console.warn('Global search elements not found');
        return;
    }

    // Input handling
    searchInput.addEventListener('input', debounceSearch);
    searchInput.addEventListener('focus', () => {
        if (currentSearchResults.length > 0) {
            dropdown.classList.remove('hidden');
        }
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', (e) => {
        if (!dropdown.classList.contains('hidden')) {
            handleSearchKeydown(e);
        } else if (e.key === 'Escape') {
            searchInput.blur();
        }
    });

    // Hide dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            hideSearchDropdown();
        }
    });

    function debounceSearch() {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => performGlobalSearch(searchInput.value.trim()), 250);
    }
}

async function performGlobalSearch(query) {
    const searchInput = document.getElementById('globalSearch');
    const dropdown = document.getElementById('searchResultsDropdown');
    const resultsList = document.getElementById('searchResultsList');
    const loadingEl = document.getElementById('searchLoading');
    const noResultsEl = document.getElementById('searchNoResults');

    if (query.length < 2) {
        hideSearchDropdown();
        return;
    }

    showLoading();
    
    // Cancel previous requests
    if (searchAbortController) {
        searchAbortController.abort();
    }
    searchAbortController = new AbortController();

    try {
        const [teachersRes, subjectsRes] = await Promise.allSettled([
            fetch(`api/filter_teachers.php?search=${encodeURIComponent(query)}`, { signal: searchAbortController.signal }),
            fetch(`api/filter_subjects.php?search=${encodeURIComponent(query)}`, { signal: searchAbortController.signal })
        ]);

        currentSearchResults = [];

        // Teachers
        if (teachersRes.status === 'fulfilled' && teachersRes.value.ok) {
            const teachersData = await teachersRes.value.json();
            if (teachersData.status === 'success') {
                currentSearchResults.push(...teachersData.teachers.slice(0, 5).map(t => ({
                    id: t.id,
                    type: 'teacher',
                    title: t.name,
                    subtitle: t.type + ' • ' + t.expertise_tags || 'No expertise',
                    data: t
                })));
            }
        }

        // Subjects  
        if (subjectsRes.status === 'fulfilled' && subjectsRes.value.ok) {
            const subjectsData = await subjectsRes.value.json();
            if (subjectsData.status === 'success') {
                currentSearchResults.push(...subjectsData.subjects.slice(0, 5).map(s => ({
                    id: s.id,
                    type: 'subject', 
                    title: s.course_code + ' - ' + s.name,
                    subtitle: s.program + ' (' + s.units + ' units)',
                    data: s
                })));
            }
        }

        renderSearchResults(currentSearchResults);

        // Highlight in dashboard table
        highlightDashboardResults(query);

    } catch (err) {
        if (err.name !== 'AbortError') {
            console.error('Search failed:', err);
            showNoResults();
        }
    } finally {
        hideLoading();
    }

    function showLoading() {
        resultsList.innerHTML = '';
        loadingEl.classList.remove('hidden');
        noResultsEl.classList.add('hidden');
        dropdown.classList.remove('hidden');
    }

    function hideLoading() {
        loadingEl.classList.add('hidden');
    }

    function showNoResults() {
        resultsList.innerHTML = '';
        noResultsEl.classList.remove('hidden');
        dropdown.classList.remove('hidden');
    }

    function renderSearchResults(results) {
        resultsList.innerHTML = '';
        if (results.length === 0) {
            showNoResults();
            return;
        }

        noResultsEl.classList.add('hidden');
        results.forEach((result, index) => {
            const item = document.createElement('div');
            item.className = 'search-result-item';
            item.dataset.index = index;
            item.dataset.type = result.type;
            item.dataset.id = result.id;
            item.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r ${result.type === 'teacher' ? 'from-indigo-500 to-blue-600' : 'from-emerald-500 to-teal-600'} rounded-lg flex items-center justify-center text-white font-medium text-sm flex-shrink-0">
                        ${result.type === 'teacher' ? initialsFromName(result.title) : result.title.charAt(0)}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-slate-900 truncate">${escapeHtml(result.title)}</div>
                        <div class="search-result-type text-indigo-600">${result.type.charAt(0).toUpperCase() + result.type.slice(1)}</div>
                        <div class="search-result-preview truncate">${escapeHtml(result.subtitle)}</div>
                    </div>
                </div>
            `;
            item.addEventListener('click', () => selectSearchResult(result));
            resultsList.appendChild(item);
        });
    }
}

function highlightDashboardResults(query) {
    const tbody = document.getElementById('dashboardReportTbody');
    if (!tbody) return;

    // Clear previous highlights
    tbody.querySelectorAll('.search-highlight-row').forEach(row => {
        row.classList.remove('search-highlight-row', 'ring-2', 'ring-indigo-500', 'ring-opacity-50');
    });

    // Highlight matching rows
    const lowerQuery = query.toLowerCase();
    tbody.querySelectorAll('tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(lowerQuery)) {
            row.classList.add('search-highlight-row', 'ring-2', 'ring-indigo-500', 'ring-opacity-50');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

function handleSearchKeydown(e) {
    const resultsList = document.getElementById('searchResultsList');
    const results = Array.from(resultsList.querySelectorAll('.search-result-item'));
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedResultIndex = Math.min(selectedResultIndex + 1, results.length - 1);
        updateSelection(results);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedResultIndex = Math.max(selectedResultIndex - 1, -1);
        updateSelection(results);
    } else if (e.key === 'Enter' && selectedResultIndex >= 0) {
        e.preventDefault();
        const result = currentSearchResults[selectedResultIndex];
        if (result) selectSearchResult(result);
    } else if (e.key === 'Escape') {
        hideSearchDropdown();
    }
}

function updateSelection(results) {
    results.forEach((item, index) => {
        item.classList.toggle('search-highlight', index === selectedResultIndex);
    });
}

function selectSearchResult(result) {
    hideSearchDropdown();
    const searchInput = document.getElementById('globalSearch');
    searchInput.value = result.title;

    // Navigate to relevant page or highlight
    if (result.type === 'teacher') {
        switchPage('teachers');
    } else if (result.type === 'subject') {
        switchPage('subjects');
    }

    // Highlight row
    highlightDashboardResults(result.title);
}

function hideSearchDropdown() {
    const dropdown = document.getElementById('searchResultsDropdown');
    dropdown.classList.add('hidden');
    selectedResultIndex = -1;
    currentSearchResults = [];
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initializeGlobalSearch);

// --------------------------------------------------
// Keyboard Shortcuts
// --------------------------------------------------
document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
});

// Initialize search when document is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSearch);
} else {
    initializeSearch();
}

// --------------------------------------------------
// Load Report Modals (View / Close)
// --------------------------------------------------
function openReportModal(type) {
    const modal = document.getElementById('reportModal-' + type);
    if (modal) modal.classList.remove('hidden');
}

function closeReportModal(type) {
    const modal = document.getElementById('reportModal-' + type);
    if (modal) modal.classList.add('hidden');
}

// Close modals on backdrop click
document.addEventListener('click', (e) => {
    ['faculty', 'subject', 'overload'].forEach(type => {
        const modal = document.getElementById('reportModal-' + type);
        if (modal && e.target === modal) modal.classList.add('hidden');
    });
});

// --------------------------------------------------
// Load Report Export (CSV & PDF)
// --------------------------------------------------
function toggleExportMenu(event, type) {
    event.stopPropagation();
    ['faculty', 'subject', 'overload'].forEach(t => {
        const m = document.getElementById('exportMenu-' + t);
        if (m) m.classList.add('hidden');
    });
    const menu = document.getElementById('exportMenu-' + type);
    if (menu) menu.classList.toggle('hidden');
}

document.addEventListener('click', () => {
    ['faculty', 'subject', 'overload'].forEach(t => {
        const m = document.getElementById('exportMenu-' + t);
        if (m) m.classList.add('hidden');
    });
});

async function exportReport(type, format) {
    const el = document.getElementById('reportData-' + type);
    if (!el) { alert('No report data available.'); return; }

    let rows;
    try { rows = JSON.parse(el.textContent); } catch (_) { alert('Failed to parse report data.'); return; }
    if (!rows.length) { alert('No data to export.'); return; }

    // Close any open export dropdown
    ['faculty', 'subject', 'overload'].forEach(t => {
        const m = document.getElementById('exportMenu-' + t);
        if (m) m.classList.add('hidden');
    });

    const filenames = { faculty: 'faculty_load_summary', subject: 'subject_assignment_report', overload: 'overload_analysis' };

    if (format === 'csv') {
        let csv = '';
        if (type === 'faculty') {
            csv = 'Teacher,Type,Current Units,Max Units,Status,Expertise\n';
            rows.forEach(r => {
                const cur = Number(r.current_units), max = Number(r.max_units);
                const status = cur > max ? 'Overloaded' : (cur === max ? 'At Capacity' : 'Normal');
                csv += csvRow([r.name, r.type, cur, max, status, r.expertise_tags || '']);
            });
        } else if (type === 'subject') {
            csv = 'Code,Subject,Program,Units,Assigned To,Status\n';
            rows.forEach(r => {
                const teacher = r.teacher_name || 'Unassigned';
                const status = teacher === 'Unassigned' ? 'Unassigned' : (r.assignment_status || 'Assigned');
                csv += csvRow([r.course_code, r.subject_name, r.program, r.units, teacher, status]);
            });
        } else if (type === 'overload') {
            csv = 'Teacher,Type,Current Units,Max Units,Excess Units\n';
            rows.forEach(r => {
                csv += csvRow([r.name, r.type, r.current_units, r.max_units, r.excess_units]);
            });
        }
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = (filenames[type] || 'report') + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

    } else if (format === 'pdf') {
        try {
            await ensurePdfLibrariesLoaded();
        } catch (_) {
            alert('Unable to load PDF export library. Please check your internet connection and try again.');
            return;
        }

        const titles = { faculty: 'Faculty Load Summary', subject: 'Subject Assignment Report', overload: 'Overload Analysis' };
        const { jsPDF } = window.jspdf;
        const isWide = type === 'subject';
        const doc = new jsPDF({ orientation: isWide ? 'landscape' : 'portrait', unit: 'pt', format: 'a4' });

        let headers = [];
        let body = [];

        if (type === 'faculty') {
            headers = ['Teacher', 'Type', 'Current Units', 'Max Units', 'Status', 'Expertise'];
            body = rows.map(r => {
                const cur = Number(r.current_units);
                const max = Number(r.max_units);
                const status = cur > max ? 'Overloaded' : (cur === max ? 'At Capacity' : 'Normal');
                return [toText(r.name), toText(r.type), cur, max, status, toText(r.expertise_tags || 'N/A')];
            });
        } else if (type === 'subject') {
            headers = ['Code', 'Subject', 'Program', 'Units', 'Assigned To', 'Status'];
            body = rows.map(r => {
                const teacher = r.teacher_name || 'Unassigned';
                const status = teacher === 'Unassigned' ? 'Unassigned' : (r.assignment_status || 'Assigned');
                return [toText(r.course_code), toText(r.subject_name), toText(r.program), Number(r.units), toText(teacher), toText(status)];
            });
        } else if (type === 'overload') {
            headers = ['Teacher', 'Type', 'Current Units', 'Max Units', 'Excess Units'];
            body = rows.map(r => [toText(r.name), toText(r.type), Number(r.current_units), Number(r.max_units), '+' + Number(r.excess_units)]);
        }

        doc.setFontSize(16);
        doc.text(titles[type] || 'Report', 40, 40);
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text('SmartLoad - Generated ' + new Date().toLocaleString(), 40, 58);

        doc.autoTable({
            head: [headers],
            body: body,
            startY: 72,
            styles: { fontSize: 9, cellPadding: 6, textColor: [30, 41, 59] },
            headStyles: { fillColor: [241, 245, 249], textColor: [71, 85, 105], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [248, 250, 252] },
            margin: { left: 40, right: 40 }
        });

        doc.save((filenames[type] || 'report') + '.pdf');
    }
}

let pdfLibPromise = null;

function ensurePdfLibrariesLoaded() {
    if (window.jspdf && window.jspdf.jsPDF && window.jspdf.jsPDF.API.autoTable) {
        return Promise.resolve();
    }
    if (pdfLibPromise) return pdfLibPromise;

    const loadScript = (src) => new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });

    pdfLibPromise = (async () => {
        if (!(window.jspdf && window.jspdf.jsPDF)) {
            await loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js');
        }
        if (!(window.jspdf && window.jspdf.jsPDF && window.jspdf.jsPDF.API.autoTable)) {
            await loadScript('https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js');
        }
    })().catch((err) => {
        pdfLibPromise = null;
        throw err;
    });

    return pdfLibPromise;
}

function csvRow(fields) {
    return fields.map(f => '"' + String(f).replace(/"/g, '""') + '"').join(',') + '\n';
}

function toText(value) {
    return value === null || value === undefined ? '' : String(value);
}

// --------------------------------------------------
// Audit Log Export (CSV & PDF)
// --------------------------------------------------
async function exportAuditLogs(format) {
    const el = document.getElementById('auditLogData');
    if (!el) { alert('No audit log data available.'); return; }

    let logs;
    try { logs = JSON.parse(el.textContent); } catch (_) { alert('Failed to parse audit log data.'); return; }
    if (!logs.length) { alert('No logs to export.'); return; }

    // Close export dropdown
    document.getElementById('exportMenu-audit').classList.add('hidden');

    if (format === 'csv') {
        let csv = 'Action Type,Description,User,Date/Time\n';
        logs.forEach(l => {
            const date = new Date(l.created_at).toLocaleString();
            csv += csvRow([l.action_type, l.description || '', l.user, date]);
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'audit_logs_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

    } else if (format === 'pdf') {
        try {
            await ensurePdfLibrariesLoaded();
        } catch (_) {
            alert('Unable to load PDF export library. Please check your internet connection and try again.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

        const headers = ['Action Type', 'Description', 'User', 'Date/Time'];
        const body = logs.map(l => {
            const date = new Date(l.created_at).toLocaleString();
            return [toText(l.action_type), toText(l.description || ''), toText(l.user), toText(date)];
        });

        doc.setFontSize(16);
        doc.text('Audit Trail Export', 40, 40);
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text('SmartLoad - Generated ' + new Date().toLocaleString(), 40, 58);

        doc.autoTable({
            head: [headers],
            body: body,
            startY: 72,
            styles: { fontSize: 9, cellPadding: 6, textColor: [30, 41, 59] },
            headStyles: { fillColor: [241, 245, 249], textColor: [71, 85, 105], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [248, 250, 252] },
            margin: { left: 40, right: 40 },
            columnStyles: {
                0: { cellWidth: 100 },
                1: { cellWidth: 'auto' },
                2: { cellWidth: 80 },
                3: { cellWidth: 120 }
            }
        });

        doc.save('audit_logs_' + new Date().toISOString().split('T')[0] + '.pdf');
    }
}
