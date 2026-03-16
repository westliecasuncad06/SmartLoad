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
function openModal() {
    document.getElementById('overrideModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('overrideModal').classList.add('hidden');
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
            handleFile(type, e.dataTransfer.files[0]);
        });

        input.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFile(type, e.target.files[0]);
            }
        });
    });
}

function handleFile(type, file) {
    if (file) {
        const zone = document.getElementById(type + 'Upload');
        const fileInfo = document.getElementById(type + 'FileInfo');
        const fileName = document.getElementById(type + 'FileName');
        const status = document.getElementById(type + 'Status');

        zone.classList.add('uploaded');
        fileInfo.classList.remove('hidden');
        fileName.textContent = file.name;
        status.classList.remove('bg-slate-300');
        status.classList.add('bg-green-500');

        uploadedFiles[type] = true;
        updateUploadSummary();
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
// Generate Schedule Simulation
// --------------------------------------------------
function generateSchedule() {
    const btn = document.getElementById('generateBtn');
    const indicator = document.getElementById('generatingIndicator');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Generating...';
    indicator.classList.remove('hidden');
    indicator.classList.add('flex');

    // Simulate generation time
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bolt"></i> Generate Schedule';
        indicator.classList.add('hidden');
        indicator.classList.remove('flex');

        // Show success message (in real app, would update the table via AJAX)
        alert('Schedule generated successfully!\n\n• 156 subjects assigned\n• 42 teachers matched\n• 0 conflicts detected\n• Generation time: 2.3 seconds');
    }, 2500);
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
