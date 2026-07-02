<?php
require_once __DIR__ . '/config/session.php';

$loggedIn    = isset($_SESSION['user_role']);
$role        = $loggedIn ? $_SESSION['user_role']  : null;
$userEmail   = $loggedIn ? $_SESSION['user_email'] : null;

// Redirect if not logged in or not investor
if (!$loggedIn || $role !== 'investor') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$successAlert = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $minInvestment = (float)($_POST['minInvestment'] ?? 10000);
    $maxInvestment = (float)($_POST['maxInvestment'] ?? 100000);
    $preferredSectors = $_POST['sectors'] ?? [];
    $riskTolerance = $_POST['riskTolerance'] ?? 'medium';
    $preferredStage = $_POST['stages'] ?? [];
    $minROI = (int)($_POST['minROI'] ?? 100);
    $location = $_POST['location'] ?? 'tanzania';

    // Save preferences in database
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO investor_preferences (investor_email, min_investment, max_investment, preferred_sectors, risk_tolerance, preferred_stages, min_roi, location)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            min_investment = VALUES(min_investment),
            max_investment = VALUES(max_investment),
            preferred_sectors = VALUES(preferred_sectors),
            risk_tolerance = VALUES(risk_tolerance),
            preferred_stages = VALUES(preferred_stages),
            min_roi = VALUES(min_roi),
            location = VALUES(location)
    ");
    $stmt->execute([
        $userEmail,
        $minInvestment,
        $maxInvestment,
        json_encode($preferredSectors),
        $riskTolerance,
        json_encode($preferredStage),
        $minROI,
        $location
    ]);

    $successAlert = true;
}

// Fetch current preferences from DB
$dbPref = dbGetInvestorPreferences($userEmail);
$pref = [
    'minInvestment'    => $dbPref['min_investment'] ?? 10000,
    'maxInvestment'    => $dbPref['max_investment'] ?? 100000,
    'preferredSectors' => $dbPref['preferred_sectors'] ?? ['technology', 'healthcare'],
    'riskTolerance'    => $dbPref['risk_tolerance'] ?? 'medium',
    'preferredStage'   => $dbPref['preferred_stages'] ?? ['mvp', 'beta'],
    'minROI'           => $dbPref['min_roi'] ?? 100,
    'location'         => $dbPref['location'] ?? 'tanzania'
];
?>

