<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full animate-fade-in">
    <div class="bg-white rounded-3xl border border-slate-200/85 p-8 sm:p-12 shadow-xl shadow-slate-100/50">
        
        <!-- Header -->
        <div class="border-b border-slate-100 pb-8 mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="font-heading font-extrabold text-3xl sm:text-4xl text-slate-800 tracking-tight flex items-center gap-2">
                    <i data-lucide="scroll" class="w-8 h-8 text-blue-600"></i>
                    Terms of Service
                </h1>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1.5">Last Updated: May 25, 2026</p>
            </div>
            <a href="dashboard.php" class="px-4 py-2 border border-slate-200 hover:border-blue-200 hover:bg-blue-50 text-slate-600 hover:text-blue-600 font-bold rounded-xl text-xs transition-all shadow-sm flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Dashboard
            </a>
        </div>

        <!-- Content sections -->
        <div class="space-y-8 text-slate-600 text-sm leading-relaxed font-medium">
            <section class="space-y-3">
                <h2 class="font-heading font-extrabold text-lg text-slate-800">1. Acceptance of Terms</h2>
                <p>By creating an account or accessing the EIISS platform, you agree to comply with and be bound by these Terms of Service. If you do not agree, please do not use the services.</p>
            </section>

            <section class="space-y-3">
                <h2 class="font-heading font-extrabold text-lg text-slate-800">2. Platform Purpose & Vetting</h2>
                <p>EIISS acts as a secure platform connecting entrepreneurs looking for seed support with institutional investors. Users must register using accurate credentials. Entrepreneurs who submit profiles for verification guarantee that all ID details and organization claims are true. Administrators reserve the right to suspend accounts providing deceptive details.</p>
            </section>

            <section class="space-y-3">
                <h2 class="font-heading font-extrabold text-lg text-slate-800">3. Intellectual Property Rights</h2>
                <p>EIISS is committed to protecting innovation ownership. All ideas submitted with blockchain hash notarization retain original ownership records. Investors unlocking premium files or downloading attachments are strictly prohibited from copying, distributing, or utilizing the concept documents without expressing interest and negotiating terms directly with the owner.</p>
            </section>

            <section class="space-y-3">
                <h2 class="font-heading font-extrabold text-lg text-slate-800">4. Gateway Payments and Transactions</h2>
                <p>All unlock fee transactions are processed securely through SSL-encrypted payment providers. Payments are non-refundable. Entrepreneurs will receive their share of payouts in accordance with platform policies after administrative review of concept status.</p>
            </section>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
