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
                    <i data-lucide="shield-alert" class="w-8 h-8 text-blue-600"></i>
                    Privacy Policy
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
                <h2 class="font-heading font-extrabold text-lg text-slate-800">1. Information We Collect</h2>
                <p>We collect information you provide directly to us when creating an account, posting investment proposals, or exchanging messages. This includes:</p>
                <ul class="list-disc pl-5 space-y-1.5 text-xs font-semibold text-slate-500">
                    <li>Name, email address, password, organization name, and contact details.</li>
                    <li>Government identity documents uploaded for profile verification and vetting.</li>
                    <li>Pitch metadata, business models, financial requirements, and supporting attachment files.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="font-heading font-extrabold text-lg text-slate-800">2. How We Protect Your Data</h2>
                <p>EIISS uses state-of-the-art security practices to keep your intellectual property and personal credentials safe:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-start gap-3">
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-xl mt-0.5"><i data-lucide="lock" class="w-4 h-4"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-1">Encrypted Storage</h4>
                            <p class="text-[11px] text-slate-400 font-bold">Files and passwords are cryptographically salted and hashed using industry-standard protocols.</p>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-start gap-3">
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-xl mt-0.5"><i data-lucide="link" class="w-4 h-4"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-1">Blockchain Notarization</h4>
                            <p class="text-[11px] text-slate-400 font-bold">Venture ideas are stamped onto the blockchain log to provide transparent, unalterable proof of ownership.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="font-heading font-extrabold text-lg text-slate-800">3. Sharing and Disclosing</h2>
                <p>We do not sell your personal details or intellectual assets to third parties. Access to investment pitch concepts is strictly restricted based on the payment and lock choices set by the entrepreneur. Verification documents are only reviewed by authorized administrators to confirm vetting status.</p>
            </section>

            <section class="space-y-3">
                <h2 class="font-heading font-extrabold text-lg text-slate-800">4. Your Privacy Rights</h2>
                <p>You can access, modify, or permanently delete your account profile details and uploaded venture submissions at any time from your settings panel. To request complete data export, contact our technical team.</p>
            </section>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
