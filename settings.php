<?php
require_once __DIR__ . '/config/session.php';

$loggedIn    = isset($_SESSION['user_role']);
$role        = $loggedIn ? $_SESSION['user_role']  : null;
$userEmail   = $loggedIn ? $_SESSION['user_email'] : null;

// Redirect if not logged in
if (!$loggedIn) {
    header("Location: login.php");
    exit;
}

// Redirect if admin (admin manages users from admin panel, no settings page needed)
if ($role === 'admin') {
    header("Location: admin.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$successAlert = false;
$successMsg = '';
$errorMsg = '';
$activeTab = isset($_GET['tab']) ? (int)$_GET['tab'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $fullName = trim($_POST['fullName'] ?? '');
        $phone = trim($_POST['phoneNumber'] ?? '');
        $org = trim($_POST['organization'] ?? '');
        $sect = $_POST['sector'] ?? '';
        $loc = trim($_POST['location'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $db = getDB();
        $stmt = $db->prepare("
            UPDATE users
            SET name = ?, phone_number = ?, organization = ?, sector = ?, location = ?, bio = ?
            WHERE email = ?
        ");
        $stmt->execute([$fullName, $phone, $org, $sect, $loc, $bio, $userEmail]);

        // Process avatar upload if provided
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarFile = $_FILES['avatar'];
            $avatarName = basename($avatarFile['name']);
            $ext = strtolower(pathinfo($avatarName, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $uploadDir = __DIR__ . '/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $uniqueAvatarName = uniqid() . '_' . str_replace(['@', '.'], '_', $userEmail) . '.' . $ext;
                $targetFile = $uploadDir . $uniqueAvatarName;
                if (move_uploaded_file($avatarFile['tmp_name'], $targetFile)) {
                    $avatarPath = 'uploads/avatars/' . $uniqueAvatarName;
                    $db->prepare("UPDATE users SET avatar = ? WHERE email = ?")->execute([$avatarPath, $userEmail]);
                }
            }
        }

        $_SESSION['user_name'] = $fullName; // Sync session name
        $successAlert = true;
    } else if ($action === 'verification') {
        $idType = $_POST['idType'] ?? 'nida';
        $idNumber = trim($_POST['idNumber'] ?? '');

        // Process ID document upload if provided
        $idDocPath = null;
        if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
            $idDocFile = $_FILES['id_document'];
            $idDocName = basename($idDocFile['name']);
            $ext = strtolower(pathinfo($idDocName, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'pdf'])) {
                $uploadDir = __DIR__ . '/uploads/ids/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $uniqueIdName = uniqid() . '_' . str_replace(['@', '.'], '_', $userEmail) . '.' . $ext;
                $targetFile = $uploadDir . $uniqueIdName;
                if (move_uploaded_file($idDocFile['tmp_name'], $targetFile)) {
                    $idDocPath = 'uploads/ids/' . $uniqueIdName;
                }
            }
        }

        $db = getDB();
        if ($idDocPath) {
            $stmt = $db->prepare("
                UPDATE users
                SET id_type = ?, id_number = ?, id_document = ?, verified = 0
                WHERE email = ?
            ");
            $stmt->execute([$idType, $idNumber, $idDocPath, $userEmail]);
        } else {
            $stmt = $db->prepare("
                UPDATE users
                SET id_type = ?, id_number = ?, verified = 0
                WHERE email = ?
            ");
            $stmt->execute([$idType, $idNumber, $userEmail]);
        }

        // Get admin email dynamically
        $adminEmail = $db->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn() ?: 'admin@eiiss.co.tz';

        // Add pending check alert in admin's notifications in DB
        $message = $_SESSION['user_name'] . " submitted ID verification documents for vetting.";
        $stmtNotif = $db->prepare("
            INSERT INTO notifications (user_email, type, title, message, time_ago, sender)
            VALUES (?, 'interest', 'Vetting Request', ?, 'Just now', 'System Admin')
        ");
        $stmtNotif->execute([$adminEmail, $message]);

        $successAlert = true;
    } else if ($action === 'notifications') {
        $successAlert = true;
    } else if ($action === 'password') {
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMsg = 'Please fill in all password fields.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'New passwords do not match.';
        } else {
            // Password strength check
            $hasLetter = preg_match('/[a-zA-Z]/', $newPassword);
            $hasUpper  = preg_match('/[A-Z]/', $newPassword);
            $hasDigit  = preg_match('/[0-9]/', $newPassword);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $newPassword);

            if (strlen($newPassword) < 8 || !$hasLetter || !$hasUpper || !$hasDigit || !$hasSpecial) {
                $errorMsg = 'New password must be at least 8 characters long and include alphabets, numbers, special characters, and at least one capital letter.';
            } else {
                $db = getDB();
                $user = dbGetUserByEmail($userEmail);
                if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                    $errorMsg = 'Your current password is incorrect.';
                } else {
                    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                    $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userEmail]);
                    $successMsg = 'Your security password was successfully changed.';
                    $successAlert = true;
                }
            }
        }
    }
}