<main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full animate-fade-in">
    
    <!-- Top Back button -->
    <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-700 uppercase tracking-wider mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
    </a>

    <!-- Header area -->
    <div class="mb-8">
        <h1 class="font-heading font-extrabold text-3xl text-slate-800">Investment Preferences</h1>
        <p class="text-sm text-slate-500 font-medium">Fine-tune your venture target criteria to optimize AI recommendation matches</p>
    </div>

    <!-- Alert success -->
    <?php if ($successAlert): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-100 rounded-xl flex gap-3 text-green-700 text-sm">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5 text-green-600"></i>
            <p class="font-semibold">Preferences saved successfully! AI matches updated immediately on your dashboard.</p>
        </div>
    <?php endif; ?>

    <!-- Main Card form -->
    <form method="POST" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8 space-y-6">
        
        <!-- Section 1: Investment Range -->
        <div>
            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="dollar-sign" class="w-5 h-5 text-blue-600"></i>
                Venture Capital Budget Bracket (USD)
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="minInvestment" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Minimum Funding Target</label>
                    <input type="number" name="minInvestment" id="minInvestment" required value="<?= e($pref['minInvestment']) ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                </div>
                <div>
                    <label for="maxInvestment" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Maximum Funding Limit</label>
                    <input type="number" name="maxInvestment" id="maxInvestment" required value="<?= e($pref['maxInvestment']) ?>" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50">
                </div>
            </div>
        </div>

        <!-- Section 2: Preferred Sectors (Interactive Chips uploader) -->
        <div class="pt-5 border-t border-slate-100">
            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3 flex items-center gap-2">
                <i data-lucide="layers" class="w-5 h-5 text-blue-600"></i>
                Preferred Innovation Sectors
            </h3>
            <div class="flex flex-wrap gap-2.5">
                <?php 
                $allSectors = ['Technology', 'Healthcare', 'Agriculture', 'Education', 'E-commerce', 'FinTech', 'Manufacturing'];
                foreach ($allSectors as $sector):
                    $lowerSec = strtolower($sector);
                    $isSelected = in_array($lowerSec, $pref['preferredSectors']);
                ?>
                    <button type="button" onclick="toggleSectorChip('<?= $lowerSec ?>')" id="chip-sec-<?= $lowerSec ?>" 
                            class="px-4 py-2 text-xs font-bold border rounded-xl transition-all shadow-sm
                            <?= $isSelected ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700' : 'bg-white hover:bg-slate-50 text-slate-500 border-slate-200 hover:border-slate-300' ?>">
                        <?= $sector ?>
                    </button>
                    <!-- Hidden checkbox to submit in post -->
                    <input type="checkbox" name="sectors[]" value="<?= $lowerSec ?>" id="input-sec-<?= $lowerSec ?>" class="hidden" <?= $isSelected ? 'checked' : '' ?>>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 3: Risk Tolerance -->
        <div class="pt-5 border-t border-slate-100">
            <label for="riskTolerance" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Venture Risk Appetite</label>
            <select name="riskTolerance" id="riskTolerance" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                <option value="low" <?= $pref['riskTolerance'] === 'low' ? 'selected' : '' ?>>Low Risk - Established Launched Startups</option>
                <option value="medium" <?= $pref['riskTolerance'] === 'medium' ? 'selected' : '' ?>>Medium Risk - Growth MVP Startups</option>
                <option value="high" <?= $pref['riskTolerance'] === 'high' ? 'selected' : '' ?>>High Risk - Early Seed Concept ventures</option>
            </select>
        </div>

        <!-- Section 4: Development Stage (Interactive Chips uploader) -->
        <div class="pt-5 border-t border-slate-100">
            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-3 flex items-center gap-2">
                <i data-lucide="milestone" class="w-5 h-5 text-blue-600"></i>
                Preferred Startup Development Stage
            </h3>
            <div class="flex flex-wrap gap-2.5">
                <?php 
                $allStages = [
                    ['Concept', 'concept'],
                    ['Prototype', 'prototype'],
                    ['MVP Ready', 'mvp'],
                    ['Beta Testing', 'beta'],
                    ['Launched', 'launched']
                ];
                foreach ($allStages as $stage):
                    $isSelected = in_array($stage[1], $pref['preferredStage']);
                ?>
                    <button type="button" onclick="toggleStageChip('<?= $stage[1] ?>')" id="chip-stage-<?= $stage[1] ?>" 
                            class="px-4 py-2 text-xs font-bold border rounded-xl transition-all shadow-sm
                            <?= $isSelected ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700' : 'bg-white hover:bg-slate-50 text-slate-500 border-slate-200 hover:border-slate-300' ?>">
                        <?= $stage[0] ?>
                    </button>
                    <!-- Hidden checkbox to submit in post -->
                    <input type="checkbox" name="stages[]" value="<?= $stage[1] ?>" id="input-stage-<?= $stage[1] ?>" class="hidden" <?= $isSelected ? 'checked' : '' ?>>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 5: Minimum ROI slider -->
        <div class="pt-5 border-t border-slate-100">
            <h3 class="font-heading font-extrabold text-base text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="trending-up" class="w-5 h-5 text-blue-600"></i>
                Minimum Expected Venture ROI: <span class="text-blue-600" id="roi-label-val"><?= $pref['minROI'] ?>%</span>
            </h3>
            <div class="p-4 bg-slate-50 rounded-2xl border">
                <input type="range" name="minROI" id="minROI" min="0" max="500" step="10" value="<?= $pref['minROI'] ?>" 
                       class="w-full range-slider h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                <div class="flex justify-between text-[10px] font-bold text-slate-400 mt-2 px-1">
                    <span>0% ROI</span>
                    <span>100% ROI</span>
                    <span>250% ROI</span>
                    <span>500% ROI</span>
                </div>
            </div>
        </div>

        <!-- Section 6: Preferred Location -->
        <div class="pt-5 border-t border-slate-100">
            <label for="location" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Preferred Regional Coverage</label>
            <select name="location" id="location" class="block w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50/50 cursor-pointer">
                <option value="tanzania" <?= $pref['location'] === 'tanzania' ? 'selected' : '' ?>>Tanzania Coverage</option>
                <option value="eastafrica" <?= $pref['location'] === 'eastafrica' ? 'selected' : '' ?>>East Africa Coverage</option>
                <option value="africa" <?= $pref['location'] === 'africa' ? 'selected' : '' ?>>Sub-Saharan Africa Coverage</option>
                <option value="global" <?= $pref['location'] === 'global' ? 'selected' : '' ?>>Global Coverage</option>
            </select>
        </div>

        <button type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-md shadow-blue-500/10 flex items-center justify-center gap-1.5 group transition-all mt-4">
            <i data-lucide="save" class="w-4 h-4"></i> Save Vetting Preferences
        </button>

    </form>

</main>

<script>
    // Sector chips toggle handler
    function toggleSectorChip(id) {
        const chip = document.getElementById('chip-sec-' + id);
        const input = document.getElementById('input-sec-' + id);
        
        if (input.checked) {
            input.checked = false;
            chip.className = "px-4 py-2 text-xs font-bold border rounded-xl bg-white hover:bg-slate-50 text-slate-500 border-slate-200 hover:border-slate-300 transition-all shadow-sm";
        } else {
            input.checked = true;
            chip.className = "px-4 py-2 text-xs font-bold border rounded-xl bg-blue-600 text-white border-blue-600 hover:bg-blue-700 transition-all shadow-sm";
        }
    }

    // Stage chips toggle handler
    function toggleStageChip(id) {
        const chip = document.getElementById('chip-stage-' + id);
        const input = document.getElementById('input-stage-' + id);

        if (input.checked) {
            input.checked = false;
            chip.className = "px-4 py-2 text-xs font-bold border rounded-xl bg-white hover:bg-slate-50 text-slate-500 border-slate-200 hover:border-slate-300 transition-all shadow-sm";
        } else {
            input.checked = true;
            chip.className = "px-4 py-2 text-xs font-bold border rounded-xl bg-blue-600 text-white border-blue-600 hover:bg-blue-700 transition-all shadow-sm";
        }
    }

    // ROI slider updates
    document.addEventListener('DOMContentLoaded', () => {
        const slider = document.getElementById('minROI');
        const valLabel = document.getElementById('roi-label-val');

        slider.addEventListener('input', () => {
            valLabel.innerText = slider.value + '%';
        });
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
