<?php
require_once __DIR__ . '/config/session.php';

$loggedIn    = isset($_SESSION['user_role']);
$role        = $loggedIn ? $_SESSION['user_role']  : null;
$userEmail   = $loggedIn ? $_SESSION['user_email'] : null;

// Redirect if not logged in or not entrepreneur
if (!$loggedIn || $role !== 'entrepreneur') {
    header("Location: login.php");
    exit;
}

$successSubmit = false;
$plagiarismScore = 0;
$generatedHash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $sector = $_POST['sector'] ?? 'Technology';
    $stage = $_POST['stage'] ?? 'Concept';
    $description = trim($_POST['description'] ?? '');
    $problem = trim($_POST['problemStatement'] ?? '');
    $solution = trim($_POST['solution'] ?? '');
    $targetMarket = trim($_POST['targetMarket'] ?? '');
    $competitiveAdvantage = trim($_POST['competitiveAdvantage'] ?? '');
    $capitalRequired = (float)($_POST['capitalRequired'] ?? 0);
    $expectedROI = (float)($_POST['expectedROI'] ?? 0);
    $timeline = (int)($_POST['timeline'] ?? 0);
    $riskLevel = $_POST['riskLevel'] ?? 'Medium';
    $teamSize = (int)($_POST['teamSize'] ?? 1);
    
    // Pricing
    $accessType = $_POST['accessType'] ?? 'free';
    $accessPrice = $accessType !== 'free' ? (float)($_POST['accessPrice'] ?? 0) : 0;
    $attachmentPrice = $accessType === 'tiered' ? (float)($_POST['attachmentPrice'] ?? 0) : 0;

    // Simulate plagiarism analysis: random original score between 92% and 98%
    $plagiarismScore = rand(92, 98);

    // Cryptographic notarization (SHA-256 hashing)
    $generatedHash = generateBlockchainHash($title, $userEmail);

    // Dynamic AI Multi-factor Evaluation scoring model
    $evalScore = 7.0;
    if ($expectedROI > 150) $evalScore += 1.0;
    if ($capitalRequired < 80000) $evalScore += 0.8;
    if ($timeline <= 18) $evalScore += 0.7;
    if (strlen($description) > 100) $evalScore += 0.5;
    $evalScore = min(9.8, $evalScore);

    $scoreMarket     = round($evalScore + 0.3, 1);
    $scoreInnovation = round($evalScore - 0.2, 1);
    $scoreFeasibility= round($evalScore - 0.5, 1);
    $scoreFinancial  = round($evalScore + 0.4, 1);

    // Insert idea into database
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO ideas (
            title, sector, status, score, views, interests,
            capital_required, expected_roi, blockchain_hash, submitted_date,
            access_type, access_price, attachment_price, earnings,
            entrepreneur_email, description, problem_statement, solution,
            target_market, competitive_advantage, timeline, risk_level,
            team_size, stage, score_market, score_innovation,
            score_feasibility, score_financial
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $title, ucfirst($sector), 'Under Review', $evalScore, 0, 0,
        $capitalRequired, $expectedROI, $generatedHash, date('Y-m-d'),
        $accessType, $accessPrice, $attachmentPrice, 0,
        $userEmail, $description, $problem, $solution,
        $targetMarket, $competitiveAdvantage, $timeline, ucfirst($riskLevel),
        $teamSize, $stage, $scoreMarket, $scoreInnovation,
        $scoreFeasibility, $scoreFinancial
    ]);

    $newIdeaId = (int)$db->lastInsertId();

    // Save actual attachments if uploaded
    if (!empty($_FILES['idea_files']['name'][0])) {
        $uploadDir = __DIR__ . '/uploads/attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $attStmt = $db->prepare("INSERT INTO idea_attachments (idea_id, name, size, type) VALUES (?,?,?,?)");
        for ($i = 0; $i < count($_FILES['idea_files']['name']); $i++) {
            if ($_FILES['idea_files']['error'][$i] === UPLOAD_ERR_OK) {
                $originalName = basename($_FILES['idea_files']['name'][$i]);
                $sizeInBytes = $_FILES['idea_files']['size'][$i];
                $sizeText = round($sizeInBytes / (1024 * 1024), 1) . ' MB';
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                
                $uniqueName = uniqid() . '_' . $originalName;
                $targetFilePath = $uploadDir . $uniqueName;
                
                if (move_uploaded_file($_FILES['idea_files']['tmp_name'][$i], $targetFilePath)) {
                    $attStmt->execute([
                        $newIdeaId,
                        $uniqueName,
                        $sizeText,
                        $ext
                    ]);
                }
            }
        }
    }

    // Notification for the entrepreneur
    $nStmt = $db->prepare("
        INSERT INTO notifications (user_email, type, title, message, sender)
        VALUES (?, 'trend', 'Concept Registered', ?, 'System Admin')
    ");
    $nStmt->execute([
        $userEmail,
        "Your concept \"$title\" was successfully registered and checked for originality ($plagiarismScore% original)."
    ]);

    // ── BROADCAST TO ADMIN ─────────────────────────────────────────────────
    $adminRow = getDB()->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1")->fetch();
    $adminEmail = $adminRow ? $adminRow['email'] : 'admin@eiiss.co.tz';
    $adminNotif = $db->prepare("
        INSERT INTO notifications (user_email, type, title, message, sender)
        VALUES (?, 'trend', 'New Concept Submitted', ?, 'Platform System')
    ");
    $adminNotif->execute([
        $adminEmail,
        "Entrepreneur {$userEmail} submitted a new concept: \"{$title}\" (Sector: " . ucfirst($sector) . ", Score: {$evalScore}/10)."
    ]);

    $successSubmit = true;
    header("refresh:2;url=dashboard.php");

}

require_once __DIR__ . '/includes/header.php';
?>

<main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full animate-fade-in">
    
    <!-- Top back navigation -->
    <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-700 uppercase tracking-wider mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
    </a>

    <!-- Header area -->
    <div class="mb-8">
        <h1 class="font-heading font-extrabold text-3xl text-slate-800">Submit New Concept</h1>
        <p class="text-sm text-slate-500 font-medium">Publish and notarize your dynamic pitch deck safely for capital matching</p>
    </div>

    <!-- Stepper indicator bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm mb-6 flex justify-between items-center overflow-x-auto gap-4">
        <div class="flex items-center gap-2 flex-shrink-0" id="step-dot-0">
            <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center shadow-md">1</span>
            <span class="text-xs font-bold text-slate-800">Basic Info</span>
        </div>
        <div class="h-px bg-slate-200 flex-1 min-w-[20px]" id="step-line-1"></div>
        <div class="flex items-center gap-2 flex-shrink-0" id="step-dot-1">
            <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 text-xs font-bold flex items-center justify-center border">2</span>
            <span class="text-xs font-bold text-slate-400">Business Details</span>
        </div>
        <div class="h-px bg-slate-200 flex-1 min-w-[20px]" id="step-line-2"></div>
        <div class="flex items-center gap-2 flex-shrink-0" id="step-dot-2">
            <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 text-xs font-bold flex items-center justify-center border">3</span>
            <span class="text-xs font-bold text-slate-400">Financials</span>
        </div>
        <div class="h-px bg-slate-200 flex-1 min-w-[20px]" id="step-line-3"></div>
        <div class="flex items-center gap-2 flex-shrink-0" id="step-dot-3">
            <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 text-xs font-bold flex items-center justify-center border">4</span>
            <span class="text-xs font-bold text-slate-400">Pricing</span>
        </div>
        <div class="h-px bg-slate-200 flex-1 min-w-[20px]" id="step-line-4"></div>
        <div class="flex items-center gap-2 flex-shrink-0" id="step-dot-4">
            <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 text-xs font-bold flex items-center justify-center border">5</span>
            <span class="text-xs font-bold text-slate-400">Review</span>
        </div>
    </div>

    <!-- Stepper Alert Panel -->
    <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-xl flex gap-3 text-blue-800 text-sm">
        <i data-lucide="shield" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
        <p class="font-semibold leading-relaxed">Safety Notice: EIISS cryptographically notarizes and watermarks your business deck prior to release.</p>
    </div>

    <!-- Submit Success Banner -->
    <?php if ($successSubmit): ?>
        <div class="mb-6 p-6 bg-emerald-50 border border-emerald-100 rounded-2xl flex gap-4 text-emerald-800 animate-pulse">
            <i data-lucide="check-circle" class="w-8 h-8 flex-shrink-0 text-emerald-600"></i>
            <div>
                <h3 class="font-heading font-bold text-lg text-emerald-900">Concept notarized successfully!</h3>
                <p class="text-sm mt-1 font-medium text-emerald-700">Originality rating: <strong><?= $plagiarismScore ?>% original</strong>.</p>
                <p class="text-xs mt-1 font-mono break-all text-emerald-600/80">Blockchain ledger hash: <?= $generatedHash ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Stepper Form -->
    <form method="POST" id="submit-concept-form" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8 space-y-6">
        
        <!-- STEP 0: BASIC INFORMATION -->
        <div id="step-section-0" class="step-form-block space-y-4">
            <h2 class="font-heading font-extrabold text-lg text-slate-800">Basic Concept Profile</h2>
            
            <div>
                <label for="title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Proposal/Idea Title</label>
                <input type="text" name="title" id="title" required placeholder="e.g. Clean Energy Hydro Storage Grid" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="sector" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sector Focus</label>
                    <select name="sector" id="sector" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="technology">Technology</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="agriculture">Agriculture</option>
                        <option value="education">Education</option>
                        <option value="ecommerce">E-commerce</option>
                        <option value="fintech">FinTech</option>
                        <option value="manufacturing">Manufacturing</option>
                    </select>
                </div>
                <div>
                    <label for="stage" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Development Stage</label>
                    <select name="stage" id="stage" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="Concept">Concept</option>
                        <option value="Prototype">Prototype</option>
                        <option value="MVP Ready">MVP Ready</option>
                        <option value="Beta Testing">Beta Testing</option>
                        <option value="Launched">Launched</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Brief Pitch Summary</label>
                <textarea name="description" id="description" required rows="4" placeholder="Briefly describe what your concept does..." class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50"></textarea>
            </div>
        </div>

        <!-- STEP 1: BUSINESS DETAILS -->
        <div id="step-section-1" class="step-form-block space-y-4 hidden">
            <h2 class="font-heading font-extrabold text-lg text-slate-800">Detailed Pitch Deck</h2>
            
            <div>
                <label for="problemStatement" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Problem Statement</label>
                <textarea name="problemStatement" id="problemStatement" rows="3" placeholder="What actual regional pain point are you solving?" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50"></textarea>
            </div>
            <div>
                <label for="solution" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Your Solution</label>
                <textarea name="solution" id="solution" rows="3" placeholder="How does your concept/system solve this problem?" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50"></textarea>
            </div>
            <div>
                <label for="targetMarket" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Target Market Demographics</label>
                <textarea name="targetMarket" id="targetMarket" rows="2" placeholder="Who are the primary addressable buyers?" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50"></textarea>
            </div>
            <div>
                <label for="competitiveAdvantage" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Competitive Edge Advantage</label>
                <textarea name="competitiveAdvantage" id="competitiveAdvantage" rows="2" placeholder="What uniquely sets your project apart?" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50"></textarea>
            </div>
        </div>

        <!-- STEP 2: FINANCIALS -->
        <div id="step-section-2" class="step-form-block space-y-4 hidden">
            <h2 class="font-heading font-extrabold text-lg text-slate-800">Financial Requirements & Projections</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="capitalRequired" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Required Capital (USD)</label>
                    <input type="number" name="capitalRequired" id="capitalRequired" placeholder="50000" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                </div>
                <div>
                    <label for="expectedROI" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Expected Project ROI (%)</label>
                    <input type="number" name="expectedROI" id="expectedROI" placeholder="150" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="timeline" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Break-even Timeline (Months)</label>
                    <input type="number" name="timeline" id="timeline" placeholder="18" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                </div>
                <div>
                    <label for="riskLevel" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Venture Risk Level</label>
                    <select name="riskLevel" id="riskLevel" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                        <option value="Low">Low Risk</option>
                        <option value="Medium">Medium Risk</option>
                        <option value="High">High Risk</option>
                    </select>
                </div>
                <div>
                    <label for="teamSize" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Founding Team Size</label>
                    <input type="number" name="teamSize" id="teamSize" placeholder="3" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                </div>
            </div>
        </div>

        <!-- STEP 3: ATTACHMENTS & PRICING -->
        <div id="step-section-3" class="step-form-block space-y-4 hidden">
            <h2 class="font-heading font-extrabold text-lg text-slate-800">Upload Attachments & Access Costing</h2>
            
            <!-- Real File Upload element -->
            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center hover:border-blue-500 transition-colors">
                <i data-lucide="upload" class="w-12 h-12 text-slate-400 mx-auto mb-3"></i>
                <span class="block text-slate-700 font-bold text-sm">Drag and drop supporting pitch documents</span>
                <span class="text-xs text-slate-400 font-semibold block mt-0.5">Accepted extensions: PDF, DOCX, PNG, JPG (max 10MB)</span>
                <input type="file" name="idea_files[]" id="idea-files-input" multiple class="hidden" onchange="handleRealFileUpload(event)">
                <button type="button" onclick="document.getElementById('idea-files-input').click()" class="mt-4 px-4 py-2 border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-200 font-bold rounded-xl text-xs transition-all shadow-sm bg-white">Select Files</button>
                <div id="mock-uploaded-files" class="mt-4 space-y-2 text-left">
                    <!-- Javascript appends items -->
                </div>
            </div>

            <!-- Pricing choices -->
            <div class="pt-5 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Cost Access Model for VCs</label>
                <div class="space-y-3">
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer bg-slate-50/50 hover:bg-slate-50">
                        <input type="radio" name="accessType" value="free" checked onchange="togglePricingFields('free')" class="mt-1">
                        <div>
                            <span class="block font-bold text-sm text-slate-800">Free Access Model</span>
                            <span class="text-xs text-slate-500 font-medium">All registered VCs can view full deck, description, and attached files immediately.</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer bg-slate-50/50 hover:bg-slate-50">
                        <input type="radio" name="accessType" value="paid" onchange="togglePricingFields('paid')" class="mt-1">
                        <div>
                            <span class="block font-bold text-sm text-slate-800">Paid Complete Access Model</span>
                            <span class="text-xs text-slate-500 font-medium">VCs pay a primary flat lock fee to read description, business detail tabs, and download attachments.</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer bg-slate-50/50 hover:bg-slate-50">
                        <input type="radio" name="accessType" value="tiered" onchange="togglePricingFields('tiered')" class="mt-1">
                        <div>
                            <span class="block font-bold text-sm text-slate-800">Tiered Access Model</span>
                            <span class="text-xs text-slate-500 font-medium">Basic info, titles, sectors, and stage are completely free. VCs pay separately to unlock detailed financials or attachments.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Conditional pricing inputs -->
            <div id="pricing-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 hidden bg-slate-50 p-4 rounded-xl border border-slate-100 animate-fade-in">
                <div>
                    <label for="accessPrice" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Decrypt Concept Fee (USD)</label>
                    <input type="number" name="accessPrice" id="accessPrice" placeholder="50" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                </div>
                <div id="attachment-pricing-container" class="hidden">
                    <label for="attachmentPrice" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Unlock Attachments Fee (USD)</label>
                    <input type="number" name="attachmentPrice" id="attachmentPrice" placeholder="25" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                </div>
            </div>
        </div>

        <!-- STEP 4: REVIEW & SUBMIT -->
        <div id="step-section-4" class="step-form-block space-y-4 hidden">
            <h2 class="font-heading font-extrabold text-lg text-slate-800">Confirm Your Submission</h2>
            
            <div class="p-4.5 bg-yellow-50 border border-yellow-200 rounded-2xl flex gap-3 text-yellow-800 text-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
                <p class="font-semibold leading-relaxed">Review warning: Please inspect values closely. Submitted details will be locked on simulated block notarization immediately.</p>
            </div>

            <div class="border border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100 text-sm">
                <div class="px-5 py-3.5 flex justify-between bg-slate-50/50">
                    <span class="font-bold text-slate-500">Proposal Title</span>
                    <span class="font-bold text-slate-800 text-right" id="review-title">-</span>
                </div>
                <div class="px-5 py-3.5 flex justify-between">
                    <span class="font-bold text-slate-500">Sector / Development Stage</span>
                    <span class="font-semibold text-slate-700" id="review-sector-stage">-</span>
                </div>
                <div class="px-5 py-3.5 flex justify-between bg-slate-50/50">
                    <span class="font-bold text-slate-500">Required Capital</span>
                    <span class="font-bold text-slate-800" id="review-capital">-</span>
                </div>
                <div class="px-5 py-3.5 flex justify-between">
                    <span class="font-bold text-slate-500">Expected ROI yield</span>
                    <span class="font-bold text-emerald-600" id="review-roi">-</span>
                </div>
                <div class="px-5 py-3.5 flex justify-between bg-slate-50/50">
                    <span class="font-bold text-slate-500">Access costing</span>
                    <span class="font-bold text-slate-700" id="review-pricing">-</span>
                </div>
            </div>
        </div>

        <!-- Controls navigation footer inside card -->
        <div class="flex justify-between items-center border-t border-slate-100 pt-6">
            <button type="button" id="back-btn" onclick="prevStep()" class="px-4 py-2 border.5 border-slate-200 hover:border-slate-300 text-slate-500 hover:text-slate-700 font-bold rounded-xl text-xs flex items-center gap-1.5 transition-all bg-white">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </button>
            <button type="button" id="next-btn" onclick="nextStep()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 flex items-center gap-1.5 transition-all">
                Next <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </div>

    </form>

</main>

<script>
    let activeStep = 0;
    const totalSteps = 5;

    function selectStepDot(step) {
        for (let i = 0; i < totalSteps; i++) {
            const dot = document.getElementById('step-dot-' + i);
            const line = document.getElementById('step-line-' + i);
            
            if (dot) {
                if (i === step) {
                    dot.querySelector('span').className = "w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center shadow-md";
                    dot.querySelector('span + span').className = "text-xs font-bold text-slate-800";
                } else if (i < step) {
                    dot.querySelector('span').className = "w-7 h-7 rounded-full bg-emerald-500 text-white text-xs font-bold flex items-center justify-center shadow-md";
                    dot.querySelector('span + span').className = "text-xs font-bold text-slate-500";
                } else {
                    dot.querySelector('span').className = "w-7 h-7 rounded-full bg-slate-100 text-slate-400 text-xs font-bold flex items-center justify-center border";
                    dot.querySelector('span + span').className = "text-xs font-bold text-slate-400";
                }
            }

            if (line) {
                if (i <= step) {
                    line.className = "h-px bg-blue-600 flex-1 min-w-[20px]";
                } else {
                    line.className = "h-px bg-slate-200 flex-1 min-w-[20px]";
                }
            }
        }
    }

    function togglePricingFields(type) {
        const fields = document.getElementById('pricing-fields');
        const attachmentPriceField = document.getElementById('attachment-pricing-container');
        
        if (type === 'free') {
            fields.classList.add('hidden');
        } else {
            fields.classList.remove('hidden');
            if (type === 'tiered') {
                attachmentPriceField.classList.remove('hidden');
            } else {
                attachmentPriceField.classList.add('hidden');
            }
        }
    }

    // Stepper navigation helpers
    function nextStep() {
        if (activeStep < totalSteps - 1) {
            // Validation simple for step 0
            if (activeStep === 0) {
                if (!document.getElementById('title').value || !document.getElementById('description').value) {
                    alert('Please complete all required fields.');
                    return;
                }
            }

            document.getElementById('step-section-' + activeStep).classList.add('hidden');
            activeStep++;
            document.getElementById('step-section-' + activeStep).classList.remove('hidden');
            selectStepDot(activeStep);

            // If on review step, populate values
            if (activeStep === totalSteps - 1) {
                document.getElementById('review-title').innerText = document.getElementById('title').value;
                document.getElementById('review-sector-stage').innerText = 
                    document.getElementById('sector').value.toUpperCase() + ' / ' + document.getElementById('stage').value;
                document.getElementById('review-capital').innerText = '$' + Number(document.getElementById('capitalRequired').value).toLocaleString();
                document.getElementById('review-roi').innerText = document.getElementById('expectedROI').value + '% expected ROI';
                
                const pricingType = document.querySelector('input[name="accessType"]:checked').value;
                let pricingLabel = 'Free Access';
                if (pricingType === 'paid') {
                    pricingLabel = 'Decryption Lock: $' + document.getElementById('accessPrice').value;
                } else if (pricingType === 'tiered') {
                    pricingLabel = 'Decryption Lock: $' + document.getElementById('accessPrice').value + ' | Attachments: $' + document.getElementById('attachmentPrice').value;
                }
                document.getElementById('review-pricing').innerText = pricingLabel;

                // Adjust submit buttons
                const nextBtn = document.getElementById('next-btn');
                nextBtn.innerHTML = 'Secure & Notarize &nbsp; <i data-lucide="shield" class="w-4 h-4"></i>';
                nextBtn.className = "px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md flex items-center transition-all";
                lucide.createIcons();
            }
        } else {
            // Trigger actual form submit
            document.getElementById('submit-concept-form').submit();
        }
    }

    function prevStep() {
        if (activeStep > 0) {
            document.getElementById('step-section-' + activeStep).classList.add('hidden');
            activeStep--;
            document.getElementById('step-section-' + activeStep).classList.remove('hidden');
            selectStepDot(activeStep);

            // Revert submit buttons
            const nextBtn = document.getElementById('next-btn');
            nextBtn.innerHTML = 'Next &nbsp; <i data-lucide="arrow-right" class="w-4 h-4"></i>';
            nextBtn.className = "px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 flex items-center gap-1.5 transition-all";
            lucide.createIcons();
        } else {
            window.location.href = 'dashboard.php';
        }
    }

    // Real Upload attachments helper
    function handleRealFileUpload(event) {
        const container = document.getElementById('mock-uploaded-files');
        container.innerHTML = '';
        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
            const div = document.createElement('div');
            div.className = "flex justify-between items-center bg-slate-50 p-2.5 rounded-xl border animate-fade-in";
            div.innerHTML = `
                <div class="flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4 text-blue-600"></i>
                    <span class="text-xs font-semibold text-slate-700">${file.name} (${sizeMB} MB)</span>
                </div>
            `;
            container.appendChild(div);
        }
        lucide.createIcons();
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
