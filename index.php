<?php
require_once __DIR__ . '/includes/db.php';

// Fetch dashboard stats from database
try {
    $totalTeachers    = $pdo->query("SELECT COUNT(*) FROM teachers WHERE is_archived = 0")->fetchColumn();
    $fullTimeCount    = $pdo->query("SELECT COUNT(*) FROM teachers WHERE is_archived = 0 AND type = 'Full-time'")->fetchColumn();
    $partTimeCount    = $pdo->query("SELECT COUNT(*) FROM teachers WHERE is_archived = 0 AND type = 'Part-time'")->fetchColumn();
    $totalSubjects    = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $totalUnits       = $pdo->query("SELECT COALESCE(SUM(units), 0) FROM subjects")->fetchColumn();
    $assignedSubjects = $pdo->query("SELECT COUNT(DISTINCT subject_id) FROM assignments")->fetchColumn();
    $unassignedCount  = (int)$totalSubjects - (int)$assignedSubjects;
    $overloadCount    = $pdo->query("SELECT COUNT(*) FROM teachers WHERE is_archived = 0 AND current_units > max_units")->fetchColumn();
    $recentLogs       = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();

    // Last generation timestamp
    $lastGenRow = $pdo->query("SELECT created_at FROM audit_logs WHERE action_type = 'Schedule Generation' ORDER BY created_at DESC LIMIT 1")->fetch();
    $lastGenTime = $lastGenRow ? date('M j, g:i A', strtotime($lastGenRow['created_at'])) : null;

    // Policy settings for dynamic bar
    $policyRow = $pdo->query("SELECT * FROM policy_settings ORDER BY id DESC LIMIT 1")->fetch();
    $policyMaxLoad        = $policyRow ? (int)$policyRow['max_teaching_load'] : 18;
    $policyExpertiseWeight = $policyRow ? (int)$policyRow['expertise_weight'] : 70;
    $policyAvailWeight     = $policyRow ? (int)$policyRow['availability_weight'] : 30;
} catch (PDOException $e) {
    // Use fallback values when DB is not yet set up
    $totalTeachers    = 0;
    $fullTimeCount    = 0;
    $partTimeCount    = 0;
    $totalSubjects    = 0;
    $totalUnits       = 0;
    $assignedSubjects = 0;
    $unassignedCount  = 0;
    $overloadCount    = 0;
    $recentLogs       = [];
    $lastGenTime      = null;
    $policyMaxLoad    = 18;
    $policyExpertiseWeight = 70;
    $policyAvailWeight     = 30;
}

