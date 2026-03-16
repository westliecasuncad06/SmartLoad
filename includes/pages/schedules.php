<?php
// Fetch schedule data
$scheduleStmt = $pdo->query("
    SELECT s.day_of_week, s.start_time, s.room, sub.course_code, sub.name, t.name as teacher_name
    FROM schedules s
    JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN assignments a ON sub.id = a.subject_id
    LEFT JOIN teachers t ON a.teacher_id = t.id
");
$scheduleRows = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Group by day and time
$grid = [];
foreach ($scheduleRows as $row) {
    $timeKey = date('g:i A', strtotime($row['start_time']));
    $grid[$row['day_of_week']][$timeKey][] = $row;
}

// Color palette for class cards
$cardColors = ['indigo', 'pink', 'emerald', 'amber', 'blue', 'violet', 'rose', 'teal'];
$colorMap = [];
$colorIdx = 0;
foreach ($scheduleRows as $row) {
    if (!isset($colorMap[$row['course_code']])) {
        $colorMap[$row['course_code']] = $cardColors[$colorIdx % count($cardColors)];
        $colorIdx++;
    }
}

// Unique teachers and rooms for filters
$filterTeachers = array_unique(array_filter(array_column($scheduleRows, 'teacher_name')));
$filterRooms = array_unique(array_filter(array_column($scheduleRows, 'room')));
sort($filterTeachers);
sort($filterRooms);

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$timeSlots = ['7:00 AM', '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM'];
?>
<!-- SCHEDULES PAGE -->
<div id="page-schedules" class="page-content hidden p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 schedule-no-print">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Schedule Management</h2>
            <p class="text-slate-600 mt-1 text-sm">View and manage class schedules by day, time, and room</p>
        </div>
        <div class="flex items-center gap-3">
            <select id="scheduleSemesterSelect" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="1st Semester 2026-2027">1st Semester 2026-2027</option>
                <option value="2nd Semester 2025-2026">2nd Semester 2025-2026</option>
            </select>
            <button id="schedulePrintBtn" type="button" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"><i class="fas fa-print"></i> Print Schedule</button>
        </div>
    </div>

    <!-- Print-only header -->
    <div id="schedulePrintHeader" class="schedule-print-only">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Class Schedule</h2>
                <p class="text-sm text-slate-600" id="schedulePrintSemester"></p>
            </div>
            <div class="text-sm text-slate-600 text-right" id="schedulePrintFilters"></div>
        </div>
        <hr class="border-slate-200 mt-3" />
    </div>

    <!-- View Toggle & Filter -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 schedule-no-print">
        <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
            <button id="scheduleViewWeekly" type="button" data-view="weekly" class="px-4 py-2 bg-white text-slate-900 rounded-md shadow-sm text-sm font-medium">Weekly View</button>
            <button id="scheduleViewDaily" type="button" data-view="daily" class="px-4 py-2 text-slate-600 hover:text-slate-900 rounded-md text-sm font-medium">Daily View</button>
            <button id="scheduleViewList" type="button" data-view="list" class="px-4 py-2 text-slate-600 hover:text-slate-900 rounded-md text-sm font-medium">List View</button>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
            <input id="scheduleTeacherSearch" type="text" placeholder="Search teacher..." class="w-full sm:w-64 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            <select id="scheduleTeacherFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>All Teachers</option>
                <?php foreach ($filterTeachers as $ft): ?>
                    <option><?= htmlspecialchars($ft) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="scheduleRoomFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>All Rooms</option>
                <?php foreach ($filterRooms as $fr): ?>
                    <option><?= htmlspecialchars($fr) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Weekly Schedule Grid -->
    <div id="scheduleWeeklyView" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[800px]">
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Tuesday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Wednesday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Thursday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Friday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Saturday</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeSlots as $time): ?>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50"><?= $time ?></td>
                        <?php if ($time === '12:00 PM'): ?>
                            <td class="px-2 py-2 text-center text-slate-400 text-xs" colspan="6">Lunch Break</td>
                        <?php else: ?>
                            <?php foreach ($days as $day): ?>
                            <td class="px-2 py-2">
                                <?php if (isset($grid[$day][$time])): ?>
                                    <?php foreach ($grid[$day][$time] as $class): ?>
                                        <?php $c = $colorMap[$class['course_code']]; ?>
                                        <div class="schedule-class-card bg-<?= $c ?>-100 border-l-4 border-<?= $c ?>-500 rounded p-2 cursor-pointer hover:bg-<?= $c ?>-200 transition-colors"
                                            data-teacher="<?= htmlspecialchars((string)($class['teacher_name'] ?? '')) ?>"
                                            data-room="<?= htmlspecialchars((string)($class['room'] ?? '')) ?>"
                                            data-day="<?= htmlspecialchars((string)$day) ?>"
                                            data-time="<?= htmlspecialchars((string)$time) ?>"
                                            data-course="<?= htmlspecialchars((string)($class['course_code'] ?? '')) ?>"
                                            data-subject="<?= htmlspecialchars((string)($class['name'] ?? '')) ?>">
                                            <p class="font-medium text-<?= $c ?>-900 text-xs"><?= htmlspecialchars($class['course_code']) ?></p>
                                            <p class="text-<?= $c ?>-700 text-xs"><?= htmlspecialchars($class['name']) ?></p>
                                            <?php if (!empty($class['teacher_name'])): ?>
                                            <p class="text-<?= $c ?>-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i><?= htmlspecialchars($class['teacher_name']) ?></p>
                                            <?php endif; ?>
                                            <p class="text-<?= $c ?>-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i><?= htmlspecialchars($class['room']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily View -->
    <div id="scheduleDailyView" class="hidden bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 schedule-no-print">
            <div class="text-sm font-semibold text-slate-700">Daily View</div>
            <select id="scheduleDayFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <?php foreach ($days as $day): ?>
                    <option value="<?= htmlspecialchars($day) ?>"><?= htmlspecialchars($day) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="scheduleDailyList" class="p-4 space-y-3"></div>
    </div>

    <!-- List View -->
    <div id="scheduleListView" class="hidden bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left text-slate-600 font-semibold">Day</th>
                        <th class="px-4 py-3 text-left text-slate-600 font-semibold">Time</th>
                        <th class="px-4 py-3 text-left text-slate-600 font-semibold">Course</th>
                        <th class="px-4 py-3 text-left text-slate-600 font-semibold">Subject</th>
                        <th class="px-4 py-3 text-left text-slate-600 font-semibold">Teacher</th>
                        <th class="px-4 py-3 text-left text-slate-600 font-semibold">Room</th>
                    </tr>
                </thead>
                <tbody id="scheduleListBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-sm" id="scheduleLegend">
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-indigo-100 border-l-4 border-indigo-500 rounded"></div><span class="text-slate-600">Computer Science</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-pink-100 border-l-4 border-pink-500 rounded"></div><span class="text-slate-600">IT / Networking</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-emerald-100 border-l-4 border-emerald-500 rounded"></div><span class="text-slate-600">Mathematics</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-amber-100 border-l-4 border-amber-500 rounded"></div><span class="text-slate-600">English / Literature</span></div>
    </div>
</div>
<!-- END SCHEDULES PAGE -->
