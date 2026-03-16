// ===================================================
// SmartLoad - Faculty Scheduling System
// Main Application JavaScript
// ===================================================

// --------------------------------------------------
// Page Switching / Navigation
// --------------------------------------------------
function switchPage(pageName) {
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(page => {
        page.classList.add('hidden');
    });

    // Show selected page
    const selectedPage = document.getElementById('page-' + pageName);
    if (selectedPage) {
        selectedPage.classList.remove('hidden');
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

    // Update breadcrumb
    const pageNames = {
        'dashboard': 'Dashboard',
        'teachers': 'Teachers',
        'subjects': 'Subjects',
        'schedules': 'Schedules',
        'loadreports': 'Load Reports',
        'audittrail': 'Audit Trail'
    };
    document.getElementById('breadcrumbTitle').textContent = pageNames[pageName] || 'Dashboard';
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

    // Initialize dashboard controls (filters/export/actions)
    initDashboardReport();
});

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
        tr.style.display = show ? '' : 'none';
    });
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

    const prereqLabel = (prerequisites || '').trim() ? prerequisites : 'None';

    return `
<tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
  <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
  <td class="px-6 py-4 font-medium text-indigo-600">${escapeHtml(courseCode)}</td>
  <td class="px-6 py-4 font-medium text-slate-900">${escapeHtml(name)}</td>
  <td class="px-6 py-4 text-slate-600">${escapeHtml(program)}</td>
  <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium">${escapeHtml(units)}</span></td>
  <td class="px-6 py-4 text-slate-500 text-xs">${escapeHtml(prereqLabel)}</td>
  <td class="px-6 py-4 text-slate-400 italic">—</td>
  <td class="px-6 py-4"><span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Unassigned</span></td>
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

    if (!page || !tbody || (!searchEl && !programEl)) return;

    let abortController = null;

    const run = async () => {
        const search = (searchEl ? searchEl.value : '').trim();
        const program = programEl ? String(programEl.value || 'All') : 'All';

        const qs = new URLSearchParams();
        qs.set('search', search);
        qs.set('program', program);

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
        } catch (err) {
            if (err && err.name === 'AbortError') return;
            console.error(err);
        }
    };

    const runDebounced = debounce(run, 200);
    if (searchEl) searchEl.addEventListener('keyup', runDebounced);
    if (programEl) programEl.addEventListener('change', run);
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
            const payload = {
                name: (document.getElementById('addTeacherName') || {}).value || '',
                email: (document.getElementById('addTeacherEmail') || {}).value || '',
                type: (document.getElementById('addTeacherType') || {}).value || 'Full-time',
                max_units: Number((document.getElementById('addTeacherMaxUnits') || {}).value || 0),
                expertise_tags: (document.getElementById('addTeacherExpertise') || {}).value || '',
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

    ['btnTeacherEditClose', 'btnTeacherEditCancel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', () => closeTeacherModal('teacherEditModal'));
    });

    const editForm = document.getElementById('teacherEditForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                id: Number((document.getElementById('editTeacherId') || {}).value || 0),
                name: (document.getElementById('editTeacherName') || {}).value || '',
                email: (document.getElementById('editTeacherEmail') || {}).value || '',
                type: (document.getElementById('editTeacherType') || {}).value || 'Full-time',
                max_units: Number((document.getElementById('editTeacherMaxUnits') || {}).value || 0),
                expertise_tags: (document.getElementById('editTeacherExpertise') || {}).value || '',
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

function initFileUploads() {
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

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    formData.append('conflict_action', conflictAction);

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
// Keyboard Shortcuts
// --------------------------------------------------
document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('globalSearch').focus();
    }
});

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