// Fetch load assignment report data (teachers with assignments + unassigned subjects)
try {
    $assignStmt = $pdo->query("
        SELECT t.id AS teacher_id, t.name AS teacher_name, t.type AS teacher_type,
               t.expertise_tags, t.current_units, t.max_units,
               a.id AS assignment_id, a.status AS assignment_status,
               a.rationale, a.created_at AS assigned_at,
               sub.id AS subject_id, sub.course_code, sub.name AS subject_name,
               sub.units AS subject_units, sub.prerequisites
        FROM assignments a
        JOIN teachers t ON a.teacher_id = t.id
        JOIN subjects sub ON a.subject_id = sub.id
        ORDER BY t.name ASC, sub.course_code ASC
    ");
    $assignRows = $assignStmt->fetchAll(PDO::FETCH_ASSOC);

    $teacherAssignments = [];
    foreach ($assignRows as $row) {
        $tid = (int)$row['teacher_id'];
        if (!isset($teacherAssignments[$tid])) {
            $teacherAssignments[$tid] = [
                'teacher_id'     => $tid,
                'teacher_name'   => $row['teacher_name'],
                'teacher_type'   => $row['teacher_type'],
                'expertise_tags' => $row['expertise_tags'],
                'current_units'  => (int)$row['current_units'],
                'max_units'      => (int)$row['max_units'],
                'subjects'       => [],
            ];
        }
        $teacherAssignments[$tid]['subjects'][] = [
            'assignment_id'     => (int)$row['assignment_id'],
            'assignment_status' => $row['assignment_status'],
            'rationale'         => $row['rationale'],
            'assigned_at'       => $row['assigned_at'],
            'subject_id'        => (int)$row['subject_id'],
            'course_code'       => $row['course_code'],
            'subject_name'      => $row['subject_name'],
            'subject_units'     => (int)$row['subject_units'],
            'prerequisites'     => $row['prerequisites'],
        ];
    }

    $schedBySubject     = [];
    $assignedSubjectIds = array_unique(array_column($assignRows, 'subject_id'));
    if (!empty($assignedSubjectIds)) {
        $ph       = implode(',', array_fill(0, count($assignedSubjectIds), '?'));
        $schedStmt = $pdo->prepare("SELECT subject_id, day_of_week, start_time FROM schedules WHERE subject_id IN ($ph) ORDER BY start_time, day_of_week");
        $schedStmt->execute($assignedSubjectIds);
        foreach ($schedStmt->fetchAll(PDO::FETCH_ASSOC) as $sr) {
            $schedBySubject[(int)$sr['subject_id']][] = $sr;
        }
    }

    $unassignedStmt = $pdo->query("
        SELECT sub.id, sub.course_code, sub.name, sub.units, sub.prerequisites
        FROM subjects sub
        LEFT JOIN assignments a ON a.subject_id = sub.id
        WHERE a.id IS NULL
    ");
    $unassignedSubjects = $unassignedStmt->fetchAll(PDO::FETCH_ASSOC);

    $unassignedSchedBySubject = [];
    if (!empty($unassignedSubjects)) {
        $unassignedIds        = array_column($unassignedSubjects, 'id');
        $ph2                  = implode(',', array_fill(0, count($unassignedIds), '?'));
        $unassignedSchedStmt  = $pdo->prepare("SELECT subject_id, day_of_week, start_time FROM schedules WHERE subject_id IN ($ph2) ORDER BY start_time, day_of_week");
        $unassignedSchedStmt->execute($unassignedIds);
        foreach ($unassignedSchedStmt->fetchAll(PDO::FETCH_ASSOC) as $sr) {
            $unassignedSchedBySubject[(int)$sr['subject_id']][] = $sr;
        }
    }
} catch (PDOException $e) {
    $teacherAssignments       = [];
    $unassignedSubjects       = [];
    $schedBySubject           = [];
    $unassignedSchedBySubject = [];
}

function formatSubjectSchedules(array $schedules): array {
    $byTime = [];
    foreach ($schedules as $s) {
        $byTime[$s['start_time']][] = substr($s['day_of_week'], 0, 3);
    }
    $result = [];
    foreach ($byTime as $time => $days) {
        $result[] = implode('/', array_unique($days)) . ' ' . date('g:i A', strtotime($time));
    }
    return $result;
}

// -----------------------------------------------------------
// Load Reports data
// -----------------------------------------------------------
try {
    // All teachers with load info
    $reportTeachers = $pdo->query("
        SELECT id, name, type, current_units, max_units, expertise_tags
        FROM teachers WHERE is_archived = 0 ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Subjects with assigned teacher
    $reportSubjects = $pdo->query("
        SELECT s.id, s.course_code, s.name AS subject_name, s.program, s.units,
               COALESCE(t.name, 'Unassigned') AS teacher_name, a.status AS assignment_status
        FROM subjects s
        LEFT JOIN assignments a ON a.subject_id = s.id
        LEFT JOIN teachers t ON a.teacher_id = t.id
        ORDER BY s.course_code ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Overloaded teachers
    $reportOverloaded = $pdo->query("
        SELECT t.id, t.name, t.type, t.current_units, t.max_units,
               (t.current_units - t.max_units) AS excess_units
        FROM teachers t
        WHERE t.is_archived = 0 AND t.current_units > t.max_units
        ORDER BY excess_units DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Quick stats
    $totalMaxUnits   = $pdo->query("SELECT COALESCE(SUM(max_units),0) FROM teachers WHERE is_archived = 0")->fetchColumn();
    $totalCurrUnits  = $pdo->query("SELECT COALESCE(SUM(current_units),0) FROM teachers WHERE is_archived = 0")->fetchColumn();
    $atCapacityCount = $pdo->query("SELECT COUNT(*) FROM teachers WHERE is_archived = 0 AND current_units = max_units")->fetchColumn();
    $avgLoad         = $totalTeachers > 0 ? round($totalCurrUnits / $totalTeachers, 1) : 0;
    $availableUnits  = $totalMaxUnits - $totalCurrUnits;
    $utilization     = $totalMaxUnits > 0 ? round(($totalCurrUnits / $totalMaxUnits) * 100, 1) : 0;
} catch (PDOException $e) {
    $reportTeachers  = [];
    $reportSubjects  = [];
    $reportOverloaded = [];
    $totalMaxUnits   = 0;
    $totalCurrUnits  = 0;
    $atCapacityCount = 0;
    $avgLoad         = 0;
    $availableUnits  = 0;
    $utilization     = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartLoad - Intelligent Faculty Scheduling</title>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome via CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-slate-50">
    <div id="appWrapper" class="flex h-screen" style="display:none;">
        <!-- Sidebar Overlay (mobile) -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="closeSidebar()"></div>

        <!-- SIDEBAR -->
        <aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-slate-900 text-white shadow-xl flex flex-col z-40 sidebar-transition -translate-x-full lg:translate-x-0">
            <!-- Logo -->
            <div class="p-6 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="bg-indigo-500 p-2 rounded-lg">
                        <i class="fas fa-bolt text-lg"></i>
                    </div>
                    <h1 class="text-xl font-bold">SmartLoad</h1>
                </div>
                <p class="text-xs text-slate-400 mt-1">Faculty Scheduling System</p>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 px-4 py-6 space-y-1">
                <p class="text-xs text-slate-500 uppercase tracking-wider px-4 mb-2">Main Menu</p>

                <a href="#" onclick="switchPage('dashboard'); return false;" data-page="dashboard" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg bg-indigo-600 text-white font-medium transition-all">
                    <i class="fas fa-gauge-high w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>

                <a href="#" onclick="switchPage('teachers'); return false;" data-page="teachers" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fas fa-chalkboard-user w-5 text-center"></i>
                    <span>Teachers</span>
                    <span class="ml-auto bg-slate-700 text-xs px-2 py-0.5 rounded-full"><?php echo (int)$totalTeachers; ?></span>
                </a>

                <a href="#" onclick="switchPage('subjects'); return false;" data-page="subjects" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fas fa-book-open w-5 text-center"></i>
                    <span>Subjects</span>
                    <span class="ml-auto bg-slate-700 text-xs px-2 py-0.5 rounded-full"><?php echo (int)$totalSubjects; ?></span>
                </a>

                <a href="#" onclick="switchPage('schedules'); return false;" data-page="schedules" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fas fa-calendar-days w-5 text-center"></i>
                    <span>Schedules</span>
                </a>

                <p class="text-xs text-slate-500 uppercase tracking-wider px-4 mb-2 mt-6">Reports</p>

                <a href="#" onclick="switchPage('loadreports'); return false;" data-page="loadreports" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fas fa-file-lines w-5 text-center"></i>
                    <span>Load Reports</span>
                </a>

                <a href="#" onclick="switchPage('audittrail'); return false;" data-page="audittrail" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fas fa-clock-rotate-left w-5 text-center"></i>
                    <span>Audit Trail</span>
                </a>

                <p class="text-xs text-slate-500 uppercase tracking-wider px-4 mb-2 mt-6">System</p>

                <a href="#" onclick="openSettingsModal(); return false;" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fas fa-sliders w-5 text-center"></i>
                    <span>Policy Settings</span>
                </a>
            </nav>

            <!-- Footer -->
            <div class="p-4 border-t border-slate-800">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-9 h-9 bg-gradient-to-br from-indigo-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        PC
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">Program Chair</p>
                        <p class="text-xs text-slate-400">Admin Access</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="lg:ml-64 flex-1 flex flex-col min-w-0">
            <!-- TOP NAVBAR -->
            <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-10">
                <div class="flex items-center justify-between px-3 sm:px-6 py-3 sm:py-4">
                    <div class="flex items-center gap-3">
                        <!-- Hamburger toggle (mobile/tablet) -->
                        <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors" aria-label="Toggle menu">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <!-- Desktop breadcrumb -->
                        <div class="hidden sm:block text-sm text-slate-500">
                            <span class="text-slate-400">SmartLoad</span>
                            <i class="fas fa-chevron-right text-xs mx-2"></i>
                            <span id="breadcrumbTitle" class="text-slate-700 font-medium">Dashboard</span>
                        </div>
                        <!-- Mobile breadcrumb -->
                        <div id="mobileBreadcrumb" class="sm:hidden text-sm font-medium text-slate-700">Dashboard</div>
                    </div>

                    <div class="hidden sm:block flex-1 max-w-lg mx-4 lg:mx-8">
                        <div class="relative">
                            <input type="text" id="globalSearch" placeholder="Search teachers, subjects, schedules..." class="w-full px-4 py-2 pl-10 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                            <kbd class="absolute right-3 top-2 px-2 py-0.5 text-xs bg-slate-100 text-slate-500 rounded hidden md:inline">Ctrl+K</kbd>
                            <!-- Search Results Dropdown -->
                            <div id="searchResultsDropdown" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-50 max-h-96 overflow-y-auto">
                                <div id="searchLoading" class="hidden p-4 text-center text-slate-500 text-sm">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Searching...
                                </div>
                                <div id="searchResultsList"></div>
                                <div id="searchNoResults" class="hidden p-4 text-center text-slate-400 text-sm">
                                    <i class="fas fa-search mr-2"></i>No results found
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-4">
                        <!-- Mobile search toggle -->
                        <button onclick="toggleMobileSearch()" class="sm:hidden p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                        <button onclick="openSettingsModal()" class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors" title="Settings">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="hidden sm:flex items-center gap-3 pl-4 border-l border-slate-200 relative">
                            <button onclick="toggleApiDropdown()" class="flex items-center gap-3 hover:opacity-80 transition-opacity cursor-pointer">
                                <div class="w-9 h-9 bg-gradient-to-br from-indigo-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                    PC
                                </div>
                                <div class="hidden lg:block text-left">
                                    <p class="text-sm font-medium text-slate-900">Program Chair</p>
                                    <p class="text-xs text-slate-500">College of Engineering</p>
                                </div>
                                <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                            </button>

                            <!-- API Key Management Dropdown -->
                            <div id="apiDropdown" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden">
                                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-slate-800">
                                            <i class="fas fa-key text-indigo-500 mr-1.5"></i>Gemini API
                                        </h3>
                                        <span id="apiStatusBadge" class="px-2 py-0.5 text-xs font-medium rounded-full bg-slate-200 text-slate-600">Checking...</span>
                                    </div>
                                </div>
                                <div class="px-4 py-3 space-y-3">
                                    <div>
                                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide">Current Key</label>
                                        <p id="apiKeyMasked" class="text-sm font-mono text-slate-700 mt-0.5">Loading...</p>
                                    </div>
                                    <div class="flex gap-4">
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide">Requests Made</label>
                                            <p id="apiTotalRequests" class="text-sm font-semibold text-slate-800 mt-0.5">—</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide">Last Used</label>
                                            <p id="apiLastUsed" class="text-sm text-slate-700 mt-0.5">—</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3 border-t border-slate-100 bg-slate-50 flex gap-2">
                                    <button onclick="openApiKeyModal()" class="flex-1 px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                        <i class="fas fa-pen-to-square mr-1"></i>Change Key
                                    </button>
                                    <button onclick="refreshApiStatus()" class="px-3 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-100 transition-colors">
                                        <i class="fas fa-rotate"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Mobile search bar (hidden by default) -->
                <div id="mobileSearchBar" class="hidden sm:hidden px-3 pb-3">
                    <div class="relative">
                        <input type="text" id="mobileSearchInput" placeholder="Search..." class="w-full px-4 py-2 pl-10 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                        <!-- Mobile Search Results Dropdown -->
                        <div id="mobileSearchResultsDropdown" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-50 max-h-80 overflow-y-auto">
                            <div id="mobileSearchLoading" class="hidden p-4 text-center text-slate-500 text-sm">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Searching...
                            </div>
                            <div id="mobileSearchResultsList"></div>
                            <div id="mobileSearchNoResults" class="hidden p-4 text-center text-slate-400 text-sm">
                                <i class="fas fa-search mr-2"></i>No results found
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <div class="flex-1 overflow-auto">

                <!-- ========== DASHBOARD PAGE ========== -->
                <div id="page-dashboard" class="page-content p-4 sm:p-6 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">
                                <?php
                                    $hour = (int)date('G');
                                    if ($hour < 12) $greeting = 'Good Morning';
                                    elseif ($hour < 17) $greeting = 'Good Afternoon';
                                    else $greeting = 'Good Evening';
                                ?>
                                <?= $greeting ?>, Program Chair
                            </h2>
                            <p class="text-slate-600 mt-1 text-sm">Here's your faculty load assignment overview for <span class="font-medium text-slate-700"><?= date('F j, Y') ?></span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <?php if ($lastGenTime): ?>
                            <span class="text-sm text-slate-500 hidden sm:inline">
                                <i class="fas fa-clock text-slate-400 mr-1"></i>
                                Last generated: <span class="font-medium text-slate-700"><?= htmlspecialchars($lastGenTime) ?></span>
                            </span>
                            <?php else: ?>
                            <span class="text-sm text-slate-400 hidden sm:inline italic">No schedule generated yet</span>
                            <?php endif; ?>
                            <button onclick="openHistoryModal()" class="flex items-center gap-2 px-3 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">
                                <i class="fas fa-clock-rotate-left"></i>
                                History
                            </button>
                        </div>
                    </div>

                    <!-- QUICK STATS -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200 cursor-pointer" onclick="switchPage('teachers')">
                            <div class="flex items-center justify-between">
                                <div class="bg-indigo-100 p-2.5 rounded-lg"><i class="fas fa-users text-indigo-600"></i></div>
                                <span class="text-xs text-indigo-600 font-medium bg-indigo-50 px-2 py-0.5 rounded-full" title="Full-time / Part-time">
                                    <?= (int)$fullTimeCount ?> FT / <?= (int)$partTimeCount ?> PT
                                </span>
                            </div>
                            <p class="text-2xl font-bold text-slate-900 mt-3"><?= (int)$totalTeachers ?></p>
                            <p class="text-slate-500 text-sm">Total Faculty</p>
                            <div class="mt-2 flex gap-1">
                                <?php if ((int)$totalTeachers > 0): ?>
                                <div class="h-1 rounded-full bg-indigo-500" style="width:<?= round(((int)$fullTimeCount / (int)$totalTeachers) * 100) ?>%"></div>
                                <div class="h-1 rounded-full bg-indigo-200" style="width:<?= round(((int)$partTimeCount / (int)$totalTeachers) * 100) ?>%"></div>
                                <?php else: ?>
                                <div class="h-1 rounded-full bg-slate-200 w-full"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200 cursor-pointer" onclick="switchPage('subjects')">
                            <div class="flex items-center justify-between">
                                <div class="bg-blue-100 p-2.5 rounded-lg"><i class="fas fa-book-open text-blue-600"></i></div>
                                <span class="text-xs text-slate-500 font-medium"><?= (int)$totalUnits ?> total units</span>
                            </div>
                            <p class="text-2xl font-bold text-slate-900 mt-3"><?= (int)$totalSubjects ?></p>
                            <p class="text-slate-500 text-sm">Total Subjects</p>
                            <?php if ((int)$unassignedCount > 0): ?>
                            <p class="text-xs text-amber-600 mt-2"><i class="fas fa-circle-exclamation mr-1"></i><?= (int)$unassignedCount ?> pending assignment</p>
                            <?php else: ?>
                            <p class="text-xs text-green-600 mt-2"><i class="fas fa-circle-check mr-1"></i>All assigned</p>
                            <?php endif; ?>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="bg-green-100 p-2.5 rounded-lg"><i class="fas fa-circle-check text-green-600"></i></div>
                                <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-0.5 rounded-full"><?= $totalSubjects > 0 ? round(($assignedSubjects / $totalSubjects) * 100) : 0 ?>%</span>
                            </div>
                            <p class="text-2xl font-bold text-slate-900 mt-3"><?= (int)$assignedSubjects ?><span class="text-base font-normal text-slate-400"> / <?= (int)$totalSubjects ?></span></p>
                            <p class="text-slate-500 text-sm">Subjects Assigned</p>
                            <div class="mt-2 w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full transition-all" style="width:<?= $totalSubjects > 0 ? round(($assignedSubjects / $totalSubjects) * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border <?= (int)$overloadCount > 0 ? 'border-red-200 bg-red-50/30' : 'border-slate-200' ?>">
                            <div class="flex items-center justify-between">
                                <div class="<?= (int)$overloadCount > 0 ? 'bg-red-100' : 'bg-amber-100' ?> p-2.5 rounded-lg">
                                    <i class="fas fa-triangle-exclamation <?= (int)$overloadCount > 0 ? 'text-red-600' : 'text-amber-600' ?>"></i>
                                </div>
                                <?php if ((int)$overloadCount > 0): ?>
                                <span class="text-xs text-red-600 font-medium bg-red-50 px-2 py-0.5 rounded-full animate-pulse">Action needed</span>
                                <?php else: ?>
                                <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-0.5 rounded-full">All clear</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-2xl font-bold <?= (int)$overloadCount > 0 ? 'text-red-600' : 'text-slate-900' ?> mt-3"><?= (int)$overloadCount ?></p>
                            <p class="text-slate-500 text-sm">Overload Flags</p>
                        </div>
                    </div>

                    <!-- FACULTY UTILIZATION OVERVIEW -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-slate-900">Faculty Load Distribution</h3>
                                <button onclick="switchPage('loadreports')" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View Full Report <i class="fas fa-arrow-right ml-1"></i></button>
                            </div>
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div class="text-center p-3 bg-slate-50 rounded-lg">
                                    <p class="text-lg font-bold text-slate-900"><?= $avgLoad ?></p>
                                    <p class="text-xs text-slate-500">Avg. Load (units)</p>
                                </div>
                                <div class="text-center p-3 bg-slate-50 rounded-lg">
                                    <p class="text-lg font-bold text-slate-900"><?= $availableUnits >= 0 ? $availableUnits : 0 ?></p>
                                    <p class="text-xs text-slate-500">Available Units</p>
                                </div>
                                <div class="text-center p-3 bg-slate-50 rounded-lg">
                                    <p class="text-lg font-bold <?= $utilization > 90 ? 'text-red-600' : ($utilization > 75 ? 'text-amber-600' : 'text-green-600') ?>"><?= $utilization ?>%</p>
                                    <p class="text-xs text-slate-500">Utilization Rate</p>
                                </div>
                            </div>
                            <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all <?= $utilization > 90 ? 'bg-red-500' : ($utilization > 75 ? 'bg-amber-500' : 'bg-green-500') ?>" style="width:<?= min(100, $utilization) ?>%"></div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-xs text-slate-400">0%</span>
                                <span class="text-xs text-slate-400">Capacity: <?= (int)$totalMaxUnits ?> units</span>
                                <span class="text-xs text-slate-400">100%</span>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                            <h3 class="text-sm font-semibold text-slate-900 mb-4">Quick Actions</h3>
                            <div class="space-y-2">
                                <button onclick="document.getElementById('teacherUpload').click()" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 bg-slate-50 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-colors text-left">
                                    <i class="fas fa-cloud-arrow-up w-5 text-center text-indigo-500"></i>
                                    <span>Upload Data Files</span>
                                    <i class="fas fa-chevron-right ml-auto text-xs text-slate-400"></i>
                                </button>
                                <button onclick="switchPage('teachers')" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 bg-slate-50 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-colors text-left">
                                    <i class="fas fa-user-plus w-5 text-center text-indigo-500"></i>
                                    <span>Manage Teachers</span>
                                    <i class="fas fa-chevron-right ml-auto text-xs text-slate-400"></i>
                                </button>
                                <button onclick="switchPage('subjects')" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 bg-slate-50 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-colors text-left">
                                    <i class="fas fa-book-open w-5 text-center text-indigo-500"></i>
                                    <span>Manage Subjects</span>
                                    <i class="fas fa-chevron-right ml-auto text-xs text-slate-400"></i>
                                </button>
                                <button onclick="openSettingsModal()" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 bg-slate-50 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition-colors text-left">
                                    <i class="fas fa-sliders w-5 text-center text-indigo-500"></i>
                                    <span>Policy Settings</span>
                                    <i class="fas fa-chevron-right ml-auto text-xs text-slate-400"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- UPLOAD FILES -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Step 1: Upload Input Files</h3>
                                    <p class="text-sm text-slate-500 mt-0.5">Upload Excel/CSV files containing teacher profiles, subject catalog, and schedules</p>
                                </div>
                                <button class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1 self-start">
                                    <i class="fas fa-download text-xs"></i>
                                    Download Templates
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    <label class="flex items-center gap-2 text-sm text-slate-700 select-none">
                                        <input type="checkbox" id="uploadPreviousToggle" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>Uploading previous AY/Sem (historical data)</span>
                                    </label>

                                    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                                        <div class="flex items-center gap-2">
                                            <label for="uploadAcademicYear" class="text-xs text-slate-600 whitespace-nowrap">Academic Year</label>
                                            <input id="uploadAcademicYear" type="text" placeholder="e.g., 2025-2026" disabled class="w-40 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-500">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label for="uploadSemester" class="text-xs text-slate-600 whitespace-nowrap">Semester</label>
                                            <select id="uploadSemester" disabled class="w-44 pl-3 pr-8 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white disabled:bg-slate-100 disabled:text-slate-500">
                                                <option value="">Select</option>
                                                <option value="1st">1st Semester</option>
                                                <option value="2nd">2nd Semester</option>
                                                <option value="summer">Summer</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-500">Historical uploads are saved for forecasting and won’t change current scheduling data.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Upload Teachers -->
                                <div class="upload-zone border-2 border-dashed border-slate-300 rounded-xl p-6 hover:border-indigo-400 hover:bg-indigo-50/50 transition-all cursor-pointer group" id="teacherUpload">
                                    <input type="file" class="hidden" accept=".csv,.xlsx,.xls" id="teacherFileInput">
                                    <div class="flex flex-col items-center gap-3 text-center">
                                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                                            <i class="fas fa-user-group text-indigo-600 text-lg"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Teachers Profile</p>
                                            <p class="text-xs text-slate-500 mt-1">Name, Expertise Areas, Availability</p>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-400">
                                            <i class="fas fa-cloud-arrow-up"></i>
                                            <span>Drop CSV/Excel or click</span>
                                        </div>
                                    </div>
                                    <div class="hidden mt-4 p-3 bg-green-50 rounded-lg" id="teacherFileInfo">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-file-excel text-green-600"></i>
                                            <span class="text-sm text-green-700 font-medium" id="teacherFileName"></span>
                                            <button class="ml-auto text-slate-400 hover:text-red-500" onclick="removeFile('teacher')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Upload Subjects -->
                                <div class="upload-zone border-2 border-dashed border-slate-300 rounded-xl p-6 hover:border-blue-400 hover:bg-blue-50/50 transition-all cursor-pointer group" id="subjectUpload">
                                    <input type="file" class="hidden" accept=".csv,.xlsx,.xls" id="subjectFileInput">
                                    <div class="flex flex-col items-center gap-3 text-center">
                                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                            <i class="fas fa-book-open text-blue-600 text-lg"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Subject Catalog</p>
                                            <p class="text-xs text-slate-500 mt-1">Code, Title, Units, Prerequisites</p>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-400">
                                            <i class="fas fa-cloud-arrow-up"></i>
                                            <span>Drop CSV/Excel or click</span>
                                        </div>
                                    </div>
                                    <div class="hidden mt-4 p-3 bg-green-50 rounded-lg" id="subjectFileInfo">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-file-excel text-green-600"></i>
                                            <span class="text-sm text-green-700 font-medium" id="subjectFileName"></span>
                                            <button class="ml-auto text-slate-400 hover:text-red-500" onclick="removeFile('subject')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Upload Schedule/Rooms -->
                                <div class="upload-zone border-2 border-dashed border-slate-300 rounded-xl p-6 hover:border-emerald-400 hover:bg-emerald-50/50 transition-all cursor-pointer group" id="scheduleUpload">
                                    <input type="file" class="hidden" accept=".csv,.xlsx,.xls" id="scheduleFileInput">
                                    <div class="flex flex-col items-center gap-3 text-center">
                                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                                            <i class="fas fa-calendar-days text-emerald-600 text-lg"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Schedule Slots</p>
                                            <p class="text-xs text-slate-500 mt-1">Day, Time, Room, Section</p>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-400">
                                            <i class="fas fa-cloud-arrow-up"></i>
                                            <span>Drop CSV/Excel or click</span>
                                        </div>
                                    </div>
                                    <div class="hidden mt-4 p-3 bg-green-50 rounded-lg" id="scheduleFileInfo">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-file-excel text-green-600"></i>
                                            <span class="text-sm text-green-700 font-medium" id="scheduleFileName"></span>
                                            <button class="ml-auto text-slate-400 hover:text-red-500" onclick="removeFile('schedule')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Upload Status Summary -->
                            <div class="mt-4 p-4 bg-slate-50 rounded-lg flex items-center justify-between">
                                <div class="flex items-center gap-6">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-slate-300" id="teacherStatus"></span>
                                        <span class="text-sm text-slate-600">Teachers</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-slate-300" id="subjectStatus"></span>
                                        <span class="text-sm text-slate-600">Subjects</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-slate-300" id="scheduleStatus"></span>
                                        <span class="text-sm text-slate-600">Schedules</span>
                                    </div>
                                </div>
                                <span class="text-sm text-slate-500" id="uploadSummary">0 of 3 files uploaded</span>
                            </div>
                        </div>
                    </div>

                    <!-- GENERATE SCHEDULE -->
                    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-xl shadow-lg overflow-hidden">
                        <div class="p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-wand-magic-sparkles text-white text-xl sm:text-2xl"></i>
                                </div>
                                <div class="text-white">
                                    <h3 class="text-lg sm:text-xl font-semibold">Step 2: Generate Smart Schedule</h3>
                                    <p class="text-indigo-100 text-sm mt-0.5">AI-powered matching: Expertise first, then availability</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 w-full sm:w-auto">
                                <div class="hidden items-center gap-3 text-white" id="generatingIndicator">
                                    <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    <span class="text-sm">Generating schedule...</span>
                                </div>
                                <button id="generateBtn" onclick="generateSchedule()" class="w-full sm:w-auto px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition-all transform hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                                    <i class="fas fa-bolt"></i>
                                    Generate Schedule
                                </button>
                            </div>
                        </div>
                        <div class="px-4 sm:px-6 py-3 bg-indigo-700/50 flex flex-wrap items-center gap-4 sm:gap-6 text-sm text-indigo-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-scale-balanced"></i>
                                <span>Priority: <strong class="text-white">Expertise (<?= (int)$policyExpertiseWeight ?>%) → Availability (<?= (int)$policyAvailWeight ?>%)</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-weight-scale"></i>
                                <span>Max Load: <strong class="text-white"><?= (int)$policyMaxLoad ?> units</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-shield-check"></i>
                                <span>Conflict Detection: <strong class="text-white">Enabled</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- LOAD ASSIGNMENT REPORT TABLE -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-lg font-semibold text-slate-900">Load Assignment Report</h3>
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium"><?php echo (int)$totalTeachers; ?> Teachers • <?php echo (int)$totalSubjects; ?> Subjects</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <select id="statusFilter" class="pl-3 pr-8 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white">
                                            <option value="all">All Status</option>
                                            <option value="optimal">Optimal</option>
                                            <option value="overload">Overload</option>
                                            <option value="manual">Manual Override</option>
                                            <option value="unassigned">Unassigned</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
                                    </div>
                                    <div class="flex items-center border border-slate-300 rounded-lg overflow-hidden">
                                        <button id="btnDashboardExportCsv" type="button" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition-colors text-sm font-medium border-r border-slate-300">
                                            <i class="fas fa-file-csv text-green-600"></i> CSV
                                        </button>
                                        <button id="btnDashboardExportPdf" type="button" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition-colors text-sm font-medium">
                                            <i class="fas fa-file-pdf text-red-600"></i> PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-3">
                                <span class="text-xs text-slate-500">Quick filters:</span>
                                <button type="button" class="dashboard-quick-filter px-3 py-1 text-xs bg-slate-100 text-slate-700 rounded-full hover:bg-slate-200 transition-colors" data-status="all">All</button>
                                <button type="button" class="dashboard-quick-filter px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full hover:bg-green-200 transition-colors" data-status="optimal">Optimal</button>
                                <button type="button" class="dashboard-quick-filter px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full hover:bg-red-200 transition-colors" data-status="overload">Overload</button>
                                <button type="button" class="dashboard-quick-filter px-3 py-1 text-xs bg-purple-100 text-purple-700 rounded-full hover:bg-purple-200 transition-colors" data-status="manual">Manual Override</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[900px]">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200">
                                        <th class="px-6 py-3 text-left"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></th>
                                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">
                                            <div class="flex items-center gap-1 cursor-pointer hover:text-slate-900">Teacher <i class="fas fa-sort text-slate-400 text-xs"></i></div>
                                        </th>
                                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Expertise</th>
                                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Assigned Subjects</th>
                                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Schedule</th>
                                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">
                                            <div class="flex items-center gap-1 cursor-pointer hover:text-slate-900">Total Units <i class="fas fa-sort text-slate-400 text-xs"></i></div>
                                        </th>
                                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Assignment Rationale</th>
                                        <th class="px-6 py-3 text-left text-slate-600 font-semibold">Status</th>
                                        <th class="px-6 py-3 text-center text-slate-600 font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboardReportTbody">
                                    <?php
                                    $avatarGradients = [
                                        'from-blue-400 to-indigo-500',
                                        'from-pink-400 to-rose-500',
                                        'from-emerald-400 to-teal-500',
                                        'from-amber-400 to-orange-500',
                                        'from-violet-400 to-purple-500',
                                        'from-cyan-400 to-sky-500',
                                        'from-fuchsia-400 to-pink-500',
                                        'from-lime-400 to-green-500',
                                    ];
                                    $totalReportRows = count($teacherAssignments) + count($unassignedSubjects);
                                    if ($totalReportRows === 0):
                                    ?>
                                    <tr>
                                        <td colspan="9" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center gap-4">
                                                <div class="w-20 h-20 bg-gradient-to-br from-indigo-100 to-blue-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-wand-magic-sparkles text-indigo-400 text-3xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-lg font-semibold text-slate-700">No load assignments yet</p>
                                                    <p class="text-sm text-slate-400 max-w-md mt-1">Follow the steps above to get started: upload your CSV files, then click "Generate Schedule" to auto-assign teachers.</p>
                                                </div>
                                                <div class="flex items-center gap-4 sm:gap-6 mt-2 text-xs text-slate-400">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-[10px]">1</span>
                                                        <span>Upload Files</span>
                                                    </div>
                                                    <i class="fas fa-arrow-right text-slate-300"></i>
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-[10px]">2</span>
                                                        <span>Generate</span>
                                                    </div>
                                                    <i class="fas fa-arrow-right text-slate-300"></i>
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-bold text-[10px]">3</span>
                                                        <span>Review</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>

                                    <?php $taIdx = 0; foreach ($teacherAssignments as $ta):
                                        $initials = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), explode(' ', trim($ta['teacher_name']))));
                                        $isOverload = $ta['current_units'] > $ta['max_units'];
                                        $isAtMax    = $ta['current_units'] === $ta['max_units'];
                                        $hasManual  = false;
                                        foreach ($ta['subjects'] as $s) {
                                            if ($s['assignment_status'] === 'Manual') { $hasManual = true; break; }
                                        }
                                        if ($hasManual) {
                                            $statusLabel       = 'Manual';
                                            $statusClass       = 'bg-purple-100 text-purple-800';
                                            $statusIcon        = 'fa-user-pen';
                                            $rowBg             = 'bg-purple-50/30';
                                            $rationaleIcon     = 'fas fa-user-pen text-purple-500';
                                            $rationaleTitleCls = 'font-medium text-purple-600';
                                            $rationaleTitleTxt = 'Manual Override';
                                        } elseif ($isOverload) {
                                            $statusLabel       = 'Overload';
                                            $statusClass       = 'bg-red-100 text-red-800';
                                            $statusIcon        = 'fa-triangle-exclamation';
                                            $rowBg             = 'bg-red-50/30';
                                            $rationaleIcon     = 'fas fa-brain text-indigo-500';
                                            $rationaleTitleCls = 'font-medium text-indigo-600';
                                            $rationaleTitleTxt = 'Expertise Match';
                                        } else {
                                            $statusLabel       = 'Optimal';
                                            $statusClass       = 'bg-green-100 text-green-800';
                                            $statusIcon        = 'fa-circle-check';
                                            $rowBg             = '';
                                            $rationaleIcon     = 'fas fa-brain text-indigo-500';
                                            $rationaleTitleCls = 'font-medium text-indigo-600';
                                            $rationaleTitleTxt = 'Expertise Match';
                                        }
                                        $pct      = $ta['max_units'] > 0 ? min(100, round(($ta['current_units'] / $ta['max_units']) * 100)) : 0;
                                        $barColor = $isOverload ? 'bg-red-500' : ($isAtMax ? 'bg-amber-500' : 'bg-green-500');
                                        $unitsCls  = $isOverload ? 'text-red-600' : 'text-slate-900';
                                        $gradient  = $avatarGradients[$taIdx % count($avatarGradients)];
                                        $taIdx++;
                                        $firstSubject   = $ta['subjects'][0];
                                        $rationaleBody  = $firstSubject['rationale'] ?? '';
                                        if (empty($rationaleBody)) {
                                            if ($hasManual) {
                                                $rationaleBody = 'Manually assigned by Program Chair';
                                                if (!empty($firstSubject['assigned_at'])) {
                                                    $rationaleBody .= ' on ' . date('m/d/Y', strtotime($firstSubject['assigned_at']));
                                                }
                                            } elseif ($isOverload) {
                                                $rationaleBody = 'Exceeds ' . $ta['max_units'] . ' unit policy threshold';
                                            } else {
                                                $rationaleBody = 'Assigned based on expertise and availability';
                                            }
                                        }
                                    ?>
                                    <?php
                                        $rowStatus = ($hasManual ? 'manual' : ($isOverload ? 'overload' : 'optimal'));
                                    ?>
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors <?= $rowBg ?>" data-status="<?= htmlspecialchars($rowStatus) ?>" data-teacher-id="<?= (int)$ta['teacher_id'] ?>">
                                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-gradient-to-br <?= htmlspecialchars($gradient) ?> rounded-full flex items-center justify-center text-white font-medium text-sm"><?= htmlspecialchars($initials) ?></div>
                                                <div>
                                                    <p class="font-medium text-slate-900"><?= htmlspecialchars($ta['teacher_name']) ?></p>
                                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($ta['teacher_type']) ?> Faculty</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <?php if (!empty($ta['expertise_tags'])):
                                                    foreach (array_slice(explode(',', $ta['expertise_tags']), 0, 3) as $tag): ?>
                                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs"><?= htmlspecialchars(trim($tag)) ?></span>
                                                <?php endforeach; else: ?>
                                                    <span class="text-slate-400 text-xs">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <?php foreach ($ta['subjects'] as $si => $s): ?>
                                                <p class="<?= $si === 0 ? 'text-slate-900 font-medium' : 'text-slate-600 text-xs' ?>"><?= htmlspecialchars($s['course_code'] . ' - ' . $s['subject_name']) ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="space-y-1 text-xs">
                                                <?php foreach ($ta['subjects'] as $s):
                                                    $schedLines = !empty($schedBySubject[$s['subject_id']]) ? formatSubjectSchedules($schedBySubject[$s['subject_id']]) : [];
                                                    foreach ($schedLines as $line): ?>
                                                    <p><?= htmlspecialchars($line) ?></p>
                                                    <?php endforeach;
                                                    if (empty($schedLines)): ?>
                                                    <p class="text-slate-400">—</p>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold <?= $unitsCls ?>"><?= $ta['current_units'] ?></span>
                                                <div class="w-16 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="<?= $barColor ?> h-full rounded-full" style="width:<?= $pct ?>%"></div>
                                                </div>
                                                <?php if ($isOverload): ?><i class="fas fa-triangle-exclamation text-red-500 text-xs"></i><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start gap-2">
                                                <i class="<?= $rationaleIcon ?> mt-0.5 text-xs"></i>
                                                <span class="text-xs text-slate-600 leading-relaxed">
                                                    <span class="<?= $rationaleTitleCls ?>"><?= htmlspecialchars($rationaleTitleTxt) ?></span><br>
                                                    <?php if ($isOverload): ?>
                                                    <span class="text-red-600 font-medium"><?= htmlspecialchars($rationaleBody) ?></span>
                                                    <?php else: ?>
                                                    <?= htmlspecialchars($rationaleBody) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                                <i class="fas <?= $statusIcon ?> text-[10px]"></i> <?= $statusLabel ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" class="dashboard-action-view p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View Details" data-teacher-id="<?= (int)$ta['teacher_id'] ?>" data-teacher-name="<?= htmlspecialchars($ta['teacher_name'], ENT_QUOTES) ?>"><i class="fas fa-eye text-sm"></i></button>
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" onclick="openModal(<?= $firstSubject['assignment_id'] ?>)" title="Manual Override"><i class="fas fa-pen-to-square text-sm"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php foreach ($unassignedSubjects as $us):
                                        $uSchedLines = !empty($unassignedSchedBySubject[(int)$us['id']]) ? formatSubjectSchedules($unassignedSchedBySubject[(int)$us['id']]) : [];
                                    ?>
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors bg-amber-50/30" data-status="unassigned" data-subject-id="<?= (int)$us['id'] ?>">
                                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-slate-200 rounded-full flex items-center justify-center text-slate-400"><i class="fas fa-question text-sm"></i></div>
                                                <div>
                                                    <p class="font-medium text-slate-400 italic">Unassigned</p>
                                                    <p class="text-xs text-slate-400">Needs teacher</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><span class="text-slate-400 text-xs">—</span></td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-slate-900 font-medium"><?= htmlspecialchars($us['course_code'] . ' - ' . $us['name']) ?></p>
                                                <?php if (!empty($us['prerequisites'])): ?>
                                                <p class="text-xs text-amber-600">Prerequisite: <?= htmlspecialchars($us['prerequisites']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="space-y-1 text-xs">
                                                <?php foreach ($uSchedLines as $line): ?><p><?= htmlspecialchars($line) ?></p><?php endforeach; ?>
                                                <?php if (empty($uSchedLines)): ?><p class="text-slate-400">—</p><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-slate-400"><?= (int)$us['units'] ?></span>
                                                <span class="text-xs text-slate-400">units</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start gap-2">
                                                <i class="fas fa-circle-exclamation text-amber-500 mt-0.5 text-xs"></i>
                                                <span class="text-xs text-amber-600 leading-relaxed">
                                                    <span class="font-medium">No matching teacher found</span><br>
                                                    No faculty with matching expertise available for this subject
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                <i class="fas fa-clock text-[10px]"></i> Unassigned
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" class="dashboard-action-view-unassigned p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View Details" data-subject-id="<?= (int)$us['id'] ?>" data-course-code="<?= htmlspecialchars($us['course_code'], ENT_QUOTES) ?>"><i class="fas fa-eye text-sm"></i></button>
                                                <button class="p-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded transition-colors" onclick="openModal()" title="Assign Teacher"><i class="fas fa-user-plus text-sm"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div id="dashboardPaginationBar" class="px-4 sm:px-6 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-4">
                                <span id="dashboardShowing" class="text-sm text-slate-600">Showing <?php echo count($teacherAssignments) + count($unassignedSubjects); ?> entries</span>
                                <select id="dashboardPageSize" class="text-sm border border-slate-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option>10 per page</option>
                                    <option>25 per page</option>
                                    <option>50 per page</option>
                                    <option>100 per page</option>
                                </select>
                            </div>
                            <div id="dashboardPagination" class="flex items-center gap-1">
                                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors disabled:opacity-50" disabled><i class="fas fa-chevron-left text-xs"></i></button>
                                <button class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg">1</button>
                                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">2</button>
                                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">3</button>
                                <span class="px-2 text-slate-400">...</span>
                                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors">5</button>
                                <button class="px-3 py-1 text-sm border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT ACTIVITY (AUDIT TRAIL) -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-clock-rotate-left text-slate-400"></i>
                                <h3 class="text-lg font-semibold text-slate-900">Recent Activity</h3>
                                <?php if (!empty($recentLogs)): ?>
                                <span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full"><?= count($recentLogs) ?> latest</span>
                                <?php endif; ?>
                            </div>
                            <button onclick="switchPage('audittrail')" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">View All <i class="fas fa-arrow-right text-xs ml-1"></i></button>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <?php if (empty($recentLogs)): ?>
                            <div class="px-6 py-10 text-center">
                                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-clock text-slate-400 text-lg"></i>
                                </div>
                                <p class="text-sm text-slate-500">No activity recorded yet</p>
                                <p class="text-xs text-slate-400 mt-1">Upload files and generate a schedule to see activity here</p>
                            </div>
                            <?php else: ?>
                            <?php foreach (array_slice($recentLogs, 0, 5) as $log):
                                $actionType = $log['action_type'] ?? '';
                                $logDesc    = $log['description'] ?? '';
                                $logUser    = $log['user'] ?? 'System';
                                $logTime    = isset($log['created_at']) ? date('M j, g:i A', strtotime($log['created_at'])) : '';

                                // Determine icon and color based on action type
                                switch ($actionType) {
                                    case 'Schedule Generation':
                                        $iconBg    = 'bg-green-100';
                                        $iconClass = 'fas fa-bolt text-green-600';
                                        break;
                                    case 'Manual Override':
                                        $iconBg    = 'bg-purple-100';
                                        $iconClass = 'fas fa-user-pen text-purple-600';
                                        break;
                                    case 'File Upload':
                                        $iconBg    = 'bg-blue-100';
                                        $iconClass = 'fas fa-file-arrow-up text-blue-600';
                                        break;
                                    case 'Overload Warning':
                                        $iconBg    = 'bg-amber-100';
                                        $iconClass = 'fas fa-triangle-exclamation text-amber-600';
                                        break;
                                    case 'Analytics Update':
                                        $iconBg    = 'bg-cyan-100';
                                        $iconClass = 'fas fa-chart-line text-cyan-600';
                                        break;
                                    case 'Settings Changed':
                                        $iconBg    = 'bg-slate-100';
                                        $iconClass = 'fas fa-sliders text-slate-600';
                                        break;
                                    default:
                                        $iconBg    = 'bg-slate-100';
                                        $iconClass = 'fas fa-circle-info text-slate-500';
                                        break;
                                }
                            ?>
                            <div class="px-6 py-3.5 flex items-start gap-4 hover:bg-slate-50 transition-colors">
                                <div class="w-8 h-8 <?= $iconBg ?> rounded-full flex items-center justify-center flex-shrink-0"><i class="<?= $iconClass ?> text-xs"></i></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-900 truncate"><span class="font-medium"><?= htmlspecialchars($actionType) ?></span> — <?= htmlspecialchars(mb_strimwidth($logDesc, 0, 100, '...')) ?></p>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($logTime) ?> • By: <?= htmlspecialchars($logUser) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- END DASHBOARD PAGE -->

                <!-- ========== TEACHERS PAGE ========== -->
                <?php include __DIR__ . '/includes/pages/teachers.php'; ?>

                <!-- ========== SUBJECTS PAGE ========== -->
                <?php include __DIR__ . '/includes/pages/subjects.php'; ?>

                <!-- ========== SCHEDULES PAGE ========== -->
                <?php include __DIR__ . '/includes/pages/schedules.php'; ?>

                <!-- ========== LOAD REPORTS PAGE ========== -->
                <?php include __DIR__ . '/includes/pages/loadreports.php'; ?>

                <!-- ========== AUDIT TRAIL PAGE ========== -->
                <?php include __DIR__ . '/includes/pages/audittrail.php'; ?>

            </div>
        </main>
    </div>

    <!-- MODAL: MANUAL OVERRIDE -->
    <div id="overrideModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Manual Override</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Reassign subject to a different teacher</p>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="px-6 py-6 space-y-5">
                <div class="p-4 bg-slate-50 rounded-lg">
                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Current Assignment</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-medium">JD</div>
                        <div>
                            <p class="font-medium text-slate-900">John Doe</p>
                            <p class="text-sm text-slate-500">CS101 - Web Development</p>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Reassign To</label>
                    <select class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">-- Select Teacher --</option>
                        <?php
                        $teacherStmt = $pdo->query("SELECT id, name, current_units, max_units, expertise_tags FROM teachers ORDER BY name ASC");
                        while ($t = $teacherStmt->fetch(PDO::FETCH_ASSOC)):
                            $overloaded = $t['current_units'] >= $t['max_units'] ? ' ⚠️ OVERLOADED' : '';
                            $expertise = $t['expertise_tags'] ? htmlspecialchars($t['expertise_tags'], ENT_QUOTES, 'UTF-8') : 'N/A';
                            $label = htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8')
                                   . ' (' . (int)$t['current_units'] . '/' . (int)$t['max_units'] . ' units)'
                                   . ' - Expertise: ' . $expertise
                                   . $overloaded;
                        ?>
                        <option value="<?= (int)$t['id'] ?>"><?= $label ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Reason for Override <span class="text-red-500">*</span></label>
                    <textarea placeholder="Enter the reason for this manual reassignment (required for audit trail)..." rows="3" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"></textarea>
                    <p class="text-xs text-slate-500 mt-1">This will be logged in the audit trail for compliance purposes.</p>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700">Notify affected teachers via email</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked>
                        <span class="text-sm text-slate-700">Log this override in audit trail</span>
                    </label>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex gap-3 justify-end bg-slate-50 rounded-b-xl">
                <button onclick="closeModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Cancel</button>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center gap-2"><i class="fas fa-check"></i> Save Override</button>
            </div>
        </div>
    </div>

    <!-- MODAL: DASHBOARD TEACHER LOAD DETAILS -->
    <div id="dashboardTeacherLoadModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Teacher Load Details</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Assigned subjects for this teacher</p>
                </div>
                <button type="button" id="btnDashboardTeacherLoadClose" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-400">Teacher</p>
                        <p id="dashTeacherName" class="text-sm font-semibold text-slate-900">—</p>
                        <p id="dashTeacherEmail" class="text-xs text-slate-500">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Load</p>
                        <p class="text-sm text-slate-900"><span id="dashTeacherUnits" class="font-semibold">—</span></p>
                        <p id="dashTeacherType" class="text-xs text-slate-500">—</p>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <div class="max-h-72 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-4 py-2 text-left text-slate-600 font-semibold">Code</th>
                                    <th class="px-4 py-2 text-left text-slate-600 font-semibold">Subject</th>
                                    <th class="px-4 py-2 text-left text-slate-600 font-semibold">Units</th>
                                    <th class="px-4 py-2 text-left text-slate-600 font-semibold">Schedule</th>
                                    <th class="px-4 py-2 text-left text-slate-600 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody id="dashTeacherSubjectsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex gap-3 justify-end bg-slate-50 rounded-b-xl">
                <button type="button" id="btnDashboardTeacherLoadCancel" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Close</button>
                <button type="button" id="btnDashboardTeacherSendPdf" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center gap-2"><i class="fas fa-paper-plane"></i> Send PDF to Email</button>
            </div>
        </div>
    </div>

    <!-- MODAL: POLICY SETTINGS -->
    <div id="settingsModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Policy Settings</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Configure load assignment rules</p>
                </div>
                <button onclick="closeSettingsModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="px-6 py-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Maximum Teaching Load (Units)</label>
                    <input id="policyMaxLoad" type="number" min="1" value="18" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-slate-500 mt-1">Teachers exceeding this will be flagged as "Overload"</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-3">Matching Priority Weights</label>
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1"><span class="text-sm text-slate-600">Expertise Match</span><span id="policyExpertiseWeightLabel" class="text-sm font-medium text-indigo-600">70%</span></div>
                            <input id="policyExpertiseWeight" type="range" min="0" max="100" value="70" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1"><span class="text-sm text-slate-600">Availability Match</span><span id="policyAvailabilityWeightLabel" class="text-sm font-medium text-blue-600">30%</span></div>
                            <input id="policyAvailabilityWeight" type="range" min="0" max="100" value="30" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-3">Conflict Detection</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer"><input id="policyDetectScheduleOverlaps" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked><span class="text-sm text-slate-700">Detect schedule overlaps</span></label>
                        <label class="flex items-center gap-3 cursor-pointer"><input id="policyFlagOverloadTeachers" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked><span class="text-sm text-slate-700">Flag overload teachers</span></label>
                        <label class="flex items-center gap-3 cursor-pointer"><input id="policyCheckPrerequisites" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked><span class="text-sm text-slate-700">Check prerequisite requirements</span></label>
                    </div>
                </div>
                <p id="policySettingsStatus" class="hidden text-sm"></p>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex gap-3 justify-end bg-slate-50 rounded-b-xl">
                <button onclick="closeSettingsModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Cancel</button>
                <button onclick="savePolicySettings()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center gap-2"><i class="fas fa-check"></i> Save Settings</button>
            </div>
        </div>
    </div>

    <!-- MODAL: GENERATION HISTORY -->
    <div id="historyModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Generation History</h2>
                    <p class="text-sm text-slate-500 mt-0.5">View and restore previous schedules</p>
                </div>
                <button onclick="closeHistoryModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
            </div>
            <div class="px-6 py-4 max-h-96 overflow-y-auto">
                <div class="space-y-3">
                    <div class="p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"><i class="fas fa-check text-green-600"></i></div>
                                <div>
                                    <p class="font-medium text-slate-900">Schedule v1.3 <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full ml-2">Current</span></p>
                                    <p class="text-xs text-slate-500">Today at 2:45 PM • 156 subjects • 0 conflicts</p>
                                </div>
                            </div>
                            <button class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors">View</button>
                        </div>
                    </div>
                    <div class="p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center"><i class="fas fa-clock-rotate-left text-slate-400"></i></div>
                                <div>
                                    <p class="font-medium text-slate-900">Schedule v1.2</p>
                                    <p class="text-xs text-slate-500">Yesterday at 4:30 PM • 152 subjects • 2 conflicts</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors">View</button>
                                <button class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors">Restore</button>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center"><i class="fas fa-clock-rotate-left text-slate-400"></i></div>
                                <div>
                                    <p class="font-medium text-slate-900">Schedule v1.1</p>
                                    <p class="text-xs text-slate-500">Mar 14, 2026 at 10:15 AM • 148 subjects • 5 conflicts</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors">View</button>
                                <button class="px-3 py-1.5 text-xs bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-colors">Restore</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex gap-3 justify-end bg-slate-50 rounded-b-xl">
                <button onclick="closeHistoryModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL: UPLOAD CONFLICTS (RESOLUTION) -->
    <div id="uploadConflictModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Upload Conflicts Detected</h2>
                    <p class="text-sm text-slate-500 mt-0.5" id="uploadConflictSubtitle">Some rows already exist in the database.</p>
                </div>
                <button onclick="closeUploadConflictModal()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
            </div>

            <div class="px-6 py-4">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-slate-700" id="uploadConflictSummary">0 conflicts</div>
                    <div class="flex items-center gap-2">
                        <button id="uploadConflictKeepBtn" onclick="resolveConflictKeep()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Keep Existing</button>
                        <button id="uploadConflictUpdateBtn" onclick="resolveConflictUpdate()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center gap-2">
                            <i class="fas fa-rotate"></i> Update Existing
                        </button>
                    </div>
                </div>

                <div class="mt-4 border border-slate-200 rounded-lg overflow-hidden">
                    <div class="max-h-96 overflow-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-4 py-3 text-left text-slate-600 font-semibold">Key</th>
                                    <th class="px-4 py-3 text-left text-slate-600 font-semibold">Existing (DB)</th>
                                    <th class="px-4 py-3 text-left text-slate-600 font-semibold">Incoming (File)</th>
                                </tr>
                            </thead>
                            <tbody id="uploadConflictTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-3">Tip: Choose “Update Existing” to apply the uploaded values for duplicate keys.</p>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 flex gap-3 justify-end bg-slate-50 rounded-b-xl">
                <button onclick="closeUploadConflictModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL: UPLOAD COMPLETE WITH CONFLICTS (DUPLICATES SKIPPED) -->
    <div id="conflictModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                <i class="fas fa-triangle-exclamation text-amber-500"></i>
                <h2 class="text-lg font-bold text-slate-900">Upload Complete with Conflicts</h2>
            </div>

            <div class="px-6 py-4">
                <p id="conflictSummary" class="text-sm text-slate-700"></p>

                <ul id="conflictList" class="mt-4 max-h-64 overflow-y-auto bg-amber-50 border border-amber-200 rounded-lg px-4 py-3"></ul>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 flex justify-end bg-slate-50 rounded-b-xl">
                <button onclick="closeConflictModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">Understood</button>
            </div>
        </div>
    </div>

    <!-- MODAL: API KEY MANAGEMENT -->
    <div id="apiKeyModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-key text-indigo-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Manage API Key</h2>
                    <p class="text-xs text-slate-500">Google Gemini API for AI-powered features</p>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                <div>
                    <label for="apiKeyInput" class="block text-sm font-medium text-slate-700 mb-1.5">API Key</label>
                    <div class="relative">
                        <input type="password" id="apiKeyInput" placeholder="AIza..." class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm" autocomplete="off">
                        <button onclick="toggleApiKeyVisibility()" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                            <i id="apiKeyEyeIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Get your key from <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Google AI Studio</a></p>
                </div>
                <div id="apiKeyMsg" class="hidden px-3 py-2 rounded-lg text-sm"></div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 flex gap-3 justify-between bg-slate-50 rounded-b-xl">
                <button onclick="removeApiKey()" class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors text-sm font-medium">
                    <i class="fas fa-trash-can mr-1"></i>Remove Key
                </button>
                <div class="flex gap-2">
                    <button onclick="closeApiKeyModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Cancel</button>
                    <button onclick="saveApiKey()" id="saveApiKeyBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                        <i class="fas fa-check mr-1"></i>Save Key
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- LOADING OVERLAY (visible by default via inline style; hidden after page load) -->
    <div id="loadingOverlay" style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:9999;background:linear-gradient(135deg,#0f172a,#1e293b);" class="fixed inset-0 bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center z-[9999] backdrop-blur-sm">
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.5rem;" class="flex flex-col items-center justify-center gap-6">
            <!-- Logo Container with Spin Animation -->
            <div style="position:relative;width:6rem;height:6rem;display:flex;align-items:center;justify-content:center;" class="relative w-24 h-24 flex items-center justify-center">
                <!-- Outer rotating ring -->
                <div style="position:absolute;inset:0;border-radius:9999px;border:4px solid transparent;border-top-color:#6366f1;border-right-color:#818cf8;animation:spin 1s linear infinite;" class="absolute inset-0 rounded-full border-4 border-transparent border-t-indigo-500 border-r-indigo-400 animate-spin"></div>
                
                <!-- Inner logo -->
                <div style="position:relative;z-index:10;background:linear-gradient(135deg,#6366f1,#4f46e5);padding:1rem;border-radius:0.5rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);" class="relative z-10 bg-gradient-to-br from-indigo-500 to-indigo-600 p-4 rounded-lg shadow-2xl">
                    <i class="fas fa-bolt" style="font-size:2.25rem;color:white;"></i>
                </div>
            </div>

            <!-- Loading Text -->
            <div style="text-align:center;" class="text-center">
                <h2 style="font-size:1.5rem;font-weight:700;color:white;margin-bottom:0.5rem;font-family:'Inter',sans-serif;" class="text-2xl font-bold text-white mb-2">SmartLoad</h2>
                <p id="loadingText" style="color:#cbd5e1;font-size:0.875rem;font-family:'Inter',sans-serif;" class="text-slate-300 text-sm">Loading...</p>
                
                <!-- Animated dots -->
                <div style="display:flex;align-items:center;justify-content:center;gap:0.25rem;margin-top:0.75rem;" class="flex items-center justify-center gap-1 mt-3">
                    <div style="width:0.5rem;height:0.5rem;background:#818cf8;border-radius:9999px;animation:bounce 1s infinite;animation-delay:0s;" class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></div>
                    <div style="width:0.5rem;height:0.5rem;background:#818cf8;border-radius:9999px;animation:bounce 1s infinite;animation-delay:0.15s;" class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></div>
                    <div style="width:0.5rem;height:0.5rem;background:#818cf8;border-radius:9999px;animation:bounce 1s infinite;animation-delay:0.3s;" class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></div>
                </div>
            </div>
        </div>
    </div>
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes bounce { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    </style>

    <!-- Tailwind (script) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Application JavaScript -->
    <script src="js/app.js"></script>
    
    <!-- Loading Overlay Control Script -->
    <script>
        // Show loading overlay
        function showLoadingOverlay(text = 'Processing...') {
            const overlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');
            if (overlay) {
                loadingText.textContent = text;
                overlay.style.display = '';
                overlay.classList.remove('hidden');
            }
        }

        // Hide loading overlay
        function hideLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
                overlay.classList.add('hidden');
            }
        }

        // Hide loading overlay and show app when page fully loads
        window.addEventListener('load', function() {
            var app = document.getElementById('appWrapper');
            if (app) app.style.display = '';
            hideLoadingOverlay();
        });
    </script>
</body>
</html>
