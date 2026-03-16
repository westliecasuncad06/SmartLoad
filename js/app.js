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
}

function closeSettingsModal() {
    document.getElementById('settingsModal').classList.add('hidden');
}

function openHistoryModal() {
    document.getElementById('historyModal').classList.remove('hidden');
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

function openConflictModal() {
    document.getElementById('conflictModal').classList.remove('hidden');
}

function closeConflictModal() {
    document.getElementById('conflictModal').classList.add('hidden');
    conflictModalState = { type: null, file: null, conflicts: [], rowsInserted: 0 };
    const tbody = document.getElementById('conflictTableBody');
    if (tbody) tbody.innerHTML = '';
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function () {
    ['overrideModal', 'settingsModal', 'historyModal', 'conflictModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        }
    });

    // Initialize file upload zones
    initFileUploads();
});

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

    if (!response.ok) {
        throw new Error((data && data.message) ? data.message : 'Upload failed.');
    }

    if (data.status === 'success') {
        const updatedMsg = (data.rows_updated && data.rows_updated > 0)
            ? (' ' + data.rows_updated + ' rows updated.')
            : '';

        if (data.duplicates && Array.isArray(data.duplicates) && data.duplicates.length > 0) {
            const dupList = data.duplicates.join(', ');
            alert(
                'Successfully inserted ' + data.rows_inserted + ' rows.' + updatedMsg +
                '\n\nHowever, the following entries were skipped because they already exist: ' + dupList
            );
        } else {
            alert('Successfully inserted ' + data.rows_inserted + ' rows!' + updatedMsg);
        }
        return data;
    }

    if (data.status === 'conflict') {
        alert(
            type.charAt(0).toUpperCase() + type.slice(1) + ' uploaded with duplicates found.\n\n' +
            '• ' + (data.rows_inserted || 0) + ' new rows inserted\n' +
            '• ' + (data.conflict_count || 0) + ' duplicates detected\n\n' +
            'You can review and choose to update existing records.'
        );
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

    const subtitle = document.getElementById('conflictSubtitle');
    const summary = document.getElementById('conflictSummary');
    const tbody = document.getElementById('conflictTableBody');
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

    openConflictModal();
}

async function resolveConflictUpdate() {
    if (!conflictModalState.file || !conflictModalState.type) return;

    const btnUpdate = document.getElementById('conflictUpdateBtn');
    const btnKeep = document.getElementById('conflictKeepBtn');
    if (btnUpdate) btnUpdate.disabled = true;
    if (btnKeep) btnKeep.disabled = true;

    try {
        const data = await uploadFile(conflictModalState.file, conflictModalState.type, { conflict_action: 'update' });
        closeConflictModal();
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
    closeConflictModal();
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

        alert(
            'Schedule generated successfully!\n\n' +
            '• ' + data.assigned_count + ' subjects assigned\n' +
            '• ' + data.unassigned_count + ' subjects unassigned'
        );

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
// Load Report CSV Export
// --------------------------------------------------
function exportReport(type) {
    const el = document.getElementById('reportData-' + type);
    if (!el) { alert('No report data available.'); return; }

    let rows;
    try { rows = JSON.parse(el.textContent); } catch (_) { alert('Failed to parse report data.'); return; }
    if (!rows.length) { alert('No data to export.'); return; }

    let csv = '';
    const filenames = { faculty: 'faculty_load_summary', subject: 'subject_assignment_report', overload: 'overload_analysis' };

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
}

function csvRow(fields) {
    return fields.map(f => '"' + String(f).replace(/"/g, '""') + '"').join(',') + '\n';
}
