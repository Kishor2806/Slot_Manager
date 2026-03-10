<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token for OAuth state
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));

// Using Zoho Accounts URL
// For EU/IN domains, this would be accounts.zoho.eu or accounts.zoho.in
$zoho_auth_url = "https://accounts.zoho.com/oauth/v2/auth?" . http_build_query([
    'response_type' => 'code',
    'client_id' => $zoho_client_id,
    'scope' => 'ZohoCRM.users.READ', // or any minimal scope like email profile depending on exactly what Zoho service is used
    'redirect_uri' => $zoho_redirect_uri,
    'state' => $_SESSION['oauth_state'],
    'access_type' => 'offline'
]);

// A fallback for local dev if client ID isn't set
$is_dev_mode = ($zoho_client_id === 'YOUR_ZOHO_CLIENT_ID');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - The Nexus Slot Manager</title>
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
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white dark:bg-slate-900 rounded-2xl shadow-xl overflow-hidden border border-slate-200 dark:border-slate-800">
        <div class="p-8 pb-6 text-center">
            <div class="size-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-6 text-primary">
                <span class="material-symbols-outlined text-4xl">hub</span>
            </div>
            
            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-2">The Nexus</h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium">Internal Resource &amp; Slot Manager</p>
        </div>

        <div class="p-8 pt-2">
            
            <?php if(isset($_SESSION['error_msg'])): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm font-semibold flex items-center gap-3">
                    <span class="material-symbols-outlined">error</span>
                    <?= htmlspecialchars($_SESSION['error_msg']) ?>
                </div>
                <?php unset($_SESSION['error_msg']); ?>
            <?php endif; ?>

            <?php if($is_dev_mode): ?>
                <div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400 text-sm flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-semibold">
                        <span class="material-symbols-outlined text-lg">warning</span>
                        Dev Mode
                    </div>
                    <p>Zoho credentials not configured in config.php. <a href="dev_login.php" class="underline font-bold hover:text-amber-900 dark:hover:text-amber-300">Skip SSO for local testing</a></p>
                </div>
            <?php endif; ?>

            <a href="<?= $zoho_auth_url ?>" class="w-full flex items-center justify-center gap-3 bg-primary hover:bg-primary/90 text-white font-bold py-3.5 px-4 rounded-xl transition-all shadow-lg shadow-primary/25 cursor-pointer">
                <span class="material-symbols-outlined">login</span>
                <span>Sign in with Zoho</span>
            </a>

            <div class="mt-8 text-center">
                <p class="text-xs text-slate-400">Secure entry via Corporate SSO</p>
            </div>
        </div>
    </div>

</body>
</html>
