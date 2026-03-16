<!-- TEACHERS PAGE -->
<div id="page-teachers" class="page-content hidden p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Teachers Management</h2>
            <p class="text-slate-600 mt-1">Manage faculty members, their expertise, and availability</p>
        </div>
        <div class="flex items-center gap-3">
            <button class="flex items-center gap-2 px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm"><i class="fas fa-file-import"></i> Import CSV</button>
            <button class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"><i class="fas fa-plus"></i> Add Teacher</button>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-indigo-100 p-2.5 rounded-lg w-fit"><i class="fas fa-users text-indigo-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo (int)$totalTeachers; ?></p>
            <p class="text-slate-500 text-sm">Total Teachers</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-green-100 p-2.5 rounded-lg w-fit"><i class="fas fa-user-check text-green-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3">38</p>
            <p class="text-slate-500 text-sm">Full-time</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-blue-100 p-2.5 rounded-lg w-fit"><i class="fas fa-user-clock text-blue-600"></i></div>
            <p class="text-2xl font-bold text-slate-900 mt-3">4</p>
            <p class="text-slate-500 text-sm">Part-time</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
            <div class="bg-amber-100 p-2.5 rounded-lg w-fit"><i class="fas fa-triangle-exclamation text-amber-600"></i></div>
            <p class="text-2xl font-bold text-amber-600 mt-3"><?php echo (int)$overloadCount; ?></p>
            <p class="text-slate-500 text-sm">Overloaded</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">All Teachers</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input type="text" placeholder="Search teachers..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                    </div>
                    <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option>All Departments</option>
                        <option>Computer Science</option>
                        <option>Mathematics</option>
                        <option>Engineering</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-3 text-left"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Teacher</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Expertise</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Employment</th>
                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Current Load</th>
                        <th class="px-6 py-3 text-center text-slate-600 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $teacherRows = $pdo->query('SELECT * FROM teachers ORDER BY name ASC');
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
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View"><i class="fas fa-eye text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Edit"><i class="fas fa-pen text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <span class="text-sm text-slate-600">Showing <?php echo (int)$totalTeachers; ?> of <?php echo (int)$totalTeachers; ?> teachers</span>
            <div class="flex items-center gap-1">
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors disabled:opacity-50" disabled><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg">1</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>
</div>
<!-- END TEACHERS PAGE -->
