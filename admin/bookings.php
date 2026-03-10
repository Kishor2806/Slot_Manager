<?php
// admin/bookings.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();

// Fetch all bookings
$stmt = $pdo->query("
    SELECT b.id, b.start_time, b.end_time, b.status, b.description, b.created_at,
           u.name as user_name, me.title as event_title, me.color_code
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN master_events me ON b.event_id = me.id
    ORDER BY b.start_time DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manage Bookings - The Nexus Admin</title>
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
                <span class="material-symbols-outlined">verified_user</span>
                <span>Whitelist</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="calendar.php">
                <span class="material-symbols-outlined">event</span>
                <span>Calendar</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary font-medium" href="bookings.php">
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

    <div class="flex-1 flex flex-col overflow-y-auto relative">
        <header class="h-16 flex items-center justify-between px-8 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10">
            <h2 class="text-xl font-bold">All Bookings</h2>
            <div class="flex items-center gap-6">
                <div class="flex gap-2">
                    <button class="flex items-center justify-center rounded-lg h-10 w-10 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                </div>
            </div>
        </header>

        <div class="p-8">
            <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
                <div class="flex flex-col gap-1">
                    <h1 class="text-slate-900 dark:text-slate-100 text-3xl font-black tracking-tight">Manage Bookings</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-base">View, approve, shift, or cancel corporate resource reservations.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="bookingsTable">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr class="border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">ID</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Event / Resource</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Time Slot</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php foreach($bookings as $b): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4 text-sm text-slate-500 font-medium whitespace-nowrap">#<?= $b['id'] ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="size-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center font-bold">
                                                <span class="material-symbols-outlined text-sm">person</span>
                                            </div>
                                            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($b['user_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="size-2 rounded-full" style="background-color: <?= htmlspecialchars($b['color_code'] ?? '#ccc') ?>;"></div>
                                            <span class="text-sm text-slate-900 dark:text-slate-100 font-medium"><?= htmlspecialchars($b['event_title']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        <div class="font-medium text-slate-900 dark:text-slate-100"><?= date('M d, Y', strtotime($b['start_time'])) ?></div>
                                        <div class="text-xs"><?= date('h:i A', strtotime($b['start_time'])) ?> - <?= date('h:i A', strtotime($b['end_time'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($b['status']==='approved'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                <span class="size-1.5 rounded-full bg-emerald-500 mr-2"></span> Approved
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
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <?php if($b['status'] === 'pending'): ?>
                                                <button onclick="updateStatus(<?= $b['id'] ?>, 'approved')" class="p-1.5 rounded bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 transition-colors" title="Approve">
                                                    <span class="material-symbols-outlined text-lg">check</span>
                                                </button>
                                            <?php endif; ?>
                                            <?php if($b['status'] !== 'cancelled'): ?>
                                                <button onclick="updateStatus(<?= $b['id'] ?>, 'cancelled')" class="p-1.5 rounded bg-red-50 dark:bg-red-900/20 text-red-600 hover:bg-red-100 transition-colors" title="Cancel">
                                                    <span class="material-symbols-outlined text-lg">close</span>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="openShiftModal(<?= $b['id'] ?>, '<?= $b['start_time'] ?>', '<?= $b['end_time'] ?>')" class="p-1.5 rounded bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors" title="Shift Time">
                                                <span class="material-symbols-outlined text-lg">schedule</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($bookings)): ?>
                                <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">No bookings found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Shift Modal (Tailwind) -->
<div id="shiftModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">Shift Booking Time</h3>
            <button onclick="closeShiftModal()" class="text-slate-400 hover:text-red-500 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="shift_booking_id">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">New Start Time</label>
                <input type="datetime-local" id="shift_start" class="form-input rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary focus:ring-primary h-10 px-3 w-full">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">New End Time</label>
                <input type="datetime-local" id="shift_end" class="form-input rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary focus:ring-primary h-10 px-3 w-full">
            </div>
        </div>
        <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3 bg-slate-50 dark:bg-slate-800/50">
            <button onclick="closeShiftModal()" class="px-5 h-10 rounded-lg text-slate-600 dark:text-slate-400 font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Cancel</button>
            <button onclick="submitShift()" class="px-5 h-10 rounded-lg bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors shadow-sm">Confirm Shift</button>
        </div>
    </div>
</div>

<script>
    function updateStatus(id, action) {
        if(!confirm(`Are you sure you want to mark this booking as ${action}?`)) return;
        
        const fd = new FormData();
        fd.append('booking_id', id);
        fd.append('action', action); // 'approved' or 'cancelled'

        fetch('../api/admin_update_booking.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) location.reload();
            else alert(res.error || 'Failed');
        });
    }

    const shiftModal = document.getElementById('shiftModal');

    function openShiftModal(id, start, end) {
        document.getElementById('shift_booking_id').value = id;
        document.getElementById('shift_start').value = start.slice(0,16); // format for input
        document.getElementById('shift_end').value = end.slice(0,16);
        shiftModal.classList.remove('hidden');
        shiftModal.classList.add('flex');
    }

    function closeShiftModal() {
        shiftModal.classList.add('hidden');
        shiftModal.classList.remove('flex');
    }

    function submitShift() {
        const id = document.getElementById('shift_booking_id').value;
        const start = document.getElementById('shift_start').value;
        const end = document.getElementById('shift_end').value;

        const fd = new FormData();
        fd.append('booking_id', id);
        fd.append('action', 'shift');
        fd.append('start_time', start);
        fd.append('end_time', end);

        fetch('../api/admin_update_booking.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) location.reload();
            else alert(res.error || 'Failed to shift');
        });
    }
</script>
</body>
</html>
