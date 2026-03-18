<?php
// admin/reports.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();

// Handle CSV Download
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=bookings_report_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Booking ID', 'Employee Name', 'Event Type', 'Start Time', 'End Time', 'Status', 'Booked On'));
    
    $stmt = $pdo->query("
        SELECT b.id, u.name, me.title, b.start_time, b.end_time, b.status, b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN master_events me ON b.event_id = me.id
        ORDER BY b.start_time DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Total Bookings
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Bookings by hour (8 AM to 6 PM mapping approximately)
$hours_data = $pdo->query("SELECT HOUR(start_time) as h, COUNT(*) as c FROM bookings WHERE status != 'cancelled' GROUP BY h")->fetchAll(PDO::FETCH_KEY_PAIR);
$max_h = !empty($hours_data) ? max($hours_data) : 1;
function getHourHeight($h, $hours_data, $max_h) {
    $val = $hours_data[$h] ?? 0;
    $pct = ($val / $max_h) * 100;
    return max(5, $pct); // at least 5%
}

// Event Types DB
$event_counts = $pdo->query("
    SELECT me.title, me.color_code, COUNT(b.id) as c 
    FROM master_events me 
    LEFT JOIN bookings b ON b.event_id=me.id AND b.status != 'cancelled' 
    GROUP BY me.id 
    ORDER BY c DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

// Recent Bookings
$recent_bookings = $pdo->query("
    SELECT b.*, u.name, me.title as event_title, me.color_code 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN master_events me ON b.event_id = me.id 
    ORDER BY b.start_time DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Reports &amp; Analytics - The Nexus</title>
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
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="calendar.php">
                <span class="material-symbols-outlined">event</span>
                <span>Calendar</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="bookings.php">
                <span class="material-symbols-outlined">calendar_month</span>
                <span>Bookings</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary font-medium" href="reports.php">
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

    <div class="flex-1 flex flex-col overflow-y-auto">
        <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-10 py-3 sticky top-0 z-10">
            <h2 class="text-xl font-bold">Reports & Analytics</h2>
            <div class="flex items-center gap-6">
                <!-- Top Nav right side -->
                <div class="flex gap-2">
                    <button class="flex items-center justify-center rounded-lg h-10 w-10 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 justify-center py-8">
            <div class="layout-content-container flex flex-col max-w-[1200px] mx-auto px-10">
                <div class="flex flex-wrap justify-between items-end gap-3 pb-8">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-slate-900 dark:text-white text-3xl font-black leading-tight tracking-tight">Reports & Insights</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base font-normal">Analyze resource utilization, booking efficiency, and team patterns across the organization.</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="?download=csv" class="flex items-center justify-center gap-2 rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold hover:bg-primary/90 transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">download</span>
                            <span>Download CSV</span>
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-6 py-2">
                    <!-- Bar Chart Card -->
                    <div class="flex min-w-[400px] flex-1 flex-col gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                        <div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Most Used Time Slots</p>
                            <div class="flex items-baseline gap-2 pt-1">
                                <h3 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight"><?= $total_bookings ?></h3>
                            </div>
                            <p class="text-slate-400 text-xs">Total Bookings across all times</p>
                        </div>
                        <div class="grid min-h-[200px] grid-flow-col gap-4 items-end justify-items-center px-2 pt-4">
                            <?php foreach([8=>'8AM', 10=>'10AM', 12=>'12PM', 14=>'2PM', 16=>'4PM', 18=>'6PM'] as $h => $label): ?>
                            <div class="flex flex-col items-center gap-2 w-full">
                                <div class="bg-primary/10 dark:bg-primary/20 w-full rounded-t-lg relative group transition-all" style="height: 140px;">
                                    <div class="absolute bottom-0 w-full bg-primary rounded-t-lg transition-all duration-1000" style="height: <?= getHourHeight($h, $hours_data, $max_h) ?>%;"></div>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-xs font-bold"><?= $label ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Event Distribution Card -->
                    <div class="flex min-w-[400px] flex-1 flex-col gap-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                        <div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Booking Frequency by Event Type</p>
                            <div class="flex items-baseline gap-2 pt-1">
                                <h3 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight"><?= !empty($event_counts) ? htmlspecialchars($event_counts[0]['title']) : 'N/A' ?></h3>
                                <span class="text-primary text-sm font-bold">Top Category</span>
                            </div>
                        </div>
                        <div class="flex flex-1 items-center justify-center py-4 relative">
                            <!-- Visual Ring placeholder -->
                            <div class="relative w-44 h-44 rounded-full border-[16px] border-slate-100 dark:border-slate-800 flex items-center justify-center">
                                <div class="absolute inset-0 rounded-full border-[16px] border-transparent border-t-primary border-r-primary rotate-45"></div>
                                <div class="absolute inset-0 rounded-full border-[16px] border-transparent border-l-teal-400 -rotate-12"></div>
                                <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-700">pie_chart</span>
                            </div>
                            
                            <div class="ml-10 flex flex-col gap-3">
                                <?php 
                                    $valid_bookings_count = array_sum(array_column($event_counts, 'c'));
                                    foreach($event_counts as $ev): 
                                        $pct = $valid_bookings_count > 0 ? round(($ev['c'] / $valid_bookings_count)*100) : 0;
                                ?>
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full" style="background-color: <?= htmlspecialchars($ev['color_code']) ?>;"></div>
                                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400"><?= htmlspecialchars($ev['title']) ?> (<?= $pct ?>%)</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-4">
                    <div class="flex items-center justify-between px-2">
                        <h2 class="text-slate-900 dark:text-white text-xl font-bold leading-tight tracking-tight">Recent Usage Report</h2>
                    </div>
                    
                    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">User / Team</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Event / Resource</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Duration</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php foreach($recent_bookings as $rb): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                            <?= date('M d, Y', strtotime($rb['start_time'])) ?><br>
                                            <span class="text-xs"><?= date('h:i A', strtotime($rb['start_time'])) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center font-bold">
                                                    <span class="material-symbols-outlined text-sm">person</span>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($rb['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="size-2 rounded-full" style="background-color: <?= htmlspecialchars($rb['color_code']) ?>"></div>
                                                <span class="text-sm text-slate-600 dark:text-slate-300 font-medium"><?= htmlspecialchars($rb['event_title']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-500">
                                            <?= round((strtotime($rb['end_time']) - strtotime($rb['start_time']))/3600, 1) ?> hrs
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if($rb['status'] === 'approved'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                                    <span class="size-1.5 rounded-full bg-emerald-500"></span> Approved
                                                </span>
                                            <?php elseif($rb['status'] === 'cancelled'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                                    <span class="size-1.5 rounded-full bg-red-500"></span> Cancelled
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                                                    <span class="size-1.5 rounded-full bg-amber-500"></span> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($recent_bookings)): ?>
                                        <tr><td colspan="5" class="px-6 py-4 text-center text-slate-500">No recent bookings.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
