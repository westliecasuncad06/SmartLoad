<?php
require_once __DIR__ . '/includes/db.php';

// Fetch dashboard stats from database
try {
    $totalTeachers    = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $totalSubjects    = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $totalUnits       = $pdo->query("SELECT COALESCE(SUM(units), 0) FROM subjects")->fetchColumn();
    $assignedSubjects = $pdo->query("SELECT COUNT(DISTINCT subject_id) FROM assignments")->fetchColumn();
    $overloadCount    = $pdo->query("SELECT COUNT(*) FROM teachers WHERE current_units > max_units")->fetchColumn();
    $recentLogs       = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();
} catch (PDOException $e) {
    // Use fallback values when DB is not yet set up
    $totalTeachers    = 0;
    $totalSubjects    = 0;
    $totalUnits       = 0;
    $assignedSubjects = 0;
    $overloadCount    = 0;
    $recentLogs       = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartLoad - Intelligent Faculty Scheduling</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome via CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-slate-50">
    <div class="flex h-screen">
        <!-- SIDEBAR -->
        <aside class="fixed left-0 top-0 h-screen w-64 bg-slate-900 text-white shadow-xl flex flex-col z-20">
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
        <main class="ml-64 flex-1 flex flex-col">
            <!-- TOP NAVBAR -->
            <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-10">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-slate-500">
                            <span class="text-slate-400">SmartLoad</span>
                            <i class="fas fa-chevron-right text-xs mx-2"></i>
                            <span id="breadcrumbTitle" class="text-slate-700 font-medium">Dashboard</span>
                        </div>
                    </div>

                    <div class="flex-1 max-w-lg mx-8">
                        <div class="relative">
                            <input type="text" id="globalSearch" placeholder="Search teachers, subjects, schedules..." class="w-full px-4 py-2 pl-10 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                            <kbd class="absolute right-3 top-2 px-2 py-0.5 text-xs bg-slate-100 text-slate-500 rounded">Ctrl+K</kbd>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <button onclick="openSettingsModal()" class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors" title="Settings">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="relative p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                            <i class="fas fa-bell"></i>
                            <span class="absolute top-1 right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center text-[10px]">3</span>
                        </button>
                        <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                PC
                            </div>
                            <div class="hidden lg:block">
                                <p class="text-sm font-medium text-slate-900">Program Chair</p>
                                <p class="text-xs text-slate-500">College of Engineering</p>
                            </div>
                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <div class="flex-1 overflow-auto">

                <!-- ========== DASHBOARD PAGE ========== -->
                <div id="page-dashboard" class="page-content p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">Load Assignment Dashboard</h2>
                            <p class="text-slate-600 mt-1">Automatically assign teachers to subjects based on expertise and availability</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-slate-500">Last generated: <span class="font-medium text-slate-700">Today, 2:45 PM</span></span>
                            <button onclick="openHistoryModal()" class="flex items-center gap-2 px-3 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">
                                <i class="fas fa-clock-rotate-left"></i>
                                History
                            </button>
                        </div>
                    </div>

                    <!-- QUICK STATS -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="bg-indigo-100 p-2.5 rounded-lg"><i class="fas fa-users text-indigo-600"></i></div>
                                <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-0.5 rounded-full">+3 new</span>
                            </div>
                            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo (int)$totalTeachers; ?></p>
                            <p class="text-slate-500 text-sm">Total Teachers</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="bg-blue-100 p-2.5 rounded-lg"><i class="fas fa-book-open text-blue-600"></i></div>
                                <span class="text-xs text-slate-500 font-medium"><?php echo (int)$totalUnits; ?> units</span>
                            </div>
                            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo (int)$totalSubjects; ?></p>
                            <p class="text-slate-500 text-sm">Total Subjects</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="bg-green-100 p-2.5 rounded-lg"><i class="fas fa-circle-check text-green-600"></i></div>
                                <span class="text-xs text-green-600 font-medium"><?php echo $totalSubjects > 0 ? round(($assignedSubjects / $totalSubjects) * 100) : 0; ?>%</span>
                            </div>
                            <p class="text-2xl font-bold text-slate-900 mt-3"><?php echo (int)$assignedSubjects; ?></p>
                            <p class="text-slate-500 text-sm">Subjects Assigned</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="bg-amber-100 p-2.5 rounded-lg"><i class="fas fa-triangle-exclamation text-amber-600"></i></div>
                                <span class="text-xs text-amber-600 font-medium">Needs review</span>
                            </div>
                            <p class="text-2xl font-bold text-amber-600 mt-3"><?php echo (int)$overloadCount; ?></p>
                            <p class="text-slate-500 text-sm">Overload Flags</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="bg-emerald-100 p-2.5 rounded-lg"><i class="fas fa-bolt text-emerald-600"></i></div>
                                <span class="text-xs text-emerald-600 font-medium">Target: &lt;5min</span>
                            </div>
                            <p class="text-2xl font-bold text-emerald-600 mt-3">2.3s</p>
                            <p class="text-slate-500 text-sm">Generation Time</p>
                        </div>
                    </div>

                    <!-- UPLOAD FILES -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Step 1: Upload Input Files</h3>
                                    <p class="text-sm text-slate-500 mt-0.5">Upload Excel/CSV files containing teacher profiles, subject catalog, and schedules</p>
                                </div>
                                <button class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                                    <i class="fas fa-download text-xs"></i>
                                    Download Templates
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
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
                        <div class="p-6 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-wand-magic-sparkles text-white text-2xl"></i>
                                </div>
                                <div class="text-white">
                                    <h3 class="text-xl font-semibold">Step 2: Generate Smart Schedule</h3>
                                    <p class="text-indigo-100 text-sm mt-0.5">AI-powered matching: Expertise first, then availability</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="hidden items-center gap-3 text-white" id="generatingIndicator">
                                    <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    <span class="text-sm">Generating schedule...</span>
                                </div>
                                <button id="generateBtn" onclick="generateSchedule()" class="px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition-all transform hover:scale-105 shadow-lg flex items-center gap-2">
                                    <i class="fas fa-bolt"></i>
                                    Generate Schedule
                                </button>
                            </div>
                        </div>
                        <div class="px-6 py-3 bg-indigo-700/50 flex items-center gap-6 text-sm text-indigo-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-scale-balanced"></i>
                                <span>Priority: <strong class="text-white">Expertise (70%) → Availability (30%)</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-weight-scale"></i>
                                <span>Max Load: <strong class="text-white">18 units</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-shield-check"></i>
                                <span>Conflict Detection: <strong class="text-white">Enabled</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- LOAD ASSIGNMENT REPORT TABLE -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
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
                                        <button class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition-colors text-sm font-medium border-r border-slate-300">
                                            <i class="fas fa-file-csv text-green-600"></i> CSV
                                        </button>
                                        <button class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition-colors text-sm font-medium">
                                            <i class="fas fa-file-pdf text-red-600"></i> PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-3">
                                <span class="text-xs text-slate-500">Quick filters:</span>
                                <button class="px-3 py-1 text-xs bg-slate-100 text-slate-700 rounded-full hover:bg-slate-200 transition-colors">All</button>
                                <button class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full hover:bg-green-200 transition-colors">Optimal</button>
                                <button class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full hover:bg-red-200 transition-colors">Overload</button>
                                <button class="px-3 py-1 text-xs bg-purple-100 text-purple-700 rounded-full hover:bg-purple-200 transition-colors">Manual Override</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
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
                                <tbody>
                                    <!-- Row 1: Expertise Match -->
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-medium text-sm">JD</div>
                                                <div>
                                                    <p class="font-medium text-slate-900">John Doe</p>
                                                    <p class="text-xs text-slate-500">Full-time Faculty</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">PHP</span>
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">MySQL</span>
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">Web Dev</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-slate-900 font-medium">CS101 - Web Development</p>
                                                <p class="text-slate-600 text-xs">IT202 - Database Systems</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="space-y-1 text-xs">
                                                <p>Mon/Wed 9:00 AM</p>
                                                <p>Tue/Thu 10:30 AM</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-slate-900">15</span>
                                                <div class="w-16 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="w-10/12 h-full bg-green-500 rounded-full"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start gap-2">
                                                <i class="fas fa-brain text-indigo-500 mt-0.5 text-xs"></i>
                                                <span class="text-xs text-slate-600 leading-relaxed">
                                                    <span class="font-medium text-indigo-600">Expertise Match (95%)</span><br>
                                                    5 years PHP experience; Available MWF morning
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-circle-check text-[10px]"></i> Optimal
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View Details"><i class="fas fa-eye text-sm"></i></button>
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" onclick="openModal()" title="Manual Override"><i class="fas fa-pen-to-square text-sm"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Row 2: Overload Flag -->
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors bg-red-50/30">
                                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-gradient-to-br from-pink-400 to-rose-500 rounded-full flex items-center justify-center text-white font-medium text-sm">JS</div>
                                                <div>
                                                    <p class="font-medium text-slate-900">Jane Smith</p>
                                                    <p class="text-xs text-slate-500">Full-time Faculty</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">Networking</span>
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">Security</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-slate-900 font-medium">IT204 - Networking</p>
                                                <p class="text-slate-600 text-xs">IT301 - Cybersecurity</p>
                                                <p class="text-slate-600 text-xs">IT102 - IT Fundamentals</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="space-y-1 text-xs">
                                                <p>Tue/Thu 1:00 PM</p>
                                                <p>Mon/Wed 2:30 PM</p>
                                                <p>Fri 9:00 AM</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-red-600">21</span>
                                                <div class="w-16 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="w-full h-full bg-red-500 rounded-full"></div>
                                                </div>
                                                <i class="fas fa-triangle-exclamation text-red-500 text-xs"></i>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start gap-2">
                                                <i class="fas fa-brain text-indigo-500 mt-0.5 text-xs"></i>
                                                <span class="text-xs text-slate-600 leading-relaxed">
                                                    <span class="font-medium text-indigo-600">Expertise Match (92%)</span><br>
                                                    <span class="text-red-600 font-medium">Exceeds 18 unit policy threshold</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-triangle-exclamation text-[10px]"></i> Overload
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View Details"><i class="fas fa-eye text-sm"></i></button>
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" onclick="openModal()" title="Manual Override"><i class="fas fa-pen-to-square text-sm"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Row 3: Availability Match -->
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-medium text-sm">AT</div>
                                                <div>
                                                    <p class="font-medium text-slate-900">Alan Turing</p>
                                                    <p class="text-xs text-slate-500">Part-time Faculty</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">Mathematics</span>
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">Algorithms</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-slate-900 font-medium">Math101 - Calculus I</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="space-y-1 text-xs"><p>Fri 10:00 AM</p></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-slate-900">12</span>
                                                <div class="w-16 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="w-8/12 h-full bg-green-500 rounded-full"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start gap-2">
                                                <i class="fas fa-clock text-blue-500 mt-0.5 text-xs"></i>
                                                <span class="text-xs text-slate-600 leading-relaxed">
                                                    <span class="font-medium text-blue-600">Availability Match (85%)</span><br>
                                                    Only available Fridays; Strong math background
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-circle-check text-[10px]"></i> Optimal
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View Details"><i class="fas fa-eye text-sm"></i></button>
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" onclick="openModal()" title="Manual Override"><i class="fas fa-pen-to-square text-sm"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Row 4: Manual Override -->
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors bg-purple-50/30">
                                        <td class="px-6 py-4"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-medium text-sm">MG</div>
                                                <div>
                                                    <p class="font-medium text-slate-900">Maria Garcia</p>
                                                    <p class="text-xs text-slate-500">Full-time Faculty</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">Literature</span>
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs">Writing</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-slate-900 font-medium">ENG102 - Literature</p>
                                                <p class="text-slate-600 text-xs">ENG201 - Creative Writing</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="space-y-1 text-xs">
                                                <p>Mon/Wed 2:00 PM</p>
                                                <p>Tue/Thu 3:30 PM</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-slate-900">18</span>
                                                <div class="w-16 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="w-full h-full bg-amber-500 rounded-full"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start gap-2">
                                                <i class="fas fa-user-pen text-purple-500 mt-0.5 text-xs"></i>
                                                <span class="text-xs text-slate-600 leading-relaxed">
                                                    <span class="font-medium text-purple-600">Manual Override</span><br>
                                                    Reassigned by Program Chair on 03/15/2026<br>
                                                    <span class="italic">"Teacher request due to schedule preference"</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <i class="fas fa-user-pen text-[10px]"></i> Manual
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View Details"><i class="fas fa-eye text-sm"></i></button>
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" onclick="openModal()" title="Manual Override"><i class="fas fa-pen-to-square text-sm"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Row 5: Unassigned Subject -->
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors bg-amber-50/30">
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
                                        <td class="px-6 py-4"><span class="text-slate-400 text-xs">-</span></td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-slate-900 font-medium">PHY101 - Physics I</p>
                                                <p class="text-xs text-amber-600">Prerequisite: Math101</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="space-y-1 text-xs"><p>Mon/Wed 8:00 AM</p></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-slate-400">3</span>
                                                <span class="text-xs text-slate-400">units</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-start gap-2">
                                                <i class="fas fa-circle-exclamation text-amber-500 mt-0.5 text-xs"></i>
                                                <span class="text-xs text-amber-600 leading-relaxed">
                                                    <span class="font-medium">No matching teacher found</span><br>
                                                    No faculty with Physics expertise available at this time slot
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
                                                <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="View Details"><i class="fas fa-eye text-sm"></i></button>
                                                <button class="p-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded transition-colors" onclick="openModal()" title="Assign Teacher"><i class="fas fa-user-plus text-sm"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-slate-600">Showing 5 of <?php echo (int)$totalTeachers; ?> entries</span>
                                <select class="text-sm border border-slate-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option>10 per page</option>
                                    <option>25 per page</option>
                                    <option>50 per page</option>
                                    <option>100 per page</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-1">
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
                                <h3 class="text-lg font-semibold text-slate-900">Recent Activity (Audit Trail)</h3>
                            </div>
                            <button onclick="switchPage('audittrail')" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">View All</button>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div class="px-6 py-4 flex items-start gap-4">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-bolt text-green-600 text-xs"></i></div>
                                <div class="flex-1">
                                    <p class="text-sm text-slate-900"><span class="font-medium">Schedule Generated</span> - 156 subjects assigned to 42 teachers</p>
                                    <p class="text-xs text-slate-500 mt-1">Today at 2:45 PM • Generated in 2.3 seconds • 0 conflicts</p>
                                </div>
                            </div>
                            <div class="px-6 py-4 flex items-start gap-4">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-user-pen text-purple-600 text-xs"></i></div>
                                <div class="flex-1">
                                    <p class="text-sm text-slate-900"><span class="font-medium">Manual Override</span> - ENG102 reassigned from John Doe to Maria Garcia</p>
                                    <p class="text-xs text-slate-500 mt-1">Today at 2:50 PM • By: Program Chair • Reason: "Teacher request due to schedule preference"</p>
                                </div>
                            </div>
                            <div class="px-6 py-4 flex items-start gap-4">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-file-arrow-up text-blue-600 text-xs"></i></div>
                                <div class="flex-1">
                                    <p class="text-sm text-slate-900"><span class="font-medium">Files Uploaded</span> - teachers.csv, subjects.csv, schedules.csv</p>
                                    <p class="text-xs text-slate-500 mt-1">Today at 2:40 PM • By: Program Chair</p>
                                </div>
                            </div>
                            <div class="px-6 py-4 flex items-start gap-4">
                                <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-triangle-exclamation text-amber-600 text-xs"></i></div>
                                <div class="flex-1">
                                    <p class="text-sm text-slate-900"><span class="font-medium">Overload Warning</span> - Jane Smith assigned 21 units (exceeds 18 unit policy)</p>
                                    <p class="text-xs text-slate-500 mt-1">Today at 2:45 PM • Auto-flagged by system</p>
                                </div>
                            </div>
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
                        <option value="jane_smith">Jane Smith (15 units) - Expertise: Networking, Security</option>
                        <option value="alan_turing">Alan Turing (12 units) - Expertise: Mathematics, Algorithms</option>
                        <option value="maria_garcia">Maria Garcia (18 units) - Expertise: Literature, Writing</option>
                        <option value="robert_johnson">Robert Johnson (9 units) - Expertise: Physics, Chemistry</option>
                        <option value="sarah_wilson">Sarah Wilson (6 units) - Expertise: Web Dev, PHP, JavaScript</option>
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
                    <input type="number" value="18" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-slate-500 mt-1">Teachers exceeding this will be flagged as "Overload"</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-3">Matching Priority Weights</label>
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1"><span class="text-sm text-slate-600">Expertise Match</span><span class="text-sm font-medium text-indigo-600">70%</span></div>
                            <input type="range" min="0" max="100" value="70" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1"><span class="text-sm text-slate-600">Availability Match</span><span class="text-sm font-medium text-blue-600">30%</span></div>
                            <input type="range" min="0" max="100" value="30" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-3">Conflict Detection</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked><span class="text-sm text-slate-700">Detect schedule overlaps</span></label>
                        <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked><span class="text-sm text-slate-700">Flag overload teachers</span></label>
                        <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked><span class="text-sm text-slate-700">Check prerequisite requirements</span></label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex gap-3 justify-end bg-slate-50 rounded-b-xl">
                <button onclick="closeSettingsModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors font-medium">Cancel</button>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium flex items-center gap-2"><i class="fas fa-check"></i> Save Settings</button>
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

    <!-- Application JavaScript -->
    <script src="js/app.js"></script>
</body>
</html>
