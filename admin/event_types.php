<?php
// admin/event_types.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title']);
        $duration = (int)$_POST['duration'];
        $color = $_POST['color'];
        
        $stmt = $pdo->prepare("INSERT INTO master_events (title, default_duration, color_code) VALUES (?, ?, ?)");
        $stmt->execute([$title, $duration, $color]);
        $success_msg = "Event Type saved!";
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM master_events WHERE id = ?")->execute([$id]);
        $success_msg = "Event Type deleted!";
    }
}

$events = $pdo->query("SELECT * FROM master_events ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Event Types - The Nexus Admin</title>
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
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="bookings.php">
                <span class="material-symbols-outlined">calendar_month</span>
                <span>Bookings</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="reports.php">
                <span class="material-symbols-outlined">analytics</span>
                <span>Reports</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary font-medium" href="event_types.php">
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
            <h2 class="text-xl font-bold">Event Types</h2>
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
                    <h1 class="text-slate-900 dark:text-slate-100 text-3xl font-black tracking-tight">Master Event Types</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Categories and resources available for corporate booking.</p>
                </div>
                <button onclick="openAddModal()" class="flex items-center justify-center gap-2 rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold hover:bg-primary/90 transition-colors shadow-sm shadow-primary/20">
                    <span class="material-symbols-outlined text-xl">add</span>
                    <span>Create New Event Type</span>
                </button>
            </div>

            <?php if(isset($success_msg)): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800 font-bold shadow-sm"><?= $success_msg ?></div>
            <?php endif; ?>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr class="border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Color Preview</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Title</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Default Duration (mins)</th>
                                <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php foreach($events as $e): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="size-6 rounded-full shadow-sm" style="background-color: <?= htmlspecialchars($e['color_code']) ?>;"></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($e['title']) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400"><?= $e['default_duration'] ?> mins</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this event type? Warning: This may cascade delete bookings depending on DB settings.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                            <button type="submit" class="p-1 rounded bg-red-50 dark:bg-red-900/20 text-red-600 hover:bg-red-100 transition-colors" title="Delete">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($events)): ?>
                                <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">No event types found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Add Modal (Tailwind) -->
<div id="addModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">category</span>
                    Create Event Type
                </h3>
                <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Event Title</label>
                    <input type="text" name="title" required class="form-input rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary focus:ring-primary h-10 px-3 w-full" placeholder="e.g. Conference Room A">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Duration (mins)</label>
                        <input type="number" name="duration" required value="60" class="form-input rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary focus:ring-primary h-10 px-3 w-full">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Theme Color</label>
                        <input type="color" name="color" required value="#136dec" class="h-10 w-full rounded-lg cursor-pointer border-0 p-0 bg-transparent">
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3 bg-slate-50 dark:bg-slate-800/50">
                <button type="button" onclick="closeAddModal()" class="px-5 h-10 rounded-lg text-slate-600 dark:text-slate-400 font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Cancel</button>
                <button type="submit" class="px-5 h-10 rounded-lg bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors shadow-sm">Save Event Type</button>
            </div>
        </form>
    </div>
</div>

<script>
    const addModal = document.getElementById('addModal');

    function openAddModal() {
        addModal.classList.remove('hidden');
        addModal.classList.add('flex');
    }

    function closeAddModal() {
        addModal.classList.add('hidden');
        addModal.classList.remove('flex');
    }
</script>
</body>
</html>
