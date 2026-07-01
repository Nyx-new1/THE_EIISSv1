<?php
require_once __DIR__ . '/config/session.php';
$loggedIn  = isset($_SESSION['user_role']);
$role      = $loggedIn ? $_SESSION['user_role']  : null;
require_once __DIR__ . '/includes/header.php';
?>

<main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full animate-fade-in">

    <a href="support.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-700 uppercase tracking-wider mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Support
    </a>

    <!-- Page Hero -->
    <div class="bg-gradient-to-br from-indigo-700 via-blue-700 to-blue-500 text-white p-10 rounded-3xl shadow-xl mb-10 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_20%,rgba(255,255,255,0.07),transparent)] pointer-events-none"></div>
        <div class="flex items-center gap-3 mb-4">
            <div class="p-3 bg-white/10 backdrop-blur-sm rounded-2xl">
                <i data-lucide="book-open" class="w-7 h-7"></i>
            </div>
            <span class="text-blue-200 font-bold text-sm uppercase tracking-wider">Knowledge Base</span>
        </div>
        <h1 class="font-heading font-extrabold text-4xl leading-tight mb-3">EIISS Documentation<br>& Wiki Portal</h1>
        <p class="text-blue-100/90 text-sm font-medium max-w-2xl">Everything you need to understand how the platform works — from registration and payments to blockchain verification and investor matching.</p>
    </div>

    <!-- Quick Jump Navigation -->
    <div class="flex flex-wrap gap-2.5 mb-10">
        <?php
        $quickLinks = [
            ['#getting-started',  'rocket',       'Getting Started'],
            ['#authentication',   'shield-check',  'Blockchain Auth'],
            ['#billing',          'credit-card',   'Billing & Payments'],
            ['#ideas',            'lightbulb',     'Submitting Ideas'],
            ['#investors',        'trending-up',   'For Investors'],
            ['#faq',              'help-circle',   'FAQ'],
        ];
        foreach ($quickLinks as [$href, $icon, $label]):
        ?>
        <a href="<?= $href ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 text-slate-600 font-bold text-xs rounded-xl transition-all shadow-sm">
            <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i> <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Sections -->
    <div class="space-y-10">

        <!-- Getting Started -->
        <section id="getting-started" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-indigo-50/60 to-transparent flex items-center gap-3">
                <div class="p-2.5 bg-indigo-100 text-indigo-700 rounded-xl">
                    <i data-lucide="rocket" class="w-5 h-5"></i>
                </div>
                <h2 class="font-heading font-extrabold text-xl text-slate-800">Getting Started</h2>
            </div>
            <div class="px-8 py-7 space-y-6 text-sm text-slate-600 font-medium leading-relaxed">

                <div>
                    <h3 class="font-heading font-bold text-slate-800 text-base mb-2">1. Create Your Account</h3>
                    <p>Visit the <a href="register.php" class="text-blue-600 hover:underline font-bold">Registration Page</a> and choose your role:</p>
                    <ul class="mt-3 space-y-1.5 ml-4 list-disc text-slate-500">
                        <li><strong class="text-slate-700">Entrepreneur</strong> — register startup ideas and attract investors</li>
                        <li><strong class="text-slate-700">Investor</strong> — browse verified concepts and unlock full pitch decks</li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-heading font-bold text-slate-800 text-base mb-2">2. Complete ID Verification</h3>
                    <p>After registration you'll enter a pending queue. Our admin team will review your government-issued ID (NIDA, Passport, or Driving Licence). Once approved you'll receive a notification and gain full platform access.</p>
                    <div class="mt-3 p-4 bg-amber-50 border border-amber-100 rounded-2xl flex gap-3 text-amber-800 text-xs">
                        <i data-lucide="clock" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                        <p>Verification typically completes within <strong>24 hours</strong> during business days.</p>
                    </div>
                </div>

                <div>
                    <h3 class="font-heading font-bold text-slate-800 text-base mb-2">3. Explore the Dashboard</h3>
                    <p>Your personalised <a href="dashboard.php" class="text-blue-600 hover:underline font-bold">Dashboard</a> is the central hub. Entrepreneurs see their submitted concepts with live analytics; investors see the curated idea marketplace.</p>
                </div>

            </div>
        </section>

        <!-- Blockchain Authentication -->
        <section id="authentication" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-emerald-50/60 to-transparent flex items-center gap-3">
                <div class="p-2.5 bg-emerald-100 text-emerald-700 rounded-xl">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <h2 class="font-heading font-extrabold text-xl text-slate-800">Blockchain Authentication</h2>
            </div>
            <div class="px-8 py-7 space-y-6 text-sm text-slate-600 font-medium leading-relaxed">

                <p>Every concept submitted to EIISS receives a <strong>SHA-256 cryptographic hash</strong> — a unique digital fingerprint tied to the idea's content and the entrepreneur's email address. This serves as immutable proof of original authorship.</p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <?php
                    $steps = [
                        ['hash', 'emerald', 'Hash Generation', 'When you submit an idea, the system combines your email, the concept title, and a timestamp into a SHA-256 hash.'],
                        ['database', 'blue', 'Immutable Storage', 'The hash is stored alongside the idea in the database and cannot be altered without invalidating the record.'],
                        ['shield', 'indigo', 'Proof of Ownership', 'Any investor can independently verify the hash to confirm the idea has not been tampered with.'],
                    ];
                    foreach ($steps as [$icon, $color, $title, $desc]):
                    ?>
                    <div class="p-5 bg-<?= $color ?>-50 border border-<?= $color ?>-100 rounded-2xl">
                        <div class="p-2 bg-<?= $color ?>-100 text-<?= $color ?>-700 rounded-xl inline-flex mb-3">
                            <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                        </div>
                        <h4 class="font-heading font-bold text-slate-800 mb-1"><?= $title ?></h4>
                        <p class="text-xs text-slate-500"><?= $desc ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="p-4 bg-slate-900 rounded-2xl text-emerald-400 font-mono text-xs overflow-x-auto leading-relaxed">
                    <span class="text-slate-500"># Example hash generation logic</span><br>
                    hash = SHA256( idea_title + entrepreneur_email + unix_timestamp + random_nonce )<br>
                    <span class="text-yellow-400">→ 0x3f9a21c7b5e8d...</span>
                </div>

            </div>
        </section>

        <!-- Billing & Payments -->
        <section id="billing" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-blue-50/60 to-transparent flex items-center gap-3">
                <div class="p-2.5 bg-blue-100 text-blue-700 rounded-xl">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                </div>
                <h2 class="font-heading font-extrabold text-xl text-slate-800">Billing & Payments</h2>
            </div>
            <div class="px-8 py-7 space-y-6 text-sm text-slate-600 font-medium leading-relaxed">

                <p>EIISS operates a <strong>pay-per-concept</strong> model for investors. There are no platform subscriptions. You only pay for what you unlock.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs font-semibold border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px]">
                                <th class="text-left px-4 py-3 rounded-tl-xl border border-slate-200">Access Type</th>
                                <th class="text-left px-4 py-3 border-t border-b border-slate-200">What You Get</th>
                                <th class="text-right px-4 py-3 rounded-tr-xl border border-slate-200">Fee</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 border-l border-slate-200 font-bold text-slate-800">Free</td>
                                <td class="px-4 py-3 text-slate-500">Concept overview and elevator pitch visible to all investors</td>
                                <td class="px-4 py-3 text-right border-r border-slate-200 text-emerald-600 font-bold">$0.00</td>
                            </tr>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 border-l border-slate-200 font-bold text-slate-800">Paid Access</td>
                                <td class="px-4 py-3 text-slate-500">Full financials, market analysis, team info, and AI evaluation report</td>
                                <td class="px-4 py-3 text-right border-r border-slate-200 text-blue-600 font-bold">Set by Entrepreneur</td>
                            </tr>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 border-l border-b border-slate-200 font-bold text-slate-800 rounded-bl-xl">Attachments</td>
                                <td class="px-4 py-3 border-b border-slate-200 text-slate-500">Pitch decks, financial models, and supporting documents</td>
                                <td class="px-4 py-3 text-right border-r border-b border-slate-200 text-blue-600 font-bold rounded-br-xl">Additional unlock</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h3 class="font-heading font-bold text-slate-800 text-base mb-2">Supported Payment Methods</h3>
                    <div class="flex flex-wrap gap-2.5">
                        <?php foreach (['Visa / Mastercard', 'M-Pesa', 'Airtel Money', 'Bank Transfer'] as $method): ?>
                        <span class="px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-600 font-bold text-xs rounded-full"><?= $method ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl flex gap-3 text-blue-800 text-xs">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                    <p>All payments are processed securely. Entrepreneurs receive earnings directly into their account ledger visible on their dashboard. The platform takes no commission.</p>
                </div>

            </div>
        </section>

        <!-- Submitting Ideas -->
        <section id="ideas" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-amber-50/60 to-transparent flex items-center gap-3">
                <div class="p-2.5 bg-amber-100 text-amber-700 rounded-xl">
                    <i data-lucide="lightbulb" class="w-5 h-5"></i>
                </div>
                <h2 class="font-heading font-extrabold text-xl text-slate-800">Submitting a Concept</h2>
            </div>
            <div class="px-8 py-7 space-y-5 text-sm text-slate-600 font-medium leading-relaxed">

                <p>As a verified entrepreneur you can submit unlimited startup concepts. Each concept goes through an AI-powered evaluation pipeline before appearing in the investor marketplace.</p>

                <div class="space-y-3">
                    <?php
                    $submissionSteps = [
                        ['Fill the submission form', 'Describe your concept, sector, problem statement, proposed solution, target market, competitive advantages, and timeline.'],
                        ['Choose an access model', 'Decide whether investors access the full pitch for free, pay a flat fee, or via tiered pricing (concept fee + separate attachments fee).'],
                        ['Attach supporting documents', 'Upload pitch decks, financial models, or any supporting PDF / Office files (max 10 MB each).'],
                        ['AI evaluation runs automatically', 'The system evaluates your concept across 4 dimensions: Market Potential, Innovation, Feasibility, and Financial Viability, generating a score out of 10.'],
                        ['Blockchain notarization', 'A SHA-256 hash is generated and stored alongside your concept, serving as immutable proof of submission date and authorship.'],
                    ];
                    foreach ($submissionSteps as $idx => [$title, $desc]):
                    ?>
                    <div class="flex gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-100">
                        <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 font-heading font-black text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><?= $idx + 1 ?></div>
                        <div>
                            <p class="font-bold text-slate-800"><?= $title ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?= $desc ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>

        <!-- For Investors -->
        <section id="investors" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-pink-50/60 to-transparent flex items-center gap-3">
                <div class="p-2.5 bg-pink-100 text-pink-700 rounded-xl">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                <h2 class="font-heading font-extrabold text-xl text-slate-800">Investor Guide</h2>
            </div>
            <div class="px-8 py-7 space-y-5 text-sm text-slate-600 font-medium leading-relaxed">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php
                    $investorFeatures = [
                        ['heart', 'pink', 'Express Interest', 'Click "Express Interest" on any concept to notify the entrepreneur you are watching it. You can only do this once per concept.'],
                        ['unlock', 'indigo', 'Unlock Full Pitch', 'Pay the concept access fee to read the full financials, AI evaluation, and team details.'],
                        ['paperclip', 'blue', 'Download Attachments', 'Pay the separate attachments fee to download pitch decks and financial models.'],
                        ['calendar', 'amber', 'Schedule a Pitch Audit', 'Request a structured Q&A session with the entrepreneur directly through the messaging system.'],
                    ];
                    foreach ($investorFeatures as [$icon, $color, $title, $desc]):
                    ?>
                    <div class="p-5 bg-<?= $color ?>-50/60 border border-<?= $color ?>-100 rounded-2xl">
                        <div class="p-2 bg-<?= $color ?>-100 text-<?= $color ?>-700 rounded-xl inline-flex mb-3">
                            <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                        </div>
                        <h4 class="font-heading font-bold text-slate-800 mb-1"><?= $title ?></h4>
                        <p class="text-xs text-slate-500"><?= $desc ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent flex items-center gap-3">
                <div class="p-2.5 bg-slate-100 text-slate-700 rounded-xl">
                    <i data-lucide="help-circle" class="w-5 h-5"></i>
                </div>
                <h2 class="font-heading font-extrabold text-xl text-slate-800">Frequently Asked Questions</h2>
            </div>
            <div class="px-8 py-7 space-y-4">
                <?php
                $faqs = [
                    ['Can I submit the same idea twice?', 'No. The system checks originality using a plagiarism score. Duplicate concepts will have significantly lower scores and may be flagged by the admin during review.'],
                    ['How is the AI evaluation score calculated?', 'The score is an average of 4 metrics: Market Potential (how large and reachable the target market is), Innovation (uniqueness of the solution), Feasibility (technical and operational realism), and Financial Viability (ROI projection and capital requirements). Each is scored out of 10.'],
                    ['Can an investor express interest more than once?', 'No. The platform restricts each investor to a single interest declaration per concept to ensure accurate VC engagement data.'],
                    ['Are my attached documents secure?', 'Yes. Files are stored outside the public web root and can only be downloaded through the secure download endpoint after authorisation checks confirm you have the correct access level.'],
                    ['What happens after I express interest as an investor?', 'The entrepreneur receives an instant notification. You can also schedule a formal pitch audit session via the messaging system to arrange a structured discussion.'],
                    ['How do I contact platform support?', 'Visit the Support page and send us a message through the contact form. Responses are typically within one business day.'],
                ];
                foreach ($faqs as $idx => [$q, $a]):
                ?>
                <div class="border border-slate-200 rounded-2xl overflow-hidden" x-data="{ open: false }">
                    <button
                        onclick="toggleFaq(this)"
                        class="w-full text-left px-5 py-4 flex justify-between items-center font-bold text-sm text-slate-800 hover:bg-slate-50/50 transition-colors focus:outline-none"
                    >
                        <span><?= $q ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 faq-chevron transition-transform flex-shrink-0 ml-3"></i>
                    </button>
                    <div class="faq-answer px-5 pb-4 text-sm text-slate-500 font-medium leading-relaxed hidden">
                        <?= $a ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>
</main>

<script>
function toggleFaq(btn) {
    const answer  = btn.nextElementSibling;
    const chevron = btn.querySelector('.faq-chevron');
    const isOpen  = !answer.classList.contains('hidden');

    // Close all others
    document.querySelectorAll('.faq-answer').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.faq-chevron').forEach(el => el.style.transform = '');

    if (!isOpen) {
        answer.classList.remove('hidden');
        chevron.style.transform = 'rotate(180deg)';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
