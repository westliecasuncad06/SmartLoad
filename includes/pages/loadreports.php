<!-- LOAD REPORTS PAGE -->
<div id="page-loadreports" class="page-content hidden p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Load Reports</h2>
            <p class="text-slate-600 mt-1 text-sm">Generate and download faculty load assignment reports</p>
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
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-users text-indigo-600 text-xl"></i></div>
                <h3 class="text-lg font-semibold text-slate-900">Faculty Load Summary</h3>
                <p class="text-slate-500 text-sm mt-2">Overview of all faculty members and their assigned teaching loads</p>
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs text-slate-400"><?= (int)$totalTeachers ?> teachers</span>
                    <div class="flex items-center gap-2">
                        <button onclick="openReportModal('faculty')" class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"><i class="fas fa-eye mr-1"></i>View</button>
                        <div class="relative inline-block">
                            <button onclick="toggleExportMenu(event,'faculty')" class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors flex items-center gap-1"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down text-[9px]"></i></button>
                            <div id="exportMenu-faculty" class="hidden absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-xl z-50 overflow-hidden">
                                <button onclick="exportReport('faculty','csv')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2 border-b border-slate-100"><i class="fas fa-file-csv text-green-600 w-4"></i>Export as CSV</button>
                                <button onclick="exportReport('faculty','pdf')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-file-pdf text-red-500 w-4"></i>Export as PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-book-open text-blue-600 text-xl"></i></div>
                <h3 class="text-lg font-semibold text-slate-900">Subject Assignment Report</h3>
                <p class="text-slate-500 text-sm mt-2">Detailed list of all subjects with their assigned teachers and schedules</p>
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs text-slate-400"><?= (int)$totalSubjects ?> subjects</span>
                    <div class="flex items-center gap-2">
                        <button onclick="openReportModal('subject')" class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"><i class="fas fa-eye mr-1"></i>View</button>
                        <div class="relative inline-block">
                            <button onclick="toggleExportMenu(event,'subject')" class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors flex items-center gap-1"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down text-[9px]"></i></button>
                            <div id="exportMenu-subject" class="hidden absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-xl z-50 overflow-hidden">
                                <button onclick="exportReport('subject','csv')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2 border-b border-slate-100"><i class="fas fa-file-csv text-green-600 w-4"></i>Export as CSV</button>
                                <button onclick="exportReport('subject','pdf')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-file-pdf text-red-500 w-4"></i>Export as PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-4"><i class="fas fa-triangle-exclamation text-amber-600 text-xl"></i></div>
                <h3 class="text-lg font-semibold text-slate-900">Overload Analysis</h3>
                <p class="text-slate-500 text-sm mt-2">Report of teachers exceeding the maximum unit load policy</p>
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs text-red-500 font-medium"><?= (int)$overloadCount ?> overloaded</span>
                    <div class="flex items-center gap-2">
                        <button onclick="openReportModal('overload')" class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"><i class="fas fa-eye mr-1"></i>View</button>
                        <div class="relative inline-block">
                            <button onclick="toggleExportMenu(event,'overload')" class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors flex items-center gap-1"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down text-[9px]"></i></button>
                            <div id="exportMenu-overload" class="hidden absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-xl z-50 overflow-hidden">
                                <button onclick="exportReport('overload','csv')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2 border-b border-slate-100"><i class="fas fa-file-csv text-green-600 w-4"></i>Export as CSV</button>
                                <button onclick="exportReport('overload','pdf')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-file-pdf text-red-500 w-4"></i>Export as PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Faculty Load Distribution (dynamic from DB) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-900">Faculty Load Distribution</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php if (empty($reportTeachers)): ?>
                    <p class="text-sm text-slate-400 text-center py-4">No teachers found. Import teacher data to see the load distribution.</p>
                <?php else: ?>
                    <?php foreach ($reportTeachers as $rt):
                        $maxU = (int)$rt['max_units'];
                        $curU = (int)$rt['current_units'];
                        $pct  = $maxU > 0 ? min(round(($curU / $maxU) * 100), 100) : 0;
                        // Color: red if over max, amber if at max, indigo otherwise
                        if ($curU > $maxU) {
                            $barColor = 'bg-red-500';
                        } elseif ($curU === $maxU) {
                            $barColor = 'bg-amber-500';
                        } else {
                            $barColor = 'bg-indigo-500';
                        }
                        // If overloaded, bar can exceed 100% visually — cap at 100 for CSS
                        $barWidth = $curU > $maxU ? 100 : $pct;
                    ?>
                    <div class="flex items-center gap-4">
                        <span class="w-24 sm:w-32 text-sm text-slate-600 truncate" title="<?= htmlspecialchars($rt['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rt['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="flex-1 bg-slate-100 rounded-full h-6 overflow-hidden">
                            <div class="<?= $barColor ?> h-full rounded-full flex items-center justify-end pr-2" style="width: <?= $barWidth ?>%">
                                <span class="text-xs text-white font-medium"><?= $curU ?>/<?= $maxU ?> units</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-4 sm:gap-6 text-xs text-slate-500">
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-indigo-500"></div><span>Normal Load</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-amber-500"></div><span>At Maximum</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-red-500"></div><span>Overloaded</span></div>
            </div>
        </div>
    </div>

    <!-- Quick Stats (dynamic from DB) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">Average Load</p>
            <p class="text-2xl font-bold text-slate-900 mt-1"><?= $avgLoad ?> <span class="text-sm font-normal text-slate-400">units</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">At Capacity</p>
            <p class="text-2xl font-bold text-amber-600 mt-1"><?= (int)$atCapacityCount ?> <span class="text-sm font-normal text-slate-400">teachers</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">Available</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= (int)$availableUnits ?> <span class="text-sm font-normal text-slate-400">units</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-200">
            <p class="text-sm text-slate-500">Utilization</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1"><?= $utilization ?>%</p>
        </div>
    </div>

    <!-- Predictive Analytics -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-900">Predictive Faculty Demand Forecast</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="relative h-80">
                        <canvas id="predictiveChart"></canvas>
                    </div>
                </div>
                <div>
                    <div id="predictiveInsight" class="bg-amber-50 text-amber-800 border border-amber-200 rounded-lg p-4 text-sm">
                        Loading predictions...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Predictive HR Insights -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Forecasted Faculty Shortages (2027)</h3>
                <p class="text-sm text-slate-500 mt-0.5">Based on historical sections offered and current specialized capacity</p>
            </div>
        </div>
        <div class="p-6">
            <div id="predictiveHrInsights" class="space-y-3">
                <div class="text-sm text-slate-500" id="predictiveHrInsightsStatus">Loading forecast…</div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- REPORT VIEW MODALS                                           -->
