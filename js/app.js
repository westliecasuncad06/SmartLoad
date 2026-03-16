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

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function () {
    ['overrideModal', 'settingsModal', 'historyModal'].forEach(modalId => {
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
        .then(() => {
            zone.classList.add('uploaded');
            status.classList.remove('bg-slate-300');
            status.classList.add('bg-green-500');
            uploadedFiles[type] = true;
            updateUploadSummary();
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
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);

    const response = await fetch('api/upload.php', {
        method: 'POST',
        body: formData,
    });

    const data = await response.json();

    if (!response.ok || data.status !== 'success') {
        throw new Error(data.message || 'Upload failed.');
    }

    alert(type.charAt(0).toUpperCase() + type.slice(1) + ' file uploaded successfully! ' + data.rows_inserted + ' rows inserted.');
    return data;
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
