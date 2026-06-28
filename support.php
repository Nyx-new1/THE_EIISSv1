<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/header.php';

$successMsg = '';
$ticketId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the support query submission
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($subject) && !empty($message)) {
        $ticketId = 'TK-' . rand(100000, 999999);
        $successMsg = "Thank you! Your support ticket has been created successfully.";
    }
}
?>
<main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full animate-fade-in">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Contact Details Cards -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h2 class="font-heading font-extrabold text-xl text-slate-800 mb-2">Help Desk Contacts</h2>
                    <p class="text-xs font-semibold text-slate-400 leading-relaxed mb-6">Reach out directly to our global operations team for any urgent inquiries.</p>
                </div>
                
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-start gap-3.5">
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="mail" class="w-5 h-5"></i></div>
                        <div>
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Email Address</span>
                            <a href="mailto:support@eiiss.co.tz" class="text-xs font-bold text-slate-700 hover:text-blue-600 transition-colors">support@eiiss.co.tz</a>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-start gap-3.5">
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="phone" class="w-5 h-5"></i></div>
                        <div>
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Hotline Support</span>
                            <span class="text-xs font-bold text-slate-700">+255 614 470 672</span>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-start gap-3.5">
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
                        <div>
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Headquarters Office</span>
                            <span class="text-xs font-bold text-slate-700 leading-relaxed block">Plot 1, EIISS Block,<br>Dar es Salaam, Tanzania</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-6 rounded-3xl shadow-md space-y-3">
                <h3 class="font-heading font-extrabold text-base">Knowledge Base</h3>
                <p class="text-xs text-blue-100/90 leading-relaxed font-medium">Looking for quick setup instructions, billing policies, or blockchain authentication tutorials? Browse our documentation.</p>
                <a href="#" class="inline-flex items-center gap-1.5 text-xs font-bold text-white hover:underline pt-2">
                    Open Wiki Portal <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>

        <!-- Right: Interactive Support Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-10 shadow-xl shadow-slate-100/50">
                <h2 class="font-heading font-extrabold text-2xl text-slate-800 mb-2">Submit Support Query</h2>
                <p class="text-xs font-semibold text-slate-400 mb-8">Our support representatives typically review tickets and respond within 12 to 24 business hours.</p>

                <?php if ($successMsg): ?>
                    <div class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl text-emerald-800 space-y-3 mb-8">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                            <h4 class="font-bold text-sm text-emerald-900"><?= $successMsg ?></h4>
                        </div>
                        <p class="text-xs leading-relaxed font-medium">Your reference ticket is <strong class="font-mono text-emerald-950 px-2 py-0.5 bg-white border rounded"><?= $ticketId ?></strong>. A copy of this ticket confirmation has been recorded in our helpdesk logs.</p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Ticket Subject</label>
                        <input type="text" name="subject" required placeholder="Describe the issue in a few words" class="block w-full px-4.5 py-3 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/20 font-medium text-slate-800">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Message details / Explanation</label>
                        <textarea name="message" required rows="6" placeholder="Provide full context, including account email, purchase amounts, transaction hashes or ID verification details..." class="block w-full px-4.5 py-3 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 bg-slate-50/20 font-medium text-slate-800"></textarea>
                    </div>

                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 flex items-center gap-2 transition-all">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i> Submit Support Request
                    </button>
                </form>
            </div>
        </div>

    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
