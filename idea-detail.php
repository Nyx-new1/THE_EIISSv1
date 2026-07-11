<?php
require_once __DIR__ . '/config/session.php';

$loggedIn    = isset($_SESSION['user_role']);
$role        = $loggedIn ? $_SESSION['user_role']  : null;
$userEmail   = $loggedIn ? $_SESSION['user_email'] : null;

if (!$loggedIn) {
    header("Location: login.php");
    exit;
}

$ideaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$idea = dbGetIdeaById($ideaId);

if (!$idea) {
    require_once __DIR__ . '/includes/header.php';
    echo "<main class='flex-grow max-w-7xl mx-auto px-4 py-16 text-center'><h2 class='text-2xl font-bold text-slate-800'>Idea not found</h2><a href='dashboard.php' class='mt-4 inline-block text-blue-600 font-bold hover:underline'>Back to Dashboard</a></main>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ─── POST CAMPAIGN UPDATE HANDLER ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_campaign_update') {
    if ($role === 'entrepreneur' && $idea['entrepreneur_email'] === $userEmail) {
        $updateTitle = trim($_POST['update_title'] ?? '');
        $updateContent = trim($_POST['update_content'] ?? '');
        if (!empty($updateTitle) && !empty($updateContent)) {
            $db = getDB();
            $db->prepare("
                INSERT INTO campaign_updates (idea_id, title, content, created_at)
                VALUES (?, ?, ?, NOW())
            ")->execute([$ideaId, $updateTitle, $updateContent]);

            // Notify backers
            $pledges = dbGetCampaignPledges($ideaId);
            $backerEmails = array_unique(array_column($pledges, 'investor_email'));
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_email, type, title, message, sender)
                VALUES (?, 'info', 'Campaign Update Posted', ?, ?)
            ");
            foreach ($backerEmails as $bEmail) {
                $notifStmt->execute([
                    $bEmail,
                    "Entrepreneur posted a new campaign update: \"$updateTitle\" for startup \"{$idea['title']}\".",
                    $_SESSION['user_name'] ?? 'Entrepreneur'
                ]);
            }

            header("Location: idea-detail.php?id=$ideaId&tab=3");
            exit;
        }
    }
}

// ─── POST COMMENT HANDLER ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_comment') {
    $commentText = trim($_POST['comment_text'] ?? '');
    if (!empty($commentText)) {
        $db = getDB();
        $db->prepare("
            INSERT INTO campaign_comments (idea_id, user_email, user_name, text, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ")->execute([$ideaId, $userEmail, $_SESSION['user_name'] ?? 'User', $commentText]);

        if ($idea['entrepreneur_email'] !== $userEmail) {
            $db->prepare("
                INSERT INTO notifications (user_email, type, title, message, sender)
                VALUES (?, 'info', 'New Comment on Campaign', ?, ?)
            ")->execute([
                $idea['entrepreneur_email'],
                "Investor " . ($_SESSION['user_name'] ?? 'Investor') . " commented on your campaign \"" . $idea['title'] . "\".",
                $_SESSION['user_name'] ?? 'Investor'
            ]);
        }

        header("Location: idea-detail.php?id=$ideaId&tab=4");
        exit;
    }
}

