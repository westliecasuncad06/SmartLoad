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
});

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
        if (shouldReload) location.reload();
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
    if (shouldReload) location.reload();
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
