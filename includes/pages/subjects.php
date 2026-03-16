<!-- SUBJECTS PAGE -->
<div id="page-subjects" class="page-content hidden p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Subject Catalog</h2>
            <p class="text-slate-600 mt-1">Manage course subjects, units, and prerequisites</p>
        </div>
        <div class="flex items-center gap-3">
            <button class="flex items-center gap-2 px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm"><i class="fas fa-file-import"></i> Import CSV</button>
            <button class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"><i class="fas fa-plus"></i> Add Subject</button>
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
                        <input type="text" placeholder="Search subjects..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                    </div>
                    <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option>All Programs</option>
                        <option>BS Computer Science</option>
                        <option>BS Information Technology</option>
                        <option>BS Mathematics</option>
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
                <tbody>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
                        <td class="px-6 py-4 font-medium text-indigo-600">CS101</td>
                        <td class="px-6 py-4 font-medium text-slate-900">Web Development</td>
                        <td class="px-6 py-4 text-slate-600">BS Computer Science</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium">3</span></td>
                        <td class="px-6 py-4 text-slate-500 text-xs">None</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-medium text-xs">JD</div>
                                <span class="text-slate-700">John Doe</span>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Assigned</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View"><i class="fas fa-eye text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Edit"><i class="fas fa-pen text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
                        <td class="px-6 py-4 font-medium text-indigo-600">IT202</td>
                        <td class="px-6 py-4 font-medium text-slate-900">Database Systems</td>
                        <td class="px-6 py-4 text-slate-600">BS Information Technology</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium">3</span></td>
                        <td class="px-6 py-4 text-slate-500 text-xs">CS101</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-medium text-xs">JD</div>
                                <span class="text-slate-700">John Doe</span>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Assigned</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View"><i class="fas fa-eye text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Edit"><i class="fas fa-pen text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
                        <td class="px-6 py-4 font-medium text-indigo-600">IT204</td>
                        <td class="px-6 py-4 font-medium text-slate-900">Computer Networking</td>
                        <td class="px-6 py-4 text-slate-600">BS Information Technology</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium">3</span></td>
                        <td class="px-6 py-4 text-slate-500 text-xs">IT102</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gradient-to-br from-pink-400 to-rose-500 rounded-full flex items-center justify-center text-white font-medium text-xs">JS</div>
                                <span class="text-slate-700">Jane Smith</span>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Assigned</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View"><i class="fas fa-eye text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Edit"><i class="fas fa-pen text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors bg-amber-50/30">
                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
                        <td class="px-6 py-4 font-medium text-indigo-600">PHY101</td>
                        <td class="px-6 py-4 font-medium text-slate-900">Physics I</td>
                        <td class="px-6 py-4 text-slate-600">BS Engineering</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium">3</span></td>
                        <td class="px-6 py-4 text-slate-500 text-xs">Math101</td>
                        <td class="px-6 py-4 text-slate-400 italic">Unassigned</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Unassigned</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View"><i class="fas fa-eye text-sm"></i></button>
                                <button class="p-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded transition-colors" title="Assign Teacher"><i class="fas fa-user-plus text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600"></td>
                        <td class="px-6 py-4 font-medium text-indigo-600">Math101</td>
                        <td class="px-6 py-4 font-medium text-slate-900">Calculus I</td>
                        <td class="px-6 py-4 text-slate-600">BS Mathematics</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-700 rounded font-medium">3</span></td>
                        <td class="px-6 py-4 text-slate-500 text-xs">None</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-medium text-xs">AT</div>
                                <span class="text-slate-700">Alan Turing</span>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Assigned</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View"><i class="fas fa-eye text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Edit"><i class="fas fa-pen text-sm"></i></button>
                                <button class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <span class="text-sm text-slate-600">Showing 5 of <?php echo (int)$totalSubjects; ?> subjects</span>
            <div class="flex items-center gap-1">
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
</div>
<!-- END SUBJECTS PAGE -->
