<?php
// admin/index.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();

// Fetch Stats
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled'")->fetchColumn();
$today_meetings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved' AND DATE(start_time) = CURDATE()")->fetchColumn();
$pending_approvals = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$active_employees = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Fetch Recent Bookings
$recent = $pdo->query("
    SELECT b.id, b.start_time, b.status, u.name, me.title
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN master_events me ON b.event_id = me.id
    ORDER BY b.created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Overview Dashboard - The Nexus</title>
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
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-size: 24px; vertical-align: middle; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display">
<div class="flex h-screen overflow-hidden">
<!-- Sidebar Navigation -->
<aside class="w-64 flex flex-col bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800">
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
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary font-medium" href="index.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="whitelist.php">
            <span class="material-symbols-outlined">admin_panel_settings</span>
            <span>Manage Admins</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="calendar.php">
            <span class="material-symbols-outlined">event</span>
            <span>Calendar</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="bookings.php">
            <span class="material-symbols-outlined">calendar_month</span>
            <span>Bookings</span>
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

<!-- Main Content -->
<main class="flex-1 flex flex-col overflow-y-auto">
    <!-- Header -->
    <header class="h-16 flex items-center justify-between px-8 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10">
        <div class="flex items-center gap-4">
            <h2 class="text-xl font-bold">Admin Overview</h2>
            <div class="px-2 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold tracking-wider uppercase">Live</div>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <button class="size-10 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 relative">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Dashboard Content -->
    <div class="p-8 space-y-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined">book_online</span>
                    </div>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Bookings</p>
                <h3 class="text-3xl font-bold mt-1"><?= $total_bookings ?></h3>
            </div>
            <div class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-12 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">groups</span>
                    </div>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Today's Meetings</p>
                <h3 class="text-3xl font-bold mt-1"><?= $today_meetings ?></h3>
            </div>
            <div class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-12 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Pending Approvals</p>
                <h3 class="text-3xl font-bold mt-1"><?= $pending_approvals ?></h3>
            </div>
            <div class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="size-12 rounded-lg bg-info/10 text-cyan-600 bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                        <span class="material-symbols-outlined">person_check</span>
                    </div>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Employees</p>
                <h3 class="text-3xl font-bold mt-1"><?= $active_employees ?></h3>
            </div>
        </div>

        <!-- Main Activity Section -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold">Recent Booking Requests</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Review and manage incoming resource slot requests</p>
                </div>
                <a href="bookings.php" class="text-primary text-sm font-semibold hover:underline">View All Requests</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Resource / Event</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach($recent as $r): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center text-slate-500">
                                            <span class="material-symbols-outlined text-sm">person</span>
                                        </div>
                                        <div class="text-sm">
                                            <p class="font-semibold"><?= htmlspecialchars($r['name']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="font-medium"><?= htmlspecialchars($r['title']) ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600 dark:text-slate-400">
                                        <p><?= date('M d, Y', strtotime($r['start_time'])) ?></p>
                                        <p class="text-xs"><?= date('h:i A', strtotime($r['start_time'])) ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($r['status'] == 'approved'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Approved
                                        </span>
                                    <?php elseif ($r['status'] == 'pending'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                            Pending
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400">
                                            <?= ucfirst(htmlspecialchars($r['status'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-slate-500">No recent bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</div>
</body>
</html>
