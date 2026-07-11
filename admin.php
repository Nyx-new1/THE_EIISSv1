<?php
require_once __DIR__ . '/config/session.php';

$loggedIn    = isset($_SESSION['user_role']);
$role        = $loggedIn ? $_SESSION['user_role']  : null;
$userEmail   = $loggedIn ? $_SESSION['user_email'] : null;

// Redirect if not logged in or not admin
if (!$loggedIn || $role !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle dynamic settings post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $usdToTsh = trim($_POST['usd_to_tsh'] ?? '2600');
    dbSetSystemSetting('usd_to_tsh', $usdToTsh);
    $messageAlert = "Exchange rate settings updated successfully!";
}

require_once __DIR__ . '/includes/header.php';

// Handle dynamic user actions (approvals / rejections) inside the DB
$messageAlert = '';
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $actionType = $_GET['action'];
    $userId = (int)$_GET['user_id'];

    if ($actionType === 'approve') {
        $user = dbGetUserById($userId);
        if ($user) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET verified = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            $messageAlert = "Successfully approved user: <strong>" . e($user['name']) . "</strong>. They can now access all verified portals.";
            
            // Notify the approved user
            $stmt2 = $db->prepare("
                INSERT INTO notifications (user_email, type, title, message, sender)
                VALUES (?, 'trend', 'ID Verification Complete', 'Congratulations! Your identification documents were successfully verified by administration.', 'System Admin')
            ");
            $stmt2->execute([$user['email']]);

        }
    } else if ($actionType === 'reject') {
        $user = dbGetUserById($userId);
        if ($user) {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $messageAlert = "Verification rejected for user: <strong>" . e($user['name']) . "</strong>. Account credentials deleted.";
        }
    }
}

