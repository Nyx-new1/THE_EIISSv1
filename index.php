<?php
require_once __DIR__ . '/includes/header.php';

// Check if features page is requested via router
$view = $_GET['view'] ?? 'landing';

if ($view === 'features'):
?>
    <!-- ================= FEATURES PAGE SHOWCASE ================= -->
    <main class="flex-grow animate-fade-in">
        
        <!-- Premium Header Banner with Slide Backgrounds -->
        <div id="features-header-slider" class="slider-wrapper text-white py-20 relative overflow-hidden min-h-[280px] flex items-center bg-slate-950">
            <!-- Background slides -->
            <div class="slider-img active" style="background-image: url('assets/feature_slide-1.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/feature_slide-2.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/feature_slide-3.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/feature_slide-4.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/feature_slide-5.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/feature_slide-6.jpg');"></div>
            
            <!-- Dark Overlay for readability -->
            <div class="absolute inset-0 bg-slate-950/75 z-20 pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center sm:text-left relative z-30 w-full slider-content">
                <a href="index.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-300 hover:text-white uppercase tracking-wider mb-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Back to Home
                </a>
                <h1 class="font-heading font-extrabold text-4xl sm:text-5xl">Platform Capabilities</h1>
                <p class="text-blue-100/90 text-sm sm:text-lg max-w-2xl mt-3 font-medium leading-relaxed">
                    Discover all the powerful, state-of-the-art features making EIISS the leading platform for connecting ideas with investment securely.
                </p>
            </div>

            <!-- Slide Navigation Controls -->
            <button onclick="prevFeatureSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 p-2 bg-black/30 hover:bg-black/50 backdrop-blur-md rounded-full text-white z-40 transition-all border border-white/10" aria-label="Previous Slide">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button onclick="nextFeatureSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 bg-black/30 hover:bg-black/50 backdrop-blur-md rounded-full text-white z-40 transition-all border border-white/10" aria-label="Next Slide">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>

            <!-- Dots pagination -->
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-40">
                <span onclick="setFeatureSlide(0)" class="w-2.5 h-2.5 rounded-full bg-white cursor-pointer transition-all duration-300" id="feature-dot-0"></span>
                <span onclick="setFeatureSlide(1)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="feature-dot-1"></span>
                <span onclick="setFeatureSlide(2)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="feature-dot-2"></span>
                <span onclick="setFeatureSlide(3)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="feature-dot-3"></span>
                <span onclick="setFeatureSlide(4)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="feature-dot-4"></span>
                <span onclick="setFeatureSlide(5)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="feature-dot-5"></span>
            </div>
        </div>

        <!-- Detailed Features Grid Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Card 1 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all flex flex-col">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl w-fit mb-5">
                        <i data-lucide="users" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-800 mb-2">Intelligent Matching</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-grow">Our AI-powered engine analyzes investor preferences, budget, and risk tolerance to match them with the most relevant startup ideas.</p>
                    <ul class="space-y-2 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Match score algorithm</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Preference-based recommendations</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Custom filters & tags</li>
                    </ul>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all flex flex-col">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl w-fit mb-5">
                        <i data-lucide="shield" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-800 mb-2">Blockchain Protection</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-grow">Every idea submitted is cryptographically hashed and timestamped on our blockchain network, ensuring immutable proof of ownership.</p>
                    <ul class="space-y-2 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> SHA-256 cryptographic notarization</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Timestamped ledger records</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Proof of concept validation</li>
                    </ul>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all flex flex-col">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl w-fit mb-5">
                        <i data-lucide="bar-chart-3" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-800 mb-2">ROI Analysis</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-grow">Make highly informed investment decisions with our comprehensive interactive financial calculators and profitability projections.</p>
                    <ul class="space-y-2 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Dynamic ROI calculators</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Break-even analytics</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Risk-level multi-factor scores</li>
                    </ul>
                </div>

                <!-- Card 4 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all flex flex-col">
                    <div class="p-3 bg-cyan-50 text-cyan-600 rounded-2xl w-fit mb-5">
                        <i data-lucide="check-circle" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-800 mb-2">Plagiarism Detection</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-grow">AI-based similarity check reviews submitted titles and descriptions against the global base to protect authenticity and ownership rights.</p>
                    <ul class="space-y-2 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Auto duplicate scanning</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Originality percentage score</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Automated plagiarism flagging</li>
                    </ul>
                </div>

                <!-- Card 5 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all flex flex-col">
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl w-fit mb-5">
                        <i data-lucide="lock" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-800 mb-2">Dynamic Watermarking</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-grow">Secure all business plans, PDF attachments, and prototype mockups with personalized watermarks traceable back to specific visitors.</p>
                    <ul class="space-y-2 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Traceable watermark overlays</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Anti-sharing protection</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Secure PDF viewer integrations</li>
                    </ul>
                </div>

                <!-- Card 6 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all flex flex-col">
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-2xl w-fit mb-5">
                        <i data-lucide="wallet" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-800 mb-2">Monetization & Payments</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-grow">Set access rates for detailed files or full concepts, with seamless direct integration with East African mobile money wallets.</p>
                    <ul class="space-y-2 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-600">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Paid & Tiered pricing models</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Integrated M-Pesa, Tigo, Airtel</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Safe SSL encrypted processing</li>
                    </ul>
                </div>

            </div>

            <!-- CTA Call -->
            <div class="mt-20 text-center bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-3xl p-10 sm:p-14 shadow-xl shadow-blue-500/10">
                <h2 class="font-heading font-extrabold text-3xl mb-4">Ready to elevate your startup connectivity?</h2>
                <p class="text-blue-100 text-sm sm:text-base mb-8 max-w-lg mx-auto">Create your authorized user account today and explore the region's top innovations.</p>
                <div class="flex gap-4 justify-center flex-wrap">
                    <a href="register.php" class="px-7 py-3.5 bg-white text-blue-600 hover:bg-blue-50 rounded-xl font-bold shadow-md transition-all">Sign Up Now</a>
                    <a href="index.php" class="px-7 py-3.5 bg-transparent hover:bg-white/10 text-white border border-white/20 hover:border-white/50 rounded-xl font-bold transition-all">Learn More</a>
                </div>
            </div>
        </section>

    </main>