// ─── POST RATING HANDLER ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_rating') {
    $ratingVal = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');
    if ($ratingVal >= 1 && $ratingVal <= 5) {
        $db = getDB();
        $checkStmt = $db->prepare("SELECT id FROM campaign_ratings WHERE idea_id = ? AND investor_email = ? LIMIT 1");
        $checkStmt->execute([$ideaId, $userEmail]);
        $existingRating = $checkStmt->fetch();

        if ($existingRating) {
            $db->prepare("
                UPDATE campaign_ratings 
                SET rating = ?, review = ?, created_at = NOW() 
                WHERE id = ?
            ")->execute([$ratingVal, $reviewText, $existingRating['id']]);
        } else {
            $db->prepare("
                INSERT INTO campaign_ratings (idea_id, investor_email, rating, review, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ")->execute([$ideaId, $userEmail, $ratingVal, $reviewText]);

            $db->prepare("
                INSERT INTO notifications (user_email, type, title, message, sender)
                VALUES (?, 'info', 'New Rating on Campaign', ?, ?)
            ")->execute([
                $idea['entrepreneur_email'],
                "Investor " . ($_SESSION['user_name'] ?? 'Investor') . " rated your campaign with $ratingVal stars.",
                $_SESSION['user_name'] ?? 'Investor'
            ]);
        }

        header("Location: idea-detail.php?id=$ideaId&tab=4");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

$entrepreneur = dbGetUserByEmail($idea['entrepreneur_email']);

$detailTab = isset($_GET['tab']) ? (int)$_GET['tab'] : 0;

// Check unlock states
$hasPaidAccess      = ($role === 'entrepreneur' || $idea['access_type'] === 'free');
$hasPaidAttachments = ($role === 'entrepreneur' || empty($idea['attachment_price']) || (float)$idea['attachment_price'] === 0.0);

// Check accredited investor status
$isAccredited = ($role === 'investor') ? (dbIsInvestorAccredited($userEmail, $ideaId) ? 1 : 0) : 1;

// Track whether investor has already expressed interest
$alreadyInterested = false;

if ($role === 'investor') {
    if (dbIsIdeaUnlocked($userEmail, $ideaId, 'access'))      $hasPaidAccess = true;
    if (dbIsIdeaUnlocked($userEmail, $ideaId, 'attachments'))  $hasPaidAttachments = true;

    // Check if they already expressed interest
    $intCheck = getDB()->prepare("
        SELECT id FROM unlocked_ideas
        WHERE investor_email = ? AND idea_id = ? AND unlock_type = 'interest'
        LIMIT 1
    ");
    $intCheck->execute([$userEmail, $ideaId]);
    $alreadyInterested = (bool)$intCheck->fetch();

    // Check if they already watchlisted this idea
    $watchCheck = getDB()->prepare("
        SELECT id FROM unlocked_ideas
        WHERE investor_email = ? AND idea_id = ? AND unlock_type = 'watchlist'
        LIMIT 1
    ");
    $watchCheck->execute([$userEmail, $ideaId]);
    $alreadyWatched = (bool)$watchCheck->fetch();

    // Increment view count (impression) once per investor
    $impCheck = getDB()->prepare("
        SELECT id FROM unlocked_ideas
        WHERE investor_email = ? AND idea_id = ? AND unlock_type = 'impression'
        LIMIT 1
    ");
    $impCheck->execute([$userEmail, $ideaId]);
    $alreadyImpressed = (bool)$impCheck->fetch();

    if (!$alreadyImpressed) {
        getDB()->prepare("
            INSERT IGNORE INTO unlocked_ideas (investor_email, idea_id, unlock_type)
            VALUES (?, ?, 'impression')
        ")->execute([$userEmail, $ideaId]);

        getDB()->prepare("UPDATE ideas SET views = views + 1 WHERE id = ?")->execute([$ideaId]);
    }
}

$watermarkClass = ($role === 'investor') ? 'watermarked-container' : '';
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full animate-fade-in">

    <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-700 uppercase tracking-wider mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
    </a>

    <!-- Detail Header -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-start gap-6">
        <div class="flex-1 min-w-0">
            <h1 class="font-heading font-extrabold text-3xl text-slate-800 leading-tight"><?= e($idea['title']) ?></h1>
            <div class="flex gap-2.5 mt-3 flex-wrap">
                <span class="px-2.5 py-0.5 text-[11px] font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-full uppercase tracking-wider"><?= e($idea['sector']) ?></span>
                <span class="px-2.5 py-0.5 text-[11px] font-bold text-slate-500 bg-slate-50 border border-slate-100 rounded-full uppercase tracking-wider"><?= e($idea['stage']) ?></span>
                <span class="px-2.5 py-0.5 text-[11px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full uppercase tracking-wider"><?= e($idea['status']) ?></span>
                <span class="px-2.5 py-0.5 text-[11px] font-bold border border-amber-100 text-amber-600 bg-amber-50 rounded-full uppercase tracking-wider">Risk: <?= e($idea['risk_level']) ?></span>
            </div>
            <div class="flex items-center gap-2 mt-4 text-xs text-slate-400 font-semibold">
                <i data-lucide="shield" class="w-4 h-4 text-emerald-500"></i>
                <span class="font-mono truncate max-w-[250px] sm:max-w-none">Blockchain Proof: <?= e($idea['blockchain_hash']) ?></span>
            </div>
        </div>
        <div class="flex items-start md:items-end flex-col gap-3 flex-shrink-0">
            <div class="flex items-center gap-2">
                <i data-lucide="award" class="w-7 h-7 text-amber-500"></i>
                <span class="text-3xl font-heading font-black text-blue-600 leading-none"><?= $idea['score'] ?></span>
                <span class="text-slate-400 font-bold text-lg">/10</span>
            </div>
            <?php if ($role === 'investor'): ?>
                <div class="flex flex-wrap gap-2">
                    <?php if ($alreadyWatched): ?>
                        <button onclick="toggleWatchlist(0)" id="watch-btn" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs shadow-md flex items-center gap-1.5 transition-all">
                            <i data-lucide="star-off" class="w-4 h-4"></i> Unwatch Idea
                        </button>
                    <?php else: ?>
                        <button onclick="toggleWatchlist(1)" id="watch-btn" class="px-5 py-2.5 border border-slate-200 hover:border-amber-300 hover:bg-amber-50 text-slate-600 hover:text-amber-600 font-bold rounded-xl text-xs shadow-sm bg-white flex items-center gap-1.5 transition-all">
                            <i data-lucide="star" class="w-4 h-4"></i> Watch Idea
                        </button>
                    <?php endif; ?>

                    <?php if ($alreadyInterested): ?>
                        <button id="interest-btn" disabled class="px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-xl text-xs shadow-md flex items-center gap-1.5 cursor-default opacity-90">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="vertical-align:middle"><polyline points="20 6 9 17 4 12"/></svg>
                            Interest Registered
                        </button>
                    <?php else: ?>
                        <button onclick="expressInterest()" id="interest-btn" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 flex items-center gap-1.5 transition-all">
                            <i data-lucide="heart" class="w-4 h-4"></i> Express Interest
                        </button>
                    <?php endif; ?>
                    <!-- Follow Campaign Button -->
                    <button onclick="toggleFollow()" id="follow-btn" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 <?= $isFollowing ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 bg-white' ?>">
                        <i data-lucide="<?= $isFollowing ? 'heart-off' : 'heart' ?>" class="w-4 h-4"></i>
                        <span id="follow-btn-text"><?= $isFollowing ? 'Unfollow Venture' : 'Follow Venture' ?></span>
                        <span id="follow-count-badge" class="ml-1 px-1.5 py-0.5 bg-indigo-500 text-white rounded text-[10px]"><?= $followersCount ?></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Crowdfunding calculations
    $goalAmount = (float)($idea['funding_goal'] ?: $idea['capital_required']);
    $raisedAmount = dbGetCampaignRaisedAmount($ideaId);
    $percentageRaised = $goalAmount > 0 ? min(100, round(($raisedAmount / $goalAmount) * 100)) : 0;
    $pledges = dbGetCampaignPledges($ideaId);
    $backersCount = count($pledges);

    // Deadline calculations
    $daysLeft = 30; // default fallback
    if (!empty($idea['funding_deadline'])) {
        $deadlineDate = new DateTime($idea['funding_deadline']);
        $nowDate = new DateTime();
        $interval = $nowDate->diff($deadlineDate);
        $daysLeft = $deadlineDate > $nowDate ? $interval->days : 0;
    }

    $updates = dbGetCampaignUpdates($ideaId);
    $updateCount = count($updates);

    $commentsList = dbGetCampaignComments($ideaId);
    $commentCount = count($commentsList);

    $ratingsList = dbGetCampaignRatings($ideaId);
    $ratingCount = count($ratingsList);
    $avgRating   = dbGetIdeaAvgRating($ideaId);

    $followersCount = dbGetFollowersCount($ideaId);
    $isFollowing = $loggedIn ? dbIsFollowing($userEmail, $ideaId) : false;

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

    <!-- Beautiful Cover Card Banner -->
    <div class="w-full h-64 rounded-3xl overflow-hidden mb-8 relative border border-slate-200 shadow-md">
        <img src="<?= $cardImage ?>" alt="Venture Cover Banner" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-900/20 to-transparent"></div>
        <div class="absolute bottom-6 left-6 right-6 text-white flex flex-col md:flex-row justify-between items-start md:items-end gap-4 z-10">
            <div class="text-left">
                <span class="px-2.5 py-0.5 text-[9px] font-black text-white bg-blue-600 rounded-md uppercase tracking-wider mb-2 inline-block">
                    <?= $idea['campaign_type'] === 'equity' ? 'Equity Funding' : 'Rewards Crowdfunding' ?>
                </span>
                <h1 class="text-2xl md:text-3xl font-heading font-black tracking-tight text-white"><?= e($idea['title']) ?></h1>
                <p class="text-xs text-slate-300 font-semibold mt-1 flex items-center gap-1.5">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-indigo-400"></i>
                    <?= e($idea['covered_area'] ?: 'Tanzania') ?> Region Coverage &bull; Stage: <?= e($idea['stage']) ?>
                </p>
            </div>
            
            <div class="flex items-center gap-2.5 bg-black/40 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/10">
                <div class="flex text-amber-400">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 <?= $star <= round($avgRating) ? 'fill-amber-400 text-amber-400' : 'text-slate-500' ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php endfor; ?>
                </div>
                <span class="text-sm font-heading font-black text-white"><?= number_format($avgRating, 1) ?></span>
                <span class="text-xs text-slate-400 font-semibold">(<?= $ratingCount ?>)</span>
            </div>
        </div>
    </div>

    <!-- Crowdfunding Dashboard Header -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm mb-8 animate-fade-in">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center md:text-left divide-y md:divide-y-0 md:divide-x divide-slate-100">
            <!-- Raised -->
            <div class="pb-4 md:pb-0">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Fund Raised</span>
                <span class="text-2xl font-heading font-black text-emerald-600 mt-1.5 inline-block"><?= formatCurrency($raisedAmount) ?></span>
                <span class="block text-[11px] text-slate-400 font-semibold mt-1">pledged of <?= formatCurrency($goalAmount) ?> goal</span>
            </div>
            <!-- Backers -->
            <div class="pt-4 md:pt-0 md:pl-6">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Backers</span>
                <span class="text-2xl font-heading font-black text-slate-800 mt-1.5 inline-block"><?= $backersCount ?></span>
                <span class="block text-[11px] text-slate-400 font-semibold mt-1">supportive backers active</span>
            </div>
            <!-- Days Left -->
            <div class="pt-4 md:pt-0 md:pl-6">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Time Remaining</span>
                <span class="text-2xl font-heading font-black text-slate-800 mt-1.5 inline-block"><?= $daysLeft ?></span>
                <span class="block text-[11px] text-slate-400 font-semibold mt-1">days left to fund campaign</span>
            </div>
            <!-- Progress bar & Type badge -->
            <div class="pt-4 md:pt-0 md:pl-6 flex flex-col justify-center">
                <div class="flex justify-between items-center text-xs font-bold mb-1.5 text-slate-500">
                    <span>Funding Goal Met</span>
                    <span class="text-blue-600"><?= $percentageRaised ?>%</span>
                </div>
                <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200/50">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-emerald-500 rounded-full transition-all duration-500" style="width: <?= $percentageRaised ?>%"></div>
                </div>
                <div class="flex justify-between items-center mt-2.5">
                    <span class="px-2 py-0.5 text-[9px] font-black uppercase text-blue-600 bg-blue-50 border border-blue-100 rounded-md">
                        <?= $idea['campaign_type'] === 'equity' ? 'Equity Investment' : 'Rewards Crowdfunding' ?>
                    </span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase truncate max-w-[120px]"><?= e($idea['covered_area'] ?: 'Tanzania') ?> Only</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Tabbed Left Column -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Tabs -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-1.5 flex gap-2 shadow-sm overflow-x-auto">
                <a href="?id=<?= $ideaId ?>&tab=0" class="flex-grow py-2 text-center text-xs font-bold rounded-lg transition-all whitespace-nowrap <?= $detailTab === 0 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Concept Overview</a>
                <a href="?id=<?= $ideaId ?>&tab=1" class="flex-grow py-2 text-center text-xs font-bold rounded-lg transition-all whitespace-nowrap <?= $detailTab === 1 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Financial Details</a>
                <a href="?id=<?= $ideaId ?>&tab=2" class="flex-grow py-2 text-center text-xs font-bold rounded-lg transition-all whitespace-nowrap <?= $detailTab === 2 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">AI Evaluation</a>
                <a href="?id=<?= $ideaId ?>&tab=3" class="flex-grow py-2 text-center text-xs font-bold rounded-lg transition-all whitespace-nowrap <?= $detailTab === 3 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Campaign Updates (<?= $updateCount ?>)</a>
                <a href="?id=<?= $ideaId ?>&tab=4" class="flex-grow py-2 text-center text-xs font-bold rounded-lg transition-all whitespace-nowrap <?= $detailTab === 4 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Comments & Ratings (<?= $commentCount ?>)</a>
            </div>

            <!-- TAB 0: CONCEPT OVERVIEW -->
            <?php if ($detailTab === 0): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3">Elevator Pitch</h3>
                    <p class="text-slate-600 text-sm leading-relaxed font-medium"><?= e($idea['description']) ?></p>
                </div>

                <?php if (!$hasPaidAccess): ?>
                    <div class="bg-gradient-to-r from-blue-500/5 to-purple-600/5 border-2 border-dashed border-blue-200 rounded-3xl p-10 text-center flex flex-col items-center animate-fade-in">
                        <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl mb-4">
                            <i data-lucide="lock" class="w-10 h-10"></i>
                        </div>
                        <h3 class="font-heading font-extrabold text-xl text-slate-800">Premium Proposal Locked</h3>
                        <p class="text-sm text-slate-500 font-medium max-w-sm mt-1.5 leading-relaxed">Unlock complete access to detailed business plans, structural problem statements, and comprehensive technical solutions.</p>
                        <div class="flex items-baseline gap-1 mt-6">
                            <span class="text-3xl font-heading font-black text-blue-600"><?= formatCurrency($idea['access_price']) ?></span>
                            <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">one-time unlock</span>
                        </div>
                        <button onclick="openPaymentModal('access', <?= $idea['access_price'] ?>)" class="mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow-md shadow-blue-500/15 flex items-center gap-2 transition-all">
                            <i data-lucide="unlock" class="w-4 h-4"></i> Decrypt Premium Details
                        </button>
                    </div>
                <?php else: ?>
                    <div class="space-y-6 relative <?= $watermarkClass ?>">
                        <?php if ($role === 'investor'): ?><div class="watermark-overlay"></div><?php endif; ?>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3">Problem Statement</h3>
                            <p class="text-slate-600 text-sm leading-relaxed font-medium"><?= e($idea['problem_statement']) ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3">Proposed Solution</h3>
                            <p class="text-slate-600 text-sm leading-relaxed font-medium"><?= e($idea['solution']) ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3">Addressable Target Market</h3>
                            <p class="text-slate-600 text-sm leading-relaxed font-medium"><?= e($idea['target_market']) ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3">Competitive Advantage</h3>
                            <p class="text-slate-600 text-sm leading-relaxed font-medium"><?= e($idea['competitive_advantage']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Supporting Documents -->
                <?php if (!empty($idea['attachments'])): ?>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                        <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i> Supporting Documents
                        </h3>
                        <?php if (!$hasPaidAttachments): ?>
                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 text-center flex flex-col items-center">
                                <i data-lucide="lock" class="w-8 h-8 text-slate-400 mb-2"></i>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Document download tier locked</p>
                                <button onclick="openPaymentModal('attachments', <?= $idea['attachment_price'] ?>)" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm flex items-center gap-1.5 transition-all">
                                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> Unlock Files (<?= formatCurrency($idea['attachment_price']) ?>)
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($idea['attachments'] as $file): ?>
                                    <div class="p-3 bg-slate-50 border border-slate-200/50 hover:border-slate-300 rounded-xl flex items-center justify-between gap-3 transition-all">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="p-2 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-slate-800 truncate"><?= e($file['name']) ?></p>
                                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5"><?= e($file['size']) ?></p>
                                            </div>
                                        </div>
                                        <button onclick="downloadSecureAttachment('<?= e($file['name']) ?>')" class="px-3.5 py-1.5 border border-slate-200 hover:border-blue-200 hover:bg-blue-50 text-slate-500 hover:text-blue-600 font-bold rounded-lg text-xs transition-all shadow-sm bg-white flex items-center gap-1">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i> Download
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Owner Verification Details Card (revealed when supporting files are unlocked) -->
                <?php if ($hasPaidAttachments && $entrepreneur && $role === 'investor'): ?>
                    <div class="bg-white p-6 rounded-2xl border border-emerald-200/80 shadow-sm relative overflow-hidden animate-fade-in mt-6">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-full -mr-8 -mt-8"></div>
                        <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600"></i> Owner Verification Details
                        </h3>
                        <div class="flex items-center gap-3.5 mb-4">
                            <?php if (!empty($entrepreneur['avatar']) && file_exists(__DIR__ . '/' . $entrepreneur['avatar'])): ?>
                                <img src="<?= e($entrepreneur['avatar']) ?>" class="w-12 h-12 rounded-full object-cover border border-slate-200 shadow-sm">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-full bg-emerald-600 text-white font-heading font-bold flex items-center justify-center border border-slate-200 shadow-sm">
                                    <?= strtoupper(substr($entrepreneur['name'] ?? 'U', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-sm font-bold text-slate-800"><?= e($entrepreneur['name']) ?></p>
                                <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider"><?= e($entrepreneur['organization'] ?: 'Independent Entrepreneur') ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold text-slate-600 bg-slate-50 p-3.5 rounded-xl">
                            <div>
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Email Contact</span>
                                <span class="text-slate-800 font-mono"><?= e($entrepreneur['email']) ?></span>
                            </div>
                            <div>
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Phone Number</span>
                                <span class="text-slate-800"><?= e($entrepreneur['phone_number'] ?: 'Not Provided') ?></span>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Government ID Verification Status</span>
                                <span class="inline-flex items-center gap-1 mt-1 text-[11px] font-bold <?= $entrepreneur['verified'] ? 'text-emerald-600' : 'text-amber-600' ?>">
                                    <i data-lucide="<?= $entrepreneur['verified'] ? 'verified' : 'clock' ?>" class="w-4 h-4"></i>
                                    <?= $entrepreneur['verified'] ? 'Admin Verified (Property Ownership Proof Confirmed)' : 'Pending Verification Vetting' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- TAB 1: FINANCIAL DETAILS -->
            <?php if ($detailTab === 1): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-6">Financial Overview</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="pb-4 border-b border-slate-100">
                            <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Required Capital</span>
                            <span class="text-2xl font-heading font-black text-slate-800 mt-1 inline-block"><?= formatCurrency($idea['capital_required']) ?></span>
                        </div>
                        <div class="pb-4 border-b border-slate-100">
                            <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Expected Venture ROI</span>
                            <span class="text-2xl font-heading font-black text-emerald-600 mt-1 inline-block"><?= $idea['expected_roi'] ?>%</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Break-even Timeline</span>
                            <span class="text-xl font-heading font-extrabold text-slate-700 mt-1 inline-block"><?= $idea['timeline'] ?> Months</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Project Team Size</span>
                            <span class="text-xl font-heading font-extrabold text-slate-700 mt-1 inline-block"><?= $idea['team_size'] ?> Specialists</span>
                        </div>
                    </div>
                </div>

                <?php if ($idea['campaign_type'] === 'equity'): ?>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                        <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="award" class="w-5 h-5 text-blue-600"></i> Equity Investment Terms
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs font-semibold text-slate-600">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Company Valuation</span>
                                <span class="text-xs font-heading font-black text-slate-800"><?= formatCurrency($idea['company_valuation'] ?: ($idea['capital_required'] * 10)) ?></span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Equity Offered</span>
                                <span class="text-xs font-heading font-black text-slate-800"><?= $idea['equity_offered'] ?>%</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Funding Round</span>
                                <span class="text-xs font-heading font-black text-blue-600 uppercase"><?= e($idea['funding_round'] ?: 'Seed') ?></span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Minimum Investment</span>
                                <span class="text-xs font-heading font-black text-slate-800"><?= formatCurrency($idea['min_investment'] ?: 1000000) ?></span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Maximum Investment</span>
                                <span class="text-xs font-heading font-black text-slate-800"><?= formatCurrency($idea['max_investment'] ?: 50000000) ?></span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Expected ROI</span>
                                <span class="text-xs font-heading font-black text-emerald-600"><?= $idea['expected_roi'] ?>%</span>
                            </div>
                            <div class="sm:col-span-2 md:col-span-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Exit Strategy</span>
                                <p class="text-slate-700 font-medium leading-relaxed"><?= e($idea['exit_strategy'] ?: 'To be negotiated with lead investors during seed round.') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4">Projected ROI Growth</h3>
                    <div class="w-full h-[280px]"><canvas id="roiTimelineChart"></canvas></div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center justify-between">
                        ROI Yield Calculator <i data-lucide="calculator" class="w-5 h-5 text-slate-400"></i>
                    </h3>
                    <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100/50 space-y-4">
                        <div>
                            <label class="flex justify-between items-center text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                Adjust Initial Investment
                                <span class="text-sm font-black text-blue-600 font-heading" id="calc-val-label">$<?= number_format($idea['capital_required']) ?></span>
                            </label>
                            <input type="range" id="calc-slider" min="1000" max="500000" step="5000" value="<?= $idea['capital_required'] ?>" class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-3 border-t border-blue-100">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Projected Revenue (3 Years)</span>
                                <span class="text-lg font-heading font-black text-emerald-600 mt-0.5 inline-block" id="calc-rev-label">-</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Net Yield Profit</span>
                                <span class="text-lg font-heading font-black text-emerald-600 mt-0.5 inline-block" id="calc-profit-label">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const ctxLine = document.getElementById('roiTimelineChart');
                    if (ctxLine) {
                        new Chart(ctxLine, {
                            type: 'line',
                            data: {
                                labels: ['Month 6','Month 12','Month 18','Month 24','Month 36'],
                                datasets: [{ label:'Projected ROI Net Return ($)',
                                    data: [-<?= $idea['capital_required']*0.3 ?>, <?= $idea['capital_required']*0.2 ?>, <?= $idea['capital_required']*0.73 ?>,
                                           <?= $idea['capital_required']*(1+$idea['expected_roi']/100-0.5) ?>, <?= $idea['capital_required']*(1+$idea['expected_roi']/100) ?>],
                                    borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,0.05)', fill:true, tension:0.3, borderWidth:2.5 }]
                            },
                            options: { responsive:true, maintainAspectRatio:false }
                        });
                    }
                    const slider = document.getElementById('calc-slider');
                    const valLabel = document.getElementById('calc-val-label');
                    const revLabel = document.getElementById('calc-rev-label');
                    const profitLabel = document.getElementById('calc-profit-label');
                    const roiRate = <?= $idea['expected_roi'] ?>;
                    const recalculate = () => {
                        const val = parseFloat(slider.value);
                        const totalReturn = val * (1 + roiRate/100);
                        valLabel.innerText = '$' + val.toLocaleString();
                        revLabel.innerText = '$' + Math.round(totalReturn).toLocaleString();
                        profitLabel.innerText = '$' + Math.round(totalReturn - val).toLocaleString();
                    };
                    slider.addEventListener('input', recalculate);
                    recalculate();
                });
                </script>
            <?php endif; ?>

            <!-- TAB 2: AI EVALUATION -->
            <?php if ($detailTab === 2): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-6">Evaluation dimension scores</h3>
                    <div class="w-full h-[250px]"><canvas id="scoreRadarChart"></canvas></div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4">Detailed Evaluation Matrix</h3>
                    <div class="space-y-4 text-sm font-medium">
                        <?php
                        $dimensions = [
                            ['Market Potential', $idea['scoreBreakdown']['market'], 'Estimates sizing of total addressable user base, validation metrics, and segment growth.'],
                            ['Innovation Quotient', $idea['scoreBreakdown']['innovation'], 'Rates uniqueness, competitive barrier height, and technology advantage.'],
                            ['Feasibility Strength', $idea['scoreBreakdown']['feasibility'], 'Reviews deployment timelines, staffing sizes, and resource access levels.'],
                            ['Financial Sustainability', $idea['scoreBreakdown']['financial'], 'Analyzes revenue streams stability, margins, and target timelines to break even.']
                        ];
                        foreach ($dimensions as $dim):
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-slate-700"><?= $dim[0] ?></span>
                                <span class="text-blue-600 font-extrabold font-heading text-base"><?= $dim[1] ?>/10</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600" style="width: <?= $dim[1] * 10 ?>%"></div>
                            </div>
                            <p class="text-xs text-slate-400 font-semibold mt-1"><?= $dim[2] ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- AI Copilot Pitch Audit Insights -->
                <div class="bg-white p-6 rounded-2xl border border-blue-200/80 shadow-sm relative overflow-hidden animate-fade-in">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/5 rounded-full -mr-8 -mt-8"></div>
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-5 h-5 text-blue-600"></i> AI Copilot Pitch Insights
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50/40 rounded-xl border border-blue-100 text-xs font-semibold text-slate-700">
                            <span class="block text-[10px] text-blue-600 font-extrabold uppercase tracking-wider mb-1.5">Executive Venture Summary</span>
                            <p class="leading-relaxed font-medium"><?= e($idea['ai_summary'] ?: 'AI analysis in progress. Our models are currently auditing the pitch deck parameters...') ?></p>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-xs font-semibold text-slate-700">
                            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1.5">Venture Risk Factors & Mitigation</span>
                            <p class="leading-relaxed font-medium whitespace-pre-line"><?= e($idea['ai_risk_analysis'] ?: "1. Market Access Risk: Regulatory dependencies in region.\n2. Team Execution risk: Local developer staffing capabilities.") ?></p>
                        </div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const ctxRadar = document.getElementById('scoreRadarChart');
                    if (ctxRadar) {
                        new Chart(ctxRadar, {
                            type: 'bar',
                            data: {
                                labels: ['Market','Innovation','Feasibility','Financials'],
                                datasets: [{ label:'Scores', data: [<?= $idea['scoreBreakdown']['market'] ?>,<?= $idea['scoreBreakdown']['innovation'] ?>,<?= $idea['scoreBreakdown']['feasibility'] ?>,<?= $idea['scoreBreakdown']['financial'] ?>],
                                    backgroundColor:'rgba(59,130,246,0.85)', borderColor:'#2563eb', borderRadius:8, borderWidth:1.5 }]
                            },
                            options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ min:0, max:10 } } }
                        });
                    }
                });
                </script>
            <?php endif; ?>

            <!-- TAB 3: CAMPAIGN UPDATES -->
            <?php if ($detailTab === 3): ?>
                <?php if ($role === 'entrepreneur' && $idea['entrepreneur_email'] === $userEmail): ?>
                    <!-- Post Update Form (only for Entrepreneur) -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm space-y-4 animate-fade-in">
                        <h3 class="font-heading font-extrabold text-base text-slate-800 flex items-center gap-1.5">
                            <i data-lucide="edit-3" class="w-5 h-5 text-blue-600"></i> Write a Campaign Update
                        </h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="post_campaign_update">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Update Title</label>
                                <input type="text" name="update_title" required placeholder="e.g. Prototype Testing Completed in Dodoma" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/50">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Update Message Details</label>
                                <textarea name="update_content" required rows="5" placeholder="Share your milestones, business updates, or progress with your backers..." class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/50"></textarea>
                            </div>
                            <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md transition-all flex items-center gap-1.5">
                                <i data-lucide="send" class="w-4 h-4"></i> Post Update & Broadcast
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php if (empty($updates)): ?>
                        <div class="bg-white p-12 rounded-2xl border border-slate-200/60 shadow-sm text-center text-slate-400 flex flex-col items-center">
                            <i data-lucide="megaphone" class="w-12 h-12 text-slate-300 mb-2"></i>
                            <h4 class="font-heading font-semibold text-slate-500">No Campaign Updates</h4>
                            <p class="text-xs text-slate-400 mt-1 max-w-xs">The entrepreneur hasn't posted any updates yet. Backers will be notified when milestones are published.</p>
                        </div>
                    <?php else: foreach ($updates as $up): ?>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm space-y-3 animate-fade-in text-left">
                            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                <h4 class="font-heading font-extrabold text-base text-slate-800"><?= e($up['title']) ?></h4>
                                <span class="text-xs text-slate-400 font-semibold"><?= date('F j, Y, g:i a', strtotime($up['created_at'])) ?></span>
                            </div>
                            <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line font-medium"><?= e($up['content']) ?></p>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            <?php endif; ?>

            <!-- TAB 4: COMMENTS & RATINGS -->
            <?php if ($detailTab === 4): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm space-y-6 text-left">
                    <!-- Rating and Score Summary -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 bg-slate-50 border border-slate-200/50 rounded-2xl gap-4">
                        <div>
                            <h3 class="font-heading font-extrabold text-base text-slate-800 flex items-center gap-1.5">
                                <i data-lucide="star" class="w-5 h-5 text-amber-500 fill-amber-500"></i> Venture Rating Summary
                            </h3>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5">Average rating based on investor feedback audits</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-3xl font-heading font-black text-slate-800"><?= number_format($avgRating, 1) ?></span>
                            <div class="flex flex-col">
                                <div class="flex text-amber-500">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <i data-lucide="star" class="w-3.5 h-3.5 <?= $star <= round($avgRating) ? 'fill-amber-500 text-amber-500' : 'text-slate-200' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5"><?= $ratingCount ?> evaluations</span>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Rating Form (Only for logged in Investors) -->
                    <?php if ($role === 'investor'): ?>
                        <div class="p-4 border border-blue-100 bg-blue-50/20 rounded-2xl text-left">
                            <h4 class="font-heading font-extrabold text-xs text-slate-800 uppercase tracking-wider mb-3">Audit & Rate Venture</h4>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="post_rating">
                                <div class="flex items-center gap-4">
                                    <span class="text-xs font-bold text-slate-500">Your Star Rating:</span>
                                    <div class="flex gap-1 star-rating-inputs">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <label class="cursor-pointer group">
                                                <input type="radio" name="rating" value="<?= $i ?>" required class="sr-only star-radio">
                                                <i data-lucide="star" class="w-6 h-6 text-slate-300 star-icon transition-colors"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Review Remarks / Audited Risk Opinion</label>
                                    <textarea name="review_text" rows="2" required placeholder="Provide analytical feedback about this proposal..." class="block w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500/20 focus:outline-none"></textarea>
                                </div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md transition-all">Submit Rating</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Comments and Discussion Section -->
                    <div class="space-y-4 pt-4 border-t">
                        <h3 class="font-heading font-extrabold text-base text-slate-800 flex items-center gap-2">
                            <i data-lucide="message-square" class="w-5 h-5 text-blue-600"></i> Discussion & Questions (<?= $commentCount ?>)
                        </h3>

                        <!-- Leave Comment Form -->
                        <form method="POST" class="flex gap-3 items-start">
                            <input type="hidden" name="action" value="post_comment">
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shadow-md">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="flex-grow space-y-2">
                                <textarea name="comment_text" rows="2" required placeholder="Ask a question or comment on this campaign..." class="block w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500/20 focus:outline-none text-slate-800"></textarea>
                                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs transition-all">Post Comment</button>
                            </div>
                        </form>

                        <!-- Comments List -->
                        <div class="space-y-4 mt-6">
                            <?php if (empty($commentsList)): ?>
                                <div class="py-8 text-center text-slate-400 text-xs font-semibold">No comments posted yet. Start the conversation!</div>
                            <?php else: foreach ($commentsList as $c): ?>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-left flex gap-3">
                                    <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-600 font-bold text-xs flex items-center justify-center border text-sm">
                                        <?= strtoupper(substr($c['user_name'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-800"><?= e($c['user_name']) ?></span>
                                            <span class="text-[9px] text-slate-400 font-semibold"><?= timeAgo($c['created_at']) ?></span>
                                        </div>
                                        <p class="text-xs text-slate-600 font-medium mt-1 leading-relaxed"><?= e($c['text']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- CROWDFUNDING PLEDGE / INVESTMENT TIERS -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm space-y-4">
                <h3 class="font-heading font-extrabold text-base text-slate-800 flex items-center gap-1.5">
                    <i data-lucide="rocket" class="w-5 h-5 text-blue-600"></i> Support Campaign
                </h3>
                <p class="text-xs text-slate-400 font-semibold leading-relaxed">Choose an option below to back this project and unlock full files.</p>
                
                <div class="space-y-4">
                    <?php
                    $campaignTiers = dbGetPledgeTiers($ideaId);
                    if (empty($campaignTiers)):
                    ?>
                        <!-- Default backer option -->
                        <div class="p-4 border border-slate-200/60 rounded-xl bg-slate-50/30 space-y-3">
                            <div>
                                <h4 class="font-bold text-xs text-slate-800">General Supporter</h4>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Flexible Campaign Backing</p>
                            </div>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed font-medium">Pledge general support to back this entrepreneur's vision.</p>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Min. Pledge</span>
                                <span class="text-sm font-heading font-black text-blue-600"><?= formatCurrency(10) ?></span>
                            </div>
                            <?php if ($role === 'investor'): ?>
                                <button onclick="handleTierBacking(null, 10)" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs shadow-sm flex items-center justify-center gap-1.5 transition-all">
                                    <i data-lucide="zap" class="w-3.5 h-3.5"></i> Back this Campaign
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: foreach ($campaignTiers as $tier): ?>
                        <div class="p-4 border border-slate-200/80 rounded-xl bg-white hover:border-blue-300 transition-all space-y-3 shadow-sm relative group text-left">
                            <div class="flex justify-between items-start">
                                <h4 class="font-bold text-xs text-slate-800 group-hover:text-blue-600 transition-colors"><?= e($tier['title']) ?></h4>
                                <?php if ((float)$tier['equity_pct'] > 0): ?>
                                    <span class="px-1.5 py-0.5 text-[9px] font-black bg-emerald-50 text-emerald-600 border border-emerald-100 rounded"><?= $tier['equity_pct'] ?>% Equity</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed"><?= e($tier['description']) ?></p>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Min. Pledge</span>
                                <span class="text-sm font-heading font-black text-blue-600"><?= formatCurrency($tier['amount']) ?></span>
                            </div>
                            <?php if (!empty($tier['delivery_date'])): ?>
                                <div class="text-[9px] text-slate-400 font-bold uppercase flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3"></i> Delivery: <?= e($tier['delivery_date']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($role === 'investor'): ?>
                                <button onclick="handleTierBacking(<?= $tier['id'] ?>, <?= $tier['amount'] ?>)" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs shadow-sm flex items-center justify-center gap-1.5 transition-all">
                                    <i data-lucide="zap" class="w-3.5 h-3.5"></i> <?= $idea['campaign_type'] === 'equity' ? 'Invest in this Tier' : 'Back this Tier' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4">Metadata Summary</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3.5 text-sm font-semibold text-slate-600">
                        <i data-lucide="eye" class="w-5 h-5 text-slate-400"></i>
                        <div class="flex-1">
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none">Views</span>
                            <span class="text-slate-800 mt-1 inline-block"><?= $idea['views'] ?> global clicks</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3.5 text-sm font-semibold text-slate-600">
                        <i data-lucide="heart" class="w-5 h-5 text-slate-400"></i>
                        <div class="flex-1">
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none">Investor Watch</span>
                            <span class="text-slate-800 mt-1 inline-block"><?= $idea['interests'] ?> firm interests</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3.5 text-sm font-semibold text-slate-600">
                        <i data-lucide="calendar" class="w-5 h-5 text-slate-400"></i>
                        <div class="flex-1">
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none">Registered Date</span>
                            <span class="text-slate-800 mt-1 inline-block"><?= e($idea['submitted_date']) ?></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3.5 text-sm font-semibold text-slate-600">
                        <i data-lucide="user" class="w-5 h-5 text-slate-400"></i>
                        <div class="flex-1">
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none">Notarized By</span>
                            <span class="text-slate-800 mt-1 inline-block"><?= e($idea['entrepreneur_email']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3">Venture Capital Interest</h3>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="trending-up" class="w-7 h-7 text-blue-600"></i>
                    </div>
                    <div>
                        <span class="text-3xl font-heading font-black text-blue-600 live-interest-count"><?= (int)$idea['interests'] ?></span>
                        <span class="text-slate-400 font-bold text-sm ml-1">investor<?= (int)$idea['interests'] !== 1 ? 's' : '' ?></span>
                        <p class="text-xs text-slate-400 font-semibold mt-0.5">have expressed active interest in this concept</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3 flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-500"></i> Blockchain Secured
                </h3>
                <div class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs leading-relaxed">
                    This business proposal possesses a SHA-256 cryptographic notarization stamp, ensuring proof of original ownership.
                    <div class="mt-3 p-2 bg-white rounded font-mono text-[9px] break-all border border-emerald-100 select-all"><?= e($idea['blockchain_hash']) ?></div>
                </div>
            </div>

            <?php if ($role === 'investor' && $idea['access_type'] !== 'free'): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                    <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center gap-1.5">
                        <i data-lucide="credit-card" class="w-5 h-5 text-blue-600"></i> Unlock Criteria
                    </h3>
                    <div class="space-y-3.5 text-xs font-semibold text-slate-500">
                        <div class="flex justify-between items-center py-1">
                            <span>Concept access:</span>
                            <span class="px-2 py-0.5 rounded <?= $hasPaidAccess ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-600' ?>"><?= $hasPaidAccess ? 'Unlocked' : formatCurrency($idea['access_price']) ?></span>
                        </div>
                        <?php if ((float)$idea['attachment_price'] > 0): ?>
                        <div class="flex justify-between items-center py-1 border-t border-slate-100">
                            <span>Attached files:</span>
                            <span class="px-2 py-0.5 rounded <?= $hasPaidAttachments ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-600' ?>"><?= $hasPaidAttachments ? 'Unlocked' : formatCurrency($idea['attachment_price']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-6 rounded-2xl shadow-md flex flex-col gap-3">
                    <h3 class="font-heading font-extrabold text-base">Venture Contact</h3>
                    <p class="text-xs text-blue-100/90 leading-relaxed font-medium">Ready to discuss seed terms? Dispatch an automated meeting proposal to the entrepreneur directly.</p>
                    <button onclick="requestMeeting()" class="w-full py-2.5 bg-white text-blue-600 hover:bg-slate-50 rounded-xl font-bold text-xs shadow-sm transition-all">Schedule Pitch Audit</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- ═══════ PAYMENT MODAL — 5 PAYMENT METHODS ═══════ -->
<div id="payment-modal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl animate-fade-in flex flex-col overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-heading font-bold text-base text-slate-800 flex items-center gap-1.5">
                <i data-lucide="lock" class="w-5 h-5 text-blue-600 animate-pulse"></i> Secure SSL Encrypted Gateway
            </h3>
            <button onclick="closePaymentModal()" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-xl transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-5 overflow-y-auto max-h-[70vh]">
            <div id="payment-success-screen" class="text-center py-6 hidden space-y-3">
                <div class="p-4 bg-emerald-50 text-emerald-600 rounded-full w-fit mx-auto"><i data-lucide="check-circle" class="w-12 h-12"></i></div>
                <h3 class="font-heading font-extrabold text-xl text-slate-800">Unlocking Completed!</h3>
                <p class="text-xs text-slate-400 font-semibold">Payment decrypted successfully. Reloading workspace details...</p>
            </div>

            <div id="payment-form-screen" class="space-y-4">
                <!-- Error Block -->
                <div id="payment-error-block" class="bg-red-50 border border-red-100 text-red-700 text-xs font-semibold p-3.5 rounded-xl hidden flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5"></i>
                    <span id="payment-error-msg"></span>
                </div>
                <!-- Summary -->
                <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 flex justify-between items-center text-sm">
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Pay Access Fee</span>
                        <span class="font-bold text-slate-800 truncate block max-w-[200px]"><?= e($idea['title']) ?></span>
                    </div>
                    <span class="text-2xl font-heading font-black text-blue-600" id="payment-amount-label">$0</span>
                </div>

                <!-- Payment Provider Selection — 5 Methods -->
                <div class="space-y-2.5">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Choose Payment Provider</span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs font-bold text-slate-600">
                        <label onclick="selectPaymentMethod('card')" id="pay-card" class="p-3 border-2 rounded-xl flex items-center justify-between cursor-pointer border-blue-600 bg-blue-50/20">
                            <span>Visa / Card</span>
                            <i data-lucide="credit-card" class="w-4 h-4 text-blue-600"></i>
                        </label>
                        <label onclick="selectPaymentMethod('mpesa')" id="pay-mpesa" class="p-3 border-2 rounded-xl flex items-center justify-between cursor-pointer border-slate-200 hover:border-blue-300 transition-all">
                            <span>M-Pesa</span>
                            <i data-lucide="smartphone" class="w-4 h-4 text-slate-400"></i>
                        </label>
                        <label onclick="selectPaymentMethod('mixx')" id="pay-mixx" class="p-3 border-2 rounded-xl flex items-center justify-between cursor-pointer border-slate-200 hover:border-blue-300 transition-all">
                            <span>Mixx by Yas</span>
                            <i data-lucide="wallet" class="w-4 h-4 text-slate-400"></i>
                        </label>
                        <label onclick="selectPaymentMethod('airtel')" id="pay-airtel" class="p-3 border-2 rounded-xl flex items-center justify-between cursor-pointer border-slate-200 hover:border-blue-300 transition-all">
                            <span>Airtel Money</span>
                            <i data-lucide="phone" class="w-4 h-4 text-slate-400"></i>
                        </label>
                        <label onclick="selectPaymentMethod('halopesa')" id="pay-halopesa" class="p-3 border-2 rounded-xl flex items-center justify-between cursor-pointer border-slate-200 hover:border-blue-300 transition-all">
                            <span>HaloPesa</span>
                            <i data-lucide="banknote" class="w-4 h-4 text-slate-400"></i>
                        </label>
                    </div>
                </div>

                <!-- CARD Fields -->
                <div id="card-fields-block" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Card Number</label>
                        <input type="text" id="card-number-input" placeholder="4000 1234 5678 9010" class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/20">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Expiry Date</label>
                            <input type="text" placeholder="MM/YY" class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/20">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">CVV Code</label>
                            <input type="password" placeholder="***" class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/20">
                        </div>
                    </div>
                </div>

                <!-- MOBILE MONEY Fields (M-Pesa, Mixx, Airtel, HaloPesa) -->
                <div id="mobile-money-fields-block" class="space-y-3 hidden">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5" id="mobile-number-label">Mobile Number</label>
                        <input type="text" id="mobile-number-input" placeholder="+255 756 123 456" class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/20">
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border text-[11px] text-slate-500 font-semibold leading-relaxed" id="mobile-money-instruction">
                        A verification PIN request prompt will be sent to your mobile number. Authorize it by entering your secure PIN code.
                    </div>
                </div>

                <button type="button" id="pay-submit-btn" onclick="submitUnlockPayment()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md flex items-center justify-center gap-1.5 mt-6 transition-all">
                    <i data-lucide="shield-check" class="w-4 h-4"></i> Complete Unlock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════ INVESTOR COMPLIANCE MODAL ═══════ -->
<div id="compliance-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl animate-fade-in flex flex-col overflow-hidden text-slate-800">
        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-heading font-bold text-base text-slate-800 flex items-center gap-1.5">
                <i data-lucide="shield-alert" class="w-5 h-5 text-blue-600"></i> Investor Vetting & Compliance
            </h3>
            <button onclick="closeComplianceModal()" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-xl transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="compliance-form" onsubmit="submitCompliance(event)" class="p-5 space-y-4">
            <p class="text-xs text-slate-500 font-medium">To participate in equity crowdfunding under Tanzanian investor guidelines, please verify your accredited status and review risk terms.</p>
            
            <div class="space-y-3 pt-2">
                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer bg-slate-50/50 hover:bg-slate-50 text-xs font-semibold text-slate-600">
                    <input type="checkbox" required class="mt-0.5 accent-blue-600">
                    <span>I verify that I understand early-stage startup investments in Tanzania are risky and may result in partial or total loss of capital.</span>
                </label>
                <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer bg-slate-50/50 hover:bg-slate-50 text-xs font-semibold text-slate-600">
                    <input type="checkbox" required class="mt-0.5 accent-blue-600">
                    <span>I agree to the equity investment conditions, offering terms, and platform compliance guidelines.</span>
                </label>
            </div>

            <div>
                <label for="comp-signature" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Electronic Signature (Type Full Name)</label>
                <input type="text" id="comp-signature" required placeholder="e.g. <?= e($_SESSION['user_name'] ?? '') ?>" class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/20">
            </div>

            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md transition-all flex items-center justify-center gap-1.5">
                <i data-lucide="signature" class="w-4 h-4"></i> Sign & Acknowledge Terms
            </button>
        </form>
    </div>
</div>

<!-- Snackbar -->
<div id="snackbar-popup" class="fixed bottom-6 left-6 z-50 bg-slate-800 text-white px-5 py-3 rounded-xl shadow-xl flex items-center gap-2 text-xs font-semibold hidden animate-fade-in">
    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
    <span class="snackbar-msg">Interest registered!</span>
</div>

<script>
    let isAccredited = <?= $isAccredited ?>;
    let complianceTargetTierId = null;
    let complianceTargetAmount = 0;

    function openComplianceModal(tierId, amount) {
        complianceTargetTierId = tierId;
        complianceTargetAmount = amount;
        const modal = document.getElementById('compliance-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function closeComplianceModal() {
        const modal = document.getElementById('compliance-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function submitCompliance(e) {
        e.preventDefault();
        const signature = document.getElementById('comp-signature').value.trim();
        if (!signature) return;

        const fd = new FormData();
        fd.append('idea_id', <?= $ideaId ?>);
        fd.append('signature', signature);

        fetch('api/compliance-handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    isAccredited = 1;
                    closeComplianceModal();
                    showSnackbar('Accredited investor compliance signed successfully!');
                    openPaymentModal('pledge', complianceTargetAmount, complianceTargetTierId);
                } else {
                    alert('Could not sign compliance: ' + data.message);
                }
            });
    }

    function handleTierBacking(tierId, amount) {
        <?php if ($idea['campaign_type'] === 'equity'): ?>
            if (isAccredited === 0) {
                openComplianceModal(tierId, amount);
            } else {
                openPaymentModal('pledge', amount, tierId);
            }
        <?php else: ?>
            openPaymentModal('pledge', amount, tierId);
        <?php endif; ?>
    }

    let activePaymentType = 'access';
    let activePaymentAmount = 0;
    let selectedMethod = 'card';
    let selectedTierId = null;

    const allPayBtns = ['pay-card','pay-mpesa','pay-mixx','pay-airtel','pay-halopesa'];
    const mobileMoneyMethods = {
        mpesa:    { label: 'M-Pesa Mobile Number',    instruction: 'A verification PIN request prompt will be sent to your M-Pesa registered mobile number. Authorize it by entering your secure M-Pesa PIN code.' },
        mixx:     { label: 'Mixx by Yas Number',      instruction: 'You will receive a USSD push notification on your Mixx by Yas wallet. Enter your Mixx PIN to authorize the payment.' },
        airtel:   { label: 'Airtel Money Number',     instruction: 'An Airtel Money push request will be sent to your registered number. Dial *150*60# to confirm the transaction with your Airtel PIN.' },
        halopesa: { label: 'HaloPesa Mobile Number',  instruction: 'A HaloPesa payment request will be delivered to your Halotel number. Enter your HaloPesa PIN when prompted to authorize.' }
    };

    function selectPaymentMethod(method) {
        const errBlock = document.getElementById('payment-error-block');
        if (errBlock) errBlock.classList.add('hidden');

        selectedMethod = method;
        const fieldsCard   = document.getElementById('card-fields-block');
        const fieldsMobile = document.getElementById('mobile-money-fields-block');

        allPayBtns.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.className = "p-3 border-2 rounded-xl flex items-center justify-between cursor-pointer border-slate-200 hover:border-blue-300 transition-all";
        });
        const activeId = method === 'card' ? 'pay-card' : 'pay-' + method;
        const activeEl = document.getElementById(activeId);
        if (activeEl) activeEl.className = "p-3 border-2 rounded-xl flex items-center justify-between cursor-pointer border-blue-600 bg-blue-50/20";

        if (method === 'card') {
            fieldsCard.classList.remove('hidden');
            fieldsMobile.classList.add('hidden');
        } else {
            fieldsCard.classList.add('hidden');
            fieldsMobile.classList.remove('hidden');
            const config = mobileMoneyMethods[method];
            if (config) {
                document.getElementById('mobile-number-label').innerText = config.label;
                document.getElementById('mobile-money-instruction').innerText = config.instruction;
            }
        }
    }

    function openPaymentModal(type, cost, tierId = null) {
        const errBlock = document.getElementById('payment-error-block');
        if (errBlock) errBlock.classList.add('hidden');

        activePaymentType = type;
        activePaymentAmount = cost;
        selectedTierId = tierId;
        document.getElementById('payment-amount-label').innerText = formatJSVal(cost);
        const modal = document.getElementById('payment-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function closePaymentModal() {
        const modal = document.getElementById('payment-modal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    }

    function submitUnlockPayment() {
        const errBlock = document.getElementById('payment-error-block');
        const errMsg = document.getElementById('payment-error-msg');
        if (errBlock) {
            errBlock.classList.add('hidden');
            errMsg.innerText = '';
        }

        if (selectedMethod === 'card') {
            const cardInput = document.getElementById('card-number-input');
            const cardNum = cardInput ? cardInput.value.replace(/\s+/g, '') : '';
            if (!/^\d{13,19}$/.test(cardNum)) {
                if (errBlock) {
                    errMsg.innerText = 'Card number must be between 13 and 19 digits long.';
                    errBlock.classList.remove('hidden');
                    lucide.createIcons();
                }
                return;
            }
        } else {
            const mobInput = document.getElementById('mobile-number-input');
            const mobNum = mobInput ? mobInput.value.replace(/[\s+-]+/g, '') : '';
            if (!/^\d{9,13}$/.test(mobNum)) {
                if (errBlock) {
                    errMsg.innerText = 'Mobile Money number must be between 9 and 13 digits long.';
                    errBlock.classList.remove('hidden');
                    lucide.createIcons();
                }
                return;
            }
        }

        const submitBtn = document.getElementById('pay-submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span> Processing securely...';

        const formData = new FormData();
        formData.append('idea_id', <?= $ideaId ?>);
        formData.append('amount', activePaymentAmount);
        formData.append('purchase_type', activePaymentType);
        formData.append('payment_method', selectedMethod);
        if (selectedTierId) {
            formData.append('tier_id', selectedTierId);
        }

        fetch('api/payment-handler.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('payment-form-screen').classList.add('hidden');
                document.getElementById('payment-success-screen').classList.remove('hidden');
                lucide.createIcons();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert('Payment processing failed: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="shield-check" class="w-4 h-4"></i> Complete Unlock';
                lucide.createIcons();
            }
        });
    }

    function downloadSecureAttachment(name) {
        showSnackbar('Watermarking applied. Starting secure download...');
        setTimeout(() => {
            window.location.href = 'download.php?file=' + encodeURIComponent(name) + '&idea_id=<?= $ideaId ?>';
        }, 600);
    }

    function showSnackbar(msg) {
        const popup = document.getElementById('snackbar-popup');
        popup.querySelector('.snackbar-msg').innerText = msg;
        popup.classList.remove('hidden');
        setTimeout(() => popup.classList.add('hidden'), 3500);
    }

    function toggleWatchlist(watchState) {
        const btn = document.getElementById('watch-btn');
        if (!btn || btn.disabled) return;

        btn.disabled = true;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;margin-right:6px;vertical-align:middle;"></span> Saving...';

        const fd = new FormData();
        fd.append('action', 'toggle_watchlist');
        fd.append('idea_id', <?= $ideaId ?>);
        fd.append('watch', watchState);

        fetch('api/notification-handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    if (watchState === 1) {
                        btn.outerHTML = `<button onclick="toggleWatchlist(0)" id="watch-btn" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs shadow-md flex items-center gap-1.5 transition-all">
                            <i data-lucide="star-off" class="w-4 h-4"></i> Unwatch Idea
                        </button>`;
                        showSnackbar('Idea added to your Watchlist!');
                    } else {
                        btn.outerHTML = `<button onclick="toggleWatchlist(1)" id="watch-btn" class="px-5 py-2.5 border border-slate-200 hover:border-amber-300 hover:bg-amber-50 text-slate-600 hover:text-amber-600 font-bold rounded-xl text-xs shadow-sm bg-white flex items-center gap-1.5 transition-all">
                            <i data-lucide="star" class="w-4 h-4"></i> Watch Idea
                        </button>`;
                        showSnackbar('Idea removed from your Watchlist.');
                    }
                    lucide.createIcons();
                } else {
                    btn.innerHTML = originalHTML;
                    showSnackbar(data.message || 'Could not update watchlist. Please try again.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                showSnackbar('Network error. Please try again.');
            });
    }

    function expressInterest() {
        const btn = document.getElementById('interest-btn');
        if (!btn || btn.disabled) return;

        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;margin-right:6px;vertical-align:middle;"></span> Registering...';

        const fd = new FormData();
        fd.append('action', 'express_interest');
        fd.append('idea_id', <?= $ideaId ?>);

        fetch('api/notification-handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg> Interest Registered';
                    btn.className = 'px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-xl text-xs shadow-md flex items-center gap-1.5 cursor-default';
                    showSnackbar('VC Interest declared! The entrepreneur has received a push notification.');

                    // Update the live interest count on the page if present
                    const interestBadges = document.querySelectorAll('.live-interest-count');
                    interestBadges.forEach(el => {
                        const n = parseInt(el.innerText) || 0;
                        el.innerText = n + 1;
                    });
                } else if (data.already) {
                    // Already expressed — lock button green, do NOT re-enable
                    btn.disabled = true;
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg> Interest Registered';
                    btn.className = 'px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-xl text-xs shadow-md flex items-center gap-1.5 cursor-default';
                    showSnackbar('You have already expressed interest in this idea.');
                } else {
                    // Genuine error — re-enable so they can retry
                    btn.disabled = false;
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> Express Interest';
                    showSnackbar('Could not register interest. Please try again.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> Express Interest';
            });
    }

    function requestMeeting() {
        const btn = event && event.target ? event.target : document.querySelector('[onclick*="requestMeeting"]');
        if (!btn || btn.disabled) return;

        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Scheduling...';

        const fd = new FormData();
        fd.append('action', 'schedule_pitch_audit');
        fd.append('idea_id', <?= $ideaId ?>);
        fd.append('entrepreneur_email', '<?= e($idea['entrepreneur_email']) ?>');

        fetch('api/chat-handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.innerText = '✓ Audit Scheduled';
                    btn.className = btn.className
                        .replace('bg-white text-blue-600', 'bg-emerald-50 text-emerald-700')
                        .replace('hover:bg-slate-50', '');
                    showSnackbar('Pitch audit scheduled! Message sent to entrepreneur — check your Messages inbox.');
                } else {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    showSnackbar('Could not schedule pitch audit. Please try again.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerText = originalText;
                showSnackbar('Network error. Please try again.');
            });
    }

    function toggleFollow() {
        const btn = document.getElementById('follow-btn');
        if (!btn || btn.disabled) return;
        btn.disabled = true;

        const fd = new FormData();
        fd.append('idea_id', <?= $ideaId ?>);

        fetch('api/follow-handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    const textSpan = document.getElementById('follow-btn-text');
                    const badge = document.getElementById('follow-count-badge');
                    let currentCount = parseInt(badge.innerText) || 0;

                    if (data.following) {
                        btn.className = "px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md flex items-center gap-1.5 transition-all";
                        textSpan.innerText = 'Unfollow Venture';
                        badge.innerText = currentCount + 1;
                        btn.querySelector('i').setAttribute('data-lucide', 'heart-off');
                        showSnackbar('You are now following this venture!');
                    } else {
                        btn.className = "px-5 py-2.5 border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 font-bold rounded-xl text-xs shadow-sm bg-white flex items-center gap-1.5 transition-all";
                        textSpan.innerText = 'Follow Venture';
                        badge.innerText = Math.max(0, currentCount - 1);
                        btn.querySelector('i').setAttribute('data-lucide', 'heart');
                        showSnackbar('Removed from followed ventures.');
                    }
                    lucide.createIcons();
                } else {
                    showSnackbar(data.message || 'Operation failed.');
                }
            })
            .catch(() => {
                btn.disabled = false;
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const starLabels = document.querySelectorAll('.star-rating-inputs label');
        starLabels.forEach((label, index) => {
            label.addEventListener('click', () => {
                starLabels.forEach((l, idx) => {
                    const icon = l.querySelector('.star-icon');
                    if (idx <= index) {
                        icon.classList.add('text-amber-500', 'fill-amber-500');
                        icon.classList.remove('text-slate-300');
                    } else {
                        icon.classList.remove('text-amber-500', 'fill-amber-500');
                        icon.classList.add('text-slate-300');
                    }
                });
            });
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