// Re-fetch users and stats
$pendingUsers = dbGetPendingUsers();
$pendingCount = count($pendingUsers);
$totalUsersCount = dbCountUsers();
$totalIdeasCount = (int)getDB()->query("SELECT COUNT(*) FROM ideas")->fetchColumn();
$platformRevenue = dbTotalPlatformRevenue();
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full animate-fade-in">
    
    <!-- Header Banner block -->
    <div class="bg-gradient-to-r from-purple-700 via-indigo-700 to-blue-700 text-white p-8 rounded-2xl shadow-md mb-8 relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(255,255,255,0.06),transparent)] pointer-events-none"></div>
        <div class="z-10 text-left">
            <h1 class="font-heading font-extrabold text-3xl">Platform Operations Hub</h1>
            <p class="text-indigo-100/90 text-sm mt-1 font-medium">Verify user credentials, audit notarized pitch concepts, and oversee platform billing records</p>
        </div>
        
        <!-- Central USD/TSH Converter Admin Configurator -->
        <div class="z-10 bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20 shadow-inner w-full md:w-auto min-w-[280px]">
            <form method="POST" class="flex flex-col gap-2.5">
                <input type="hidden" name="action" value="save_settings">
                <div class="flex justify-between items-center">
                    <span class="text-[10px] font-black uppercase tracking-wider text-indigo-200">System Exchange Rate Center</span>
                    <span class="text-[9px] font-bold bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full uppercase">100% Dynamic</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-white">1 USD =</span>
                    <input type="number" name="usd_to_tsh" value="<?= e(dbGetSystemSetting('usd_to_tsh') ?: '2600') ?>" required min="1" step="1" class="px-2.5 py-1.5 rounded-lg bg-black/45 border border-white/20 text-white text-xs font-black focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-right w-28">
                    <span class="text-xs font-bold text-slate-300">TSH</span>
                    <button type="submit" class="p-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all shadow-md flex items-center justify-center">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Stats Cards grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div onclick="switchAdminTab(1)" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-blue-200 transition-all">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="users" class="w-6 h-6"></i></div>
            <div>
                <p class="text-xs font-semibold text-slate-400">Total Users</p>
                <p class="text-xl font-heading font-black text-slate-800 mt-0.5"><?= $totalUsersCount ?></p>
            </div>
        </div>
        <div onclick="switchAdminTab(0)" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-orange-200 transition-all">
            <div class="p-3 bg-orange-50 text-orange-600 rounded-xl"><i data-lucide="user-check" class="w-6 h-6"></i></div>
            <div>
                <p class="text-xs font-semibold text-slate-400">Pending Approvals</p>
                <p class="text-xl font-heading font-black text-slate-800 mt-0.5"><?= $pendingCount ?></p>
            </div>
        </div>
        <div onclick="switchAdminTab(3)" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-indigo-200 transition-all">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl"><i data-lucide="lightbulb" class="w-6 h-6"></i></div>
            <div>
                <p class="text-xs font-semibold text-slate-400">Ideas Notarized</p>
                <p class="text-xl font-heading font-black text-slate-800 mt-0.5"><?= $totalIdeasCount ?></p>
            </div>
        </div>
        <div onclick="switchAdminTab(2)" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-emerald-200 transition-all">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl"><i data-lucide="wallet" class="w-6 h-6"></i></div>
            <div>
                <p class="text-xs font-semibold text-slate-400">Platform Revenue</p>
                <p class="text-xl font-heading font-black text-slate-800 mt-0.5">$<?= number_format($platformRevenue) ?></p>
            </div>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($messageAlert)): ?>
        <div class="mb-6 p-4.5 bg-blue-50 border border-blue-100 rounded-xl flex gap-3 text-blue-800 text-sm">
            <i data-lucide="info" class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600"></i>
            <p class="font-semibold"><?= $messageAlert ?></p>
        </div>
    <?php endif; ?>

    <!-- Navigation tabs section inside Admin workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- Left Tab selectors column -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200/80 p-2 shadow-sm space-y-1">
                <button onclick="switchAdminTab(0)" id="tab-btn-0" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all bg-blue-600 text-white shadow-sm">
                    <i data-lucide="clock" class="w-4 h-4"></i> Pending Vetting
                </button>
                <button onclick="switchAdminTab(1)" id="tab-btn-1" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all text-slate-500 hover:text-slate-800 hover:bg-slate-50">
                    <i data-lucide="users" class="w-4 h-4"></i> Vetted Profiles
                </button>
                <button onclick="switchAdminTab(2)" id="tab-btn-2" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all text-slate-500 hover:text-slate-800 hover:bg-slate-50">
                    <i data-lucide="credit-card" class="w-4 h-4"></i> Platform Billing
                </button>
                <button onclick="switchAdminTab(3)" id="tab-btn-3" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all text-slate-500 hover:text-slate-800 hover:bg-slate-50">
                    <i data-lucide="lightbulb" class="w-4 h-4"></i> Notarized Concepts
                </button>
                <button onclick="switchAdminTab(4)" id="tab-btn-4" class="w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all text-slate-500 hover:text-slate-800 hover:bg-slate-50">
                    <i data-lucide="activity" class="w-4 h-4"></i> Activity Log
                </button>
            </div>
        </div>

        <!-- Right Main Dynamic Content column -->
        <div class="lg:col-span-3 space-y-6">
            
            <!-- SECTION 0: PENDING VETTING -->
            <div id="section-admin-0" class="admin-tab-block space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-4.5 border-b border-slate-100 flex justify-between bg-slate-50/50">
                        <h2 class="font-heading font-bold text-base text-slate-800">Pending Identification Vetting Queue</h2>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <?php if (empty($pendingUsers)): ?>
                            <div class="p-12 text-center text-slate-400 flex flex-col items-center">
                                <i data-lucide="check-circle" class="w-12 h-12 text-emerald-500 mb-2"></i>
                                <p class="font-heading font-semibold text-slate-700">Queue is completely empty!</p>
                                <p class="text-xs text-slate-400 mt-0.5">All registered members have been verified.</p>
                            </div>
                        <?php else: foreach ($pendingUsers as $user): ?>
                            <div class="p-6 hover:bg-slate-50/30 transition-colors">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-11 h-11 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-heading font-bold text-sm flex items-center justify-center">
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h3 class="font-heading font-bold text-slate-800"><?= e($user['name']) ?></h3>
                                            <p class="text-xs text-slate-400 font-semibold mt-0.5"><?= e($user['email']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="openVettingDialog(<?= $user['id'] ?>, '<?= e($user['name']) ?>', '<?= e($user['id_type']) ?>', '<?= e($user['id_number']) ?>', '<?= e($user['phone_number']) ?>', '<?= e($user['id_document'] ?? '') ?>')" class="px-3.5 py-1.5 border border-slate-200 hover:border-blue-200 text-slate-600 hover:text-blue-600 font-bold rounded-lg text-xs transition-all shadow-sm bg-white">View ID</button>
                                        <a href="?action=approve&user_id=<?= $user['id'] ?>" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-all shadow-sm flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5"></i> Approve</a>
                                        <a href="?action=reject&user_id=<?= $user['id'] ?>" class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-xs transition-all shadow-sm flex items-center gap-1"><i data-lucide="x" class="w-3.5 h-3.5"></i> Reject</a>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 bg-slate-50/50 p-4 rounded-xl text-xs font-semibold text-slate-600">
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Role</span>
                                        <span class="capitalize text-slate-700"><?= e($user['role']) ?></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">ID Type</span>
                                        <span class="text-slate-700 uppercase"><?= e($user['id_type']) ?></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">ID Card Number</span>
                                        <span class="text-slate-700"><?= e($user['id_number']) ?></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Registered Phone</span>
                                        <span class="text-slate-700"><?= e($user['phone_number']) ?></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">ID Document File</span>
                                        <?php if (!empty($user['id_document'])): ?>
                                            <span class="text-emerald-600 flex items-center gap-1 font-bold">
                                                <i data-lucide="file-check" class="w-3.5 h-3.5"></i> Attached
                                            </span>
                                        <?php else: ?>
                                            <span class="text-amber-500 flex items-center gap-1 font-bold">
                                                <i data-lucide="file-warning" class="w-3.5 h-3.5"></i> Missing
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: VETTED PROFILES -->
            <div id="section-admin-1" class="admin-tab-block space-y-4 hidden animate-fade-in">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-4.5 border-b border-slate-100 flex justify-between bg-slate-50/50">
                        <h2 class="font-heading font-bold text-base text-slate-800">Vetted Platform Members</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <?php 
                        $vetted = dbGetVerifiedUsers();
                        if (empty($vetted)):
                        ?>
                            <div class="p-8 text-center text-slate-400">No vetted members yet.</div>
                        <?php else: foreach ($vetted as $user): ?>
                            <div class="p-4.5 px-6 flex justify-between items-center hover:bg-slate-50/20 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 font-heading font-bold text-xs flex items-center justify-center">
                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800"><?= e($user['name']) ?></p>
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5"><?= e($user['email']) ?> &bull; <span class="text-emerald-600">Verified</span></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold text-slate-500 bg-slate-100 border rounded-full uppercase tracking-wider"><?= e($user['role']) ?></span>
                                    <a href="?action=reject&user_id=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user profile?')" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Delete User">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: PLATFORM BILLING -->
            <div id="section-admin-2" class="admin-tab-block space-y-4 hidden animate-fade-in">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-4.5 border-b border-slate-100 flex justify-between bg-slate-50/50">
                        <h2 class="font-heading font-bold text-base text-slate-800">Global Platform Ledger Records</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <?php 
                        $allTransactions = dbGetAllTransactions();
                        if (empty($allTransactions)): 
                        ?>
                            <div class="p-8 text-center text-slate-400">No transactions processed on platform yet.</div>
                        <?php else: foreach ($allTransactions as $tx): ?>
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50/20 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-800"><?= e($tx['investor_name']) ?></p>
                                    <p class="text-xs font-semibold text-slate-400 truncate max-w-[200px] sm:max-w-md"><?= e($tx['idea_title']) ?> &bull; <span class="bg-slate-100 text-slate-600 px-1.5 py-0.2 rounded font-semibold text-[9px]"><?= e($tx['type']) ?></span></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-emerald-600">+$<?= number_format($tx['amount']) ?></p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5"><?= e($tx['date']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: NOTARIZED CONCEPTS -->
            <div id="section-admin-3" class="admin-tab-block space-y-4 hidden animate-fade-in">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-4.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h2 class="font-heading font-bold text-base text-slate-800">All Notarized Concepts</h2>
                        <span class="text-xs font-bold text-slate-400"><?= $totalIdeasCount ?> total</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <?php 
                        $allIdeasAdmin = dbGetAllIdeas();
                        if (empty($allIdeasAdmin)):
                        ?>
                            <div class="p-12 text-center text-slate-400 flex flex-col items-center">
                                <i data-lucide="inbox" class="w-12 h-12 text-slate-300 mb-2"></i>
                                <p class="font-heading font-semibold text-slate-500">No concepts submitted yet</p>
                            </div>
                        <?php else: foreach ($allIdeasAdmin as $idea): 
                            $owner = dbGetUserByEmail($idea['entrepreneur_email']);
                        ?>
                            <div class="p-5 hover:bg-slate-50/30 transition-colors">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
                                    <div class="min-w-0">
                                        <h3 class="font-heading font-bold text-slate-800 truncate"><?= e($idea['title']) ?></h3>
                                        <div class="flex gap-2 mt-1.5 flex-wrap">
                                            <span class="px-2 py-0.5 text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-full uppercase"><?= e($idea['sector']) ?></span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border uppercase
                                                <?= $idea['status'] === 'Active' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : ($idea['status'] === 'In Negotiation' ? 'text-amber-600 bg-amber-50 border-amber-100' : 'text-slate-500 bg-slate-50 border-slate-100') ?>">
                                                <?= e($idea['status']) ?>
                                            </span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold text-slate-500 bg-slate-50 border border-slate-100 rounded-full uppercase"><?= e($idea['stage']) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 flex-shrink-0">
                                        <a href="idea-detail.php?id=<?= $idea['id'] ?>" target="_blank" class="px-3 py-1.5 border border-slate-200 hover:border-blue-200 text-slate-600 hover:text-blue-600 font-bold rounded-lg text-xs transition-all bg-white flex items-center gap-1">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i> View
                                        </a>
                                        <?php 
                                        $statusOptions = ['Under Review', 'Active', 'In Negotiation', 'Funded'];
                                        ?>
                                        <select onchange="updateIdeaStatus(<?= $idea['id'] ?>, this.value)" class="px-2 py-1.5 border border-slate-200 rounded-lg text-xs font-bold text-slate-600 bg-white cursor-pointer focus:outline-none focus:border-blue-300">
                                            <?php foreach ($statusOptions as $st): ?>
                                                <option value="<?= $st ?>" <?= $idea['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Owner info row -->
                                <div class="flex items-center gap-3 bg-slate-50/80 p-3 rounded-xl border border-slate-100">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-heading font-bold text-xs flex items-center justify-center flex-shrink-0">
                                        <?= strtoupper(substr($owner['name'] ?? 'E', 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-800"><?= e($owner['name'] ?? 'Unknown') ?></p>
                                        <p class="text-[10px] text-slate-400 font-semibold truncate"><?= e($idea['entrepreneur_email']) ?></p>
                                    </div>
                                    <div class="grid grid-cols-3 gap-3 text-xs font-semibold text-right flex-shrink-0">
                                        <div>
                                            <span class="block text-[9px] text-slate-400 uppercase tracking-wider">Score</span>
                                            <span class="text-blue-600 font-extrabold"><?= $idea['score'] ?>/10</span>
                                        </div>
                                        <div>
                                            <span class="block text-[9px] text-slate-400 uppercase tracking-wider">Views</span>
                                            <span class="text-slate-700"><?= $idea['views'] ?></span>
                                        </div>
                                        <div>
                                            <span class="block text-[9px] text-slate-400 uppercase tracking-wider">Capital</span>
                                            <span class="text-slate-700">$<?= number_format($idea['capital_required']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Blockchain hash -->
                                <div class="mt-2.5 flex items-center gap-1.5 text-[10px] text-slate-400 font-medium">
                                    <i data-lucide="shield" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0"></i>
                                    <span class="font-mono truncate"><?= e($idea['blockchain_hash']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: ACTIVITY LOG -->
            <div id="section-admin-4" class="admin-tab-block space-y-4 hidden animate-fade-in">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-4.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h2 class="font-heading font-bold text-base text-slate-800">Platform Activity Log</h2>
                        <span class="text-xs font-bold text-slate-400">All system events</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <?php
                        $adminRow  = getDB()->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1")->fetch();
                        $adminMail = $adminRow ? $adminRow['email'] : 'admin@eiiss.co.tz';
                        $activityNotifs = dbGetNotificationsForUser($adminMail);
                        if (empty($activityNotifs)):
                        ?>
                            <div class="p-12 text-center text-slate-400 flex flex-col items-center">
                                <i data-lucide="inbox" class="w-12 h-12 text-slate-300 mb-2"></i>
                                <p class="font-heading font-semibold text-slate-500">No platform activity yet</p>
                            </div>
                        <?php else: foreach ($activityNotifs as $act):
                            $iconMap = ['trend' => 'lightbulb', 'payment' => 'credit-card', 'interest' => 'heart', 'pitch' => 'calendar', 'message' => 'message-circle'];
                            $colorMap = ['trend' => 'indigo', 'payment' => 'emerald', 'interest' => 'pink', 'pitch' => 'amber', 'message' => 'blue'];
                            $actType  = $act['type'] ?? 'trend';
                            $actIcon  = $iconMap[$actType]  ?? 'bell';
                            $actColor = $colorMap[$actType] ?? 'slate';
                        ?>
                            <div class="px-6 py-4 flex items-start gap-4 hover:bg-slate-50/30 transition-colors <?= (int)($act['is_read'] ?? 0) === 0 ? 'bg-blue-50/20' : '' ?>">
                                <div class="w-9 h-9 rounded-xl bg-<?= $actColor ?>-50 text-<?= $actColor ?>-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="<?= e($actIcon) ?>" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-slate-800"><?= e($act['title']) ?></p>
                                    <p class="text-xs text-slate-500 font-medium mt-0.5 leading-relaxed"><?= e($act['message']) ?></p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-[10px] text-slate-400 font-semibold whitespace-nowrap"><?= timeAgo($act['created_at']) ?></span>
                                    <?php if ((int)($act['is_read'] ?? 0) === 0): ?>
                                        <span class="block mt-1 w-2 h-2 rounded-full bg-blue-500 ml-auto"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>


        </div>
    </div>
</main>

<!-- ================= MODALS VETTING PREVIEW ================= -->
<div id="vetting-dialog" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl animate-fade-in flex flex-col overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-heading font-bold text-base text-slate-800">Identification Document Preview</h3>
            <button onclick="closeVettingDialog()" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-xl transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">
            
            <div class="flex items-center gap-3 pb-3 border-b">
                <div class="w-10 h-10 rounded-full bg-slate-100 font-heading font-bold text-slate-700 flex items-center justify-center border text-sm" id="vet-avatar">JD</div>
                <div>
                    <p class="font-heading font-bold text-sm text-slate-800" id="vet-name">John Doe</p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5" id="vet-phone">+255 000 000 000</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-xs font-semibold text-slate-600 bg-slate-50 p-3 rounded-xl">
                <div>
                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Card Type</span>
                    <span class="text-slate-700 uppercase" id="vet-card-type">NIDA</span>
                </div>
                <div>
                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Card ID number</span>
                    <span class="text-slate-700" id="vet-card-number">0000-0000</span>
                </div>
            </div>
 
            <div>
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Government ID Scans</span>
                <div id="vet-doc-preview-container" class="border rounded-2xl p-2 text-center bg-slate-50/50 flex flex-col items-center justify-center min-h-[160px]">
                    <!-- Javascript will load the image scan or pdf download link -->
                </div>
            </div>

            <!-- Footer selectors -->
            <div class="flex gap-2 pt-4">
                <button onclick="closeVettingDialog()" class="flex-1 py-2.5 border border-slate-200 text-slate-500 hover:bg-slate-50 font-bold rounded-xl text-xs transition-all bg-white">Cancel</button>
                <a id="vet-reject-btn" href="#" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs text-center transition-all shadow-sm">Reject Profile</a>
                <a id="vet-approve-btn" href="#" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs text-center transition-all shadow-sm">Approve Profile</a>
            </div>

        </div>
    </div>
</div>

<script>
    function switchAdminTab(tabIndex) {
        const blocks = document.querySelectorAll('.admin-tab-block');
        blocks.forEach((block, idx) => {
            if (idx === tabIndex) {
                block.classList.remove('hidden');
            } else {
                block.classList.add('hidden');
            }
        });

        // Toggle buttons class
        const buttons = [
            document.getElementById('tab-btn-0'),
            document.getElementById('tab-btn-1'),
            document.getElementById('tab-btn-2'),
            document.getElementById('tab-btn-3'),
            document.getElementById('tab-btn-4'),
            document.getElementById('tab-btn-5')
        ];

        buttons.forEach((btn, idx) => {
            if (btn) {
                if (idx === tabIndex) {
                    btn.className = "w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all bg-blue-600 text-white shadow-sm";
                } else {
                    btn.className = "w-full text-left py-2.5 px-4 text-xs font-bold rounded-lg flex items-center gap-2 transition-all text-slate-500 hover:text-slate-800 hover:bg-slate-50";
                }
            }
        });
    }

    function openVettingDialog(id, name, cardType, cardNumber, phone, idDoc) {
        const dialog = document.getElementById('vetting-dialog');
        
        document.getElementById('vet-avatar').innerText = name.substring(0, 1).toUpperCase();
        document.getElementById('vet-name').innerText = name;
        document.getElementById('vet-phone').innerText = phone;
        document.getElementById('vet-card-type').innerText = cardType;
        document.getElementById('vet-card-number').innerText = cardNumber;

        const previewContainer = document.getElementById('vet-doc-preview-container');
        if (previewContainer) {
            if (idDoc) {
                const ext = idDoc.split('.').pop().toLowerCase();
                if (ext === 'pdf') {
                    previewContainer.innerHTML = `
                        <div class="p-4 flex flex-col items-center">
                            <i data-lucide="file-text" class="w-10 h-10 text-blue-600 mb-2"></i>
                            <p class="text-xs font-bold text-slate-800">${idDoc.split('/').pop()}</p>
                            <a href="${idDoc}" target="_blank" class="mt-3 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm flex items-center gap-1.5">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Open/Download PDF
                            </a>
                        </div>
                    `;
                } else {
                    previewContainer.innerHTML = `
                        <div class="p-2 flex flex-col items-center">
                            <a href="${idDoc}" target="_blank" class="block mb-2.5">
                                <img src="${idDoc}" class="max-h-[140px] rounded-xl object-contain shadow mx-auto cursor-zoom-in hover:opacity-90 transition-opacity">
                            </a>
                            <a href="${idDoc}" download class="px-3.5 py-1.5 border border-slate-200 text-slate-600 hover:text-blue-600 font-bold rounded-lg text-xs shadow-sm flex items-center gap-1.5 bg-white transition-all">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download Image
                            </a>
                        </div>
                    `;
                }
            } else {
                previewContainer.innerHTML = `
                    <div class="p-8 text-center text-slate-400">
                        <i data-lucide="alert-triangle" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold">No document uploaded</p>
                    </div>
                `;
            }
        }

        document.getElementById('vet-approve-btn').setAttribute('href', '?action=approve&user_id=' + id);
        document.getElementById('vet-reject-btn').setAttribute('href', '?action=reject&user_id=' + id);

        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        lucide.createIcons();
    }

    function closeVettingDialog() {
        const dialog = document.getElementById('vetting-dialog');
        if (dialog) {
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
        }
    }

    function updateIdeaStatus(ideaId, newStatus) {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('idea_id', ideaId);
        fd.append('status', newStatus);
        fetch('api/idea-handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Flash a brief visual confirmation
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-5 right-5 z-50 bg-emerald-600 text-white px-4 py-2.5 rounded-xl shadow-lg text-xs font-bold flex items-center gap-2 animate-fade-in';
                    toast.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Status updated to "${newStatus}"`;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2500);
                } else {
                    alert('Failed to update status: ' + data.message);
                }
            });
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
