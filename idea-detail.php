<?php
require_once __DIR__ . '/config/session.php';

$loggedIn    = isset($_SESSION['user_role']);
$role        = $loggedIn ? $_SESSION['user_role']  : null;
$userEmail   = $loggedIn ? $_SESSION['user_email'] : null;

if (!$loggedIn) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$ideaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$idea = dbGetIdeaById($ideaId);

if (!$idea) {
    echo "<main class='flex-grow max-w-7xl mx-auto px-4 py-16 text-center'><h2 class='text-2xl font-bold text-slate-800'>Idea not found</h2><a href='dashboard.php' class='mt-4 inline-block text-blue-600 font-bold hover:underline'>Back to Dashboard</a></main>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$entrepreneur = dbGetUserByEmail($idea['entrepreneur_email']);

$detailTab = isset($_GET['tab']) ? (int)$_GET['tab'] : 0;

// Check unlock states
$hasPaidAccess      = ($role === 'entrepreneur' || $idea['access_type'] === 'free');
$hasPaidAttachments = ($role === 'entrepreneur' || empty($idea['attachment_price']) || (float)$idea['attachment_price'] === 0.0);

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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Tabbed Left Column -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Tabs -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-1.5 flex gap-2 shadow-sm">
                <a href="?id=<?= $ideaId ?>&tab=0" class="flex-1 py-2 text-center text-xs font-bold rounded-lg transition-all <?= $detailTab === 0 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Concept Overview</a>
                <a href="?id=<?= $ideaId ?>&tab=1" class="flex-1 py-2 text-center text-xs font-bold rounded-lg transition-all <?= $detailTab === 1 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">Financial Details</a>
                <a href="?id=<?= $ideaId ?>&tab=2" class="flex-1 py-2 text-center text-xs font-bold rounded-lg transition-all <?= $detailTab === 2 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">AI Evaluation Breakdown</a>
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
                            <span class="text-3xl font-heading font-black text-blue-600">$<?= $idea['access_price'] ?></span>
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
                                    <i data-lucide="unlock" class="w-3.5 h-3.5"></i> Unlock Files ($<?= $idea['attachment_price'] ?>)
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
                            <span class="text-2xl font-heading font-black text-slate-800 mt-1 inline-block">$<?= number_format($idea['capital_required']) ?></span>
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

        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
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
                            <span class="px-2 py-0.5 rounded <?= $hasPaidAccess ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-600' ?>"><?= $hasPaidAccess ? 'Unlocked' : '$' . $idea['access_price'] ?></span>
                        </div>
                        <?php if ((float)$idea['attachment_price'] > 0): ?>
                        <div class="flex justify-between items-center py-1 border-t border-slate-100">
                            <span>Attached files:</span>
                            <span class="px-2 py-0.5 rounded <?= $hasPaidAttachments ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-600' ?>"><?= $hasPaidAttachments ? 'Unlocked' : '$' . $idea['attachment_price'] ?></span>
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

<!-- Snackbar -->
<div id="snackbar-popup" class="fixed bottom-6 left-6 z-50 bg-slate-800 text-white px-5 py-3 rounded-xl shadow-xl flex items-center gap-2 text-xs font-semibold hidden animate-fade-in">
    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
    <span class="snackbar-msg">Interest registered!</span>
</div>

<script>
    let activePaymentType = 'access';
    let activePaymentAmount = 0;
    let selectedMethod = 'card';

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

    function openPaymentModal(type, cost) {
        const errBlock = document.getElementById('payment-error-block');
        if (errBlock) errBlock.classList.add('hidden');

        activePaymentType = type;
        activePaymentAmount = cost;
        document.getElementById('payment-amount-label').innerText = '$' + cost;
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
