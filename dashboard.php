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

// Ensure Admin goes to admin panel
if ($role === 'admin') {
    header("Location: admin.php");
    exit;
}

$currentUser = dbGetUserByEmail($userEmail);

// Handle simulated refund
if ($role === 'entrepreneur' && isset($_GET['action']) && $_GET['action'] === 'refund' && isset($_GET['pledge_id'])) {
    $pledgeId = (int)$_GET['pledge_id'];
    $db = getDB();
    
    // Check if the pledge belongs to one of this entrepreneur's ideas
    $checkStmt = $db->prepare("
        SELECT cp.*, i.title as idea_title, i.entrepreneur_email 
        FROM campaign_pledges cp
        JOIN ideas i ON cp.idea_id = i.id
        WHERE cp.id = ? LIMIT 1
    ");
    $checkStmt->execute([$pledgeId]);
    $pledge = $checkStmt->fetch();
    
    if ($pledge && $pledge['entrepreneur_email'] === $userEmail && (int)$pledge['refunded'] === 0) {
        try {
            $db->beginTransaction();
            
            // 1. Mark pledge as refunded
            $db->prepare("UPDATE campaign_pledges SET refunded = 1 WHERE id = ?")->execute([$pledgeId]);
            
            // 2. Reduce the startup's earnings
            $db->prepare("UPDATE ideas SET earnings = earnings - ? WHERE id = ?")->execute([$pledge['amount'], $pledge['idea_id']]);
            
            // 3. Notify the investor
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_email, type, title, message, sender)
                VALUES (?, 'info', 'Refund Processed', ?, 'System Admin')
            ");
            $notifMsg = "Entrepreneur of \"{$pledge['idea_title']}\" has refunded your contribution of " . formatCurrency($pledge['amount']) . ".";
            $notifStmt->execute([$pledge['investor_email'], $notifMsg]);
            
            $db->commit();
            
            // Send real email to investor
            require_once __DIR__ . '/includes/mailer.php';
            sendRealEmail($pledge['investor_email'], "EIISS Platform - Refund Processed", $notifMsg, $userEmail);
            
            header("Location: dashboard.php?tab=2&refund_success=1");
            exit;
        } catch (Exception $ex) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Active Tab
$activeTab = isset($_GET['tab']) ? (int)$_GET['tab'] : 0;

// ─── ENTREPRENEUR DATA ───────────────────────────────────────────────
$myIdeas = [];
if ($role === 'entrepreneur') {
    $myIdeas = dbGetIdeasByEntrepreneur($userEmail);
}

// Compute stats
$totalIdeas       = count($myIdeas);
$activeIdeasCount = 0;
$totalViews       = 0;
$totalInterests   = 0;
$totalEarnings    = 0;

foreach ($myIdeas as $idea) {
    if ($idea['status'] === 'Active') $activeIdeasCount++;
    $totalViews     += (int)$idea['views'];
    $totalInterests += (int)$idea['interests'];
    $totalEarnings  += (float)$idea['earnings'];
}

// ─── INVESTOR DATA ───────────────────────────────────────────────────
$prefSectors      = [];
$prefMinROI       = 0;
$prefMaxInvestment = 1000000;

if ($role === 'investor') {
    $prefs             = dbGetInvestorPreferences($userEmail);
    $prefSectors       = $prefs['preferred_sectors'] ?? [];
    $prefMinROI        = (float)($prefs['min_roi'] ?? 0);
    $prefMaxInvestment = (float)($prefs['max_investment'] ?? 1000000);
}

// Portfolio stats for investor (computed from DB)
$invested    = 0;
$activeCount = 0;
$watching    = 0;
$avgROI      = 0;
$myTransactions = [];
$myUnlockedIdeas = [];
$myWatchlistIdeas = [];

if ($role === 'investor') {
    $allTx = getDB()->prepare("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE investor_email = ?");
    $allTx->execute([$userEmail]);
    $invested = (float)$allTx->fetch()['total'];

    // Decrypted/Unlocked ideas count
    $unlockStmt = getDB()->prepare("SELECT COUNT(DISTINCT idea_id) as cnt FROM unlocked_ideas WHERE investor_email = ? AND unlock_type IN ('access', 'attachments')");
    $unlockStmt->execute([$userEmail]);
    $activeCount = (int)$unlockStmt->fetch()['cnt'];

    // Average ROI of decrypted ideas in user's portfolio (deduplicated)
    $roiStmt = getDB()->prepare("
        SELECT AVG(expected_roi) as avg_roi
        FROM ideas
        WHERE id IN (
            SELECT DISTINCT idea_id 
            FROM unlocked_ideas 
            WHERE investor_email = ? AND unlock_type IN ('access', 'attachments')
        )
    ");
    $roiStmt->execute([$userEmail]);
    $avgROI = round((float)($roiStmt->fetch()['avg_roi'] ?? 0));

    // Watchlist count
    $watchStmt = getDB()->prepare("SELECT COUNT(idea_id) as cnt FROM unlocked_ideas WHERE investor_email = ? AND unlock_type = 'watchlist'");
    $watchStmt->execute([$userEmail]);
    $watching = (int)$watchStmt->fetch()['cnt'];

    // Fetch lists for interactive modals
    $txListStmt = getDB()->prepare("SELECT * FROM transactions WHERE investor_email = ? ORDER BY created_at DESC");
    $txListStmt->execute([$userEmail]);
    $myTransactions = $txListStmt->fetchAll();

    $unlockedListStmt = getDB()->prepare("
        SELECT i.id, i.title, i.sector, i.expected_roi, i.stage, u.created_at as unlocked_at
        FROM unlocked_ideas u
        JOIN ideas i ON u.idea_id = i.id
        WHERE u.investor_email = ? AND u.unlock_type IN ('access', 'attachments')
        GROUP BY i.id
        ORDER BY u.created_at DESC
    ");
    $unlockedListStmt->execute([$userEmail]);
    $myUnlockedIdeas = $unlockedListStmt->fetchAll();

    $watchlistListStmt = getDB()->prepare("
        SELECT i.id, i.title, i.sector, i.expected_roi, i.stage, u.created_at as watched_at
        FROM unlocked_ideas u
        JOIN ideas i ON u.idea_id = i.id
        WHERE u.investor_email = ? AND u.unlock_type = 'watchlist'
        ORDER BY u.created_at DESC
    ");
    $watchlistListStmt->execute([$userEmail]);
    $myWatchlistIdeas = $watchlistListStmt->fetchAll();
}
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full animate-fade-in">

    <?php if ($role !== 'admin' && empty($currentUser['id_document'])): ?>
        <div id="id-prompt-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl animate-fade-in p-6 text-center">
                <div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-100">
                    <i data-lucide="shield-alert" class="w-8 h-8"></i>
                </div>
                <h3 class="font-heading font-extrabold text-xl text-slate-800">Scanned ID Required</h3>
                <p class="text-sm text-slate-500 font-semibold mt-2.5 leading-relaxed">
                    Your account has been approved by the administrator. To secure your profile and unlock complete platform services, please upload a scanned copy of your selected ID type.
                </p>
                <div class="mt-6 flex flex-col gap-2">
                    <a href="settings.php?tab=1" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow-md shadow-blue-500/15 flex items-center justify-center gap-2 transition-all">
                        <i data-lucide="upload" class="w-4 h-4"></i> Go to Upload Section
                    </a>
                    <button onclick="document.getElementById('id-prompt-modal').classList.add('hidden')" class="w-full py-2.5 text-xs text-slate-400 hover:text-slate-600 font-bold transition-all">
                        Maybe Later
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= 1. ENTREPRENEUR WORKSPACE ================= -->
    <?php if ($role === 'entrepreneur'): ?>

        <!-- Header area -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="font-heading font-extrabold text-3xl text-slate-800">Entrepreneur Dashboard</h1>
                <p class="text-sm text-slate-500 font-medium">Notarize innovations, audit interests, and track passive earnings</p>
            </div>
            <a href="submit-idea.php" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md shadow-blue-500/10 flex items-center gap-2 transition-all">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Submit New Idea
            </a>
        </div>

        <!-- Dashboard Navigation Tabs -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-1.5 flex gap-2 mb-8 max-w-md shadow-sm">
            <a href="?tab=0" class="flex-1 py-2 text-center text-xs font-bold rounded-lg transition-all <?= $activeTab === 0 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>"><?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'My Ideas' : 'Mawazo Yangu' ?></a>
            <a href="?tab=1" class="flex-1 py-2 text-center text-xs font-bold rounded-lg transition-all <?= $activeTab === 1 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>"><?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'Earnings Overview' : 'Maelezo ya Mapato' ?></a>
            <a href="?tab=2" class="flex-1 py-2 text-center text-xs font-bold rounded-lg transition-all <?= $activeTab === 2 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>"><?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'Backers & Campaigns' : 'Waungaji Mkono & Kampeni' ?></a>
        </div>

        <!-- Tab 0: My Ideas list -->
        <?php if ($activeTab === 0): ?>

            <!-- USD to TSH Exchange rate Widget -->
            <div class="bg-gradient-to-br from-indigo-900 to-slate-900 text-white rounded-2xl border border-indigo-700/30 p-5 mb-8 relative overflow-hidden shadow-lg animate-fade-in flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-8 -mt-8 pointer-events-none"></div>
                <div class="z-10 text-left">
                    <h3 class="font-heading font-extrabold text-base flex items-center gap-1.5">
                        <i data-lucide="coins" class="w-5 h-5 text-amber-400"></i> Dynamic USD/TSH Currency Exchange Calculator
                    </h3>
                    <p class="text-xs text-slate-300 font-semibold mt-1">Platform conversion factor set by admin: 1 USD = <span class="font-black text-amber-400 font-heading"><?= number_format((float)dbGetSystemSetting('usd_to_tsh') ?: 2600) ?> TZS</span></p>
                </div>
                
                <div class="z-10 w-full md:w-auto flex flex-wrap items-center gap-3 bg-white/10 p-2.5 rounded-xl border border-white/10">
                    <div class="flex items-center gap-2">
                        <input type="number" id="calc-usd-input" placeholder="USD" class="w-24 px-2.5 py-1.5 rounded-lg bg-black/30 border border-white/20 text-white text-xs font-bold focus:outline-none focus:ring-1 focus:ring-amber-500 text-right" oninput="convertCurrencyWidget('usd')">
                        <span class="text-[10px] font-bold text-slate-300">USD</span>
                    </div>
                    <i data-lucide="arrow-right-left" class="w-4 h-4 text-slate-400"></i>
                    <div class="flex items-center gap-2">
                        <input type="number" id="calc-tsh-input" placeholder="TSH" class="w-32 px-2.5 py-1.5 rounded-lg bg-black/30 border border-white/20 text-white text-xs font-bold focus:outline-none focus:ring-1 focus:ring-amber-500 text-right" oninput="convertCurrencyWidget('tsh')">
                        <span class="text-[10px] font-bold text-slate-300">TZS</span>
                    </div>
                </div>
            </div>

            <!-- Dynamic Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <div onclick="openStatsModal('ideas')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-blue-200 transition-all cursor-pointer">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl w-fit mb-3"><i data-lucide="lightbulb" class="w-6 h-6"></i></div>
                    <p class="text-2xl font-heading font-black text-slate-800"><?= $totalIdeas ?></p>
                    <p class="text-xs font-semibold text-slate-400 mt-0.5">Total Submissions</p>
                </div>
                <div onclick="openStatsModal('active')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all cursor-pointer">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl w-fit mb-3"><i data-lucide="trending-up" class="w-6 h-6"></i></div>
                    <p class="text-2xl font-heading font-black text-slate-800"><?= $activeIdeasCount ?></p>
                    <p class="text-xs font-semibold text-slate-400 mt-0.5">Active Vetted Ideas</p>
                </div>
                <div onclick="openStatsModal('views')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-purple-200 transition-all cursor-pointer">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-xl w-fit mb-3"><i data-lucide="eye" class="w-6 h-6"></i></div>
                    <p class="text-2xl font-heading font-black text-slate-800"><?= $totalViews ?></p>
                    <p class="text-xs font-semibold text-slate-400 mt-0.5">Total Investor Views</p>
                </div>
                <div onclick="openStatsModal('interests')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-red-200 transition-all cursor-pointer">
                    <div class="p-3 bg-red-50 text-red-600 rounded-xl w-fit mb-3"><i data-lucide="heart" class="w-6 h-6"></i></div>
                    <p class="text-2xl font-heading font-black text-slate-800"><?= $totalInterests ?></p>
                    <p class="text-xs font-semibold text-slate-400 mt-0.5">Venture Interests</p>
                </div>
            </div>

            <!-- List Panel -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="font-heading font-bold text-lg text-slate-800">Your Innovations</h2>
                    <span class="text-xs font-bold text-slate-500"><?= count($myIdeas) ?> items</span>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php if (empty($myIdeas)): ?>
                        <div class="p-12 text-center text-slate-400 flex flex-col items-center">
                            <i data-lucide="folder-open" class="w-12 h-12 text-slate-300 mb-2"></i>
                            <p class="font-heading font-semibold text-slate-500">No ideas found</p>
                            <p class="text-xs text-slate-400 mt-1 max-w-[250px]">Click "Submit New Idea" to publish your first verified proposal.</p>
                        </div>
                    <?php else: foreach ($myIdeas as $idea): ?>
                        <div class="p-6 hover:bg-slate-50/40 transition-colors">
                            <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-heading font-extrabold text-lg text-slate-800 truncate"><?= e($idea['title']) ?></h3>
                                    <div class="flex gap-2 mt-2 flex-wrap">
                                        <span class="px-2.5 py-0.5 text-[11px] font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-full uppercase tracking-wider"><?= e($idea['sector']) ?></span>
                                        <span class="px-2.5 py-0.5 text-[11px] font-bold rounded-full border uppercase tracking-wider
                                            <?= $idea['status'] === 'Active' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : ($idea['status'] === 'In Negotiation' ? 'text-amber-600 bg-amber-50 border-amber-100' : 'text-slate-500 bg-slate-50 border-slate-100') ?>">
                                            <?= e($idea['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="idea-detail.php?id=<?= $idea['id'] ?>" class="px-4 py-2 border border-slate-200 hover:border-blue-200 text-slate-600 hover:text-blue-600 font-bold rounded-xl text-xs transition-all shadow-sm">
                                        View Details
                                    </a>
                                    <?php if ((float)$idea['funding_goal'] > 0): ?>
                                        <a href="idea-detail.php?id=<?= $idea['id'] ?>&tab=3" class="px-4 py-2 bg-blue-50 border border-blue-100 hover:bg-blue-100 text-blue-600 font-bold rounded-xl text-xs transition-all shadow-sm flex items-center gap-1">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Manage Campaign
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 py-4 border-y border-slate-100 mb-4 bg-slate-50/30 px-4 rounded-xl">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Evaluation Score</p>
                                    <p class="text-sm font-extrabold text-blue-600 mt-1"><?= $idea['score'] ?>/10</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Views</p>
                                    <p class="text-sm font-bold text-slate-700 mt-1"><?= $idea['views'] ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Interests</p>
                                    <p class="text-sm font-bold text-slate-700 mt-1"><?= $idea['interests'] ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Access Price</p>
                                    <p class="text-sm font-bold text-slate-700 mt-1"><?= $idea['access_type'] === 'free' ? 'Free' : formatCurrency($idea['access_price']) ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Accumulated Earnings</p>
                                    <p class="text-sm font-black text-emerald-600 mt-1"><?= formatCurrency($idea['earnings']) ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Submitted</p>
                                    <p class="text-sm font-bold text-slate-700 mt-1"><?= e($idea['submitted_date']) ?></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 text-xs text-slate-400 font-medium">
                                <i data-lucide="shield" class="w-4 h-4 text-emerald-500"></i>
                                <span>Blockchain notarized ledger hash:</span>
                                <span class="font-mono text-slate-500 bg-slate-100 px-2 py-0.5 rounded leading-none text-[10px] select-all truncate max-w-[200px] sm:max-w-none"><?= e($idea['blockchain_hash']) ?></span>
                            </div>

                            <!-- Crowdfunding or interest progress bar -->
                            <?php
                            $cGoal = (float)$idea['funding_goal'];
                            if ($cGoal > 0):
                                $cRaised = dbGetCampaignRaisedAmount($idea['id']);
                                $cPledges = dbGetCampaignPledges($idea['id']);
                                $cBackers = count($cPledges);
                                $cPercent = min(100, round(($cRaised / $cGoal) * 100));
                            ?>
                                <div class="mt-4 pt-4 border-t border-slate-100/85">
                                    <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                                        <span class="text-slate-500">Campaign Funding: <span class="font-bold text-blue-600"><?= $cPercent ?>%</span> raised</span>
                                        <span class="text-slate-700"><?= $cBackers ?> Backers</span>
                                    </div>
                                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200/50">
                                        <div class="h-full bg-gradient-to-r from-blue-500 to-emerald-500 rounded-full transition-all duration-300" style="width: <?= $cPercent ?>%"></div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2 text-[10px] text-slate-400 font-bold uppercase">
                                        <span>Goal: <?= formatCurrency($cGoal) ?></span>
                                        <span>Raised: <?= formatCurrency($cRaised) ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mt-4">
                                    <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                                        <span class="text-slate-500">Regional Venture Interest</span>
                                        <span class="text-slate-700"><?= $idea['interests'] ?> / 50 investors</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: <?= min(100, ((int)$idea['interests'] / 50) * 100) ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        <!-- Tab 1: Earnings Overview -->
        <?php elseif ($activeTab === 1): ?>
            <div class="space-y-6">
                <!-- Top Mini Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div onclick="openStatsModal('earnings')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all cursor-pointer">
                        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl w-fit mb-3"><i data-lucide="dollar-sign" class="w-6 h-6"></i></div>
                        <p class="text-2xl font-heading font-black text-slate-800"><?= formatCurrency($totalEarnings) ?></p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Total Lifetime Earnings</p>
                    </div>
                    <div onclick="openStatsModal('monthly')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-blue-200 transition-all cursor-pointer">
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl w-fit mb-3"><i data-lucide="trending-up" class="w-6 h-6"></i></div>
                        <p class="text-2xl font-heading font-black text-slate-800"><?= formatCurrency($totalEarnings * 0.27) ?></p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Earnings This Month</p>
                    </div>
                    <div onclick="openStatsModal('views')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-purple-200 transition-all cursor-pointer">
                        <div class="p-3 bg-purple-50 text-purple-600 rounded-xl w-fit mb-3"><i data-lucide="eye" class="w-6 h-6"></i></div>
                        <p class="text-2xl font-heading font-black text-slate-800"><?= $totalViews ?></p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Total Page Impressions</p>
                    </div>
                    <div onclick="openStatsModal('unlocks')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-orange-200 transition-all cursor-pointer">
                        <div class="p-3 bg-orange-50 text-orange-600 rounded-xl w-fit mb-3"><i data-lucide="lock" class="w-6 h-6"></i></div>
                        <p class="text-2xl font-heading font-black text-slate-800"><?= $totalEarnings > 0 ? floor($totalEarnings / 55) : 0 ?></p>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Paid Decryption Unlocks</p>
                    </div>
                </div>

                <!-- Graphs -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-heading font-bold text-lg text-slate-800">Monthly Earnings</h3>
                            <button onclick="alert('Export completed successfully!')" class="p-2 border border-slate-200 hover:border-slate-300 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
                                <i data-lucide="download" class="w-4 h-4"></i> Export
                            </button>
                        </div>
                        <div class="w-full h-[250px] relative">
                            <canvas id="monthlyEarningsChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                        <h3 class="font-heading font-bold text-lg text-slate-800 mb-4">Revenue Share by Concept</h3>
                        <div class="w-full h-[250px] relative flex justify-center">
                            <canvas id="revenuePieChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Transactions table -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="font-heading font-bold text-base text-slate-800">Recent Payment Records</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <?php
                        $myTransactions = dbGetTransactionsByEntrepreneur($userEmail);
                        if (empty($myTransactions)):
                        ?>
                            <div class="p-8 text-center text-slate-400">
                                <p class="font-heading font-semibold">No transactions recorded yet</p>
                                <p class="text-xs text-slate-400 mt-0.5">Earnings from paid unlocks will log here immediately.</p>
                            </div>
                        <?php else: foreach ($myTransactions as $tx): ?>
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50/20 transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-800"><?= e($tx['investor_name']) ?></p>
                                    <p class="text-xs font-medium text-slate-400 truncate max-w-[200px] sm:max-w-md"><?= e($tx['idea_title']) ?> &bull; <span class="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded font-semibold text-[10px]"><?= e($tx['type']) ?></span></p>
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

            <!-- Chart.js Init Scripts -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const ctxBar = document.getElementById('monthlyEarningsChart');
                    if (ctxBar) {
                        new Chart(ctxBar, {
                            type: 'bar',
                            data: {
                                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                                datasets: [{
                                    label: 'Earnings ($)',
                                    data: [<?= round($totalEarnings*0.10) ?>, <?= round($totalEarnings*0.15) ?>, <?= round($totalEarnings*0.22) ?>, <?= round($totalEarnings*0.26) ?>, <?= round($totalEarnings*0.27) ?>],
                                    backgroundColor: '#3b82f6',
                                    borderRadius: 8
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                        });
                    }

                    const ctxPie = document.getElementById('revenuePieChart');
                    if (ctxPie) {
                        const ideaData = <?php
                            $shares = [];
                            foreach ($myIdeas as $idea) {
                                if ((float)$idea['earnings'] > 0) {
                                    $shares[] = ['name' => $idea['title'], 'val' => (float)$idea['earnings']];
                                }
                            }
                            if (empty($shares)) $shares[] = ['name' => 'No earnings yet', 'val' => 1];
                            echo json_encode($shares);
                        ?>;
                        new Chart(ctxPie, {
                            type: 'doughnut',
                            data: {
                                labels: ideaData.map(i => i.name.substring(0, 20) + '...'),
                                datasets: [{ data: ideaData.map(i => i.val), backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'] }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
                        });
                    }
                });
            </script>
        <?php else: ?>
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden p-6 text-left">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h2 class="font-heading font-extrabold text-lg text-slate-800"><?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'Campaign Backers & Investment Registry' : 'Orodha ya Waungaji Mkono & Uwekezaji' ?></h2>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5"><?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'Track pledges, manage exit shares, and process simulated refunds' : 'Fuatilia ahadi, dhibiti hisa za kutoka, na fanya simulizi ya kurejesha fedha' ?></p>
                        </div>
                        <a href="export-report.php" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 transition-all shadow-md shadow-emerald-500/10">
                            <i data-lucide="download" class="w-4 h-4"></i> <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'Export Investor Report' : 'Pakua Ripoti ya Wawekezaji' ?>
                        </a>
                    </div>

                    <?php if (isset($_GET['refund_success'])): ?>
                        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex gap-3 text-emerald-800 text-xs font-semibold">
                            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
                            <p>Refund simulated successfully! The investor has been notified via email and system notification.</p>
                        </div>
                    <?php endif; ?>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs text-slate-600 font-semibold">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] text-slate-400 uppercase tracking-wider font-bold">
                                    <th class="px-6 py-3.5 text-left">Investor Name</th>
                                    <th class="px-6 py-3.5 text-left">Campaign Proposal</th>
                                    <th class="px-6 py-3.5 text-left">Tier Detail</th>
                                    <th class="px-6 py-3.5 text-right">Pledge Amount</th>
                                    <th class="px-6 py-3.5 text-right">Equity Share</th>
                                    <th class="px-6 py-3.5 text-left">Method</th>
                                    <th class="px-6 py-3.5 text-left">Date</th>
                                    <th class="px-6 py-3.5 text-center">Status</th>
                                    <th class="px-6 py-3.5 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php
                                $backersList = dbGetBackersForEntrepreneur($userEmail);
                                if (empty($backersList)):
                                ?>
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center text-slate-400 font-medium">
                                            <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                            <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'No backers have supported your campaigns yet.' : 'Hakuna waungaji mkono waliounga mkono kampeni zako bado.' ?>
                                        </td>
                                    </tr>
                                <?php else: foreach ($backersList as $b): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-800"><?= e($b['investor_name']) ?></div>
                                            <div class="text-[10px] text-slate-400 font-mono"><?= e($b['investor_email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 max-w-[150px] truncate"><?= e($b['idea_title']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200"><?= e($b['tier_title'] ?: 'Custom Contribution') ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-slate-800"><?= formatCurrency($b['amount']) ?></td>
                                        <td class="px-6 py-4 text-right text-emerald-600 font-bold"><?= $b['equity_pct'] > 0 ? $b['equity_pct'] . '%' : '-' ?></td>
                                        <td class="px-6 py-4 uppercase font-bold text-slate-500"><?= e($b['payment_method']) ?></td>
                                        <td class="px-6 py-4 text-slate-400 font-semibold"><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ((int)$b['refunded'] === 1): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-red-100 text-red-700">Refunded</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-100 text-emerald-700">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ((int)$b['refunded'] === 0): ?>
                                                <a href="?tab=2&action=refund&pledge_id=<?= $b['id'] ?>" onclick="return confirm('Simulate refunding this investment? This will reverse their transaction and update platform totals.')" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-[10px] font-black transition-all border border-red-100">
                                                    Refund
                                                </a>
                                            <?php else: ?>
                                                <span class="text-slate-300 font-bold text-[10px]">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <!-- ================= 2. INVESTOR WORKSPACE ================= -->
    <?php else: ?>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="font-heading font-extrabold text-3xl text-slate-800">Investor Workspace</h1>
                <p class="text-sm text-slate-500 font-medium">Vette blockchain-notarized concepts and fund regional startups</p>
            </div>
            <a href="preferences.php" class="px-5 py-3 border border-slate-200 hover:border-blue-200 hover:bg-blue-50 text-slate-600 hover:text-blue-600 font-bold rounded-xl shadow-sm flex items-center gap-2 transition-all">
                <i data-lucide="target" class="w-5 h-5"></i>
                Set Matching Preferences
            </a>
        </div>

        <!-- Portfolio Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div onclick="openInvestorStatsModal('invested')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-green-200 transition-all cursor-pointer">
                <div class="p-3 bg-green-50 text-green-600 rounded-xl w-fit mb-3"><i data-lucide="dollar-sign" class="w-6 h-6"></i></div>
                <p class="text-2xl font-heading font-black text-slate-800">$<?= number_format($invested) ?></p>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Total Invested Funds</p>
            </div>
            <div onclick="openInvestorStatsModal('active')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-blue-200 transition-all cursor-pointer">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl w-fit mb-3"><i data-lucide="trending-up" class="w-6 h-6"></i></div>
                <p class="text-2xl font-heading font-black text-slate-800"><?= $activeCount ?></p>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Active Vested Startups</p>
            </div>
            <div onclick="openInvestorStatsModal('roi')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-purple-200 transition-all cursor-pointer">
                <div class="p-3 bg-purple-50 text-purple-600 rounded-xl w-fit mb-3"><i data-lucide="target" class="w-6 h-6"></i></div>
                <p class="text-2xl font-heading font-black text-slate-800"><?= $avgROI ?>%</p>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Average Projected ROI</p>
            </div>
            <div onclick="openInvestorStatsModal('watchlist')" class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-yellow-200 transition-all cursor-pointer">
                <div class="p-3 bg-yellow-50 text-yellow-600 rounded-xl w-fit mb-3"><i data-lucide="star" class="w-6 h-6"></i></div>
                <p class="text-2xl font-heading font-black text-slate-800"><?= $watching ?></p>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Ideas on Watchlist</p>
            </div>
        </div>

        <!-- USD to TSH Exchange rate Widget -->
        <div class="bg-gradient-to-br from-indigo-900 to-slate-900 text-white rounded-2xl border border-indigo-700/30 p-5 mb-8 relative overflow-hidden shadow-lg animate-fade-in flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-8 -mt-8 pointer-events-none"></div>
            <div class="z-10 text-left">
                <h3 class="font-heading font-extrabold text-base flex items-center gap-1.5">
                    <i data-lucide="coins" class="w-5 h-5 text-amber-400"></i> Dynamic USD/TSH Currency Exchange Calculator
                </h3>
                <p class="text-xs text-slate-300 font-semibold mt-1">Platform conversion factor set by admin: 1 USD = <span class="font-black text-amber-400 font-heading"><?= number_format((float)dbGetSystemSetting('usd_to_tsh') ?: 2600) ?> TZS</span></p>
            </div>
            
            <div class="z-10 w-full md:w-auto flex flex-wrap items-center gap-3 bg-white/10 p-2.5 rounded-xl border border-white/10">
                <div class="flex items-center gap-2">
                    <input type="number" id="calc-usd-input-inv" placeholder="USD" class="w-24 px-2.5 py-1.5 rounded-lg bg-black/30 border border-white/20 text-white text-xs font-bold focus:outline-none focus:ring-1 focus:ring-amber-500 text-right" oninput="convertCurrencyWidgetInv('usd')">
                    <span class="text-[10px] font-bold text-slate-300">USD</span>
                </div>
                <i data-lucide="arrow-right-left" class="w-4 h-4 text-slate-400"></i>
                <div class="flex items-center gap-2">
                    <input type="number" id="calc-tsh-input-inv" placeholder="TSH" class="w-32 px-2.5 py-1.5 rounded-lg bg-black/30 border border-white/20 text-white text-xs font-bold focus:outline-none focus:ring-1 focus:ring-amber-500 text-right" oninput="convertCurrencyWidgetInv('tsh')">
                    <span class="text-[10px] font-bold text-slate-300">TZS</span>
                </div>
            </div>
        </div>

        <!-- Search and Filters Panel -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </div>
                    <input type="text" id="idea-search" placeholder="Search startup concepts..." class="block w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                </div>
                <div>
                    <select id="sector-filter" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="all">All Sectors</option>
                        <option value="technology">Technology</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="agriculture">Agriculture</option>
                        <option value="education">Education</option>
                        <option value="ecommerce">E-commerce</option>
                        <option value="fintech">FinTech</option>
                        <option value="manufacturing">Manufacturing</option>
                    </select>
                </div>
                <button onclick="toggleAuditFilters()" class="py-3 px-6 bg-slate-100 hover:bg-slate-200 font-bold text-slate-600 rounded-xl text-sm flex items-center justify-center gap-1.5 transition-all relative" id="audit-filter-btn">
                    <i data-lucide="filter" class="w-4 h-4"></i> Detailed Audit Filters
                    <span id="active-filter-badge" class="hidden ml-1 px-1.5 py-0.5 bg-blue-600 text-white text-[9px] font-black rounded-full leading-none">0</span>
                </button>
            </div>
        </div>

        <!-- ═══════ DETAILED AUDIT FILTER DRAWER ═══════ -->
        <div id="audit-filter-drawer" class="hidden mb-8 bg-white rounded-2xl border border-blue-200 shadow-lg p-6 animate-fade-in">
            <div class="flex justify-between items-center mb-5">
                <h3 class="font-heading font-bold text-base text-slate-800 flex items-center gap-2">
                    <i data-lucide="sliders-horizontal" class="w-5 h-5 text-blue-600"></i>
                    Advanced Audit Filters
                </h3>
                <div class="flex gap-2">
                    <button onclick="clearAllFilters()" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-red-600 border border-slate-200 hover:border-red-200 rounded-lg transition-all">Clear All</button>
                    <button onclick="toggleAuditFilters()" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 border border-slate-200 rounded-lg transition-all">Close</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Min Evaluation Score -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Min Evaluation Score</label>
                    <input type="range" id="filter-min-score" min="0" max="10" step="0.5" value="0" class="w-full accent-blue-600" oninput="updateFilterLabel(this, 'label-min-score', '/10')">
                    <div class="flex justify-between text-[10px] font-semibold text-slate-400 mt-1">
                        <span>0</span>
                        <span id="label-min-score" class="text-blue-600 font-bold">0/10</span>
                        <span>10</span>
                    </div>
                </div>

                <!-- Max Capital Required -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Max Capital Required</label>
                    <input type="range" id="filter-max-capital" min="0" max="500000" step="5000" value="500000" class="w-full accent-blue-600" oninput="updateFilterLabel(this, 'label-max-capital', '', '$', true)">
                    <div class="flex justify-between text-[10px] font-semibold text-slate-400 mt-1">
                        <span>$0</span>
                        <span id="label-max-capital" class="text-blue-600 font-bold">$500,000</span>
                        <span>$500k</span>
                    </div>
                </div>

                <!-- Min ROI -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Min Expected ROI</label>
                    <input type="range" id="filter-min-roi" min="0" max="300" step="10" value="0" class="w-full accent-blue-600" oninput="updateFilterLabel(this, 'label-min-roi', '%')">
                    <div class="flex justify-between text-[10px] font-semibold text-slate-400 mt-1">
                        <span>0%</span>
                        <span id="label-min-roi" class="text-blue-600 font-bold">0%</span>
                        <span>300%</span>
                    </div>
                </div>

                <!-- Sort By -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Sort Results By</label>
                    <select id="filter-sort" class="block w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="match">Match Score (High → Low)</option>
                        <option value="roi">Expected ROI (High → Low)</option>
                        <option value="capital">Capital Required (Low → High)</option>
                        <option value="score">Evaluation Score (High → Low)</option>
                        <option value="funded">Most Funded (Progress %)</option>
                        <option value="views">Trending (Views)</option>
                        <option value="deadline">Funding Deadline</option>
                    </select>
                </div>

                <!-- Region Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Region / Location (Tanzania)</label>
                    <select id="filter-region" class="block w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="all">All Regions</option>
                        <option value="dar es salaam, tanzania">Dar es Salaam</option>
                        <option value="dodoma, tanzania">Dodoma</option>
                        <option value="arusha, tanzania">Arusha</option>
                        <option value="mwanza, tanzania">Mwanza</option>
                        <option value="kilimanjaro, tanzania">Kilimanjaro</option>
                        <option value="mbeya, tanzania">Mbeya</option>
                        <option value="morogoro, tanzania">Morogoro</option>
                        <option value="tanga, tanzania">Tanga</option>
                        <option value="zanzibar, tanzania">Zanzibar</option>
                    </select>
                </div>

                <!-- Campaign Status Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Campaign Status</label>
                    <select id="filter-campaign-status" class="block w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="successful">Successful</option>
                        <option value="failed">Failed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-5 pt-5 border-t border-slate-100">
                <!-- Risk Level -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Risk Level</label>
                    <div class="flex gap-2 flex-wrap">
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-risk w-3 h-3 accent-blue-600" value="Low"> Low
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-risk w-3 h-3 accent-blue-600" value="Medium"> Medium
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-risk w-3 h-3 accent-blue-600" value="High"> High
                        </label>
                    </div>
                </div>

                <!-- Stage -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Development Stage</label>
                    <div class="flex gap-2 flex-wrap">
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-stage w-3 h-3 accent-blue-600" value="Concept"> Concept
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-stage w-3 h-3 accent-blue-600" value="Prototype"> Prototype
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-stage w-3 h-3 accent-blue-600" value="MVP Ready"> MVP
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-stage w-3 h-3 accent-blue-600" value="Beta Testing"> Beta
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="checkbox" class="filter-stage w-3 h-3 accent-blue-600" value="Launched"> Launched
                        </label>
                    </div>
                </div>

                    </div>
                </div>

                <!-- Access Type -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Access Type</label>
                    <div class="flex gap-2 flex-wrap">
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="radio" name="filter-access" class="w-3 h-3 accent-blue-600" value="all" checked> All
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="radio" name="filter-access" class="w-3 h-3 accent-blue-600" value="free"> Free
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="radio" name="filter-access" class="w-3 h-3 accent-blue-600" value="paid"> Paid
                        </label>
                    </div>
                </div>

                <!-- Campaign Type -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Campaign Type</label>
                    <div class="flex gap-2 flex-wrap">
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="radio" name="filter-campaign" class="w-3 h-3 accent-blue-600" value="all" checked> All
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="radio" name="filter-campaign" class="w-3 h-3 accent-blue-600" value="equity"> Equity
                        </label>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:text-blue-700 transition-all">
                            <input type="radio" name="filter-campaign" class="w-3 h-3 accent-blue-600" value="rewards"> Rewards
                        </label>
                    </div>
                </div>

                <!-- Campaign Funding Progress -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Funding Progress</label>
                    <select id="filter-progress" class="block w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="all">All Campaign Progress</option>
                        <option value="under25">Under 25% Funded</option>
                        <option value="25to75">25% - 75% Funded</option>
                        <option value="over75">Over 75% Funded</option>
                        <option value="funded">100%+ Completed</option>
                    </select>
                </div>
            </div>

            <!-- Apply button -->
            <div class="mt-5 pt-4 border-t border-slate-100 flex justify-end">
                <button onclick="applyAuditFilters()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 flex items-center gap-1.5 transition-all">
                    <i data-lucide="check" class="w-4 h-4"></i> Apply Filters
                </button>
            </div>
        </div>

        <!-- Recommendations Grid -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="font-heading font-bold text-lg text-slate-800">Matching Regional Innovations</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Vetted startup opportunities tailored to your investment target criteria</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-full">
                    <i data-lucide="cpu" class="w-3.5 h-3.5 animate-spin"></i>
                    AI MATCH ENABLED
                </span>
            </div>

            <div id="ideas-grid-container" class="divide-y divide-slate-100">
                <?php
                $allIdeas = dbGetAllIdeas();
                if (empty($allIdeas)):
                ?>
                    <div class="p-12 text-center text-slate-400 flex flex-col items-center">
                        <i data-lucide="inbox" class="w-12 h-12 text-slate-300 mb-2"></i>
                        <p class="font-heading font-semibold text-slate-500">No ideas available yet</p>
                        <p class="text-xs text-slate-400 mt-1">Entrepreneurs haven't submitted any ideas to the platform yet.</p>
                    </div>
                <?php else:
                foreach ($allIdeas as $idea):
                    // Dynamic AI Matching Score
                    $matchScore = 60;
                    if (!empty($prefSectors) && in_array(strtolower($idea['sector']), $prefSectors)) {
                        $matchScore += 15;
                    }
                    if ((float)$idea['expected_roi'] >= $prefMinROI) {
                        $matchScore += 10;
                    }
                    if ((float)$idea['capital_required'] <= $prefMaxInvestment) {
                        $matchScore += 5;
                    }
                    if (!empty($prefLocation) && strtolower($idea['covered_area']) === strtolower($prefLocation)) {
                        $matchScore += 10;
                    }

                    $unlocked = dbIsIdeaUnlocked($userEmail, (int)$idea['id'], 'access');

                    $goal = (float)($idea['funding_goal'] ?: $idea['capital_required']);
                    $raised = dbGetCampaignRaisedAmount($idea['id']);
                    $pctRaised = $goal > 0 ? min(100, round(($raised / $goal) * 100)) : 0;

                    $backerCount = count(dbGetCampaignPledges($idea['id']));
                    $interestCount = (int)$idea['interests'];
                    $entrepreneurUser = dbGetUserByEmail($idea['entrepreneur_email']);
                    $isVerifiedFounder = $entrepreneurUser && $entrepreneurUser['verified'];
                ?>
                    <div class="p-6 hover:bg-slate-50/40 transition-colors idea-card-item animate-fade-in"
                         data-title="<?= strtolower(e($idea['title'])) ?>"
                         data-sector="<?= strtolower(e($idea['sector'])) ?>"
                         data-score="<?= $idea['score'] ?>"
                         data-roi="<?= $idea['expected_roi'] ?>"
                         data-capital="<?= $idea['capital_required'] ?>"
                         data-risk="<?= e($idea['risk_level']) ?>"
                        data-stage="<?= e($idea['stage']) ?>"
                         data-access="<?= e($idea['access_type']) ?>"
                         data-campaign="<?= e($idea['campaign_type'] ?: 'rewards') ?>"
                         data-progress-pct="<?= $pctRaised ?>"
                         data-region="<?= strtolower(e($idea['covered_area'])) ?>"
                         data-campaign-status="<?= e($idea['campaign_status'] ?: 'Active') ?>"
                         data-views="<?= (int)$idea['views'] ?>"
                         data-deadline="<?= e($idea['funding_deadline'] ?: '2099-12-31') ?>"
                         data-match="<?= $matchScore ?>">

                        <?php
                        $sectorImages = [
                            'technology' => 'assets/feature_slide-1.jpg',
                            'healthcare' => 'assets/feature_slide-2.jpg',
                            'agriculture' => 'assets/feature_slide-3.jpg',
                            'education' => 'assets/feature_slide-4.jpg',
                            'ecommerce' => 'assets/feature_slide-5.jpg',
                            'fintech' => 'assets/feature_slide-6.jpg',
                            'manufacturing' => 'assets/core1.jpg'
                        ];
                        $cardImage = $sectorImages[strtolower($idea['sector'])] ?? 'assets/core2.jpg';
                        ?>
                        <!-- Card Header Image Decorator -->
                        <div class="w-full h-40 rounded-2xl overflow-hidden mb-4 relative shadow-sm border border-slate-100">
                            <img src="<?= $cardImage ?>" alt="Venture Cover Image" class="w-full h-full object-cover transform hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                            
                            <!-- Floating badging -->
                            <div class="absolute top-3 left-3 flex gap-1.5 flex-wrap z-10">
                                <span class="px-2 py-0.5 text-[9px] font-black text-white bg-blue-600 rounded-md uppercase tracking-wider">
                                    <?= $idea['campaign_type'] === 'equity' ? 'Equity' : 'Rewards' ?>
                                </span>
                                <?php if ($isVerifiedFounder): ?>
                                    <span class="px-2 py-0.5 text-[9px] font-black text-white bg-indigo-600 rounded-md uppercase tracking-wider flex items-center gap-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Verified
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="absolute bottom-3 left-3 right-3 flex justify-between items-center text-white z-10">
                                <span class="text-xs font-bold bg-black/40 backdrop-blur-md px-2 py-0.5 rounded border border-white/10 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-500 fill-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    <?= number_format(dbGetIdeaAvgRating($idea['id']), 1) ?>
                                </span>
                                <span class="text-[10px] font-black tracking-wider uppercase bg-black/40 backdrop-blur-md px-2 py-0.5 rounded border border-white/10">
                                    <?= e($idea['covered_area'] ?: 'Dar es Salaam') ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h3 class="font-heading font-extrabold text-lg text-slate-800 truncate"><?= e($idea['title']) ?></h3>
                                    <span class="px-2.5 py-0.5 text-[10px] font-black text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full tracking-wider"><?= $matchScore ?>% MATCH</span>
                                    
                                    <!-- Social Proof Badges -->
                                    <?php if ($backerCount >= 3 || $interestCount >= 5): ?>
                                        <span class="px-2 py-0.5 text-[9px] font-black bg-amber-500 text-white rounded-md tracking-wider uppercase badge-glow-trending">🔥 Trending</span>
                                    <?php endif; ?>
                                    <?php if ($interestCount >= 2): ?>
                                        <span class="px-2 py-0.5 text-[9px] font-black bg-purple-600 text-white rounded-md tracking-wider uppercase badge-glow-featured">⭐ VC Featured</span>
                                    <?php endif; ?>
                                    <?php if ($isVerifiedFounder): ?>
                                        <span class="px-2 py-0.5 text-[9px] font-black bg-blue-500 text-white rounded-md tracking-wider uppercase badge-glow-verified">✓ Verified</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex gap-2 mt-2 flex-wrap">
                                    <span class="px-2.5 py-0.5 text-[11px] font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-full uppercase tracking-wider"><?= e($idea['sector']) ?></span>
                                    <span class="px-2.5 py-0.5 text-[11px] font-bold text-slate-500 bg-slate-50 border border-slate-100 rounded-full uppercase tracking-wider"><?= e($idea['stage']) ?></span>
                                    <span class="px-2.5 py-0.5 text-[11px] font-bold border rounded-full uppercase tracking-wider
                                        <?= $idea['risk_level'] === 'Low' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : ($idea['risk_level'] === 'High' ? 'text-red-600 bg-red-50 border-red-100' : 'text-amber-600 bg-amber-50 border-amber-100') ?>">
                                        Risk: <?= e($idea['risk_level']) ?>
                                    </span>
                                    <?php if ($idea['access_type'] === 'free'): ?>
                                        <span class="px-2.5 py-0.5 text-[11px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full uppercase tracking-wider">Free Access</span>
                                    <?php elseif ($unlocked): ?>
                                        <span class="px-2.5 py-0.5 text-[11px] font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="unlock" class="w-3 h-3"></i> Unlocked
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 text-[11px] font-bold text-slate-500 bg-slate-100 border border-slate-200 rounded-full uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="lock" class="w-3 h-3"></i> <?= formatCurrency($idea['access_price']) ?> Decryption
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="idea-detail.php?id=<?= $idea['id'] ?>" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10">
                                View Proposal
                            </a>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 bg-slate-50/30 p-4 rounded-xl">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Idea Evaluation</p>
                                <p class="text-sm font-extrabold text-blue-600 mt-1"><?= $idea['score'] ?>/10</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Capital Target</p>
                                <p class="text-sm font-extrabold text-slate-700 mt-1"><?= formatCurrency($idea['capital_required']) ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Expected ROI</p>
                                <p class="text-sm font-extrabold text-emerald-600 mt-1"><?= $idea['expected_roi'] ?>%</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Impressions</p>
                                <p class="text-sm font-bold text-slate-700 mt-1"><?= $idea['views'] ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Interested VCs</p>
                                <p class="text-sm font-bold text-slate-700 mt-1"><?= $idea['interests'] ?></p>
                            </div>
                        </div>

                        <!-- Crowdfunding progress bar if goal is set -->
                        <?php if ((float)$idea['funding_goal'] > 0): ?>
                            <div class="mt-4 pt-3 border-t border-slate-100/80">
                                <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                    <span class="text-slate-500">Crowdfunding: <span class="font-bold text-blue-600"><?= $pctRaised ?>%</span> raised</span>
                                    <span class="text-slate-700 font-bold"><?= $backerCount ?> Backer<?= $backerCount !== 1 ? 's' : '' ?></span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200/30">
                                    <div class="h-full campaign-progress-fill rounded-full" style="width: <?= $pctRaised ?>%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1.5 text-[10px] text-slate-400 font-bold uppercase">
                                    <span>Goal: <?= formatCurrency($goal) ?></span>
                                    <span>Raised: <?= formatCurrency($raised) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- No Results Message (hidden by default) -->
            <div id="no-filter-results" class="p-12 text-center text-slate-400 hidden flex-col items-center">
                <i data-lucide="search-x" class="w-12 h-12 text-slate-300 mb-2"></i>
                <p class="font-heading font-semibold text-slate-500">No ideas match your filters</p>
                <p class="text-xs text-slate-400 mt-1">Try adjusting your audit filter criteria.</p>
            </div>
        </div>

        <!-- Filter + Search Script -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const search = document.getElementById('idea-search');
                const sectorFilter = document.getElementById('sector-filter');
                const cards = document.querySelectorAll('.idea-card-item');

                const filterItems = () => {
                    const query = search.value.trim().toLowerCase();
                    const sector = sectorFilter.value.toLowerCase();

                    cards.forEach(card => {
                        const title = card.getAttribute('data-title');
                        const cardSector = card.getAttribute('data-sector');
                        const matchesQuery = title.includes(query);
                        const matchesSector = (sector === 'all' || cardSector === sector);

                        if (matchesQuery && matchesSector) {
                            card.classList.remove('hidden');
                        } else {
                            card.classList.add('hidden');
                        }
                    });

                    checkNoResults();
                };

                search.addEventListener('input', filterItems);
                sectorFilter.addEventListener('change', filterItems);

                // Listen for changes in the audit filter drawer
                document.querySelectorAll('.filter-risk, .filter-stage, input[name="filter-access"], input[name="filter-campaign"]').forEach(el => {
                    el.addEventListener('change', () => updateFilterBadge());
                });
                document.getElementById('filter-progress').addEventListener('change', () => updateFilterBadge());
                document.querySelectorAll('#filter-min-score, #filter-max-capital, #filter-min-roi').forEach(el => {
                    el.addEventListener('input', () => updateFilterBadge());
                });
            });

            function toggleAuditFilters() {
                const drawer = document.getElementById('audit-filter-drawer');
                drawer.classList.toggle('hidden');
                lucide.createIcons();
            }

            function updateFilterLabel(el, labelId, suffix, prefix, format) {
                const label = document.getElementById(labelId);
                let val = parseFloat(el.value);
                if (format) {
                    // format as TSH and USD
                    const tshVal = val * 2600;
                    label.innerText = "Tsh. " + tshVal.toLocaleString() + "/= ($ " + val.toLocaleString() + ")";
                } else {
                    label.innerText = (prefix || '') + val + suffix;
                }
            }

            function countActiveFilters() {
                let count = 0;
                if (parseFloat(document.getElementById('filter-min-score').value) > 0) count++;
                if (parseFloat(document.getElementById('filter-max-capital').value) < 500000) count++;
                if (parseFloat(document.getElementById('filter-min-roi').value) > 0) count++;
                if (document.querySelectorAll('.filter-risk:checked').length > 0) count++;
                if (document.querySelectorAll('.filter-stage:checked').length > 0) count++;
                const accessVal = document.querySelector('input[name="filter-access"]:checked')?.value;
                if (accessVal && accessVal !== 'all') count++;
                const campaignVal = document.querySelector('input[name="filter-campaign"]:checked')?.value;
                if (campaignVal && campaignVal !== 'all') count++;
                const progressVal = document.getElementById('filter-progress').value;
                if (progressVal && progressVal !== 'all') count++;
                const regionVal = document.getElementById('filter-region').value;
                if (regionVal && regionVal !== 'all') count++;
                const statusVal = document.getElementById('filter-campaign-status').value;
                if (statusVal && statusVal !== 'all') count++;
                if (document.getElementById('filter-sort').value !== 'match') count++;
                return count;
            }

            function updateFilterBadge() {
                const count = countActiveFilters();
                const badge = document.getElementById('active-filter-badge');
                if (badge) {
                    if (count > 0) {
                        badge.classList.remove('hidden');
                        badge.innerText = count;
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            }

            function clearAllFilters() {
                document.getElementById('filter-min-score').value = 0;
                document.getElementById('filter-max-capital').value = 500000;
                document.getElementById('filter-min-roi').value = 0;
                document.getElementById('filter-sort').value = 'match';
                document.querySelectorAll('.filter-risk').forEach(c => c.checked = false);
                document.querySelectorAll('.filter-stage').forEach(c => c.checked = false);
                document.querySelector('input[name="filter-access"][value="all"]').checked = true;
                document.querySelector('input[name="filter-campaign"][value="all"]').checked = true;
                document.getElementById('filter-progress').value = 'all';
                document.getElementById('filter-region').value = 'all';
                document.getElementById('filter-campaign-status').value = 'all';

                // Reset labels
                document.getElementById('label-min-score').innerText = '0/10';
                document.getElementById('label-max-capital').innerText = '$500,000';
                document.getElementById('label-min-roi').innerText = '0%';

                updateFilterBadge();
                applyAuditFilters();
            }

            function applyAuditFilters() {
                const cards = document.querySelectorAll('.idea-card-item');
                const minScore   = parseFloat(document.getElementById('filter-min-score').value);
                const maxCapital = parseFloat(document.getElementById('filter-max-capital').value);
                const minROI     = parseFloat(document.getElementById('filter-min-roi').value);
                const sortBy     = document.getElementById('filter-sort').value;

                const checkedRisks  = [...document.querySelectorAll('.filter-risk:checked')].map(c => c.value);
                const checkedStages = [...document.querySelectorAll('.filter-stage:checked')].map(c => c.value);
                const accessFilter  = document.querySelector('input[name="filter-access"]:checked')?.value || 'all';
                const campaignFilter = document.querySelector('input[name="filter-campaign"]:checked')?.value || 'all';
                const progressFilter = document.getElementById('filter-progress').value || 'all';
                const regionFilter   = document.getElementById('filter-region').value || 'all';
                const statusFilter   = document.getElementById('filter-campaign-status').value || 'all';

                // Also respect search and sector
                const query  = document.getElementById('idea-search').value.trim().toLowerCase();
                const sector = document.getElementById('sector-filter').value.toLowerCase();

                let visibleCards = [];

                cards.forEach(card => {
                    const title      = card.getAttribute('data-title');
                    const cardSector = card.getAttribute('data-sector');
                    const score      = parseFloat(card.getAttribute('data-score'));
                    const roi        = parseFloat(card.getAttribute('data-roi'));
                    const capital    = parseFloat(card.getAttribute('data-capital'));
                    const risk       = card.getAttribute('data-risk');
                    const stage      = card.getAttribute('data-stage');
                    const access     = card.getAttribute('data-access');
                    const campaign   = card.getAttribute('data-campaign') || '';
                    const progressPct = parseFloat(card.getAttribute('data-progress-pct') || '0');
                    const region      = card.getAttribute('data-region') || '';
                    const campaignStatus = card.getAttribute('data-campaign-status')?.toLowerCase() || 'active';

                    let show = true;

                    if (!title.includes(query)) show = false;
                    if (sector !== 'all' && cardSector !== sector) show = false;
                    if (score < minScore) show = false;
                    if (capital > maxCapital) show = false;
                    if (roi < minROI) show = false;
                    if (checkedRisks.length > 0 && !checkedRisks.includes(risk)) show = false;
                    if (checkedStages.length > 0 && !checkedStages.includes(stage)) show = false;
                    if (accessFilter !== 'all' && access !== accessFilter) show = false;
                    
                    // Campaign type filter
                    if (campaignFilter !== 'all') {
                        if (campaignFilter === 'equity' && campaign !== 'equity') show = false;
                        if (campaignFilter === 'rewards' && campaign !== 'rewards') show = false;
                    }

                    // Progress pct filter
                    if (progressFilter !== 'all') {
                        if (progressFilter === 'under25' && (progressPct >= 25 || campaign === '')) show = false;
                        if (progressFilter === '25to75' && (progressPct < 25 || progressPct > 75 || campaign === '')) show = false;
                        if (progressFilter === 'over75' && (progressPct <= 75 || campaign === '')) show = false;
                        if (progressFilter === 'funded' && (progressPct < 100 || campaign === '')) show = false;
                    }

                    // Region filter
                    if (regionFilter !== 'all' && region !== regionFilter) show = false;

                    // Campaign Status filter
                    if (statusFilter !== 'all' && campaignStatus !== statusFilter) show = false;

                    if (show) {
                        card.classList.remove('hidden');
                        visibleCards.push(card);
                    } else {
                        card.classList.add('hidden');
                    }
                });

                // Sorting
                const container = document.getElementById('ideas-grid-container');
                visibleCards.sort((a, b) => {
                    if (sortBy === 'match') return parseFloat(b.dataset.match) - parseFloat(a.dataset.match);
                    if (sortBy === 'roi')   return parseFloat(b.dataset.roi) - parseFloat(a.dataset.roi);
                    if (sortBy === 'capital') return parseFloat(a.dataset.capital) - parseFloat(b.dataset.capital);
                    if (sortBy === 'score') return parseFloat(b.dataset.score) - parseFloat(a.dataset.score);
                    if (sortBy === 'funded') return parseFloat(b.dataset.progressPct) - parseFloat(a.dataset.progressPct);
                    if (sortBy === 'views') return parseFloat(b.dataset.views) - parseFloat(a.dataset.views);
                    if (sortBy === 'deadline') {
                        return new Date(a.dataset.deadline) - new Date(b.dataset.deadline);
                    }
                    return 0;
                });
                visibleCards.forEach(c => container.appendChild(c));

                updateFilterBadge();
                checkNoResults();
                lucide.createIcons();
            }

            function checkNoResults() {
                const cards = document.querySelectorAll('.idea-card-item');
                const visibleCount = [...cards].filter(c => !c.classList.contains('hidden')).length;
                const noResults = document.getElementById('no-filter-results');
                if (noResults) {
                    if (visibleCount === 0 && cards.length > 0) {
                        noResults.classList.remove('hidden');
                        noResults.classList.add('flex');
                    } else {
                        noResults.classList.add('hidden');
                        noResults.classList.remove('flex');
                    }
                }
            }
        </script>
    <?php endif; ?>
</main>

<!-- Stats Modal (Entrepreneur) -->
<div id="stats-modal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl animate-fade-in flex flex-col max-h-[85vh]">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-heading font-bold text-lg text-slate-800 modal-title">Statistical Breakdown</h3>
            <button onclick="closeStatsModal()" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-xl transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-5 overflow-y-auto divide-y divide-slate-100 modal-body"></div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 text-right">
            <button onclick="closeStatsModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs shadow-md transition-all">Close</button>
        </div>
    </div>
</div>

<script>
    // Pre-serialize PHP data to JSON for clean JS usage
    const _ideasData = <?php echo json_encode(array_map(function($idea) {
        return [
            'title'          => $idea['title'],
            'sector'         => $idea['sector'],
            'status'         => $idea['status'],
            'views'          => (int)$idea['views'],
            'interests'      => (int)$idea['interests'],
            'earnings'       => (float)$idea['earnings'],
            'submitted_date' => $idea['submitted_date']
        ];
    }, $myIdeas)); ?>;

    const _investorTxData = <?php echo json_encode(array_map(function($tx) {
        return [
            'idea_title'     => $tx['idea_title'],
            'amount'         => (float)$tx['amount'],
            'date'           => $tx['date'],
            'type'           => $tx['type'],
            'payment_method' => $tx['payment_method']
        ];
    }, $myTransactions)); ?>;

    const _investorUnlockedData = <?php echo json_encode(array_map(function($idea) {
        return [
            'id'           => (int)$idea['id'],
            'title'        => $idea['title'],
            'sector'       => $idea['sector'],
            'expected_roi' => (float)$idea['expected_roi'],
            'stage'        => $idea['stage'],
            'unlocked_at'  => $idea['unlocked_at']
        ];
    }, $myUnlockedIdeas)); ?>;

    const _investorWatchlistData = <?php echo json_encode(array_map(function($idea) {
        return [
            'id'           => (int)$idea['id'],
            'title'        => $idea['title'],
            'sector'       => $idea['sector'],
            'expected_roi' => (float)$idea['expected_roi'],
            'stage'        => $idea['stage'],
            'watched_at'   => $idea['watched_at']
        ];
    }, $myWatchlistIdeas)); ?>;

    function openInvestorStatsModal(type) {
        const modal = document.getElementById('stats-modal');
        const titleEl = modal ? modal.querySelector('.modal-title') : null;
        const bodyEl  = modal ? modal.querySelector('.modal-body') : null;
        if (!modal || !titleEl || !bodyEl) return;

        let title = '';
        let listHTML = '';

        if (type === 'invested') {
            title = 'Total Invested Funds';
            if (_investorTxData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No transaction history.</div>';
            } else {
                _investorTxData.forEach(tx => {
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <div>
                            <p class="text-sm font-bold text-slate-800">${_esc(tx.idea_title)}</p>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">${_esc(tx.date)} &bull; ${_esc(tx.type)} &bull; ${_esc(tx.payment_method)}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-600 font-black text-xs flex-shrink-0 ml-2">-$${tx.amount.toLocaleString()}</span>
                    </div>`;
                });
            }
        } else if (type === 'active') {
            title = 'Active Decrypted Startups';
            if (_investorUnlockedData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No startups decrypted yet.</div>';
            } else {
                _investorUnlockedData.forEach(i => {
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <div>
                            <a href="idea-detail.php?id=${i.id}" class="text-sm font-bold text-slate-800 hover:text-blue-600">${_esc(i.title)}</a>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">${_esc(i.sector)} &bull; ${_esc(i.stage)}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 font-extrabold text-xs ml-2 flex-shrink-0">ROI: ${i.expected_roi}%</span>
                    </div>`;
                });
            }
        } else if (type === 'roi') {
            title = 'Projected ROI Breakdown';
            if (_investorUnlockedData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No decrypted startups to calculate ROI.</div>';
            } else {
                let sum = 0;
                _investorUnlockedData.forEach(i => {
                    sum += i.expected_roi;
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <div>
                            <a href="idea-detail.php?id=${i.id}" class="text-sm font-bold text-slate-800 hover:text-blue-600">${_esc(i.title)}</a>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">${_esc(i.sector)}</p>
                        </div>
                        <span class="text-xs font-black text-emerald-600 ml-2">${i.expected_roi}%</span>
                    </div>`;
                });
                let avg = Math.round(sum / _investorUnlockedData.length);
                listHTML += `<div class="py-3 flex justify-between items-center font-bold text-slate-800 bg-slate-50 px-2 rounded-xl mt-2">
                    <span>Average ROI</span>
                    <span class="text-emerald-600">${avg}%</span>
                </div>`;
            }
        } else if (type === 'watchlist') {
            title = 'Ideas on Watchlist';
            if (_investorWatchlistData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No ideas added to watchlist.</div>';
            } else {
                _investorWatchlistData.forEach(i => {
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <div>
                            <a href="idea-detail.php?id=${i.id}" class="text-sm font-bold text-slate-800 hover:text-blue-600">${_esc(i.title)}</a>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">${_esc(i.sector)} &bull; ${_esc(i.stage)}</p>
                        </div>
                        <span class="text-xs font-black text-amber-500 ml-2">ROI: ${i.expected_roi}%</span>
                    </div>`;
                });
            }
        }

        titleEl.innerText = title;
        bodyEl.innerHTML = listHTML;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    <?php $myTransactions = dbGetTransactionsByEntrepreneur($userEmail); ?>
    const _txData = <?php echo json_encode(array_map(function($tx) {
        return [
            'investor_name'  => $tx['investor_name'],
            'idea_title'     => $tx['idea_title'],
            'amount'         => (float)$tx['amount'],
            'date'           => $tx['date'],
            'type'           => $tx['type'],
            'payment_method' => $tx['payment_method']
        ];
    }, $myTransactions)); ?>;

    function _esc(text) {
        if (text === null || text === undefined) return '';
        return String(text).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    function formatJSVal(usd) {
        const tsh = usd * 2600;
        return "Tsh. " + Number(tsh).toLocaleString() + "/= ($ " + Number(usd).toLocaleString() + ")";
    }

    function openStatsModal(type) {
        const modal = document.getElementById('stats-modal');
        const titleEl = modal ? modal.querySelector('.modal-title') : null;
        const bodyEl  = modal ? modal.querySelector('.modal-body') : null;
        if (!modal || !titleEl || !bodyEl) return;

        let title = '';
        let listHTML = '';

        if (type === 'ideas') {
            title = 'Submitted Concepts';
            if (_ideasData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No concepts submitted yet.</div>';
            } else {
                _ideasData.forEach(i => {
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <div>
                            <p class="text-sm font-bold text-slate-800">${_esc(i.title)}</p>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">${_esc(i.sector)} &bull; ${_esc(i.status)}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 font-extrabold text-xs ml-2 flex-shrink-0">${formatJSVal(i.earnings)}</span>
                    </div>`;
                });
            }

        } else if (type === 'active') {
            title = 'Vetted Active Concepts';
            const active = _ideasData.filter(i => i.status === 'Active');
            if (active.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No active vetted ideas yet. Ideas approved by admin appear here.</div>';
            } else {
                active.forEach(i => {
                    listHTML += `<div class="py-3 border-b border-slate-50 last:border-0">
                        <p class="text-sm font-bold text-slate-800">${_esc(i.title)}</p>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">${_esc(i.submitted_date)} &bull; Views: ${i.views}</p>
                    </div>`;
                });
            }

        } else if (type === 'views') {
            title = 'Total Investor Views Breakdown';
            if (_ideasData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No ideas to show impressions for.</div>';
            } else {
                _ideasData.forEach(i => {
                    const pct = Math.min(100, Math.round((i.views / 50) * 100));
                    listHTML += `<div class="py-3 border-b border-slate-50 last:border-0">
                        <div class="flex justify-between items-center mb-1.5">
                            <p class="text-sm font-bold text-slate-800">${_esc(i.title)}</p>
                            <span class="text-xs font-bold text-purple-600 flex-shrink-0 ml-2">${i.views} views</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full" style="width:${pct}%"></div>
                        </div>
                    </div>`;
                });
            }

        } else if (type === 'interests') {
            title = 'Venture Capital Interests';
            if (_ideasData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No interest data yet.</div>';
            } else {
                _ideasData.forEach(i => {
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <p class="text-sm font-bold text-slate-800">${_esc(i.title)}</p>
                        <span class="text-xs font-bold text-red-500 flex items-center gap-1 flex-shrink-0 ml-2">
                            &#9829; ${i.interests} interest${i.interests !== 1 ? 's' : ''}
                        </span>
                    </div>`;
                });
            }

        } else if (type === 'earnings') {
            title = 'Lifetime Earnings Breakdown';
            if (_ideasData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No earnings recorded yet.</div>';
            } else {
                _ideasData.forEach(i => {
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <p class="text-sm font-bold text-slate-800">${_esc(i.title)}</p>
                        <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-600 font-black text-xs flex-shrink-0 ml-2">${formatJSVal(i.earnings)}</span>
                    </div>`;
                });
            }

        } else if (type === 'monthly') {
            title = 'Earnings This Month';
            if (_ideasData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No earnings recorded yet.</div>';
            } else {
                _ideasData.forEach(i => {
                    const monthly = Math.round(i.earnings * 0.27);
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <p class="text-sm font-bold text-slate-800">${_esc(i.title)}</p>
                        <span class="px-2.5 py-1 rounded-xl bg-blue-50 text-blue-600 font-black text-xs flex-shrink-0 ml-2">${formatJSVal(monthly)}</span>
                    </div>`;
                });
            }

        } else if (type === 'unlocks') {
            title = 'Paid Decryption Unlocks';
            if (_txData.length === 0) {
                listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No decryption payments recorded yet.</div>';
            } else {
                _txData.forEach(tx => {
                    listHTML += `<div class="py-3 flex justify-between items-center border-b border-slate-50 last:border-0">
                        <div>
                            <p class="text-sm font-bold text-slate-800">${_esc(tx.investor_name)}</p>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">${_esc(tx.idea_title)} &bull; ${_esc(tx.payment_method)}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-600 font-black text-xs flex-shrink-0 ml-2">+${formatJSVal(tx.amount)}</span>
                    </div>`;
                });
            }
        }

        if (!listHTML) listHTML = '<div class="py-6 text-center text-slate-400 text-sm">No data available yet.</div>';

        titleEl.innerText = title;
        bodyEl.innerHTML = listHTML;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function closeStatsModal() {
        const modal = document.getElementById('stats-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function convertCurrencyWidget(source) {
        const rate = <?= (float)dbGetSystemSetting('usd_to_tsh') ?: 2600 ?>;
        const usdInput = document.getElementById('calc-usd-input');
        const tshInput = document.getElementById('calc-tsh-input');
        
        if (!usdInput || !tshInput) return;
        
        if (source === 'usd') {
            const val = parseFloat(usdInput.value);
            if (!isNaN(val)) {
                tshInput.value = Math.round(val * rate);
            } else {
                tshInput.value = '';
            }
        } else {
            const val = parseFloat(tshInput.value);
            if (!isNaN(val)) {
                usdInput.value = (val / rate).toFixed(2);
            } else {
                usdInput.value = '';
            }
        }
    }

    function convertCurrencyWidgetInv(source) {
        const rate = <?= (float)dbGetSystemSetting('usd_to_tsh') ?: 2600 ?>;
        const usdInput = document.getElementById('calc-usd-input-inv');
        const tshInput = document.getElementById('calc-tsh-input-inv');
        
        if (!usdInput || !tshInput) return;
        
        if (source === 'usd') {
            const val = parseFloat(usdInput.value);
            if (!isNaN(val)) {
                tshInput.value = Math.round(val * rate);
            } else {
                tshInput.value = '';
            }
        } else {
            const val = parseFloat(tshInput.value);
            if (!isNaN(val)) {
                usdInput.value = (val / rate).toFixed(2);
            } else {
                usdInput.value = '';
            }
        }
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
