<!-- AUDIT TRAIL PAGE -->
<div id="page-audittrail" class="page-content hidden p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Audit Trail</h2>
            <p class="text-slate-600 mt-1">Complete history of all system activities and changes</p>
        </div>
        <div class="flex items-center gap-3">
            <button class="flex items-center gap-2 px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm"><i class="fas fa-file-export"></i> Export Logs</button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm text-slate-600">Date Range:</label>
                <input type="date" value="2026-03-01" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <span class="text-slate-400">to</span>
                <input type="date" value="2026-03-16" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>All Activity Types</option>
                <option>Schedule Generated</option>
                <option>Manual Override</option>
                <option>File Upload</option>
                <option>Settings Changed</option>
                <option>Warnings</option>
            </select>
            <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>All Users</option>
                <option>Program Chair</option>
                <option>System</option>
            </select>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors"><i class="fas fa-filter mr-2"></i>Apply Filters</button>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Activity Log</h3>
                <span class="text-sm text-slate-500">Showing 25 of 148 entries</span>
            </div>
        </div>
        <div class="divide-y divide-slate-100">
            <!-- Activity 1 -->
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-bolt text-green-600"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">Schedule Generated</span>
                        <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">Success</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">156 subjects assigned to 42 teachers with 0 conflicts detected</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-user mr-1"></i>Program Chair</span>
                        <span><i class="fas fa-clock mr-1"></i>Today, 2:45 PM</span>
                        <span><i class="fas fa-stopwatch mr-1"></i>Generated in 2.3 seconds</span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
            <!-- Activity 2 -->
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-user-pen text-purple-600"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">Manual Override</span>
                        <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full">Override</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">ENG102 - Literature reassigned from <span class="font-medium">John Doe</span> to <span class="font-medium">Maria Garcia</span></p>
                    <p class="text-sm text-slate-500 italic mt-1">"Teacher request due to schedule preference"</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-user mr-1"></i>Program Chair</span>
                        <span><i class="fas fa-clock mr-1"></i>Today, 2:50 PM</span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
            <!-- Activity 3 -->
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-file-arrow-up text-blue-600"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">Files Uploaded</span>
                        <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full">Upload</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">3 files uploaded: teachers.csv, subjects.csv, schedules.csv</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-user mr-1"></i>Program Chair</span>
                        <span><i class="fas fa-clock mr-1"></i>Today, 2:40 PM</span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
            <!-- Activity 4 -->
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-triangle-exclamation text-amber-600"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">Overload Warning</span>
                        <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">Warning</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1"><span class="font-medium">Jane Smith</span> assigned 21 units (exceeds 18 unit policy threshold)</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-robot mr-1"></i>System</span>
                        <span><i class="fas fa-clock mr-1"></i>Today, 2:45 PM</span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
            <!-- Activity 5 -->
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-sliders text-slate-600"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">Settings Updated</span>
                        <span class="px-2 py-0.5 text-xs bg-slate-200 text-slate-700 rounded-full">Config</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">Maximum teaching load changed from 21 to 18 units</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-user mr-1"></i>Program Chair</span>
                        <span><i class="fas fa-clock mr-1"></i>Yesterday, 4:20 PM</span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
            <!-- Activity 6 -->
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-bolt text-green-600"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">Schedule Generated</span>
                        <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">Success</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1">152 subjects assigned to 40 teachers with 2 conflicts detected</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-user mr-1"></i>Program Chair</span>
                        <span><i class="fas fa-clock mr-1"></i>Yesterday, 4:30 PM</span>
                        <span><i class="fas fa-stopwatch mr-1"></i>Generated in 1.8 seconds</span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
            <!-- Activity 7 -->
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-user-plus text-emerald-600"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900">New Teacher Added</span>
                        <span class="px-2 py-0.5 text-xs bg-emerald-100 text-emerald-700 rounded-full">Created</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1"><span class="font-medium">Robert Johnson</span> added as Full-time Faculty in Physics Department</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-user mr-1"></i>Program Chair</span>
                        <span><i class="fas fa-clock mr-1"></i>Mar 14, 2026, 10:00 AM</span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <span class="text-sm text-slate-600">Showing 7 of 148 activities</span>
            <div class="flex items-center gap-1">
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors disabled:opacity-50" disabled><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg">1</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">2</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">3</button>
                <span class="px-2 text-slate-400">...</span>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">15</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>
</div>
<!-- END AUDIT TRAIL PAGE -->
