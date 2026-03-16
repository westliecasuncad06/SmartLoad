<!-- SCHEDULES PAGE -->
<div id="page-schedules" class="page-content hidden p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Schedule Management</h2>
            <p class="text-slate-600 mt-1">View and manage class schedules by day, time, and room</p>
        </div>
        <div class="flex items-center gap-3">
            <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>1st Semester 2026-2027</option>
                <option>2nd Semester 2025-2026</option>
            </select>
            <button class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"><i class="fas fa-print"></i> Print Schedule</button>
        </div>
    </div>

    <!-- View Toggle & Filter -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
            <button class="px-4 py-2 bg-white text-slate-900 rounded-md shadow-sm text-sm font-medium">Weekly View</button>
            <button class="px-4 py-2 text-slate-600 hover:text-slate-900 rounded-md text-sm font-medium">Daily View</button>
            <button class="px-4 py-2 text-slate-600 hover:text-slate-900 rounded-md text-sm font-medium">List View</button>
        </div>
        <div class="flex items-center gap-3">
            <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>All Teachers</option>
                <option>John Doe</option>
                <option>Jane Smith</option>
                <option>Alan Turing</option>
            </select>
            <select class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option>All Rooms</option>
                <option>Room 101</option>
                <option>Room 102</option>
                <option>Lab A</option>
            </select>
        </div>
    </div>

    <!-- Weekly Schedule Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-3 text-left text-slate-600 font-semibold w-24">Time</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Monday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Tuesday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Wednesday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Thursday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Friday</th>
                        <th class="px-4 py-3 text-center text-slate-600 font-semibold">Saturday</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">7:00 AM</td>
                        <td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">8:00 AM</td>
                        <td class="px-2 py-2">
                            <div class="bg-indigo-100 border-l-4 border-indigo-500 rounded p-2 cursor-pointer hover:bg-indigo-200 transition-colors">
                                <p class="font-medium text-indigo-900 text-xs">CS101</p>
                                <p class="text-indigo-700 text-xs">Web Development</p>
                                <p class="text-indigo-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Doe</p>
                                <p class="text-indigo-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 101</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-indigo-100 border-l-4 border-indigo-500 rounded p-2 cursor-pointer hover:bg-indigo-200 transition-colors">
                                <p class="font-medium text-indigo-900 text-xs">CS101</p>
                                <p class="text-indigo-700 text-xs">Web Development</p>
                                <p class="text-indigo-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Doe</p>
                                <p class="text-indigo-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 101</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-emerald-100 border-l-4 border-emerald-500 rounded p-2 cursor-pointer hover:bg-emerald-200 transition-colors">
                                <p class="font-medium text-emerald-900 text-xs">Math101</p>
                                <p class="text-emerald-700 text-xs">Calculus I</p>
                                <p class="text-emerald-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>A. Turing</p>
                                <p class="text-emerald-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 203</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">9:00 AM</td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-pink-100 border-l-4 border-pink-500 rounded p-2 cursor-pointer hover:bg-pink-200 transition-colors">
                                <p class="font-medium text-pink-900 text-xs">IT204</p>
                                <p class="text-pink-700 text-xs">Networking</p>
                                <p class="text-pink-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Smith</p>
                                <p class="text-pink-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Lab A</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-pink-100 border-l-4 border-pink-500 rounded p-2 cursor-pointer hover:bg-pink-200 transition-colors">
                                <p class="font-medium text-pink-900 text-xs">IT204</p>
                                <p class="text-pink-700 text-xs">Networking</p>
                                <p class="text-pink-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Smith</p>
                                <p class="text-pink-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Lab A</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">10:00 AM</td>
                        <td class="px-2 py-2">
                            <div class="bg-blue-100 border-l-4 border-blue-500 rounded p-2 cursor-pointer hover:bg-blue-200 transition-colors">
                                <p class="font-medium text-blue-900 text-xs">IT202</p>
                                <p class="text-blue-700 text-xs">Database Systems</p>
                                <p class="text-blue-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Doe</p>
                                <p class="text-blue-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Lab B</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-blue-100 border-l-4 border-blue-500 rounded p-2 cursor-pointer hover:bg-blue-200 transition-colors">
                                <p class="font-medium text-blue-900 text-xs">IT202</p>
                                <p class="text-blue-700 text-xs">Database Systems</p>
                                <p class="text-blue-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Doe</p>
                                <p class="text-blue-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Lab B</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-emerald-100 border-l-4 border-emerald-500 rounded p-2 cursor-pointer hover:bg-emerald-200 transition-colors">
                                <p class="font-medium text-emerald-900 text-xs">Math101</p>
                                <p class="text-emerald-700 text-xs">Calculus I (Lab)</p>
                                <p class="text-emerald-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>A. Turing</p>
                                <p class="text-emerald-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 203</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">11:00 AM</td>
                        <td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">12:00 PM</td>
                        <td class="px-2 py-2 text-center text-slate-400 text-xs" colspan="6">Lunch Break</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">1:00 PM</td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-pink-100 border-l-4 border-pink-500 rounded p-2 cursor-pointer hover:bg-pink-200 transition-colors">
                                <p class="font-medium text-pink-900 text-xs">IT301</p>
                                <p class="text-pink-700 text-xs">Cybersecurity</p>
                                <p class="text-pink-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Smith</p>
                                <p class="text-pink-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Lab A</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-pink-100 border-l-4 border-pink-500 rounded p-2 cursor-pointer hover:bg-pink-200 transition-colors">
                                <p class="font-medium text-pink-900 text-xs">IT301</p>
                                <p class="text-pink-700 text-xs">Cybersecurity</p>
                                <p class="text-pink-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>J. Smith</p>
                                <p class="text-pink-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Lab A</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">2:00 PM</td>
                        <td class="px-2 py-2">
                            <div class="bg-amber-100 border-l-4 border-amber-500 rounded p-2 cursor-pointer hover:bg-amber-200 transition-colors">
                                <p class="font-medium text-amber-900 text-xs">ENG102</p>
                                <p class="text-amber-700 text-xs">Literature</p>
                                <p class="text-amber-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>M. Garcia</p>
                                <p class="text-amber-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 305</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-amber-100 border-l-4 border-amber-500 rounded p-2 cursor-pointer hover:bg-amber-200 transition-colors">
                                <p class="font-medium text-amber-900 text-xs">ENG102</p>
                                <p class="text-amber-700 text-xs">Literature</p>
                                <p class="text-amber-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>M. Garcia</p>
                                <p class="text-amber-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 305</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">3:00 PM</td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-amber-100 border-l-4 border-amber-500 rounded p-2 cursor-pointer hover:bg-amber-200 transition-colors">
                                <p class="font-medium text-amber-900 text-xs">ENG201</p>
                                <p class="text-amber-700 text-xs">Creative Writing</p>
                                <p class="text-amber-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>M. Garcia</p>
                                <p class="text-amber-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 305</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2">
                            <div class="bg-amber-100 border-l-4 border-amber-500 rounded p-2 cursor-pointer hover:bg-amber-200 transition-colors">
                                <p class="font-medium text-amber-900 text-xs">ENG201</p>
                                <p class="text-amber-700 text-xs">Creative Writing</p>
                                <p class="text-amber-600 text-xs mt-1"><i class="fas fa-user text-[10px] mr-1"></i>M. Garcia</p>
                                <p class="text-amber-600 text-xs"><i class="fas fa-door-open text-[10px] mr-1"></i>Room 305</p>
                            </div>
                        </td>
                        <td class="px-2 py-2"></td>
                        <td class="px-2 py-2"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3 text-slate-500 font-medium bg-slate-50">4:00 PM</td>
                        <td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td><td class="px-2 py-2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legend -->
    <div class="flex items-center justify-center gap-6 text-sm">
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-indigo-100 border-l-4 border-indigo-500 rounded"></div><span class="text-slate-600">Computer Science</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-pink-100 border-l-4 border-pink-500 rounded"></div><span class="text-slate-600">IT / Networking</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-emerald-100 border-l-4 border-emerald-500 rounded"></div><span class="text-slate-600">Mathematics</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 bg-amber-100 border-l-4 border-amber-500 rounded"></div><span class="text-slate-600">English / Literature</span></div>
    </div>
</div>
<!-- END SCHEDULES PAGE -->
