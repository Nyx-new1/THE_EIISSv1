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

    // Validate Password strength: must include alphabets, numbers, non-alphabetic/special characters and at least one capital letter.
    $hasLetter = preg_match('/[a-zA-Z]/', $password);
    $hasUpper  = preg_match('/[A-Z]/', $password);
    $hasDigit  = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

    if (empty($name) || empty($email) || empty($password) || empty($idType) || empty($idNumber) || empty($phoneNumber)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8 || !$hasLetter || !$hasUpper || !$hasDigit || !$hasSpecial) {
        $error = 'Password must be at least 8 characters long and include alphabets, numbers, special characters, and at least one capital letter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^\d{9}$/', $phoneNumber)) {
        $error = 'Phone number must be exactly 9 digits.';
    } elseif ($idType === 'nida' && !preg_match('/^\d{20}$/', $idNumber)) {
        $error = 'National ID (NIDA) must be exactly 20 digits.';
    } elseif ($idType === 'drivers' && !preg_match('/^[a-zA-Z0-9]{10,}$/', $idNumber)) {
        $error = 'Driver\'s License must be at least 10 alphanumeric characters.';
    } elseif ($idType === 'passport' && !preg_match('/^[a-zA-Z0-9]{9}$/', $idNumber)) {
        $error = 'Passport number must be exactly 9 alphanumeric characters.';
    } elseif ($idType === 'voters' && !preg_match('/^[a-zA-Z0-9]{10}$/', $idNumber)) {
        $error = 'Voter\'s ID (EPIC number) must be exactly 10 alphanumeric characters.';
    } else {
        // Check if user exists in DB
        $existing = dbGetUserByEmail($email);
        if ($existing) {
            $error = 'An account with this email address already exists.';
        } else {
            // Formatted phone number with +255
            $formattedPhone = '+255' . $phoneNumber;

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
                $formattedPhone
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
<body class="min-h-screen flex flex-col md:flex-row bg-slate-50">

    <!-- Left Column (Visual Branding with register-1) -->
    <div class="hidden md:flex md:w-1/2 lg:w-2/5 relative bg-slate-900 overflow-hidden flex-col justify-between p-12 text-white sticky top-0 h-screen">
        <!-- Background image with a dark overlay -->
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/register-1.jpg');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/70 to-slate-900/40"></div>
        
        <!-- Logo -->
        <a href="index.php" class="relative z-10 flex items-center gap-2.5 group max-w-fit">
            <div class="p-2 bg-white/10 backdrop-blur-md text-white rounded-xl group-hover:bg-blue-600 transition-all duration-300">
                <i data-lucide="lightbulb" class="w-6 h-6"></i>
            </div>
            <span class="font-heading font-extrabold text-2xl tracking-tight text-white">EIISS</span>
        </a>
        
        <!-- Welcome Info -->
        <div class="relative z-10 space-y-5">
            <span class="px-3.5 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-400 bg-blue-500/10 rounded-full border border-blue-500/20">Secure Idea Notarization</span>
            <h2 class="font-heading font-extrabold text-4xl leading-tight text-white">
                Protect and grow your innovations.
            </h2>
            <div class="space-y-3.5 text-sm text-slate-300 font-medium">
                <div class="flex gap-2.5 items-start">
                    <div class="p-1 bg-emerald-500/25 text-emerald-400 rounded-lg"><i data-lucide="shield-check" class="w-4 h-4"></i></div>
                    <p>Cryptographic notarizations lock ownership details securely before sharing.</p>
                </div>
                <div class="flex gap-2.5 items-start">
                    <div class="p-1 bg-blue-500/25 text-blue-400 rounded-lg"><i data-lucide="verified" class="w-4 h-4"></i></div>
                    <p>Trust checks validate identity profiles for both founders and investors.</p>
                </div>
                <div class="flex gap-2.5 items-start">
                    <div class="p-1 bg-purple-500/25 text-purple-400 rounded-lg"><i data-lucide="zap" class="w-4 h-4"></i></div>
                    <p>Direct venture portals speed up matches and negotiation discussions.</p>
                </div>
            </div>
        </div>
        
        <!-- Footer info -->
        <div class="relative z-10 text-xs text-slate-400 font-medium">
            &copy; <?= date('Y') ?> EIISS. Secure Ownership. Verification. Venture Speed.
        </div>
    </div>

    <!-- Right Column (Form Panel) -->
    <div class="flex-grow flex flex-col justify-between min-h-screen">
        
        <!-- Mobile Logo Header -->
        <header class="p-6 md:hidden">
            <a href="index.php" class="flex items-center gap-2.5 group max-w-fit mx-auto">
                <div class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                    <i data-lucide="lightbulb" class="w-6 h-6"></i>
                </div>
                <span class="font-heading font-extrabold text-2xl tracking-tight text-slate-800">EIISS</span>
            </a>
        </header>

        <!-- Centered Register Form container -->
        <div class="flex-grow flex items-center justify-center px-6 py-12 lg:px-16">
            <div class="w-full max-w-2xl animate-fade-in">

                <div class="text-center mb-6">
                    <h1 class="font-heading font-extrabold text-3xl text-slate-800">Create Account</h1>
                    <p class="text-sm text-slate-500 mt-1.5 font-medium">Join EIISS to notarize ideas or fund regional innovations</p>
                </div>

                <div class="bg-white border border-slate-200/80 p-8 rounded-2xl shadow-xl">

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

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">

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
                                <p class="text-[10px] text-slate-400 mt-1 font-semibold">Must include letters (inc. 1 capital), numbers, and special characters.</p>
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
                                <div class="flex shadow-sm rounded-xl overflow-hidden">
                                    <span class="inline-flex items-center px-4 border border-r-0 border-slate-200 bg-slate-100 text-slate-500 text-sm font-bold rounded-l-xl">+255</span>
                                    <input type="text" name="phoneNumber" id="phoneNumber" required placeholder="756123456" maxlength="9"
                                           value="<?= e($_POST['phoneNumber'] ?? '') ?>"
                                           class="block w-full px-4 py-3 border border-slate-200 rounded-r-xl rounded-l-none text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 font-semibold">Enter exactly 9 digits after the default (+255) code. Mobile money transfers are mapped to this line.</p>
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
        </div>
        
        <!-- Form panel mini footer -->
        <footer class="p-6 text-center text-xs text-slate-400 border-t border-slate-100 bg-white">
            &copy; <?= date('Y') ?> EIISS - Entrepreneur Ideas Investment Support System.
        </footer>

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
        function updateIDRequirements() {
            const idType = document.getElementById('idType').value;
            const idInput = document.getElementById('idNumber');
            if (!idInput) return;

            if (idType === 'nida') {
                idInput.placeholder = "20-digit National ID (NIDA)";
                idInput.maxLength = 20;
            } else if (idType === 'drivers') {
                idInput.placeholder = "Driver's license number (10+ characters)";
                idInput.removeAttribute('maxLength');
            } else if (idType === 'passport') {
                idInput.placeholder = "9-character passport number";
                idInput.maxLength = 9;
            } else if (idType === 'voters') {
                idInput.placeholder = "10-character voter's ID (EPIC number)";
                idInput.maxLength = 10;
            }
        }

        document.getElementById('idType').addEventListener('change', function() {
            document.getElementById('idNumber').value = '';
            updateIDRequirements();
        });
        document.getElementById('idNumber').addEventListener('input', function() {
            const idType = document.getElementById('idType').value;
            if (idType === 'nida') {
                this.value = this.value.replace(/[^0-9]/g, '');
            } else {
                this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
            }
        });

        window.addEventListener('DOMContentLoaded', updateIDRequirements);
        lucide.createIcons();
    </script>
</body>
</html>