<?php else: ?>
    <!-- ================= LANDING HOME PAGE ================= -->
    <main class="flex-grow">
        
        <!-- Core Background Slider Wrapper -->
        <div id="landing-hero-slider" class="slider-wrapper text-white relative bg-slate-950">
            <!-- Background images -->
            <div class="slider-img active" style="background-image: url('assets/core1.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core2.jpg');"></div>
            
            <!-- Dark Tint Overlay to ensure text readability -->
            <div class="absolute inset-0 bg-slate-950/80 z-20 pointer-events-none"></div>

            <div class="slider-content z-30 relative">
                <!-- Elegant Hero Section -->
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center relative">
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 animate-fade-in">
                        
                        <h1 class="font-heading font-extrabold text-5xl sm:text-6xl tracking-tight text-white leading-[1.1] mb-6">
                            Connect Ideas with <span class="text-blue-400">Investment</span>
                        </h1>
                        
                        <p class="text-sm sm:text-lg text-slate-300 font-medium max-w-3xl mx-auto leading-relaxed mb-10">
                            EIISS is the intelligent platform bridging entrepreneurs and investors through blockchain-protected ideas, AI-powered matching, and data-driven insights.
                        </p>

                        <div class="flex gap-4 justify-center flex-wrap">
                            <a href="register.php?role=entrepreneur" class="px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-xl transition-all flex items-center gap-2 group">
                                <i data-lucide="lightbulb" class="w-5 h-5 text-blue-200"></i>
                                I'm an Entrepreneur
                                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                            <a href="register.php?role=investor" class="px-6 py-4 bg-white/10 backdrop-blur-md hover:bg-white/20 text-white border border-white/20 hover:border-white/40 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2 group">
                                <i data-lucide="target" class="w-5 h-5 text-slate-300 group-hover:text-blue-400 transition-colors"></i>
                                I'm an Investor
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Dynamic Grid Showcase -->
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-t border-white/10">
                    <div class="text-center mb-16">
                        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl text-white tracking-tight">Why Choose EIISS?</h2>
                        <p class="text-sm text-slate-300 font-semibold mt-2">Premium end-to-end security and matching features built for regional innovators</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        
                        <!-- Feature Card 1 -->
                        <div class="bg-slate-900/40 backdrop-blur-md p-8 rounded-3xl border border-white/10 hover:border-blue-500/40 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                            <div>
                                <div class="p-3 bg-blue-500/10 text-blue-400 rounded-2xl w-fit mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                                    <i data-lucide="users" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-heading font-extrabold text-lg text-white mb-2">Intelligent Matching</h3>
                                <p class="text-slate-300 text-sm leading-relaxed mb-6 font-medium">Our AI-powered engine connects investors with ideas that match their preferences, budget, and risk tolerance.</p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-bold text-blue-400 group-hover:text-blue-300 group-hover:gap-2 transition-all">
                                <span>Discover Matching</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Feature Card 2 -->
                        <div class="bg-slate-900/40 backdrop-blur-md p-8 rounded-3xl border border-white/10 hover:border-emerald-500/40 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                            <div>
                                <div class="p-3 bg-emerald-500/10 text-emerald-400 rounded-2xl w-fit mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                                    <i data-lucide="shield" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-heading font-extrabold text-lg text-white mb-2">Blockchain Protection</h3>
                                <p class="text-slate-300 text-sm leading-relaxed mb-6 font-medium">Every idea is cryptographically hashed and timestamped, ensuring immutable proof of ownership and authenticity.</p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-bold text-emerald-400 group-hover:text-emerald-300 group-hover:gap-2 transition-all">
                                <span>Check Protection</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Feature Card 3 -->
                        <div class="bg-slate-900/40 backdrop-blur-md p-8 rounded-3xl border border-white/10 hover:border-purple-500/40 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                            <div>
                                <div class="p-3 bg-purple-500/10 text-purple-400 rounded-2xl w-fit mb-6 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                                    <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-heading font-extrabold text-lg text-white mb-2">ROI Analysis</h3>
                                <p class="text-slate-300 text-sm leading-relaxed mb-6 font-medium">Make highly informed investment decisions with our digital ROI calculator and profitability projections.</p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-bold text-purple-400 group-hover:text-purple-300 group-hover:gap-2 transition-all">
                                <span>Calculate Yields</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Feature Card 4 -->
                        <div class="bg-slate-900/40 backdrop-blur-md p-8 rounded-3xl border border-white/10 hover:border-cyan-500/40 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                            <div>
                                <div class="p-3 bg-cyan-500/10 text-cyan-400 rounded-2xl w-fit mb-6 group-hover:bg-cyan-600 group-hover:text-white transition-all duration-300">
                                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-heading font-extrabold text-lg text-white mb-2">Plagiarism Detection</h3>
                                <p class="text-slate-300 text-sm leading-relaxed mb-6 font-medium">AI-based similarity analyses review submitted text files to ensure every idea is unique, fresh, and original.</p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-bold text-cyan-400 group-hover:text-cyan-300 group-hover:gap-2 transition-all">
                                <span>Check Uniqueness</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Feature Card 5 -->
                        <div class="bg-slate-900/40 backdrop-blur-md p-8 rounded-3xl border border-white/10 hover:border-orange-500/40 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                            <div>
                                <div class="p-3 bg-orange-500/10 text-orange-400 rounded-2xl w-fit mb-6 group-hover:bg-orange-600 group-hover:text-white transition-all duration-300">
                                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-heading font-extrabold text-lg text-white mb-2">Idea Scoring</h3>
                                <p class="text-slate-300 text-sm leading-relaxed mb-6 font-medium">Multi-factor evaluation system rates submissions on market potential, innovation, feasibility, and risk.</p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-bold text-orange-400 group-hover:text-orange-300 group-hover:gap-2 transition-all">
                                <span>Learn Scoring</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>

                        <!-- Feature Card 6 -->
                        <div class="bg-slate-900/40 backdrop-blur-md p-8 rounded-3xl border border-white/10 hover:border-amber-500/40 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                            <div>
                                <div class="p-3 bg-amber-500/10 text-amber-400 rounded-2xl w-fit mb-6 group-hover:bg-amber-600 group-hover:text-white transition-all duration-300">
                                    <i data-lucide="lock" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-heading font-extrabold text-lg text-white mb-2">Dynamic Watermarking</h3>
                                <p class="text-slate-300 text-sm leading-relaxed mb-6 font-medium">Protect your high-end business documents and attachments with traceable, custom watermarks.</p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-bold text-amber-400 group-hover:text-amber-300 group-hover:gap-2 transition-all">
                                <span>Secure Files</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>

                    </div>
                </section>
            </div>
        </div>

        <!-- Our Mission Section with Slide Backgrounds -->
        <section id="core-mission-slider" class="slider-wrapper text-white py-20 relative overflow-hidden rounded-3xl max-w-7xl mx-auto my-12 shadow-xl shadow-blue-500/5 bg-slate-950">
            <!-- Background slides (core_slide-1 to 10) -->
            <div class="slider-img active" style="background-image: url('assets/core_slide-1.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-2.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-3.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-4.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-5.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-6.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-7.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-8.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-9.jpg');"></div>
            <div class="slider-img" style="background-image: url('assets/core_slide-10.jpg');"></div>

            <!-- Dark Tint Overlay -->
            <div class="absolute inset-0 bg-slate-950/80 z-20 pointer-events-none"></div>

            <div class="max-w-5xl mx-auto px-6 sm:px-8 relative z-30 text-center space-y-6 slider-content">
                <span class="px-3.5 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-300 bg-white/10 rounded-full border border-white/10">Our Core Mission</span>
                <h2 class="font-heading font-extrabold text-3xl sm:text-4xl tracking-tight max-w-3xl mx-auto leading-tight">
                    Empowering local entrepreneurs with secure, trust-verified pathways to global capital.
                </h2>
                <p class="text-sm sm:text-base text-blue-100/90 font-medium max-w-2xl mx-auto leading-relaxed">
                    EIISS bridges the gap between grassroots innovators and regional venture networks. Our mission is to secure ownership through automated blockchain proofs, build verification channels that validate trust, and accelerate investment matching with total transparency.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-8 max-w-3xl mx-auto text-left">
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-3">
                        <div class="p-2 bg-white/10 text-white rounded-xl"><i data-lucide="shield-check" class="w-4 h-4"></i></div>
                        <div>
                            <h4 class="font-bold text-sm text-white">Secure Ownership</h4>
                            <p class="text-xs text-blue-200/80 font-medium mt-1">Cryptographic notarizations to prevent idea theft.</p>
                        </div>
                    </div>
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-3">
                        <div class="p-2 bg-white/10 text-white rounded-xl"><i data-lucide="verified" class="w-4 h-4"></i></div>
                        <div>
                            <h4 class="font-bold text-sm text-white">Trust Verification</h4>
                            <p class="text-xs text-blue-200/80 font-medium mt-1">Admin checked identities for credible profiles.</p>
                        </div>
                    </div>
                    <div class="p-5 bg-white/5 rounded-2xl border border-white/10 flex items-start gap-3">
                        <div class="p-2 bg-white/10 text-white rounded-xl"><i data-lucide="zap" class="w-4 h-4"></i></div>
                        <div>
                            <h4 class="font-bold text-sm text-white">Venture Speed</h4>
                            <p class="text-xs text-blue-200/80 font-medium mt-1">Direct communication chat models for fast closing.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Navigation Controls -->
            <button onclick="prevCoreMissionSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 p-2 bg-white/10 hover:bg-white/20 backdrop-blur-md rounded-full text-white z-20 transition-all border border-white/10" aria-label="Previous Slide">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button onclick="nextCoreMissionSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 bg-white/10 hover:bg-white/20 backdrop-blur-md rounded-full text-white z-20 transition-all border border-white/10" aria-label="Next Slide">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>

            <!-- Slide Indicators (Dots) -->
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                <span onclick="setCoreMissionSlide(0)" class="w-2.5 h-2.5 rounded-full bg-white cursor-pointer transition-all duration-300" id="mission-dot-0"></span>
                <span onclick="setCoreMissionSlide(1)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-1"></span>
                <span onclick="setCoreMissionSlide(2)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-2"></span>
                <span onclick="setCoreMissionSlide(3)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-3"></span>
                <span onclick="setCoreMissionSlide(4)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-4"></span>
                <span onclick="setCoreMissionSlide(5)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-5"></span>
                <span onclick="setCoreMissionSlide(6)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-6"></span>
                <span onclick="setCoreMissionSlide(7)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-7"></span>
                <span onclick="setCoreMissionSlide(8)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-8"></span>
                <span onclick="setCoreMissionSlide(9)" class="w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white cursor-pointer transition-all duration-300" id="mission-dot-9"></span>
            </div>
        </section>

    </main>