// Fetch active user profile from DB and map to camelCase for backwards compatibility
$currentUser = dbGetUserByEmail($userEmail);
if ($currentUser) {
    $currentUser['phoneNumber'] = $currentUser['phone_number'] ?? '';
    $currentUser['idType']      = $currentUser['id_type'] ?? '';
    $currentUser['idNumber']    = $currentUser['id_number'] ?? '';
}
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full animate-fade-in">
    
    <!-- Top Back button -->
    <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-700 uppercase tracking-wider mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
    </a>

    <!-- Header area -->
    <div class="mb-8">
        <h1 class="font-heading font-extrabold text-3xl text-slate-800">Account Settings</h1>
        <p class="text-sm text-slate-500 font-medium">Customize your verified workspace details, audit credentials, and manage notification targets</p>
    </div>

    <!-- Alert Success -->
    <?php if (!empty($successMsg)): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-100 rounded-xl flex gap-3 text-green-700 text-sm">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5 text-green-600"></i>
            <p class="font-semibold"><?= e($successMsg) ?></p>
        </div>
    <?php elseif ($successAlert): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-100 rounded-xl flex gap-3 text-green-700 text-sm">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5 text-green-600"></i>
            <p class="font-semibold">Your settings adjustments were successfully saved.</p>
        </div>
    <?php endif; ?>

    <!-- Alert Error -->
    <?php if (!empty($errorMsg)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl flex gap-3 text-red-700 text-sm animate-fade-in">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-600"></i>
            <p class="font-semibold"><?= e($errorMsg) ?></p>
        </div>
    <?php endif; ?>

    <!-- Split layout grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Left vertical navigation tabs -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200/80 p-2 shadow-sm space-y-1">
                <a href="?tab=0" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all <?= $activeTab === 0 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' ?>">
                    <i data-lucide="user" class="w-4 h-4"></i> Profile Details
                </a>
                <a href="?tab=1" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all <?= $activeTab === 1 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' ?>">
                    <i data-lucide="shield" class="w-4 h-4"></i> ID Verification
                </a>
                <a href="?tab=2" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all <?= $activeTab === 2 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' ?>">
                    <i data-lucide="bell" class="w-4 h-4"></i> Notification Config
                </a>
                <a href="?tab=3" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all <?= $activeTab === 3 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' ?>">
                    <i data-lucide="key" class="w-4 h-4"></i> Security & Password
                </a>
            </div>
        </div>

        <!-- Right Form column -->
        <div class="lg:col-span-3">
            
            <!-- TAB 0: PROFILE FORM -->
            <?php if ($activeTab === 0): ?>
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6.5 space-y-6">
                    <input type="hidden" name="action" value="profile">
                    
                    <h2 class="font-heading font-extrabold text-lg text-slate-800 border-b border-slate-100 pb-3">Personal Profile</h2>

                    <!-- Avatar row -->
                    <div class="flex items-center gap-5.5 pb-6 border-b border-slate-100">
                        <div id="avatar-display-box" class="flex-shrink-0">
                            <?php if (!empty($currentUser['avatar']) && file_exists(__DIR__ . '/' . $currentUser['avatar'])): ?>
                                <img src="<?= e($currentUser['avatar']) ?>" class="w-16 h-16 rounded-full object-cover shadow-lg border border-slate-200">
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-full bg-blue-600 text-white font-heading font-extrabold text-2xl flex items-center justify-center shadow-lg shadow-blue-500/15">
                                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="block font-bold text-sm text-slate-800">Avatar Image</span>
                            <span class="text-xs text-slate-400 font-semibold block mt-0.5">JPG or PNG (max 5MB)</span>
                            <input type="file" name="avatar" id="avatar-file-input" class="hidden" accept="image/*" onchange="previewAvatar(event)">
                            <button type="button" onclick="document.getElementById('avatar-file-input').click()" class="mt-2.5 px-3 py-1.5 border border-slate-200 text-slate-600 hover:text-blue-600 font-bold rounded-lg text-xs bg-white shadow-sm transition-all">Upload New Image</button>
                        </div>
                    </div>

                    <!-- Input grids -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="fullName" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Full Name</label>
                            <input type="text" name="fullName" id="fullName" required value="<?= e($currentUser['name'] ?? '') ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="email" id="email" disabled value="<?= e($currentUser['email'] ?? '') ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm bg-slate-100/70 text-slate-400 cursor-not-allowed">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phoneNumber" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Phone Number</label>
                            <input type="text" name="phoneNumber" id="phoneNumber" value="<?= e($currentUser['phoneNumber'] ?? '') ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="location" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Region/Location</label>
                            <input type="text" name="location" id="location" value="<?= e($currentUser['location'] ?? 'Dar es Salaam, Tanzania') ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="organization" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Company/Organization</label>
                            <input type="text" name="organization" id="organization" value="<?= e($currentUser['organization'] ?? '') ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="sector" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sector Focus</label>
                            <select name="sector" id="sector" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                                <option value="technology" <?= ($currentUser['sector'] ?? '') === 'technology' ? 'selected' : '' ?>>Technology</option>
                                <option value="healthcare" <?= ($currentUser['sector'] ?? '') === 'healthcare' ? 'selected' : '' ?>>Healthcare</option>
                                <option value="agriculture" <?= ($currentUser['sector'] ?? '') === 'agriculture' ? 'selected' : '' ?>>Agriculture</option>
                                <option value="education" <?= ($currentUser['sector'] ?? '') === 'education' ? 'selected' : '' ?>>Education</option>
                                <option value="ecommerce" <?= ($currentUser['sector'] ?? '') === 'ecommerce' ? 'selected' : '' ?>>E-commerce</option>
                                <option value="fintech" <?= ($currentUser['sector'] ?? '') === 'fintech' ? 'selected' : '' ?>>FinTech</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="bio" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Biography Profile</label>
                        <textarea name="bio" id="bio" rows="4" placeholder="Brief details about yourself..." class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50"><?= e($currentUser['bio'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5"><i data-lucide="save" class="w-4 h-4"></i> Save Account Profiles</button>
                </form>

            <!-- TAB 1: ID VERIFICATION -->
            <?php elseif ($activeTab === 1): ?>
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6.5 space-y-6">
                    <input type="hidden" name="action" value="verification">

                    <h2 class="font-heading font-extrabold text-lg text-slate-800 border-b border-slate-100 pb-3">Identity Document Verification</h2>

                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl flex gap-3 text-blue-800 text-sm">
                        <i data-lucide="shield" class="w-5 h-5 flex-shrink-0"></i>
                        <p class="font-semibold leading-relaxed">Secure storage: Identity cards are checked privately against registry indexes and not visible to external partners.</p>
                    </div>

                    <!-- Current Verified Badge -->
                    <div class="p-4.5 rounded-2xl border flex items-center justify-between gap-3
                        <?= ($currentUser['verified'] ?? false) ? 'bg-emerald-50 border-emerald-100 text-emerald-800' : 'bg-amber-50 border-amber-100 text-amber-800' ?>">
                        <div class="flex items-center gap-2">
                            <i data-lucide="<?= ($currentUser['verified'] ?? false) ? 'shield-check' : 'alert-circle' ?>" class="w-6 h-6"></i>
                            <div>
                                <span class="block font-bold text-sm">Verification Status: <?= ($currentUser['verified'] ?? false) ? 'Vetted' : 'Verification Required' ?></span>
                                <span class="text-xs font-semibold block mt-0.5 opacity-80"><?= ($currentUser['verified'] ?? false) ? 'Your account was verified and possesses complete clearance.' : 'Your identity scans are pending audit approval from EIISS administrators.' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="idType" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ID Type</label>
                            <select name="idType" id="idType" required class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                                <option value="nida" <?= ($currentUser['idType'] ?? '') === 'nida' ? 'selected' : '' ?>>National ID (NIDA)</option>
                                <option value="drivers" <?= ($currentUser['idType'] ?? '') === 'drivers' ? 'selected' : '' ?>>Driver's License</option>
                                <option value="passport" <?= ($currentUser['idType'] ?? '') === 'passport' ? 'selected' : '' ?>>Passport</option>
                                <option value="voters" <?= ($currentUser['idType'] ?? '') === 'voters' ? 'selected' : '' ?>>Voter's ID</option>
                            </select>
                        </div>
                        <div>
                            <label for="idNumber" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ID card Number</label>
                            <input type="text" name="idNumber" id="idNumber" required value="<?= e($currentUser['idNumber'] ?? '') ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                    </div>

                    <!-- Upload ID card scans simulator -->
                    <input type="file" name="id_document" id="id-document-file-input" class="hidden" accept="image/*,.pdf" onchange="previewIdDocument(event)">
                    <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6.5 text-center bg-slate-50/20 hover:border-blue-500 transition-all cursor-pointer" onclick="document.getElementById('id-document-file-input').click()">
                        <i data-lucide="upload-cloud" class="w-10 h-10 text-slate-400 mx-auto mb-2"></i>
                        <span class="block text-slate-700 font-bold text-sm">Upload Identity Scan Document</span>
                        <span class="text-xs text-slate-400 font-semibold block mt-0.5">PDF or High-resolution image (max 10MB)</span>
                        <div id="id-doc-info">
                            <?php if (!empty($currentUser['id_document'])): ?>
                                <div class="mt-3.5 inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-lg text-xs font-bold">
                                    <i data-lucide="file-check" class="w-4 h-4"></i>
                                    <span>Uploaded: <?= basename($currentUser['id_document']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5"><i data-lucide="shield" class="w-4 h-4"></i> Submit Identification Audit</button>
                </form>

            <!-- TAB 2: NOTIFICATION CONFIG -->
            <?php elseif ($activeTab === 2): ?>
                <form method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6.5 space-y-6">
                    <input type="hidden" name="action" value="notifications">

                    <h2 class="font-heading font-extrabold text-lg text-slate-800 border-b border-slate-100 pb-3">Notification Preferences</h2>

                    <div class="space-y-4">
                        <div class="pb-4 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Communication Channels</h3>
                            
                            <label class="flex items-center gap-3 cursor-pointer py-1.5">
                                <input type="checkbox" checked class="w-4.5 h-4.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <div>
                                    <span class="block text-xs font-bold text-slate-700 leading-none">Email updates</span>
                                    <span class="text-[10px] text-slate-400 font-semibold mt-0.5">Receive proposal matches and transactions summaries via email</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer py-1.5 mt-3">
                                <input type="checkbox" class="w-4.5 h-4.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <div>
                                    <span class="block text-xs font-bold text-slate-700 leading-none">SMS update alerts</span>
                                    <span class="text-[10px] text-slate-400 font-semibold mt-0.5">Receive immediate SMS notifications upon payment transfers or chats</span>
                                </div>
                            </label>
                        </div>

                        <div class="pb-4 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Venture Action Alerts</h3>
                            
                            <label class="flex items-center gap-3 cursor-pointer py-1.5">
                                <input type="checkbox" checked class="w-4.5 h-4.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <div>
                                    <span class="block text-xs font-bold text-slate-700 leading-none"><?= $role === 'entrepreneur' ? 'Investor Watch & Interests' : 'Recommended AI Concept Matches' ?></span>
                                    <span class="text-[10px] text-slate-400 font-semibold mt-0.5">Notify instantly when a partner matches criteria or watches proposal</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer py-1.5 mt-3">
                                <input type="checkbox" checked class="w-4.5 h-4.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <div>
                                    <span class="block text-xs font-bold text-slate-700 leading-none">Direct negotiation chats</span>
                                    <span class="text-[10px] text-slate-400 font-semibold mt-0.5">Notify immediately when partner opens direct chat conversation</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5"><i data-lucide="save" class="w-4 h-4"></i> Save Preferences</button>
                </form>

            <!-- TAB 3: SECURITY & PASSWORD -->
            <?php elseif ($activeTab === 3): ?>
                <form method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6.5 space-y-6 animate-fade-in">
                    <input type="hidden" name="action" value="password">

                    <h2 class="font-heading font-extrabold text-lg text-slate-800 border-b border-slate-100 pb-3">Update Security Password</h2>

                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl flex gap-3 text-blue-800 text-sm">
                        <i data-lucide="shield" class="w-5 h-5 flex-shrink-0"></i>
                        <p class="font-semibold leading-relaxed">Password rules: Minimum 8 characters, must contain at least one uppercase letter, one lowercase letter, one number, and one special character.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="currentPassword" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Current Password</label>
                            <input type="password" name="currentPassword" id="currentPassword" required class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="newPassword" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">New Password</label>
                            <input type="password" name="newPassword" id="newPassword" required class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                        <div>
                            <label for="confirmPassword" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Confirm New Password</label>
                            <input type="password" name="confirmPassword" id="confirmPassword" required class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                        </div>
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5"><i data-lucide="key" class="w-4 h-4"></i> Change Password</button>
                </form>
            <?php endif; ?>

        </div>
     </div>

</main>

<script>
function previewAvatar(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarEl = document.getElementById('avatar-display-box');
            if (avatarEl) {
                avatarEl.innerHTML = `<img src="${e.target.result}" class="w-16 h-16 rounded-full object-cover shadow-lg border border-slate-200">`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewIdDocument(event) {
    const input = event.target;
    const infoContainer = document.getElementById('id-doc-info');
    if (input.files && input.files[0] && infoContainer) {
        const file = input.files[0];
        const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
        infoContainer.innerHTML = `
            <div class="mt-3.5 inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg text-xs font-bold animate-fade-in">
                <i data-lucide="file-check" class="w-4 h-4"></i>
                <span>Selected: ${file.name} (${sizeMB} MB)</span>
            </div>
        `;
        lucide.createIcons();
    }
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
