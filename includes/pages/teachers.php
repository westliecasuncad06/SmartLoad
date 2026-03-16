<!-- TEACHERS PAGE -->
<div id="page-teachers" class="page-content hidden p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Teachers Management</h2>
            <p class="text-slate-600 mt-1 text-sm">Manage faculty members, their expertise, and availability</p>
        </div>
        <div class="flex items-center gap-3">
            <button id="btnTeacherImport" type="button" class="flex items-center gap-2 px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm"><i class="fas fa-file-import"></i> Import CSV</button>
            <button id="btnTeacherAdd" type="button" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"><i class="fas fa-plus"></i> Add Teacher</button>
        </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-indigo-100 p-2.5 rounded-lg w-fit"><i class="fas fa-users text-indigo-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo (int)$totalTeachers; ?></p>
            <p class="text-slate-500 text-sm">Total Teachers</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-green-100 p-2.5 rounded-lg w-fit"><i class="fas fa-user-check text-green-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo isset($fullTimeCount) ? (int)$fullTimeCount : 0; ?></p>
            <p class="text-slate-500 text-sm">Full-time</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-blue-100 p-2.5 rounded-lg w-fit"><i class="fas fa-user-clock text-blue-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo isset($partTimeCount) ? (int)$partTimeCount : 0; ?></p>
            <p class="text-slate-500 text-sm">Part-time</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-amber-100 p-2.5 rounded-lg w-fit"><i class="fas fa-triangle-exclamation text-amber-600"></i></div>
            <p class="text-2xl font-bold text-amber-600 mt-3"><?php echo (int)$overloadCount; ?></p>
            <p class="text-slate-500 text-sm">Overloaded</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="text-lg font-semibold text-slate-900">All Teachers</h3>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="relative">
                        <input id="teacherSearch" type="text" placeholder="Search teachers..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full sm:w-64">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                    </div>
                    <select id="teacherDepartmentFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option>All Departments</option>
                        <option>Computer Science</option>
                        <option>Mathematics</option>
                        <option>Engineering</option>
                    </select>
                    <select id="teacherTypeFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="All">All</option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                    </tr>
                </thead>
                <tbody id="teacherTableBody">
                    <?php
                    $teacherRows = $pdo->query('SELECT * FROM teachers WHERE is_archived = 0 ORDER BY name ASC');
                    foreach ($teacherRows as $teacher):
                        $initials = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), explode(' ', $teacher['name'])));
                        $isOverloaded = $teacher['current_units'] > $teacher['max_units'];
                    ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors <?php echo $isOverloaded ? 'bg-red-50/30' : ''; ?>">
                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-medium text-sm"><?php echo htmlspecialchars($initials); ?></div>
                                <div>
                                    <p class="font-medium text-slate-900"><?php echo htmlspecialchars($teacher['name']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($teacher['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <?php if (!empty($teacher['expertise_tags'])):
                                    foreach (explode(',', $teacher['expertise_tags']) as $tag): ?>
                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                <?php endforeach; endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($teacher['type'] === 'Full-time'): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Full-time</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Part-time</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold <?php echo $isOverloaded ? 'text-red-600' : 'text-slate-900'; ?>"><?php echo (int)$teacher['current_units']; ?></span>
                                <span class="text-slate-400">/</span>
                                <span class="text-slate-500"><?php echo (int)$teacher['max_units']; ?> units</span>
                                <?php if ($isOverloaded): ?>
                                    <i class="fas fa-triangle-exclamation text-red-500 text-xs"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    class="teacher-action-view p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                    title="View"
                                    data-teacher-id="<?php echo (int)$teacher['id']; ?>"
                                    data-teacher-name="<?php echo htmlspecialchars($teacher['name'], ENT_QUOTES); ?>"
                                    data-teacher-email="<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES); ?>"
                                    data-teacher-type="<?php echo htmlspecialchars($teacher['type'], ENT_QUOTES); ?>"
                                    data-teacher-max-units="<?php echo (int)$teacher['max_units']; ?>"
                                    data-teacher-current-units="<?php echo (int)$teacher['current_units']; ?>"
                                    data-teacher-expertise-tags="<?php echo htmlspecialchars((string)($teacher['expertise_tags'] ?? ''), ENT_QUOTES); ?>"
                                ><i class="fas fa-eye text-sm"></i></button>
                                <button
                                    type="button"
                                    class="teacher-action-edit p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                    title="Edit"
                                    data-teacher-id="<?php echo (int)$teacher['id']; ?>"
                                    data-teacher-name="<?php echo htmlspecialchars($teacher['name'], ENT_QUOTES); ?>"
                                    data-teacher-email="<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES); ?>"
                                    data-teacher-type="<?php echo htmlspecialchars($teacher['type'], ENT_QUOTES); ?>"
                                    data-teacher-max-units="<?php echo (int)$teacher['max_units']; ?>"
                                    data-teacher-current-units="<?php echo (int)$teacher['current_units']; ?>"
                                    data-teacher-expertise-tags="<?php echo htmlspecialchars((string)($teacher['expertise_tags'] ?? ''), ENT_QUOTES); ?>"
                                ><i class="fas fa-pen text-sm"></i></button>
                                <button
                                    type="button"
                                    class="teacher-action-archive p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded transition-colors"
                                    title="Archive"
                                    data-teacher-id="<?php echo (int)$teacher['id']; ?>"
                                    data-teacher-name="<?php echo htmlspecialchars($teacher['name'], ENT_QUOTES); ?>"
                                ><i class="fas fa-box-archive text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="teacherPaginationBar" class="px-4 sm:px-6 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <span id="teacherShowing" class="text-sm text-slate-600">Showing <?php echo (int)$totalTeachers; ?> of <?php echo (int)$totalTeachers; ?> teachers</span>
            <div id="teacherPagination" class="flex items-center gap-1">
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors disabled:opacity-50" disabled><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg">1</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Teacher Modals (Import / Add / View / Edit) -->
    <!-- Import CSV Modal -->
    <div id="teacherImportModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Import Teachers CSV</h4>
                <button type="button" id="btnTeacherImportClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="px-5 py-5 space-y-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-2">CSV File</label>
                    <input id="teacherCsvFileInput" type="file" accept=".csv,text/csv" class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="text-xs text-slate-400 mt-2">Expected columns: name, email, type, max_units, expertise_tags</p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="btnTeacherImportCancel" class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">Cancel</button>
                    <button type="button" id="btnTeacherImportUpload" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div id="teacherAddModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Add Teacher</h4>
                <button type="button" id="btnTeacherAddClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="teacherAddForm" class="px-5 py-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Name</label>
                        <input id="addTeacherName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Email</label>
                        <input id="addTeacherEmail" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Employment Type</label>
                        <select id="addTeacherType" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Max Units</label>
                        <input id="addTeacherMaxUnits" type="number" min="0" step="1" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Expertise Tags (comma separated)</label>
                        <input id="addTeacherExpertise" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. PHP, MySQL, Web Dev" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-2">Availability (Monday to Saturday)</label>
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <div class="grid grid-cols-12 bg-slate-50 text-xs text-slate-600 font-semibold">
                                <div class="col-span-4 px-3 py-2">Day</div>
                                <div class="col-span-2 px-3 py-2">Available</div>
                                <div class="col-span-3 px-3 py-2">Start</div>
                                <div class="col-span-3 px-3 py-2">End</div>
                            </div>

                            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): $k = preg_replace('/\s+/', '', $d); ?>
                                <div class="grid grid-cols-12 items-center border-t border-slate-200">
                                    <div class="col-span-4 px-3 py-2 text-sm text-slate-700"><?php echo htmlspecialchars($d); ?></div>
                                    <div class="col-span-2 px-3 py-2">
                                        <input id="addTeacherAvail<?php echo $k; ?>Enabled" type="checkbox" class="rounded border-slate-300 text-indigo-600" />
                                    </div>
                                    <div class="col-span-3 px-3 py-2">
                                        <input id="addTeacherAvail<?php echo $k; ?>Start" type="time" class="w-full px-2 py-1.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                    </div>
                                    <div class="col-span-3 px-3 py-2">
                                        <input id="addTeacherAvail<?php echo $k; ?>End" type="time" class="w-full px-2 py-1.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Check the day, then set start and end time.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="btnTeacherAddCancel" class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">Cancel</button>
                    <button type="submit" id="btnTeacherAddSave" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Teacher Modal -->
    <div id="teacherViewModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Teacher Details</h4>
                <button type="button" id="btnTeacherViewClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="px-5 py-5 space-y-3 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-slate-400">Name</p>
                        <p id="viewTeacherName" class="font-medium text-slate-900">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Email</p>
                        <p id="viewTeacherEmail" class="text-slate-700">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Employment Type</p>
                        <p id="viewTeacherType" class="text-slate-700">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Max Units</p>
                        <p id="viewTeacherMaxUnits" class="text-slate-700">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Current Units</p>
                        <p id="viewTeacherCurrentUnits" class="text-slate-700">—</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-400">Expertise Tags</p>
                        <p id="viewTeacherExpertise" class="text-slate-700">—</p>
                    </div>
                </div>
                <div class="flex items-center justify-end pt-2">
                    <button type="button" id="btnTeacherViewOk" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div id="teacherEditModal" class="fixed inset-0 hidden bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Edit Teacher</h4>
                <button type="button" id="btnTeacherEditClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-xmark"></i></button>
            </div>
            <form id="teacherEditForm" class="px-5 py-5 space-y-4">
                <input type="hidden" id="editTeacherId" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Name</label>
                        <input id="editTeacherName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Email</label>
                        <input id="editTeacherEmail" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Employment Type</label>
                        <select id="editTeacherType" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Max Units</label>
                        <input id="editTeacherMaxUnits" type="number" min="0" step="1" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Expertise Tags (comma separated)</label>
                        <input id="editTeacherExpertise" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-2">Availability (Monday to Saturday)</label>
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <div class="grid grid-cols-12 bg-slate-50 text-xs text-slate-600 font-semibold">
                                <div class="col-span-4 px-3 py-2">Day</div>
                                <div class="col-span-2 px-3 py-2">Available</div>
                                <div class="col-span-3 px-3 py-2">Start</div>
                                <div class="col-span-3 px-3 py-2">End</div>
                            </div>

                            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): $k = preg_replace('/\s+/', '', $d); ?>
                                <div class="grid grid-cols-12 items-center border-t border-slate-200">
                                    <div class="col-span-4 px-3 py-2 text-sm text-slate-700"><?php echo htmlspecialchars($d); ?></div>
                                    <div class="col-span-2 px-3 py-2">
                                        <input id="editTeacherAvail<?php echo $k; ?>Enabled" type="checkbox" class="rounded border-slate-300 text-indigo-600" />
                                    </div>
                                    <div class="col-span-3 px-3 py-2">
                                        <input id="editTeacherAvail<?php echo $k; ?>Start" type="time" class="w-full px-2 py-1.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                    </div>
                                    <div class="col-span-3 px-3 py-2">
                                        <input id="editTeacherAvail<?php echo $k; ?>End" type="time" class="w-full px-2 py-1.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Check the day, then set start and end time.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="btnTeacherEditCancel" class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">Cancel</button>
                    <button type="submit" id="btnTeacherEditSave" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- END TEACHERS PAGE -->
