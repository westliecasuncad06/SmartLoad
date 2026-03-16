<!-- LOAD REPORTS PAGE -->
<div id="page-loadreports" class="page-content hidden p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Load Reports</h2>
            <p class="text-slate-600 mt-1">Generate and download faculty load assignment reports</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>1st Semester 2026-2027</option>
                <option>2nd Semester 2025-2026</option>
            </select>
        </div>
    </div>

    <!-- Report Types -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-users text-indigo-600 text-xl"></i></div>
                <h3 class="text-lg font-semibold text-slate-900">Faculty Load Summary</h3>
                <p class="text-slate-500 text-sm mt-2">Overview of all faculty members and their assigned teaching loads</p>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-400"><?php echo (int)$totalTeachers; ?> teachers</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"><i class="fas fa-eye mr-1"></i>View</button>
                        <button class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors"><i class="fas fa-download mr-1"></i>Export</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-book-open text-blue-600 text-xl"></i></div>
                <h3 class="text-lg font-semibold text-slate-900">Subject Assignment Report</h3>
                <p class="text-slate-500 text-sm mt-2">Detailed list of all subjects with their assigned teachers and schedules</p>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-400"><?php echo (int)$totalSubjects; ?> subjects</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"><i class="fas fa-eye mr-1"></i>View</button>
                        <button class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors"><i class="fas fa-download mr-1"></i>Export</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-triangle-exclamation text-amber-600 text-xl"></i></div>
                <h3 class="text-lg font-semibold text-slate-900">Overload Analysis</h3>
                <p class="text-slate-500 text-sm mt-2">Report of teachers exceeding the maximum unit load policy</p>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-red-500 font-medium"><?php echo (int)$overloadCount; ?> overloaded</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"><i class="fas fa-eye mr-1"></i>View</button>
                        <button class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors"><i class="fas fa-download mr-1"></i>Export</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Faculty Load Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-900">Faculty Load Distribution</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <span class="w-32 text-sm text-slate-600">John Doe</span>
                    <div class="flex-1 bg-slate-100 rounded-full h-6 overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full flex items-center justify-end pr-2" style="width: 83%"><span class="text-xs text-white font-medium">15 units</span></div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="w-32 text-sm text-slate-600">Jane Smith</span>
                    <div class="flex-1 bg-slate-100 rounded-full h-6 overflow-hidden">
                        <div class="bg-red-500 h-full rounded-full flex items-center justify-end pr-2" style="width: 100%"><span class="text-xs text-white font-medium">21 units</span></div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="w-32 text-sm text-slate-600">Alan Turing</span>
                    <div class="flex-1 bg-slate-100 rounded-full h-6 overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full flex items-center justify-end pr-2" style="width: 67%"><span class="text-xs text-white font-medium">12 units</span></div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="w-32 text-sm text-slate-600">Maria Garcia</span>
                    <div class="flex-1 bg-slate-100 rounded-full h-6 overflow-hidden">
                        <div class="bg-amber-500 h-full rounded-full flex items-center justify-end pr-2" style="width: 100%"><span class="text-xs text-white font-medium">18 units</span></div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="w-32 text-sm text-slate-600">Robert Johnson</span>
                    <div class="flex-1 bg-slate-100 rounded-full h-6 overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full flex items-center justify-end pr-2" style="width: 50%"><span class="text-xs text-white font-medium">9 units</span></div>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-6 text-xs text-slate-500">
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-indigo-500"></div><span>Normal Load</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-amber-500"></div><span>At Maximum (18 units)</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-red-500"></div><span>Overloaded</span></div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">Average Load</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">11.1 <span class="text-sm font-normal text-slate-400">units</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">At Capacity</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">8 <span class="text-sm font-normal text-slate-400">teachers</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">Available</p>
            <p class="text-2xl font-bold text-green-600 mt-1">288 <span class="text-sm font-normal text-slate-400">units</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">Utilization</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">61.9%</p>
        </div>
    </div>
</div>
<!-- END LOAD REPORTS PAGE -->
