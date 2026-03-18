<?php
// index.php
require_once 'includes/middleware.php';
require_login();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Employee Calendar Dashboard | The Nexus</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
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
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
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
    <!-- Pass session user id and role to JS -->
    <script>
        var currentUserId = <?= json_encode($_SESSION['user_id'] ?? null); ?>;
        var currentUserRole = <?= json_encode($_SESSION['role'] ?? 'employee'); ?>;
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 antialiased overflow-x-hidden min-h-screen">
<div class="layout-container flex h-full flex-col">
    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark px-6 md:px-10 py-3">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-3">
                <div class="bg-primary text-white p-1.5 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">hub</span>
                </div>
                <h2 class="text-slate-900 dark:text-white text-xl font-bold leading-tight tracking-tight">The Nexus</h2>
            </div>
            <div class="hidden lg:flex items-center">
                <label class="flex items-center min-w-64">
                    <div class="flex w-full items-stretch rounded-lg bg-slate-100 dark:bg-slate-800 h-10 border border-slate-200 dark:border-slate-700">
                        <div class="text-slate-500 flex items-center justify-center pl-3">
                            <span class="material-symbols-outlined text-xl">search</span>
                        </div>
                        <input class="flex w-full border-none bg-transparent text-slate-900 dark:text-white focus:ring-0 text-sm font-normal placeholder:text-slate-500" placeholder="Search resources..." value=""/>
                    </div>
                </label>
            </div>
        </div>
        <div class="flex flex-1 justify-end items-center gap-6 lg:gap-8">
            <nav class="hidden md:flex items-center gap-8">
                <a class="text-primary text-sm font-semibold leading-normal relative after:absolute after:bottom-[-13px] after:left-0 after:h-[2px] after:w-full after:bg-primary" href="index.php">Dashboard</a>
                <a class="text-slate-600 dark:text-slate-400 text-sm font-medium hover:text-primary transition-colors" href="my_bookings.php">My Bookings</a>
            </nav>
            <div class="flex items-center gap-3">
                <button onclick="document.getElementById('bookingModal').classList.remove('hidden')" class="flex items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold shadow-sm hover:bg-primary/90 transition-all">
                    <span class="truncate">Book Slot</span>
                </button>
                <button class="flex items-center justify-center rounded-lg h-10 w-10 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
                <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-800 mx-1"></div>
                <div class="flex items-center gap-3 cursor-pointer group">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 ring-2 ring-primary/20 group-hover:ring-primary/40 transition-all bg-slate-200 flex items-center justify-center">
                        <span class="material-symbols-outlined text-slate-500">person</span>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-xs font-bold leading-none text-slate-900 dark:text-white"><?= htmlspecialchars($_SESSION['name']) ?></p>
                        <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider mt-1">Employee</p>
                    </div>
                    <a href="logout.php" class="ml-2 text-slate-400 hover:text-red-500"><span class="material-symbols-outlined text-sm">logout</span></a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-background-dark border-r border-slate-200 dark:border-slate-800 p-6 gap-8">
            <div class="flex flex-col gap-1">
                <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest px-3">Main Menu</h3>
                <div class="flex flex-col gap-1 mt-3">
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary group" href="index.php">
                        <span class="material-symbols-outlined text-xl fill-1">calendar_today</span>
                        <span class="text-sm font-semibold">Full Calendar</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="my_bookings.php">
                        <span class="material-symbols-outlined text-xl group-hover:text-primary">book_online</span>
                        <span class="text-sm font-medium group-hover:text-primary">My Bookings</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-background-dark/50">
            <div class="max-w-6xl mx-auto p-6 md:p-8 flex flex-col gap-6">
                <!-- Content Header -->
                <div class="flex flex-wrap justify-between items-end gap-4">
                    <div class="flex flex-col gap-1">
                        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                            <span>Dashboard</span>
                            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                            <span class="text-primary">Resource Calendar</span>
                        </nav>
                        <h1 class="text-slate-900 dark:text-white text-3xl font-black leading-tight tracking-tight">Resource Calendar</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base font-normal">Effortlessly manage your workspace and equipment bookings.</p>
                    </div>
                </div>

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
        </main>
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
<script src="assets/js/app.js"></script>
</body>
</html>