<!-- ============================================================ -->

<!-- Faculty Load Summary Modal -->
<div id="reportModal-faculty" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[85vh] flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Faculty Load Summary</h2>
                <p class="text-sm text-slate-500 mt-0.5"><?= (int)$totalTeachers ?> teachers total</p>
            </div>
            <button onclick="closeReportModal('faculty')" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-6 py-4 overflow-auto flex-1">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                        <th class="pb-3 pr-4">Teacher</th>
                        <th class="pb-3 pr-4">Type</th>
                        <th class="pb-3 pr-4">Current Units</th>
                        <th class="pb-3 pr-4">Max Units</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3">Expertise</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportTeachers)): ?>
                        <tr><td colspan="6" class="py-6 text-center text-slate-400">No teacher data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportTeachers as $rt):
                            $cur = (int)$rt['current_units'];
                            $max = (int)$rt['max_units'];
                            if ($cur > $max) {
                                $statusLabel = 'Overloaded';
                                $statusClass = 'bg-red-100 text-red-700';
                            } elseif ($cur === $max) {
                                $statusLabel = 'At Capacity';
                                $statusClass = 'bg-amber-100 text-amber-700';
                            } else {
                                $statusLabel = 'Normal';
                                $statusClass = 'bg-green-100 text-green-700';
                            }
                        ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-medium text-slate-900"><?= htmlspecialchars($rt['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= htmlspecialchars($rt['type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 pr-4 text-slate-900 font-medium"><?= $cur ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= $max ?></td>
                            <td class="py-3 pr-4"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td class="py-3 text-slate-500 text-xs"><?= htmlspecialchars($rt['expertise_tags'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-200 flex justify-end gap-3 bg-slate-50 rounded-b-xl">
            <button onclick="exportReport('faculty','csv')" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1.5"><i class="fas fa-file-csv"></i>CSV</button>
            <button onclick="exportReport('faculty','pdf')" class="px-4 py-2 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center gap-1.5"><i class="fas fa-file-pdf"></i>PDF</button>
            <button onclick="closeReportModal('faculty')" class="px-4 py-2 text-sm border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors">Close</button>
        </div>
    </div>
</div>

<!-- Subject Assignment Report Modal -->
<div id="reportModal-subject" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[85vh] flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Subject Assignment Report</h2>
                <p class="text-sm text-slate-500 mt-0.5"><?= (int)$totalSubjects ?> subjects total</p>
            </div>
            <button onclick="closeReportModal('subject')" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-6 py-4 overflow-auto flex-1">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                        <th class="pb-3 pr-4">Code</th>
                        <th class="pb-3 pr-4">Subject</th>
                        <th class="pb-3 pr-4">Program</th>
                        <th class="pb-3 pr-4">Units</th>
                        <th class="pb-3 pr-4">Assigned To</th>
                        <th class="pb-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportSubjects)): ?>
                        <tr><td colspan="6" class="py-6 text-center text-slate-400">No subject data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportSubjects as $rs):
                            $isUnassigned = ($rs['teacher_name'] === 'Unassigned');
                            $sStatus = $isUnassigned ? 'Unassigned' : htmlspecialchars($rs['assignment_status'] ?? 'Assigned', ENT_QUOTES, 'UTF-8');
                            $sClass  = $isUnassigned ? 'bg-slate-100 text-slate-600' : 'bg-green-100 text-green-700';
                        ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-mono text-xs text-slate-700"><?= htmlspecialchars($rs['course_code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 pr-4 font-medium text-slate-900"><?= htmlspecialchars($rs['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= htmlspecialchars($rs['program'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 pr-4 text-slate-900"><?= (int)$rs['units'] ?></td>
                            <td class="py-3 pr-4 <?= $isUnassigned ? 'text-slate-400 italic' : 'text-slate-900' ?>"><?= htmlspecialchars($rs['teacher_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $sClass ?>"><?= $sStatus ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-200 flex justify-end gap-3 bg-slate-50 rounded-b-xl">
            <button onclick="exportReport('subject','csv')" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1.5"><i class="fas fa-file-csv"></i>CSV</button>
            <button onclick="exportReport('subject','pdf')" class="px-4 py-2 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center gap-1.5"><i class="fas fa-file-pdf"></i>PDF</button>
            <button onclick="closeReportModal('subject')" class="px-4 py-2 text-sm border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors">Close</button>
        </div>
    </div>
</div>

<!-- Overload Analysis Modal -->
<div id="reportModal-overload" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full mx-4 max-h-[85vh] flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Overload Analysis</h2>
                <p class="text-sm text-slate-500 mt-0.5"><?= (int)$overloadCount ?> overloaded teachers</p>
            </div>
            <button onclick="closeReportModal('overload')" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-6 py-4 overflow-auto flex-1">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                        <th class="pb-3 pr-4">Teacher</th>
                        <th class="pb-3 pr-4">Type</th>
                        <th class="pb-3 pr-4">Current Units</th>
                        <th class="pb-3 pr-4">Max Units</th>
                        <th class="pb-3">Excess</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportOverloaded)): ?>
                        <tr><td colspan="5" class="py-6 text-center text-green-500"><i class="fas fa-check-circle mr-1"></i>No overloaded teachers. All loads are within policy limits.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportOverloaded as $ro): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-medium text-slate-900"><?= htmlspecialchars($ro['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= htmlspecialchars($ro['type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="py-3 pr-4 text-red-600 font-medium"><?= (int)$ro['current_units'] ?></td>
                            <td class="py-3 pr-4 text-slate-600"><?= (int)$ro['max_units'] ?></td>
                            <td class="py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">+<?= (int)$ro['excess_units'] ?> units</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-200 flex justify-end gap-3 bg-slate-50 rounded-b-xl">
            <button onclick="exportReport('overload','csv')" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1.5"><i class="fas fa-file-csv"></i>CSV</button>
            <button onclick="exportReport('overload','pdf')" class="px-4 py-2 text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center gap-1.5"><i class="fas fa-file-pdf"></i>PDF</button>
            <button onclick="closeReportModal('overload')" class="px-4 py-2 text-sm border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors">Close</button>
        </div>
    </div>
</div>

<!-- Hidden data islands for CSV export -->
<script id="reportData-faculty" type="application/json"><?= json_encode($reportTeachers, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script id="reportData-subject" type="application/json"><?= json_encode($reportSubjects, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script id="reportData-overload" type="application/json"><?= json_encode($reportOverloaded, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<!-- END LOAD REPORTS PAGE -->
