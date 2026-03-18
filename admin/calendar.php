<?php
// admin/calendar.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Resource Calendar - The Nexus Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101822",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        /* FullCalendar Custom Overrides to match Tailwind theme */
        .fc { font-family: 'Inter', sans-serif; }
        .fc-theme-standard td, .fc-theme-standard th { border-color: #e2e8f0; }
        .fc-toolbar-title { font-weight: 700 !important; color: #0f172a; }
        .fc-button-primary { background-color: #136dec !important; border-color: #136dec !important; font-weight: bold !important; border-radius: 0.5rem !important; }
        .fc-button-primary:hover { background-color: #1059c2 !important; border-color: #1059c2 !important; }
        .fc-col-header-cell-cushion { color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        .fc-daygrid-day-number { color: #0f172a; font-weight: 700; font-size: 0.875rem; }
        .fc-event { border-radius: 4px; border: none; padding: 2px 4px; font-size: 0.7rem; font-weight: bold; }
        .fc-timegrid-slot { height: 40px; }
        
        /* Modals */
        #bookingModal, #eventDetailsModal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 100;
            background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
        }
        .hidden { display: none !important; }
    </style>
    
    <!-- Pass session user id to JS and override API base path -->
    <script>
        window.API_BASE_PATH = '../api/';
        var currentUserId = <?= json_encode($_SESSION['user_id']); ?>;
        var currentUserRole = <?= json_encode($_SESSION['role'] ?? 'admin'); ?>;
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar Navigation -->
    <aside class="w-64 flex flex-col bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 shrink-0">
        <div class="p-6 flex items-center gap-3">
            <div class="size-10 rounded-lg bg-primary flex items-center justify-center text-white">
                <span class="material-symbols-outlined">hub</span>
            </div>
            <div>
                <h1 class="text-lg font-bold leading-none">The Nexus</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Resource Manager</p>
            </div>
        </div>
        <nav class="flex-1 px-4 space-y-1">
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="index.php">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="whitelist.php">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                <span>Manage Admins</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary font-medium" href="calendar.php">
                <span class="material-symbols-outlined">event</span>
                <span>Calendar</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="bookings.php">
                <span class="material-symbols-outlined">calendar_month</span>
                <span>Bookings</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="reports.php">
                <span class="material-symbols-outlined">analytics</span>
                <span>Reports</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="event_types.php">
                <span class="material-symbols-outlined">category</span>
                <span>Event Types</span>
            </a>
        </nav>
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                <div class="size-8 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex items-center justify-center">
                    <span class="material-symbols-outlined text-slate-400">person</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></p>
                    <p class="text-xs text-slate-500 truncate">Administrator</p>
                </div>
                <a href="../logout.php" title="Logout" class="text-slate-400 hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-sm">logout</span>
                </a>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-y-auto bg-slate-50 dark:bg-background-dark/50 relative">
        <header class="h-16 flex items-center justify-between px-8 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10 w-full">
            <h2 class="text-xl font-bold">Resource Calendar</h2>
            <div class="flex items-center gap-6">
                <button onclick="document.getElementById('bookingModal').classList.remove('hidden')" class="flex items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold shadow-sm hover:bg-primary/90 transition-all">
                    <span>Book Slot</span>
                </button>
                <div class="flex gap-2">
                    <button class="flex items-center justify-center rounded-lg h-10 w-10 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                </div>
            </div>
        </header>

        <div class="p-8">
            <div class="max-w-7xl mx-auto flex flex-col gap-6">
                <!-- Filters Bar / Legend -->
                <div class="flex flex-wrap items-center gap-3 pb-2 border-b border-slate-200 dark:border-slate-800 mb-2">
                    <div class="flex flex-wrap gap-3">
                        <span class="text-sm font-bold text-slate-500 mr-2 flex items-center">Legend:</span>
                        <span class="flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-700">
                            <span class="size-2 rounded-full bg-green-500"></span> Approved
                        </span>
                        <span class="flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-700">
                            <span class="size-2 rounded-full bg-amber-400"></span> Pending
                        </span>
                        <span class="flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-700">
                            <span class="size-2 rounded-full bg-slate-500"></span> Cancelled
                        </span>
                    </div>
                </div>

                <!-- Calendar Container -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden p-4">
                    <div id="calendar" class="min-h-[600px]"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tailwind Modal: Booking Slot -->
<div id="bookingModal" class="hidden">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md mx-4 overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-primary/10 flex items-center justify-between">
            <h3 class="text-lg font-bold text-primary" id="bookingModalTitle">Book a Slot</h3>
            <button onclick="document.getElementById('bookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form id="bookingForm" class="space-y-4">
                <input type="hidden" name="booking_id" id="booking_id" value="">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Event Type</label>
                    <select class="w-full rounded-lg border-slate-300 text-sm focus:ring-primary focus:border-primary" name="event_id" id="event_id" required></select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date</label>
                        <input type="date" class="w-full rounded-lg border-slate-300 text-sm focus:ring-primary focus:border-primary" id="start_date" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">End Date</label>
                        <input type="date" class="w-full rounded-lg border-slate-300 text-sm focus:ring-primary focus:border-primary" id="end_date" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Start Time</label>
                        <div class="flex items-center gap-1">
                            <input type="number" min="1" max="12" class="w-14 rounded-lg border-slate-300 text-sm text-center focus:ring-primary focus:border-primary" id="start_hour" placeholder="HH" required>
                            <span class="text-slate-400 font-bold">:</span>
                            <input type="number" min="0" max="59" step="5" class="w-14 rounded-lg border-slate-300 text-sm text-center focus:ring-primary focus:border-primary" id="start_minute" placeholder="MM" required>
                            <select class="rounded-lg border-slate-300 text-sm font-bold focus:ring-primary focus:border-primary px-2" id="start_ampm">
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">End Time</label>
                        <div class="flex items-center gap-1">
                            <input type="number" min="1" max="12" class="w-14 rounded-lg border-slate-300 text-sm text-center focus:ring-primary focus:border-primary" id="end_hour" placeholder="HH" required>
                            <span class="text-slate-400 font-bold">:</span>
                            <input type="number" min="0" max="59" step="5" class="w-14 rounded-lg border-slate-300 text-sm text-center focus:ring-primary focus:border-primary" id="end_minute" placeholder="MM" required>
                            <select class="rounded-lg border-slate-300 text-sm font-bold focus:ring-primary focus:border-primary px-2" id="end_ampm">
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Hidden fields to hold combined datetime-local values for form submission -->
                <input type="hidden" name="start_time" id="start_time">
                <input type="hidden" name="end_time" id="end_time">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Description (Optional)</label>
                    <textarea class="w-full rounded-lg border-slate-300 text-sm focus:ring-primary focus:border-primary" name="description" id="description" rows="2"></textarea>
                </div>
                <div id="bookingAlertMsg" class="hidden text-sm font-bold p-3 rounded-lg bg-red-100 text-red-700"></div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('bookingModal').classList.add('hidden')" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancel</button>
                    <button type="button" id="submitBookingBtn" class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-sm">Submit Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tailwind Modal: Event Details -->
<div id="eventDetailsModal" class="hidden">
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-sm mx-4 overflow-hidden transform transition-all">
        <div id="detailHeaderColor" class="px-6 py-4 flex items-center justify-between" style="background-color: #136dec;">
            <h3 class="text-white font-bold text-lg">Slot Details</h3>
            <button onclick="document.getElementById('eventDetailsModal').classList.add('hidden')" class="text-white/80 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <div><span class="font-bold text-sm text-slate-500">Event:</span> <span id="detailTitle" class="text-sm font-medium block"></span></div>
            <div><span class="font-bold text-sm text-slate-500">By:</span> <span id="detailUser" class="text-sm font-medium block"></span></div>
            <div><span class="font-bold text-sm text-slate-500">Time:</span> <span id="detailTime" class="text-sm font-medium block"></span></div>
            <div><span class="font-bold text-sm text-slate-500">Status:</span> <span id="detailStatus" class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-bold"></span></div>
            <hr class="border-slate-100">
            <div id="detailDesc" class="text-sm text-slate-600 dark:text-slate-400"></div>
            
            <div id="detailActions" class="pt-4 mt-2 border-t border-slate-100 flex justify-end gap-2" style="display: none;">
                <button id="editOwnEventBtn" class="px-3 py-1.5 border border-primary text-primary hover:bg-primary/10 text-xs font-bold rounded-lg transition-colors">Edit Slot</button>
                <button id="cancelOwnEventBtn" class="px-3 py-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold rounded-lg transition-colors">Cancel Slot</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
