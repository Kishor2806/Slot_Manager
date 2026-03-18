<?php
// admin/whitelist.php — Repurposed as "Manage Admins"
require_once '../config.php';
require_once '../includes/middleware.php';
require_admin();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'promote') {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $error_msg = "Please enter a valid email address.";
        } else {
            $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target_user) {
                $error_msg = "No user found with that email. The employee must log in at least once via Zoho SSO before they can be promoted.";
            } elseif ($target_user['role'] === 'admin') {
                $error_msg = "This user is already an admin.";
            } else {
                $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$target_user['id']]);
                $success_msg = "Successfully promoted to admin!";
            }
        }
    } elseif ($action === 'demote') {
        $id = intval($_POST['id'] ?? 0);
        // Prevent self-demotion
        if ($id === intval($_SESSION['user_id'])) {
            $error_msg = "You cannot demote yourself.";
        } else {
            $pdo->prepare("UPDATE users SET role = 'employee' WHERE id = ?")->execute([$id]);
            $success_msg = "User demoted to employee.";
        }
    } elseif ($action === 'make_admin') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$id]);
        $success_msg = "User promoted to admin!";
    }
}

// Fetch all users
$users = $pdo->query("
    SELECT id, name, email, role, created_at
    FROM users
    ORDER BY role DESC, name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($users);
$admin_count = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$employee_count = $total_users - $admin_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manage Admins - The Nexus Admin</title>
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

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
        <header class="h-16 flex items-center justify-between px-8 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold">Manage Admins</h2>
            </div>
            <div class="flex items-center gap-6"></div>
        </header>

        <div class="p-8">
            <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
                <div class="flex flex-col gap-1">
                    <h1 class="text-slate-900 dark:text-slate-100 text-3xl font-black tracking-tight">Admin Management</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Grant or revoke admin access for employees. All Zoho SSO users can log in automatically.</p>
                </div>
            </div>

            <?php if($success_msg): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800 font-bold shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-800 font-bold shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-600">error</span>
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Users</p>
                        <span class="material-symbols-outlined text-primary">groups</span>
                    </div>
                    <p class="text-slate-900 dark:text-slate-100 text-3xl font-bold"><?= $total_users ?></p>
                </div>
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Admins</p>
                        <span class="material-symbols-outlined text-amber-500">admin_panel_settings</span>
                    </div>
                    <p class="text-slate-900 dark:text-slate-100 text-3xl font-bold"><?= $admin_count ?></p>
                </div>
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Employees</p>
                        <span class="material-symbols-outlined text-green-500">person</span>
                    </div>
                    <p class="text-slate-900 dark:text-slate-100 text-3xl font-bold"><?= $employee_count ?></p>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start">

                <!-- User Table -->
                <div class="flex-1 w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr class="border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">User</th>
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-4 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php foreach($users as $u): ?>
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="size-9 rounded-full flex items-center justify-center font-bold text-sm
                                                    <?= $u['role'] === 'admin' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' ?>">
                                                    <span class="material-symbols-outlined text-sm"><?= $u['role'] === 'admin' ? 'shield_person' : 'person' ?></span>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($u['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400"><?= htmlspecialchars($u['email']) ?></td>
                                        <td class="px-6 py-4">
                                            <?php if($u['role'] === 'admin'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                    <span class="size-1.5 rounded-full bg-amber-500 mr-2"></span> Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                    <span class="size-1.5 rounded-full bg-slate-400 mr-2"></span> Employee
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-500"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                        <td class="px-6 py-4 text-right">
                                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                                <?php if($u['role'] === 'admin'): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Demote this admin to employee?');">
                                                        <input type="hidden" name="action" value="demote">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-200 text-red-600 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20 transition-colors" title="Demote to Employee">
                                                            <span class="material-symbols-outlined text-sm">arrow_downward</span>
                                                            Demote
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Promote this employee to admin?');">
                                                        <input type="hidden" name="action" value="make_admin">
                                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold border border-primary/30 text-primary hover:bg-primary/10 transition-colors" title="Promote to Admin">
                                                            <span class="material-symbols-outlined text-sm">arrow_upward</span>
                                                            Promote
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 italic">You</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($users)): ?>
                                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">No users found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Promote Form -->
                <div class="w-full lg:w-80 shrink-0 p-6 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm sticky top-24">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">person_add</span>
                        Promote to Admin
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="promote">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Employee Email</label>
                            <input name="email" required class="form-input rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary focus:ring-primary h-10 px-3 w-full" placeholder="e.g. employee@company.com" type="email"/>
                            <p class="text-xs text-slate-500 mt-1">The employee must have logged in at least once via Zoho SSO.</p>
                        </div>
                        <div class="pt-4">
                            <button class="w-full h-10 rounded-lg bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors shadow-sm" type="submit">Grant Admin Access</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
