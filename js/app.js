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

    // Close teacher modals when clicking outside
    ['addTeacherModal', 'viewTeacherModal', 'editTeacherModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }
            });
        }
    });

    // Import CSV button on Teachers page
    const importBtn = document.getElementById('importTeacherCsvBtn');
    const importInput = document.getElementById('teacherCsvFileInput');
    if (importBtn && importInput) {
        importBtn.addEventListener('click', () => importInput.click());
        importInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleTeacherCsvImport(e.target.files[0]);
                e.target.value = '';
            }
        });
    }

    // Add Teacher button
    const addBtn = document.getElementById('openAddTeacherModalBtn');
    if (addBtn) {
        addBtn.addEventListener('click', openAddTeacherModal);
    }

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
// Teacher CSV Import (Teachers page button)
// --------------------------------------------------
async function handleTeacherCsvImport(file) {
    const btn = document.getElementById('importTeacherCsvBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Importing...';

    try {
        const data = await uploadFile(file, 'teacher');
        alert('Teachers imported successfully! ' + data.rows_inserted + ' row(s) inserted.');
        location.reload();
    } catch (err) {
        alert('Import failed: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-file-import"></i> Import CSV';
    }
}

// --------------------------------------------------
// Teacher Modal Helpers
// --------------------------------------------------
function openTeacherModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
}

function closeTeacherModal(id) {
    const el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
}

// --------------------------------------------------
// Add Teacher Modal
// --------------------------------------------------
function openAddTeacherModal() {
    document.getElementById('addTeacherForm').reset();
    document.getElementById('addTeacherError').classList.add('hidden');
    openTeacherModal('addTeacherModal');
}

function closeAddTeacherModal() {
    closeTeacherModal('addTeacherModal');
}

async function submitAddTeacher(e) {
    e.preventDefault();
    const btn = document.getElementById('addTeacherSubmitBtn');
    const errBox = document.getElementById('addTeacherError');
    errBox.classList.add('hidden');

    const payload = {
        name:          document.getElementById('addTeacherName').value.trim(),
        email:         document.getElementById('addTeacherEmail').value.trim(),
        type:          document.getElementById('addTeacherType').value,
        max_units:     parseInt(document.getElementById('addTeacherMaxUnits').value, 10),
        expertise_tags: document.getElementById('addTeacherTags').value.trim(),
    };

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Saving...';

    try {
        const response = await fetch('api/create_teacher.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok || data.status !== 'success') {
            errBox.textContent = data.message || 'Failed to create teacher.';
            errBox.classList.remove('hidden');
            return;
        }

        closeAddTeacherModal();
        location.reload();
    } catch (err) {
        errBox.textContent = 'Network error: ' + err.message;
        errBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Add Teacher';
    }
}

// --------------------------------------------------
// View Teacher Modal
// --------------------------------------------------
function openViewTeacherModal(row) {
    const ds = row.dataset;
    const initials = ds.name.split(' ').map(w => w.charAt(0).toUpperCase()).join('');

    document.getElementById('viewTeacherAvatar').textContent = initials;
    document.getElementById('viewTeacherName').textContent = ds.name;
    document.getElementById('viewTeacherEmail').textContent = ds.email;
    document.getElementById('viewTeacherType').textContent = ds.type;
    document.getElementById('viewTeacherLoad').textContent = ds.currentunits + ' / ' + ds.maxunits + ' units';

    const tagsContainer = document.getElementById('viewTeacherTags');
    tagsContainer.innerHTML = '';
    if (ds.tags) {
        ds.tags.split(',').forEach(tag => {
            const span = document.createElement('span');
            span.className = 'px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs';
            span.textContent = tag.trim();
            tagsContainer.appendChild(span);
        });
    } else {
        tagsContainer.innerHTML = '<span class="text-sm text-slate-400">No tags set</span>';
    }

    openTeacherModal('viewTeacherModal');
}

function closeViewTeacherModal() {
    closeTeacherModal('viewTeacherModal');
}

// --------------------------------------------------
// Edit Teacher Modal
// --------------------------------------------------
function openEditTeacherModal(row) {
    const ds = row.dataset;
    document.getElementById('editTeacherId').value     = ds.id;
    document.getElementById('editTeacherName').value   = ds.name;
    document.getElementById('editTeacherEmail').value  = ds.email;
    document.getElementById('editTeacherType').value   = ds.type;
    document.getElementById('editTeacherMaxUnits').value = ds.maxunits;
    document.getElementById('editTeacherTags').value   = ds.tags;
    document.getElementById('editTeacherError').classList.add('hidden');
    openTeacherModal('editTeacherModal');
}

function closeEditTeacherModal() {
    closeTeacherModal('editTeacherModal');
}

async function submitEditTeacher(e) {
    e.preventDefault();
    const btn = document.getElementById('editTeacherSubmitBtn');
    const errBox = document.getElementById('editTeacherError');
    errBox.classList.add('hidden');

    const payload = {
        id:            parseInt(document.getElementById('editTeacherId').value, 10),
        name:          document.getElementById('editTeacherName').value.trim(),
        email:         document.getElementById('editTeacherEmail').value.trim(),
        type:          document.getElementById('editTeacherType').value,
        max_units:     parseInt(document.getElementById('editTeacherMaxUnits').value, 10),
        expertise_tags: document.getElementById('editTeacherTags').value.trim(),
    };

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Saving...';

    try {
        const response = await fetch('api/update_teacher.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok || data.status !== 'success') {
            errBox.textContent = data.message || 'Failed to update teacher.';
            errBox.classList.remove('hidden');
            return;
        }

        closeEditTeacherModal();
        location.reload();
    } catch (err) {
        errBox.textContent = 'Network error: ' + err.message;
        errBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-pen"></i> Save Changes';
    }
}

// --------------------------------------------------
// Archive Teacher
// --------------------------------------------------
async function archiveTeacher(row) {
    const ds = row.dataset;
    if (!confirm('Archive "' + ds.name + '"? They will no longer appear in the teacher list.')) {
        return;
    }

    try {
        const response = await fetch('api/archive_teacher.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(ds.id, 10) }),
        });
        const data = await response.json();

        if (!response.ok || data.status !== 'success') {
            alert('Archive failed: ' + (data.message || 'Unknown error.'));
            return;
        }

        location.reload();
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}
