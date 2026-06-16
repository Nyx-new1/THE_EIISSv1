<?php
require_once __DIR__ . '/config/session.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Already logged in
if (isset($_SESSION['user_role'])) {
    header("Location: " . ($_SESSION['user_role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = dbGetUserByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password. Please try again.';
        } elseif (!(int)$user['verified'] && $user['role'] !== 'admin') {
            $error = 'Your account is pending administrator verification. Please check back later.';
        } else {
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name']  = $user['name'];

            header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – EIISS</title>
    <meta name="description" content="Sign in to your EIISS account to manage your innovations or investment portfolio.">
    <script src="js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { primary: '#030213', accent: '#3b82f6' },
                fontFamily: { sans: ['Inter','sans-serif'], heading: ['Outfit','sans-serif'] }
            }}
        }
    </script>
    <script src="js/lucide.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 flex flex-col min-h-screen">

    <header class="p-6">
        <a href="index.php" class="flex items-center gap-2.5 group max-w-fit mx-auto sm:mx-0">
            <div class="p-2 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                <i data-lucide="lightbulb" class="w-6 h-6"></i>
            </div>
            <span class="font-heading font-extrabold text-2xl tracking-tight text-slate-800">EIISS</span>
        </a>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md animate-fade-in">

            <div class="text-center mb-6">
                <h1 class="font-heading font-extrabold text-3xl text-slate-800">Welcome Back</h1>
                <p class="text-sm text-slate-500 mt-1.5 font-medium">Sign in to manage your ideas and investments</p>
            </div>

            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 p-8 rounded-2xl shadow-xl">

                <?php if (!empty($error)): ?>
                    <div class="mb-5 p-4 bg-red-50 border border-red-100 rounded-xl flex gap-3 text-red-700 text-sm">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-bold">Login failed</p>
                            <p class="mt-0.5 font-medium text-red-600"><?= e($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </div>
                            <input type="email" name="email" id="email" required
                                   placeholder="you@example.com"
                                   value="<?= e($_POST['email'] ?? '') ?>"
                                   class="block w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Password</label>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </div>
                            <input type="password" name="password" id="password" required
                                   placeholder="••••••••"
                                   class="block w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-md shadow-blue-500/20 flex items-center justify-center gap-2 group transition-all mt-6">
                        Sign In
                        <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>


            </div>

            <p class="text-center text-sm font-semibold text-slate-500 mt-6">
                Don't have an account?
                <a href="register.php" class="text-blue-600 font-bold hover:underline">Create an account</a>
            </p>

            <a href="index.php" class="block text-center text-xs font-bold text-slate-400 hover:text-slate-600 mt-8 transition-colors">
                &larr; Back to Landing Page
            </a>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>
