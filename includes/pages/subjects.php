<!-- SUBJECTS PAGE -->
<div id="page-subjects" class="page-content hidden p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Subject Catalog</h2>
            <p class="text-slate-600 mt-1">Manage course subjects, units, and prerequisites</p>
        </div>
        <div class="flex items-center gap-3">
            <button id="btnSubjectImport" type="button" class="flex items-center gap-2 px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm"><i class="fas fa-file-import"></i> Import CSV</button>
            <button id="btnSubjectAdd" type="button" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"><i class="fas fa-plus"></i> Add Subject</button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-blue-100 p-2.5 rounded-lg w-fit"><i class="fas fa-book-open text-blue-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo (int)$totalSubjects; ?></p>
            <p class="text-slate-500 text-sm">Total Subjects</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-indigo-100 p-2.5 rounded-lg w-fit"><i class="fas fa-calculator text-indigo-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo (int)$totalUnits; ?></p>
            <p class="text-slate-500 text-sm">Total Units</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-green-100 p-2.5 rounded-lg w-fit"><i class="fas fa-circle-check text-green-600"></i></div>
            <p class="text-2xl font-bold text-green-600 mt-3"><?php echo (int)$assignedSubjects; ?></p>
            <p class="text-slate-500 text-sm">Assigned</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-amber-100 p-2.5 rounded-lg w-fit"><i class="fas fa-clock text-amber-600"></i></div>
            <p class="text-2xl font-bold text-amber-600 mt-3"><?php echo (int)($totalSubjects - $assignedSubjects); ?></p>
            <p class="text-slate-500 text-sm">Unassigned</p>
        </div>
    </div>

    <!-- Subjects Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">All Subjects</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input id="subjectSearch" type="text" placeholder="Search subjects..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                    </div>
                    <select id="subjectProgramFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option>All Programs</option>
                        <option>BS Computer Science</option>
                        <option>BS Information Technology</option>
                        <option>BS Mathematics</option>
                    </select>
                    <select id="subjectStatusFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all">All Status</option>
                        <option value="assigned">Assigned</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-3 text-left"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Code</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Subject Name</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Program</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Units</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Prerequisites</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Assigned To</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Status</th>
                        <th class="px-6 py-3 text-center text-slate-600 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody id="subjectTableBody">
                    <?php
                    // Subjects archiving support: filter archived subjects when column exists.
                    $hasSubjectArchive = false;
                    try {
                        $colStmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'is_archived'");
                        $hasSubjectArchive = (bool)$colStmt->fetch();
                    } catch (Exception $ignore) {
                        $hasSubjectArchive = false;
                    }

                    $subjectSql = 'SELECT sub.id, sub.course_code, sub.name, sub.program, sub.units, sub.prerequisites,
                                          a.id AS assignment_id, a.status AS assignment_status,
                                          t.id AS assigned_teacher_id, t.name AS assigned_teacher_name
                                   FROM subjects sub
                                   LEFT JOIN (
                                       SELECT subject_id, MAX(id) AS latest_assignment_id
                                       FROM assignments
                                       GROUP BY subject_id
                                   ) la ON la.subject_id = sub.id
                                   LEFT JOIN assignments a ON a.id = la.latest_assignment_id
                                   LEFT JOIN teachers t ON t.id = a.teacher_id';

                    if ($hasSubjectArchive) {
                        $subjectSql .= ' WHERE sub.is_archived = 0';
                    }

                    $subjectSql .= ' ORDER BY sub.course_code ASC';

                    $subjectRows = $pdo->query($subjectSql);
                    foreach ($subjectRows as $subject):
                        $isAssigned = !empty($subject['assignment_id']);
                        $assignedTo = $isAssigned ? (string)($subject['assigned_teacher_name'] ?? '') : '';
                    ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
                        <td class="px-6 py-4 font-medium text-indigo-600"><?php echo htmlspecialchars($subject['course_code']); ?></td>
                        <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($subject['name']); ?></td>
                        <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($subject['program']); ?></td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium"><?php echo (int)$subject['units']; ?></span></td>
                        <td class="px-6 py-4 text-slate-500 text-xs"><?php echo htmlspecialchars($subject['prerequisites'] ?: 'None'); ?></td>
                        <td class="px-6 py-4 <?php echo $isAssigned ? 'text-slate-700' : 'text-slate-400 italic'; ?>"><?php echo $isAssigned ? htmlspecialchars($assignedTo ?: '—') : '—'; ?></td>
                        <td class="px-6 py-4">
                            <?php if ($isAssigned): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Assigned</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    class="subject-action-view p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                    title="View"
                                    data-subject-id="<?php echo (int)$subject['id']; ?>"
                                    data-subject-course-code="<?php echo htmlspecialchars($subject['course_code'], ENT_QUOTES); ?>"
                                    data-subject-name="<?php echo htmlspecialchars($subject['name'], ENT_QUOTES); ?>"
                                    data-subject-program="<?php echo htmlspecialchars($subject['program'], ENT_QUOTES); ?>"
                                    data-subject-units="<?php echo (int)$subject['units']; ?>"
                                    data-subject-prerequisites="<?php echo htmlspecialchars((string)($subject['prerequisites'] ?? ''), ENT_QUOTES); ?>"
                                ><i class="fas fa-eye text-sm"></i></button>
                                <button
                                    type="button"
                                    class="subject-action-edit p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                    title="Edit"
                                    data-subject-id="<?php echo (int)$subject['id']; ?>"
                                    data-subject-course-code="<?php echo htmlspecialchars($subject['course_code'], ENT_QUOTES); ?>"
                                    data-subject-name="<?php echo htmlspecialchars($subject['name'], ENT_QUOTES); ?>"
                                    data-subject-program="<?php echo htmlspecialchars($subject['program'], ENT_QUOTES); ?>"
                                    data-subject-units="<?php echo (int)$subject['units']; ?>"
                                    data-subject-prerequisites="<?php echo htmlspecialchars((string)($subject['prerequisites'] ?? ''), ENT_QUOTES); ?>"
                                ><i class="fas fa-pen text-sm"></i></button>
                                <button
                                    type="button"
                                    class="subject-action-archive p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded transition-colors"
                                    title="Archive"
                                    data-subject-id="<?php echo (int)$subject['id']; ?>"
                                    data-subject-course-code="<?php echo htmlspecialchars($subject['course_code'], ENT_QUOTES); ?>"
                                ><i class="fas fa-box-archive text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="subjectPaginationBar" class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <span id="subjectShowing" class="text-sm text-slate-600">Showing <?php echo (int)$totalSubjects; ?> of <?php echo (int)$totalSubjects; ?> subjects</span>
            <div id="subjectPagination" class="flex items-center gap-1">
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors disabled:opacity-50" disabled><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg">1</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">2</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">3</button>
                <span class="px-2 text-slate-400">...</span>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">16</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Subject Modals (Import / Add / View / Edit) -->
    <!-- Import CSV Modal -->
    <div id="subjectImportModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Import Subjects CSV</h4>
                <button type="button" id="btnSubjectImportClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="px-5 py-5 space-y-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-2">CSV File</label>
                    <input id="subjectCsvFileInput" type="file" accept=".csv,text/csv" class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="text-xs text-slate-400 mt-2">Expected columns: course_code, name, program, units, prerequisites (optional)</p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="btnSubjectImportCancel" class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">Cancel</button>
                    <button type="button" id="btnSubjectImportUpload" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div id="subjectAddModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Add Subject</h4>
                <button type="button" id="btnSubjectAddClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="subjectAddForm" class="px-5 py-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Course Code</label>
                        <input id="addSubjectCourseCode" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Units</label>
                        <input id="addSubjectUnits" type="number" min="0" step="1" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Subject Name</label>
                        <input id="addSubjectName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Program</label>
                        <input id="addSubjectProgram" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Prerequisites (optional)</label>
                        <input id="addSubjectPrerequisites" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. CS101" />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="btnSubjectAddCancel" class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">Cancel</button>
                    <button type="submit" id="btnSubjectAddSave" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Subject Modal -->
    <div id="subjectViewModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Subject Details</h4>
                <button type="button" id="btnSubjectViewClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="px-5 py-5 space-y-3 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-slate-400">Course Code</p>
                        <p id="viewSubjectCourseCode" class="font-medium text-slate-900">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Units</p>
                        <p id="viewSubjectUnits" class="text-slate-700">—</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-400">Subject Name</p>
                        <p id="viewSubjectName" class="text-slate-700">—</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-400">Program</p>
                        <p id="viewSubjectProgram" class="text-slate-700">—</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-400">Prerequisites</p>
                        <p id="viewSubjectPrerequisites" class="text-slate-700">—</p>
                    </div>
                </div>
                <div class="flex items-center justify-end pt-2">
                    <button type="button" id="btnSubjectViewOk" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div id="subjectEditModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Edit Subject</h4>
                <button type="button" id="btnSubjectEditClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="subjectEditForm" class="px-5 py-5 space-y-4">
                <input type="hidden" id="editSubjectId" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Course Code</label>
                        <input id="editSubjectCourseCode" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Units</label>
                        <input id="editSubjectUnits" type="number" min="0" step="1" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Subject Name</label>
                        <input id="editSubjectName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Program</label>
                        <input id="editSubjectProgram" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Prerequisites (optional)</label>
                        <input id="editSubjectPrerequisites" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="btnSubjectEditCancel" class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">Cancel</button>
                    <button type="submit" id="btnSubjectEditSave" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- END SUBJECTS PAGE -->