<?php endif; ?>

    <!-- Interactive Slideshow Orchestration Javascript -->
    <script>
        // --- 1. Features Header Slider ---
        let currentFeatureSlide = 0;

        function showFeatureSlide(index) {
            const container = document.getElementById('features-header-slider');
            if (!container) return;
            const slides = container.querySelectorAll('.slider-img');
            const dots = container.querySelectorAll('.flex.gap-2 span');
            
            if (index >= slides.length) currentFeatureSlide = 0;
            else if (index < 0) currentFeatureSlide = slides.length - 1;
            else currentFeatureSlide = index;

            slides.forEach((slide, i) => {
                if (i === currentFeatureSlide) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

            dots.forEach((dot, i) => {
                if (i === currentFeatureSlide) {
                    dot.classList.add('bg-white');
                    dot.classList.remove('bg-white/40');
                } else {
                    dot.classList.remove('bg-white');
                    dot.classList.add('bg-white/40');
                }
            });
        }
        function nextFeatureSlide() { showFeatureSlide(currentFeatureSlide + 1); }
        function prevFeatureSlide() { showFeatureSlide(currentFeatureSlide - 1); }
        function setFeatureSlide(index) { showFeatureSlide(index); }

        // --- 2. Core Theme Background Slider ---
        let currentCoreTheme = 0;
        function showCoreTheme(index) {
            const container = document.getElementById('landing-hero-slider');
            if (!container) return;
            const slides = container.querySelectorAll('.slider-img');
            const themeNumText = document.getElementById('core-theme-num');

            if (index >= slides.length) currentCoreTheme = 0;
            else if (index < 0) currentCoreTheme = slides.length - 1;
            else currentCoreTheme = index;

            slides.forEach((slide, i) => {
                if (i === currentCoreTheme) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

            if (themeNumText) {
                themeNumText.innerText = (currentCoreTheme + 1) + " / " + slides.length;
            }
        }
        function nextCoreTheme() { showCoreTheme(currentCoreTheme + 1); }
        function prevCoreTheme() { showCoreTheme(currentCoreTheme - 1); }

        // --- 3. Core Mission Background Slider ---
        let currentCoreMissionSlide = 0;
        function showCoreMissionSlide(index) {
            const container = document.getElementById('core-mission-slider');
            if (!container) return;
            const slides = container.querySelectorAll('.slider-img');
            const dots = container.querySelectorAll('.flex.gap-2.z-20 span');

            if (index >= slides.length) currentCoreMissionSlide = 0;
            else if (index < 0) currentCoreMissionSlide = slides.length - 1;
            else currentCoreMissionSlide = index;

            slides.forEach((slide, i) => {
                if (i === currentCoreMissionSlide) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

            dots.forEach((dot, i) => {
                if (i === currentCoreMissionSlide) {
                    dot.classList.add('bg-white');
                    dot.classList.remove('bg-white/40');
                    dot.classList.add('w-6');
                } else {
                    dot.classList.remove('bg-white');
                    dot.classList.add('bg-white/40');
                    dot.classList.remove('w-6');
                }
            });
        }
        function nextCoreMissionSlide() { showCoreMissionSlide(currentCoreMissionSlide + 1); }
        function prevCoreMissionSlide() { showCoreMissionSlide(currentCoreMissionSlide - 1); }
        function setCoreMissionSlide(index) { showCoreMissionSlide(index); }

        // Initialize and setup Autoplay
        document.addEventListener('DOMContentLoaded', () => {
            // Features Header (if exists)
            if (document.getElementById('features-header-slider')) {
                showFeatureSlide(0);
                setInterval(nextFeatureSlide, 5000);
            }
            // Landing Hero Core background
            if (document.getElementById('landing-hero-slider')) {
                showCoreTheme(0);
                setInterval(nextCoreTheme, 8000);
            }
            // Core Mission background
            if (document.getElementById('core-mission-slider')) {
                showCoreMissionSlide(0);
                setInterval(nextCoreMissionSlide, 6000);
            }
        });
    </script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
