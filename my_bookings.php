<?php
// my_bookings.php
require_once 'config.php';
require_once 'includes/middleware.php';
require_login();

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'Upcoming'; // Upcoming, Past, Cancelled

$where_clause = "b.user_id = :uid";
if (strtolower($filter) === 'upcoming') {
    $where_clause .= " AND b.start_time >= NOW() AND b.status != 'cancelled'";
} elseif (strtolower($filter) === 'past') {
    $where_clause .= " AND b.start_time < NOW() AND b.status != 'cancelled'";
} elseif (strtolower($filter) === 'cancelled') {
    $where_clause .= " AND b.status = 'cancelled'";
}

$stmt = $pdo->prepare("
    SELECT 
        b.id, b.start_time, b.end_time, b.status, b.description,
        me.title as event_title, me.color_code
    FROM bookings b
    JOIN master_events me ON b.event_id = me.id
    WHERE $where_clause
    ORDER BY b.start_time DESC
");
$stmt->execute(['uid' => $user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats for the bottom section
$all_stmt = $pdo->prepare("SELECT status, start_time, end_time FROM bookings WHERE user_id = :uid");
$all_stmt->execute(['uid' => $user_id]);
$all_bookings = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

$this_month_count = 0;
$total_approved = 0;
$total_not_cancelled = 0;
$total_minutes = 0;

foreach ($all_bookings as $booking) {
    if (date('Y-m', strtotime($booking['start_time'])) === date('Y-m')) {
        $this_month_count++;
    }
    if ($booking['status'] !== 'cancelled') {
        $total_not_cancelled++;
        if ($booking['status'] === 'approved') {
            $total_approved++;
        }
        $duration = (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 60;
        $total_minutes += $duration;
    }
}

$confirmation_rate = $total_not_cancelled > 0 ? round(($total_approved / $total_not_cancelled) * 100) : 0;
$total_hours = round($total_minutes / 60);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Bookings | The Nexus</title>
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
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen">
<div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
<div class="layout-container flex h-full grow flex-col">
    <!-- Header -->
    <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-primary/10 bg-white dark:bg-background-dark px-6 md:px-10 py-3 sticky top-0 z-50">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-4 text-primary">
                <div class="size-8 bg-primary/10 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined">hub</span>
                </div>
                <h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight tracking-[-0.015em]">The Nexus</h2>
            </div>
            <nav class="hidden lg:flex items-center gap-9">
                <a class="text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary text-sm font-medium leading-normal transition-colors" href="index.php">Dashboard</a>
                <a class="text-primary text-sm font-semibold leading-normal border-b-2 border-primary pb-1" href="my_bookings.php">My Bookings</a>
            </nav>
        </div>
        <div class="flex flex-1 justify-end gap-4 md:gap-8">
            <label class="hidden md:flex flex-col min-w-40 !h-10 max-w-64">
                <div class="flex w-full flex-1 items-stretch rounded-lg h-full">
                    <div class="text-slate-400 flex border-none bg-slate-100 dark:bg-slate-800 items-center justify-center pl-4 rounded-l-lg" data-icon="search">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </div>
                    <input class="form-input flex w-full min-w-0 flex-1 border-none bg-slate-100 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 h-full placeholder:text-slate-400 px-4 rounded-r-lg text-sm" placeholder="Search resources..." value=""/>
                </div>
            </label>
            <div class="flex gap-2">
                <button class="flex items-center justify-center rounded-lg size-10 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary transition-all">
                    <span class="material-symbols-outlined text-xl">notifications</span>
                </button>
            </div>
            <div class="bg-primary/20 flex flex-col items-center justify-center rounded-full size-10 border border-primary/20">
                <span class="material-symbols-outlined text-slate-500">person</span>
            </div>
            <a href="logout.php" class="flex items-center text-slate-400 hover:text-red-500 transition-colors">
                <span class="material-symbols-outlined">logout</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex flex-1 justify-center py-8">
        <div class="layout-content-container flex flex-col w-full max-w-[1200px] px-4 md:px-10">
            <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
                <div class="flex flex-col gap-2">
                    <p class="text-slate-900 dark:text-slate-100 text-3xl font-extrabold leading-tight tracking-tight">My Bookings</p>
                    <p class="text-slate-500 dark:text-slate-400 text-base font-normal">Track and manage your upcoming and historical resource reservations.</p>
                </div>
                <a href="index.php" class="bg-primary text-white px-5 py-2.5 rounded-lg font-semibold flex items-center gap-2 hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 cursor-pointer">
                    <span class="material-symbols-outlined text-lg">add</span>
                    New Booking
                </a>
            </div>
            <div class="flex mb-6 bg-slate-200/50 dark:bg-slate-800/50 p-1.5 rounded-xl w-full max-w-md">
                <label onclick="window.location='?filter=Upcoming'" class="flex cursor-pointer h-10 grow items-center justify-center overflow-hidden rounded-lg px-2 <?= strtolower($filter)==='upcoming'?'bg-white dark:bg-slate-700 text-primary dark:text-slate-100 shadow-sm':'text-slate-500 dark:text-slate-400' ?> text-sm font-semibold transition-all">
                    <span class="truncate">Upcoming</span>
                </label>
                <label onclick="window.location='?filter=Past'" class="flex cursor-pointer h-10 grow items-center justify-center overflow-hidden rounded-lg px-2 <?= strtolower($filter)==='past'?'bg-white dark:bg-slate-700 text-primary dark:text-slate-100 shadow-sm':'text-slate-500 dark:text-slate-400' ?> text-sm font-semibold transition-all">
                    <span class="truncate">Past</span>
                </label>
                <label onclick="window.location='?filter=Cancelled'" class="flex cursor-pointer h-10 grow items-center justify-center overflow-hidden rounded-lg px-2 <?= strtolower($filter)==='cancelled'?'bg-white dark:bg-slate-700 text-primary dark:text-slate-100 shadow-sm':'text-slate-500 dark:text-slate-400' ?> text-sm font-semibold transition-all">
                    <span class="truncate">Cancelled</span>
                </label>
            </div>
            <div class="grid grid-cols-1 gap-4 @container">
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 text-sm font-semibold tracking-wide">Resource / Event</th>
                                <th class="px-6 py-4 text-sm font-semibold tracking-wide">Date</th>
                                <th class="px-6 py-4 text-sm font-semibold tracking-wide">Time Slot</th>
                                <th class="px-6 py-4 text-sm font-semibold tracking-wide text-center">Status</th>
                                <th class="px-6 py-4 text-sm font-semibold tracking-wide text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if(empty($bookings)): ?>
                                <tr><td colspan="5" class="px-6 py-5 text-center text-slate-500">No bookings found in this category.</td></tr>
                            <?php else: ?>
                                <?php foreach($bookings as $b): ?>
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors <?= $b['status'] === 'cancelled'? 'opacity-60':'' ?>">
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="size-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                                    <div style="width:14px;height:14px;border-radius:50%;background-color:<?= htmlspecialchars($b['color_code'])?>;"></div>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900 dark:text-slate-100 <?= $b['status'] === 'cancelled'? 'line-through':'' ?>"><?= htmlspecialchars($b['event_title']) ?></p>
                                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($b['description']?:'--') ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-slate-600 dark:text-slate-400 font-medium"><?= date('M d, Y', strtotime($b['start_time'])) ?></td>
                                        <td class="px-6 py-5 text-sm text-slate-600 dark:text-slate-400"><?= date('g:i A', strtotime($b['start_time'])) ?> - <?= date('g:i A', strtotime($b['end_time'])) ?></td>
                                        <td class="px-6 py-5 text-center">
                                            <?php if($b['status']==='approved'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                    <span class="size-1.5 rounded-full bg-emerald-500 mr-2"></span> Confirmed
                                                </span>
                                            <?php elseif($b['status']==='pending'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                    <span class="size-1.5 rounded-full bg-amber-500 mr-2"></span> Pending
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                    <span class="size-1.5 rounded-full bg-slate-400 mr-2"></span> Cancelled
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-5 text-right">
                                            <div class="flex justify-end gap-3 text-sm">
                                                <?php if($b['status'] !== 'cancelled' && strtotime($b['start_time']) > time()): ?>
                                                    <button onclick="cancelBooking(<?= $b['id'] ?>)" class="text-rose-500 font-bold hover:underline">Cancel</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stats -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-primary/5 dark:bg-primary/10 p-6 rounded-xl border border-primary/10">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="size-12 rounded-full bg-primary/20 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined">calendar_today</span>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100"><?= $this_month_count ?></p>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bookings this month</p>
                        </div>
                    </div>
                </div>
                <div class="bg-emerald-500/5 dark:bg-emerald-500/10 p-6 rounded-xl border border-emerald-500/10">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="size-12 rounded-full bg-emerald-500/20 text-emerald-600 flex items-center justify-center">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100"><?= $confirmation_rate ?>%</p>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Confirmation Rate</p>
                        </div>
                    </div>
                </div>
                <div class="bg-amber-500/5 dark:bg-amber-500/10 p-6 rounded-xl border border-amber-500/10">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="size-12 rounded-full bg-amber-500/20 text-amber-600 flex items-center justify-center">
                            <span class="material-symbols-outlined">history</span>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100"><?= $total_hours ?>h</p>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Resource Time</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</div>
<script>
function cancelBooking(id) {
    if(!confirm('Cancel this booking?')) return;
    const fd = new FormData();
    fd.append('booking_id', id);
    fetch('api/cancel_own_slot.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.success) location.reload();
        else alert(d.error || 'Failed to cancel');
    });
}
</script>
</body>
</html>
