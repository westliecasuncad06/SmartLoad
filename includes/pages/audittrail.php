<!-- AUDIT TRAIL PAGE -->
<div id="page-audittrail" class="page-content hidden p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Audit Trail</h2>
            <p class="text-slate-600 mt-1">Complete history of all system activities and changes</p>
        </div>
        <div class="relative inline-block">
            <button onclick="toggleExportMenu(event,'audit')" class="flex items-center gap-2 px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm"><i class="fas fa-file-export"></i> Export Logs <i class="fas fa-chevron-down text-[9px]"></i></button>
            <div id="exportMenu-audit" class="hidden absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-xl z-50 overflow-hidden">
                <button onclick="exportAuditLogs('csv')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2 border-b border-slate-100"><i class="fas fa-file-csv text-green-600 w-4"></i>Export as CSV</button>
                <button onclick="exportAuditLogs('pdf')" class="w-full text-left px-3 py-2 text-xs hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-file-pdf text-red-500 w-4"></i>Export as PDF</button>
            </div>
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
    <?php
    $auditRows = $pdo->query('SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50')->fetchAll();
    $auditCount = count($auditRows);
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Activity Log</h3>
                <span class="text-sm text-slate-500">Showing <?php echo $auditCount; ?> entries</span>
            </div>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if ($auditCount === 0): ?>
            <div class="px-6 py-8 text-center text-slate-400">
                <i class="fas fa-clipboard-list text-3xl mb-2"></i>
                <p class="text-sm">No audit log entries yet.</p>
            </div>
            <?php endif; ?>
            <?php foreach ($auditRows as $log):
                $actionType = $log['action_type'];
                switch ($actionType) {
                    case 'Schedule Generation':
                        $iconBg    = 'bg-green-100';
                        $iconClass = 'fas fa-bolt text-green-600';
                        $badgeBg   = 'bg-green-100 text-green-700';
                        $badgeText = 'Success';
                        break;
                    case 'Manual Override':
                        $iconBg    = 'bg-purple-100';
                        $iconClass = 'fas fa-user-pen text-purple-600';
                        $badgeBg   = 'bg-purple-100 text-purple-700';
                        $badgeText = 'Override';
                        break;
                    case 'Overload Warning':
                        $iconBg    = 'bg-amber-100';
                        $iconClass = 'fas fa-triangle-exclamation text-amber-600';
                        $badgeBg   = 'bg-amber-100 text-amber-700';
                        $badgeText = 'Warning';
                        break;
                    case 'File Upload':
                        $iconBg    = 'bg-blue-100';
                        $iconClass = 'fas fa-file-arrow-up text-blue-600';
                        $badgeBg   = 'bg-blue-100 text-blue-700';
                        $badgeText = 'Upload';
                        break;
                    case 'Settings Changed':
                        $iconBg    = 'bg-slate-100';
                        $iconClass = 'fas fa-sliders text-slate-600';
                        $badgeBg   = 'bg-slate-200 text-slate-700';
                        $badgeText = 'Config';
                        break;
                    default:
                        $iconBg    = 'bg-indigo-100';
                        $iconClass = 'fas fa-circle-info text-indigo-600';
                        $badgeBg   = 'bg-indigo-100 text-indigo-700';
                        $badgeText = 'Activity';
                        break;
                }
                $formattedDate = date('M j, Y, g:i A', strtotime($log['created_at']));
            ?>
            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 <?php echo $iconBg; ?> rounded-full flex items-center justify-center flex-shrink-0"><i class="<?php echo $iconClass; ?>"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-900"><?php echo htmlspecialchars($actionType); ?></span>
                        <span class="px-2 py-0.5 text-xs <?php echo $badgeBg; ?> rounded-full"><?php echo $badgeText; ?></span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1"><?php echo htmlspecialchars($log['description'] ?? ''); ?></p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($log['user']); ?></span>
                        <span><i class="fas fa-clock mr-1"></i><?php echo $formattedDate; ?></span>
                    </div>
                </div>
                <button class="px-3 py-1.5 text-xs text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">Details</button>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <span class="text-sm text-slate-600">Showing <?php echo $auditCount; ?> entries</span>
            <div class="flex items-center gap-1">
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors disabled:opacity-50" disabled><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg">1</button>
                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Hidden data island for audit log export -->
    <script id="auditLogData" type="application/json"><?= json_encode($auditRows, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</div>
<!-- END AUDIT TRAIL PAGE -->
