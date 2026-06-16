<?php
require_once __DIR__ . '/config/session.php';

// Already logged in
if (isset($_SESSION['user_role'])) {
    header("Location: " . ($_SESSION['user_role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role            = $_POST['userRole'] ?? 'entrepreneur';
    $name            = trim($_POST['fullName'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $organization    = trim($_POST['organization'] ?? '');
    $sector          = $_POST['sector'] ?? '';
    $idType          = $_POST['idType'] ?? '';
    $idNumber        = trim($_POST['idNumber'] ?? '');
    $phoneNumber     = trim($_POST['phoneNumber'] ?? '');
    $acceptTerms     = isset($_POST['acceptTerms']);

    if (empty($name) || empty($email) || empty($password) || empty($idType) || empty($idNumber) || empty($phoneNumber)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!$acceptTerms) {
        $error = 'Please accept the terms and conditions.';
    } else {
        // Check if user exists in DB
        $existing = dbGetUserByEmail($email);
        if ($existing) {
            $error = 'An account with this email address already exists.';
        } else {
            // Insert into DB — all new users start unverified (admin must approve)
            $stmt = getDB()->prepare("
                INSERT INTO users (name, email, password_hash, role, organization, sector, location, bio, id_type, id_number, phone_number, verified)
                VALUES (?, ?, ?, ?, ?, ?, '', '', ?, ?, ?, 0)
            ");
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_BCRYPT),
                $role,
                $organization,
                $sector,
                $idType,
                $idNumber,
                $phoneNumber
            ]);

            // Notify admin
            $stmt2 = getDB()->prepare("
                INSERT INTO notifications (user_email, type, title, message, time_ago)
                VALUES ((SELECT email FROM users WHERE role = 'admin' LIMIT 1), 'info', 'New Registration', ?, 'Just now')
            ");
            $stmt2->execute(["$name ($email) registered as $role and is awaiting verification."]);

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – EIISS</title>
    <meta name="description" content="Register for EIISS to notarize your business ideas or invest in regional innovations.">
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
        <div class="w-full max-w-2xl animate-fade-in">

            <div class="text-center mb-6">
                <h1 class="font-heading font-extrabold text-3xl text-slate-800">Create Account</h1>
                <p class="text-sm text-slate-500 mt-1.5 font-medium">Join EIISS to notarize ideas or fund regional innovations</p>
            </div>

            <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 p-8 rounded-2xl shadow-xl">

                <?php if (!empty($error)): ?>
                    <div class="mb-5 p-4 bg-red-50 border border-red-100 rounded-xl flex gap-3 text-red-700 text-sm">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-bold">Registration error</p>
                            <p class="mt-0.5 font-medium text-red-600"><?= e($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-5 p-4 bg-amber-50 border border-amber-100 rounded-xl flex gap-3 text-amber-700 text-sm">
                        <i data-lucide="clock" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-bold">Account created — awaiting approval</p>
                            <p class="mt-0.5 font-medium text-amber-600">Your account has been submitted for administrator verification. You'll be able to log in once approved.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">

                    <!-- Role Selector -->
                    <div>
                        <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Choose Account Type</span>
                        <input type="hidden" name="userRole" id="userRole" value="<?= e($_POST['userRole'] ?? 'entrepreneur') ?>">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div onclick="selectRole('entrepreneur')" id="card-entrepreneur"
                                 class="p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center text-center gap-2 <?= ($_POST['userRole'] ?? 'entrepreneur') === 'entrepreneur' ? 'border-blue-600 bg-blue-50/30' : 'border-slate-200 hover:border-blue-200 bg-slate-50/10' ?>">
                                <div class="p-3 bg-blue-50 text-blue-600 rounded-full"><i data-lucide="lightbulb" class="w-6 h-6"></i></div>
                                <span class="text-sm font-bold text-slate-800">Entrepreneur</span>
                                <span class="text-[11px] text-slate-400 font-medium">I have creative ideas and business proposals to notarize and share</span>
                            </div>
                            <div onclick="selectRole('investor')" id="card-investor"
                                 class="p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center text-center gap-2 <?= ($_POST['userRole'] ?? 'entrepreneur') === 'investor' ? 'border-blue-600 bg-blue-50/30' : 'border-slate-200 hover:border-blue-200 bg-slate-50/10' ?>">
                                <div class="p-3 bg-green-50 text-green-600 rounded-full"><i data-lucide="target" class="w-6 h-6"></i></div>
                                <span class="text-sm font-bold text-slate-800">Investor</span>
                                <span class="text-[11px] text-slate-400 font-medium">I am looking to discover, lock details, and fund high-potential startups</span>
                            </div>
                        </div>
                    </div>

                    <!-- Name & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="fullName" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Full Name</label>
                            <input type="text" name="fullName" id="fullName" required placeholder="Your full name"
                                   value="<?= e($_POST['fullName'] ?? '') ?>"
                                   class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="email" name="email" id="email" required placeholder="you@gmail.com"
                                   value="<?= e($_POST['email'] ?? '') ?>"
                                   class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                    </div>

                    <!-- Passwords -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Password</label>
                            <input type="password" name="password" id="password" required placeholder="Min. 8 characters"
                                   class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="confirmPassword" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Confirm Password</label>
                            <input type="password" name="confirmPassword" id="confirmPassword" required placeholder="Re-enter password"
                                   class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                    </div>

                    <!-- Organization & Sector -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="organization" id="org-label" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Company/Startup Name</label>
                            <input type="text" name="organization" id="organization" placeholder="Optional"
                                   value="<?= e($_POST['organization'] ?? '') ?>"
                                   class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="sector" id="sector-label" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Primary Sector Focus</label>
                            <select name="sector" id="sector" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                                <option value="technology" <?= ($_POST['sector'] ?? '') === 'technology' ? 'selected' : '' ?>>Technology</option>
                                <option value="healthcare" <?= ($_POST['sector'] ?? '') === 'healthcare' ? 'selected' : '' ?>>Healthcare</option>
                                <option value="agriculture" <?= ($_POST['sector'] ?? '') === 'agriculture' ? 'selected' : '' ?>>Agriculture</option>
                                <option value="education" <?= ($_POST['sector'] ?? '') === 'education' ? 'selected' : '' ?>>Education</option>
                                <option value="ecommerce" <?= ($_POST['sector'] ?? '') === 'ecommerce' ? 'selected' : '' ?>>E-commerce</option>
                                <option value="fintech" <?= ($_POST['sector'] ?? '') === 'fintech' ? 'selected' : '' ?>>FinTech</option>
                                <option value="manufacturing" <?= ($_POST['sector'] ?? '') === 'manufacturing' ? 'selected' : '' ?>>Manufacturing</option>
                            </select>
                        </div>
                    </div>

                    <!-- Identity Verification -->
                    <div class="pt-5 border-t border-slate-100">
                        <h3 class="font-heading font-bold text-sm text-slate-800 mb-3 flex items-center gap-1.5">
                            <i data-lucide="shield" class="w-4 h-4 text-blue-600"></i>
                            Trust Verification Identity Check
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="idType" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ID Type</label>
                                <select name="idType" id="idType" required class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                                    <option value="nida" <?= ($_POST['idType'] ?? '') === 'nida' ? 'selected' : '' ?>>National ID (NIDA)</option>
                                    <option value="drivers" <?= ($_POST['idType'] ?? '') === 'drivers' ? 'selected' : '' ?>>Driver's License</option>
                                    <option value="passport" <?= ($_POST['idType'] ?? '') === 'passport' ? 'selected' : '' ?>>Passport</option>
                                    <option value="voters" <?= ($_POST['idType'] ?? '') === 'voters' ? 'selected' : '' ?>>Voter's ID</option>
                                </select>
                            </div>
                            <div>
                                <label for="idNumber" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ID Card Number</label>
                                <input type="text" name="idNumber" id="idNumber" required placeholder="Enter card number"
                                       value="<?= e($_POST['idNumber'] ?? '') ?>"
                                       class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="phoneNumber" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Phone Number</label>
                            <input type="text" name="phoneNumber" id="phoneNumber" required placeholder="+255 756 123 456"
                                   value="<?= e($_POST['phoneNumber'] ?? '') ?>"
                                   class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                            <p class="text-[10px] text-slate-400 mt-1 font-semibold">Important: Verification and Mobile money transfers are mapped to this active line.</p>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-2.5">
                        <input type="checkbox" id="acceptTerms" name="acceptTerms" required class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 mt-0.5">
                        <label for="acceptTerms" class="text-xs font-semibold text-slate-500 cursor-pointer leading-relaxed">
                            I agree to the <a href="terms.php" class="text-blue-600 font-bold hover:underline">Terms of Service</a> and <a href="privacy.php" class="text-blue-600 font-bold hover:underline">Privacy Policy</a>. I consent to have my uploaded ideas notarized onto the system's simulated blockchain network.
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-md shadow-blue-500/20 flex items-center justify-center gap-2 group transition-all mt-4">
                        Create Account
                        <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>
            </div>

            <p class="text-center text-sm font-semibold text-slate-500 mt-6">
                Already have an account?
                <a href="login.php" class="text-blue-600 font-bold hover:underline">Sign in</a>
            </p>

            <a href="index.php" class="block text-center text-xs font-bold text-slate-400 hover:text-slate-600 mt-8 transition-colors">
                &larr; Back to Landing Page
            </a>
        </div>
    </main>

    <script>
        function selectRole(role) {
            document.getElementById('userRole').value = role;
            const cardEnt = document.getElementById('card-entrepreneur');
            const cardInv = document.getElementById('card-investor');
            const orgLabel = document.getElementById('org-label');
            const sectorLabel = document.getElementById('sector-label');

            if (role === 'entrepreneur') {
                cardEnt.className = "p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center text-center gap-2 border-blue-600 bg-blue-50/30";
                cardInv.className = "p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center text-center gap-2 border-slate-200 hover:border-blue-200 bg-slate-50/10";
                if (orgLabel) orgLabel.innerText = "Company/Startup Name";
                if (sectorLabel) sectorLabel.innerText = "Primary Sector Focus";
            } else {
                cardEnt.className = "p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center text-center gap-2 border-slate-200 hover:border-blue-200 bg-slate-50/10";
                cardInv.className = "p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col items-center text-center gap-2 border-blue-600 bg-blue-50/30";
                if (orgLabel) orgLabel.innerText = "Investment Firm Name";
                if (sectorLabel) sectorLabel.innerText = "Investment Sector Focus";
            }
        }
        lucide.createIcons();
    </script>
</body>
</html>
