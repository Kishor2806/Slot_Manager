<?php
// admin/whitelist.php
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $entry = trim($_POST['email_or_domain']);
        $type = str_contains($entry, '@') && str_starts_with($entry, '@') ? 'domain' : 'email';
        $status = $_POST['status'] ?? '1';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO whitelist (email_or_domain, type, is_active, added_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$entry, $type, $status, $_SESSION['user_id']]);
            $success_msg = "Added successfully!";
        } catch (PDOException $e) {
            $error_msg = "Error adding. Ensure it is unique.";
        }
    } elseif ($action === 'toggle') {
        $id = $_POST['id'];
        $pdo->prepare("UPDATE whitelist SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        $success_msg = "Status toggled!";
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM whitelist WHERE id = ?")->execute([$id]);
        $success_msg = "Deleted successfully!";
    }
}

$whitelist = $pdo->query("
    SELECT w.*, u.name as added_by_name 
    FROM whitelist w 
    LEFT JOIN users u ON w.added_by = u.id 
    ORDER BY w.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_whitelisted = count($whitelist);
$active_users = count(array_filter($whitelist, fn($w) => $w['is_active'] == 1));
$inactive_users = count(array_filter($whitelist, fn($w) => $w['is_active'] == 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Whitelist Management Panel - The Nexus</title>
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
        .material-symbols-outlined { font-size: 24px; vertical-align: middle; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen">
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
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="index.php">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary font-medium" href="whitelist.php">
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
    <main class="flex-1 overflow-y-auto">
        <header class="h-16 flex items-center justify-between px-8 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold">Whitelist Management</h2>
            </div>
            <div class="flex items-center gap-6">
                <!-- Global Top Nav placeholders if any -->
            </div>
        </header>

        <div class="p-8">
            <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
                <div class="flex flex-col gap-1">
                    <h1 class="text-slate-900 dark:text-slate-100 text-3xl font-black tracking-tight">Whitelist Management</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Authorized users and domain access control for internal corporate resources.</p>
                </div>
            </div>

            <?php if(isset($success_msg)): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800 font-bold shadow-sm"><?= $success_msg ?></div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-800 font-bold shadow-sm"><?= $error_msg ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Whitelisted</p>
                        <span class="material-symbols-outlined text-primary">groups</span>
                    </div>
                    <p class="text-slate-900 dark:text-slate-100 text-3xl font-bold"><?= $total_whitelisted ?></p>
                </div>
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Users/Domains</p>
                        <span class="material-symbols-outlined text-green-500">person_check</span>
                    </div>
                    <p class="text-slate-900 dark:text-slate-100 text-3xl font-bold"><?= $active_users ?></p>
                </div>
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Inactive Entries</p>
                        <span class="material-symbols-outlined text-slate-400">person_off</span>
                    </div>
                    <p class="text-slate-900 dark:text-slate-100 text-3xl font-bold"><?= $inactive_users ?></p>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start">
                
                <!-- Table -->
                <div class="flex-1 w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr class="border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Entry</th>
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php foreach($whitelist as $w): ?>
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4 text-slate-900 dark:text-slate-100 text-sm font-medium"><?= htmlspecialchars($w['email_or_domain']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><?= strtoupper($w['type']) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if($w['is_active']): ?>
                                                <div class="flex items-center gap-1.5">
                                                    <div class="size-2 rounded-full bg-green-500"></div>
                                                    <span class="text-slate-700 dark:text-slate-300 text-sm">Active</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex items-center gap-1.5">
                                                    <div class="size-2 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                                                    <span class="text-slate-400 dark:text-slate-500 text-sm">Inactive</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="id" value="<?= $w['id'] ?>">
                                                    <button type="submit" class="p-1 hover:text-primary transition-colors text-slate-400" title="Toggle Status">
                                                        <span class="material-symbols-outlined text-xl"><?= $w['is_active'] ? 'toggle_on' : 'toggle_off' ?></span>
                                                    </button>
                                                </form>

                                                <form method="POST" class="inline" onsubmit="return confirm('Delete this entry?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $w['id'] ?>">
                                                    <button type="submit" class="p-1 hover:text-red-500 transition-colors text-slate-400" title="Delete">
                                                        <span class="material-symbols-outlined text-xl">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($whitelist)): ?>
                                    <tr><td colspan="4" class="px-6 py-4 text-center text-slate-500">No whitelist entries found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Form -->
                <div class="w-full lg:w-80 shrink-0 p-6 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm sticky top-24">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">person_add</span>
                        Add Entry
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Email or Domain</label>
                            <input name="email_or_domain" required class="form-input rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary focus:ring-primary h-10 px-3 w-full" placeholder="e.g. user@nexus.com" type="text"/>
                            <p class="text-xs text-slate-500 mt-1">To whitelist a domain, start with '@'. Example: @gmail.com</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Initial Status</label>
                            <div class="flex flex-col gap-2 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input checked value="1" class="text-primary focus:ring-primary" name="status" type="radio"/>
                                    <span class="text-sm">Active</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input value="0" class="text-primary focus:ring-primary" name="status" type="radio"/>
                                    <span class="text-sm">Inactive</span>
                                </label>
                            </div>
                        </div>
                        <div class="pt-4">
                            <button class="w-full h-10 rounded-lg bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors shadow-sm" type="submit">Add to Whitelist</button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </main>
</div>
</body>
</html>
