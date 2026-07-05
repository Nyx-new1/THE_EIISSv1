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
                            <div onclick="openDemoModal('matching')" class="flex items-center gap-1 text-xs font-bold text-blue-400 hover:text-blue-300 hover:gap-2 transition-all cursor-pointer w-fit select-none">
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
                            <div onclick="openDemoModal('protection')" class="flex items-center gap-1 text-xs font-bold text-emerald-400 hover:text-emerald-300 hover:gap-2 transition-all cursor-pointer w-fit select-none">
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
                            <div onclick="openDemoModal('yields')" class="flex items-center gap-1 text-xs font-bold text-purple-400 hover:text-purple-300 hover:gap-2 transition-all cursor-pointer w-fit select-none">
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
                            <div onclick="openDemoModal('uniqueness')" class="flex items-center gap-1 text-xs font-bold text-cyan-400 hover:text-cyan-300 hover:gap-2 transition-all cursor-pointer w-fit select-none">
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
                            <div onclick="openDemoModal('scoring')" class="flex items-center gap-1 text-xs font-bold text-orange-400 hover:text-orange-300 hover:gap-2 transition-all cursor-pointer w-fit select-none">
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
                            <div onclick="openDemoModal('watermarking')" class="flex items-center gap-1 text-xs font-bold text-amber-400 hover:text-amber-300 hover:gap-2 transition-all cursor-pointer w-fit select-none">
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

<!-- ================= INTERACTIVE SIMULATORS MODALS ================= -->

<!-- Common Simulator Modal Container -->
<div id="simulator-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl flex flex-col overflow-hidden max-h-[90vh] border border-slate-200/80 animate-fade-in text-slate-800">
        
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div class="flex items-center gap-3">
                <div id="modal-icon-container" class="p-2.5 rounded-2xl w-fit">
                    <!-- Icon will be set dynamically -->
                </div>
                <div>
                    <h3 class="font-heading font-extrabold text-lg text-slate-800" id="modal-title">Simulator Title</h3>
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block mt-0.5">Interactive Platform Demo</span>
                </div>
            </div>
            <button onclick="closeDemoModal()" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded-xl transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Body (Dynamic content) -->
        <div class="p-6 overflow-y-auto space-y-6 flex-grow" id="modal-body-content">
            
            <!-- 1. Matching Simulator -->
            <div id="sim-matching" class="hidden space-y-5">
                <p class="text-xs text-slate-500 font-medium">Test how our AI matchmaking algorithm aligns investor parameters with startup sectors, capital targets, and risk profile parameters.</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Select Investor Sector Preferences</label>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="toggleSimSector(this, 'Technology')" class="px-3 py-1.5 rounded-lg border text-xs font-bold bg-blue-50 border-blue-200 text-blue-600">Technology</button>
                            <button onclick="toggleSimSector(this, 'Agriculture')" class="px-3 py-1.5 rounded-lg border text-xs font-bold bg-white border-slate-200 text-slate-600 hover:bg-slate-50">Agriculture</button>
                            <button onclick="toggleSimSector(this, 'Healthcare')" class="px-3 py-1.5 rounded-lg border text-xs font-bold bg-white border-slate-200 text-slate-600 hover:bg-slate-50">Healthcare</button>
                            <button onclick="toggleSimSector(this, 'FinTech')" class="px-3 py-1.5 rounded-lg border text-xs font-bold bg-white border-slate-200 text-slate-600 hover:bg-slate-50">FinTech</button>
                            <button onclick="toggleSimSector(this, 'E-Commerce')" class="px-3 py-1.5 rounded-lg border text-xs font-bold bg-white border-slate-200 text-slate-600 hover:bg-slate-50">E-Commerce</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="sim-matching-budget" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Max Capital Budget</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-matching-budget" min="10000" max="500000" step="10000" value="150000" oninput="document.getElementById('sim-matching-budval').innerText = '$' + Number(this.value).toLocaleString()" class="w-full range-slider h-2 bg-slate-100 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[70px] text-right" id="sim-matching-budval">$150,000</span>
                            </div>
                        </div>
                        <div>
                            <label for="sim-matching-risk" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Acceptable Risk Tier</label>
                            <select id="sim-matching-risk" class="block w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-slate-50 cursor-pointer font-bold text-slate-600">
                                <option value="Low">Low Risk</option>
                                <option value="Medium" selected>Medium Risk</option>
                                <option value="High">High Risk</option>
                            </select>
                        </div>
                    </div>
                    <button onclick="runMatchingSimulation()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="sparkles" class="w-4 h-4"></i> Run AI Matchmaker Scan
                    </button>
                    <div id="sim-matching-results" class="hidden space-y-3 pt-3 border-t border-slate-100">
                        <h4 class="font-heading font-extrabold text-xs text-slate-800 uppercase tracking-wider">Top AI Simulated Matches</h4>
                        <div class="space-y-2.5" id="sim-matching-list"></div>
                    </div>
                </div>
            </div>

            <!-- 2. Protection Simulator -->
            <div id="sim-protection" class="hidden space-y-5">
                <p class="text-xs text-slate-500 font-medium">Verify cryptographically secured proof-of-ownership hashes registered on the simulated decentralized blockchain ledger.</p>
                <div class="space-y-4">
                    <div>
                        <label for="sim-protection-hash" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Enter Idea Blockchain Hash</label>
                        <div class="flex gap-2">
                            <input type="text" id="sim-protection-hash" placeholder="e.g. 0x2b89c108..." class="flex-grow px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-mono focus:outline-none focus:border-emerald-500 bg-slate-50 text-slate-700">
                            <button onclick="fillSampleHash()" class="px-3.5 py-2.5 border border-slate-200 text-slate-600 hover:text-emerald-600 font-bold rounded-xl text-xs bg-white shadow-sm transition-all whitespace-nowrap">Sample Hash</button>
                        </div>
                    </div>
                    <button onclick="runProtectionSimulation()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-500/10 transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="shield-alert" class="w-4 h-4"></i> Verify Blockchain Ledger Proof
                    </button>
                    <div id="sim-protection-results" class="hidden pt-3 border-t border-slate-100 animate-fade-in">
                        <div class="bg-emerald-50/50 border border-emerald-100/80 rounded-2xl p-4.5 space-y-3 text-xs">
                            <div class="flex items-center gap-2 text-emerald-800 font-extrabold">
                                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                                <span>IMMUTABLE PROOF OF OWNERSHIP VALIDATED</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3.5 pt-2 text-slate-600 font-semibold border-t border-emerald-100/60 text-left">
                                <div>
                                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Block Number</span>
                                    <span class="text-slate-800 font-mono" id="sim-prot-block">#3,482,901</span>
                                </div>
                                <div>
                                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Timestamp</span>
                                    <span class="text-slate-800" id="sim-prot-time">2026-07-05 20:19:00</span>
                                </div>
                                <div>
                                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Proof Standard</span>
                                    <span class="text-slate-800 uppercase">SHA-256 HASH</span>
                                </div>
                                <div>
                                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Gas Fee (Spent)</span>
                                    <span class="text-slate-800">0.00018 ETH</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Yields Simulator -->
            <div id="sim-yields" class="hidden space-y-5">
                <p class="text-xs text-slate-500 font-medium">Model projected investment outputs and break-even intervals instantly by tweaking targets.</p>
                <div class="space-y-4">
                    <div class="space-y-3.5 bg-slate-50 p-4.5 rounded-2xl border border-slate-100">
                        <div>
                            <label for="sim-yields-capital" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Required Capital Investment</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-yields-capital" min="5000" max="150000" step="5000" value="50000" oninput="calculateYields()" class="w-full range-slider h-2 bg-slate-200 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[70px] text-right" id="sim-yields-capval">$50,000</span>
                            </div>
                        </div>
                        <div>
                            <label for="sim-yields-revenue" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Expected Year-1 Revenue</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-yields-revenue" min="10000" max="250000" step="5000" value="80000" oninput="calculateYields()" class="w-full range-slider h-2 bg-slate-200 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[70px] text-right" id="sim-yields-revval">$80,000</span>
                            </div>
                        </div>
                        <div>
                            <label for="sim-yields-margin" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Projected Net Margin</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-yields-margin" min="10" max="80" step="5" value="35" oninput="calculateYields()" class="w-full range-slider h-2 bg-slate-200 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[70px] text-right" id="sim-yields-margval">35%</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3.5 pt-3 border-t border-slate-100 text-center">
                        <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-100">
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-1">Annual Profit</span>
                            <span class="text-xs sm:text-sm font-black text-purple-700" id="sim-yields-profit">$28,000</span>
                        </div>
                        <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-100">
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-1">Projected ROI</span>
                            <span class="text-xs sm:text-sm font-black text-purple-700" id="sim-yields-roi">56%</span>
                        </div>
                        <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-100">
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-1">Break-Even</span>
                            <span class="text-xs sm:text-sm font-black text-purple-700" id="sim-yields-breakeven">21.4 Mos</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Uniqueness Simulator -->
            <div id="sim-uniqueness" class="hidden space-y-5">
                <p class="text-xs text-slate-500 font-medium">Verify the originality of proposal abstracts against existing entries in the network database to secure ownership rights.</p>
                <div class="space-y-4">
                    <div>
                        <label for="sim-uniqueness-text" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Paste Idea Pitch Abstract</label>
                        <textarea id="sim-uniqueness-text" rows="3" placeholder="Describe your startup idea briefly to check for originality..." class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-cyan-500 bg-slate-50/50 text-slate-700"></textarea>
                    </div>
                    <button onclick="runUniquenessSimulation()" class="w-full py-3 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl text-xs shadow-md shadow-cyan-500/10 transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="scan" class="w-4 h-4"></i> Run Plagiarism Analysis
                    </button>
                    <div id="sim-uniqueness-results" class="hidden pt-3 border-t border-slate-100">
                        <div class="bg-cyan-50 border border-cyan-100 rounded-2xl p-4.5 space-y-2 text-xs text-left">
                            <div class="flex items-center justify-between font-bold text-slate-700">
                                <span>Originality Rating</span>
                                <span class="text-cyan-700 text-sm font-black" id="sim-uniq-score">96% Original</span>
                            </div>
                            <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                <div class="bg-cyan-500 h-full transition-all duration-500" id="sim-uniq-bar" style="width: 96%"></div>
                            </div>
                            <p class="text-[10px] text-slate-400 font-semibold mt-1.5 leading-relaxed" id="sim-uniq-verdict">Validation passes. Excellent uniqueness index – proposal abstract contains no matches in existing database.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Scoring Simulator -->
            <div id="sim-scoring" class="hidden space-y-5">
                <p class="text-xs text-slate-500 font-medium">Explore how the AI grading system evaluates proposals across 4 major dimensions to calculate the final Score out of 10.</p>
                <div class="space-y-4">
                    <div class="space-y-3.5 bg-slate-50 p-4.5 rounded-2xl border border-slate-100">
                        <div>
                            <label for="sim-score-market" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Market Potential (Weight: 30%)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-score-market" min="1" max="10" step="0.5" value="7.5" oninput="calculateScoring()" class="w-full range-slider h-2 bg-slate-200 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[35px] text-right" id="sim-score-mval">7.5</span>
                            </div>
                        </div>
                        <div>
                            <label for="sim-score-innovation" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Innovation Quotient (Weight: 30%)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-score-innovation" min="1" max="10" step="0.5" value="8.0" oninput="calculateScoring()" class="w-full range-slider h-2 bg-slate-200 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[35px] text-right" id="sim-score-ival">8.0</span>
                            </div>
                        </div>
                        <div>
                            <label for="sim-score-feasibility" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Feasibility & Realism (Weight: 20%)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-score-feasibility" min="1" max="10" step="0.5" value="6.5" oninput="calculateScoring()" class="w-full range-slider h-2 bg-slate-200 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[35px] text-right" id="sim-score-fval">6.5</span>
                            </div>
                        </div>
                        <div>
                            <label for="sim-score-finance" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Financial Viability (Weight: 20%)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" id="sim-score-finance" min="1" max="10" step="0.5" value="7.0" oninput="calculateScoring()" class="w-full range-slider h-2 bg-slate-200 rounded-lg cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 min-w-[35px] text-right" id="sim-score-fnval">7.0</span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4.5 bg-orange-50 border border-orange-100 rounded-2xl text-center space-y-2.5">
                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Calculated AI Idea Score</span>
                        <div class="text-3xl font-heading font-black text-orange-600" id="sim-score-result">7.4 / 10</div>
                        <p class="text-xs text-slate-500 font-medium px-4 leading-relaxed" id="sim-score-desc">Strong Concept: Meets platform guidelines with active regional validation parameters.</p>
                    </div>
                </div>
            </div>

            <!-- 6. Watermarking Simulator -->
            <div id="sim-watermarking" class="hidden space-y-5">
                <p class="text-xs text-slate-500 font-medium">Input your name to see how our dynamic watermarking system dynamically overlays recipient data and timestamps on pitch decks to trace leaks.</p>
                <div class="space-y-4">
                    <div>
                        <label for="sim-watermark-name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Recipient Name / Company</label>
                        <input type="text" id="sim-watermark-name" value="Investor John Doe" oninput="updateWatermarkOverlay()" placeholder="Type a recipient name..." class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Watermarked PDF Document Preview</label>
                        <div class="border rounded-2xl bg-slate-100 p-8 text-center relative overflow-hidden h-44 flex items-center justify-center border-slate-200">
                            <!-- Document content -->
                            <div class="relative z-10 space-y-2">
                                <i data-lucide="file-text" class="w-8 h-8 text-slate-400 mx-auto"></i>
                                <span class="block font-bold text-xs text-slate-700">BUSINESS PLAN DECK</span>
                                <span class="block text-[10px] text-slate-400">Section 1: Confidential Market Projections</span>
                            </div>
                            <!-- Dynamic Watermark overlay lines repeating -->
                            <div id="sim-watermark-overlay" class="absolute inset-0 pointer-events-none select-none opacity-[0.08] flex flex-wrap gap-x-8 gap-y-12 items-center justify-center p-4 font-mono font-bold text-[8px] text-black uppercase tracking-wider rotate-[-20deg]">
                                <!-- Will be loaded dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <span class="text-[10px] text-slate-400 font-semibold">Join EIISS to unlock full automated systems.</span>
            <div class="flex gap-2">
                <button onclick="closeDemoModal()" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 font-bold rounded-xl text-xs transition-all bg-white shadow-sm">Close Demo</button>
                <a href="register.php" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10">Get Verified Account</a>
            </div>
        </div>

    </div>
</div>

<script>
    // Global simulation matching sector
    let simSector = 'Technology';

    function openDemoModal(type) {
        const modal = document.getElementById('simulator-modal');
        const headerContainer = document.getElementById('modal-icon-container');
        const titleEl = document.getElementById('modal-title');
        
        // Hide all blocks
        document.getElementById('sim-matching').classList.add('hidden');
        document.getElementById('sim-protection').classList.add('hidden');
        document.getElementById('sim-yields').classList.add('hidden');
        document.getElementById('sim-uniqueness').classList.add('hidden');
        document.getElementById('sim-scoring').classList.add('hidden');
        document.getElementById('sim-watermarking').classList.add('hidden');
        
        // Setup details
        if (type === 'matching') {
            headerContainer.className = "p-2.5 rounded-2xl w-fit bg-blue-50 text-blue-600";
            headerContainer.innerHTML = '<i data-lucide="users" class="w-5 h-5"></i>';
            titleEl.innerText = "Investor Matchmaker Simulator";
            document.getElementById('sim-matching').classList.remove('hidden');
            document.getElementById('sim-matching-results').classList.add('hidden');
        } else if (type === 'protection') {
            headerContainer.className = "p-2.5 rounded-2xl w-fit bg-emerald-50 text-emerald-600";
            headerContainer.innerHTML = '<i data-lucide="shield" class="w-5 h-5"></i>';
            titleEl.innerText = "Blockchain Ledger Verification";
            document.getElementById('sim-protection').classList.remove('hidden');
            document.getElementById('sim-protection-results').classList.add('hidden');
        } else if (type === 'yields') {
            headerContainer.className = "p-2.5 rounded-2xl w-fit bg-purple-50 text-purple-600";
            headerContainer.innerHTML = '<i data-lucide="bar-chart-3" class="w-5 h-5"></i>';
            titleEl.innerText = "ROI Projections Calculator";
            document.getElementById('sim-yields').classList.remove('hidden');
            calculateYields();
        } else if (type === 'uniqueness') {
            headerContainer.className = "p-2.5 rounded-2xl w-fit bg-cyan-50 text-cyan-600";
            headerContainer.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5"></i>';
            titleEl.innerText = "AI Originality Checker Simulator";
            document.getElementById('sim-uniqueness').classList.remove('hidden');
            document.getElementById('sim-uniqueness-results').classList.add('hidden');
            document.getElementById('sim-uniqueness-text').value = '';
        } else if (type === 'scoring') {
            headerContainer.className = "p-2.5 rounded-2xl w-fit bg-orange-50 text-orange-600";
            headerContainer.innerHTML = '<i data-lucide="trending-up" class="w-5 h-5"></i>';
            titleEl.innerText = "Multi-Factor Scoring Weights";
            document.getElementById('sim-scoring').classList.remove('hidden');
            calculateScoring();
        } else if (type === 'watermarking') {
            headerContainer.className = "p-2.5 rounded-2xl w-fit bg-amber-50 text-amber-600";
            headerContainer.innerHTML = '<i data-lucide="lock" class="w-5 h-5"></i>';
            titleEl.innerText = "Confidential Watermark customizer";
            document.getElementById('sim-watermarking').classList.remove('hidden');
            updateWatermarkOverlay();
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function closeDemoModal() {
        const modal = document.getElementById('simulator-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // Matching Simulator script
    function toggleSimSector(btn, sector) {
        simSector = sector;
        // Reset buttons background
        const container = btn.parentNode;
        container.querySelectorAll('button').forEach(b => {
            b.className = "px-3 py-1.5 rounded-lg border text-xs font-bold bg-white border-slate-200 text-slate-600 hover:bg-slate-50";
        });
        btn.className = "px-3 py-1.5 rounded-lg border text-xs font-bold bg-blue-50 border-blue-200 text-blue-600";
    }

    function runMatchingSimulation() {
        const budget = parseInt(document.getElementById('sim-matching-budget').value);
        const risk = document.getElementById('sim-matching-risk').value;
        const resultsBox = document.getElementById('sim-matching-results');
        const resultsList = document.getElementById('sim-matching-list');
        
        resultsList.innerHTML = `
            <div class="py-6 text-center text-slate-400 flex flex-col items-center">
                <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                <span class="text-xs font-semibold">Running AI matching scan...</span>
            </div>
        `;
        resultsBox.classList.remove('hidden');

        setTimeout(() => {
            const projects = [
                { title: 'EcoFarm Drip Systems', sector: 'Agriculture', budget: 45000, risk: 'Low', score: 9.6 },
                { title: 'PayFast Mobile Wallet', sector: 'FinTech', budget: 120000, risk: 'Medium', score: 9.4 },
                { title: 'HealthTrak Diagnostic Suite', sector: 'Healthcare', budget: 95000, risk: 'Medium', score: 9.1 },
                { title: 'AgriCorp Seed Vending', sector: 'Agriculture', budget: 35000, risk: 'Low', score: 8.8 },
                { title: 'SokoFree E-comm Hub', sector: 'E-Commerce', budget: 200000, risk: 'High', score: 8.5 },
                { title: 'EduLearn Remote Video', sector: 'Technology', budget: 75000, risk: 'Low', score: 8.2 },
                { title: 'SmartGrid Dar IoT Node', sector: 'Technology', budget: 180000, risk: 'High', score: 9.2 }
            ];

            const filtered = projects.filter(p => p.sector === simSector || p.risk === risk);
            filtered.sort((a,b) => b.score - a.score);

            resultsList.innerHTML = '';
            if (filtered.length === 0) {
                resultsList.innerHTML = '<p class="text-xs text-slate-400 text-center py-2 font-semibold">No simulation matches found for parameters.</p>';
                return;
            }

            filtered.forEach(p => {
                const diffPct = Math.min(100, Math.round(100 - (Math.abs(p.budget - budget) / budget) * 20));
                const matchVal = Math.round((p.score * 10 + diffPct) / 2);
                resultsList.innerHTML += `
                    <div class="flex justify-between items-center bg-slate-50 hover:bg-slate-100/80 p-3 rounded-xl border border-slate-100 transition-colors text-left">
                        <div>
                            <span class="block font-bold text-xs text-slate-800">${p.title}</span>
                            <span class="text-[9px] text-slate-400 font-semibold uppercase mt-0.5">${p.sector} &bull; ${p.risk} Risk &bull; Target: $${p.budget.toLocaleString()}</span>
                        </div>
                        <div class="text-right flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[9px] font-black text-blue-600 bg-blue-50 border border-blue-100 rounded-full">${matchVal}% Match</span>
                        </div>
                    </div>
                `;
            });
        }, 1200);
    }

    // Protection Simulator
    function fillSampleHash() {
        const hashes = [
            '0x8f2d9c104abcf402da19f2a2491a92e105e1948482abf0f0b4d4e284a26101c8',
            '0xd2a49fbf8d1a1b181c0024f9b8c005f02abcb47d962ea98c74fb91e0a29487c5',
            '0x7c92b84a92c90e3ab7c0e0b4ffc5d80b62e49c0d12e840d02b89f81a7a402cb9'
        ];
        document.getElementById('sim-protection-hash').value = hashes[Math.floor(Math.random() * hashes.length)];
    }

    function runProtectionSimulation() {
        const hashVal = document.getElementById('sim-protection-hash').value.trim();
        const resultsBox = document.getElementById('sim-protection-results');
        
        if (!hashVal) {
            alert('Please enter or scan a hash first.');
            return;
        }

        resultsBox.innerHTML = `
            <div class="py-6 text-center text-slate-400 flex flex-col items-center">
                <div class="w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                <span class="text-xs font-semibold">Running ledger scan for hash records...</span>
            </div>
        `;
        resultsBox.classList.remove('hidden');

        setTimeout(() => {
            const blockNum = Math.floor(Math.random() * 2000000) + 1500000;
            const now = new Date().toISOString().replace('T', ' ').substring(0, 19);
            resultsBox.innerHTML = `
                <div class="bg-emerald-50/50 border border-emerald-100/80 rounded-2xl p-4.5 space-y-3 text-xs animate-fade-in text-left">
                    <div class="flex items-center gap-2 text-emerald-800 font-extrabold">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                        <span>IMMUTABLE PROOF OF OWNERSHIP VALIDATED</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3.5 pt-2 text-slate-600 font-semibold border-t border-emerald-100/60">
                        <div>
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Block Number</span>
                            <span class="text-slate-800 font-mono">#${blockNum.toLocaleString()}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Timestamp</span>
                            <span class="text-slate-800">${now}</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Proof Standard</span>
                            <span class="text-slate-800 uppercase">SHA-256 NOTARIZATION</span>
                        </div>
                        <div>
                            <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Gas Fee (Spent)</span>
                            <span class="text-slate-800">0.00018 ETH</span>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }, 1000);
    }

    // Yields simulator
    function calculateYields() {
        const capital = parseInt(document.getElementById('sim-yields-capital').value);
        const revenue = parseInt(document.getElementById('sim-yields-revenue').value);
        const margin = parseInt(document.getElementById('sim-yields-margin').value);

        document.getElementById('sim-yields-capval').innerText = '$' + capital.toLocaleString();
        document.getElementById('sim-yields-revval').innerText = '$' + revenue.toLocaleString();
        document.getElementById('sim-yields-margval').innerText = margin + '%';

        const profit = Math.round(revenue * (margin / 100));
        const roi = Math.round((profit / capital) * 100);
        
        let breakEven = 'N/A';
        if (profit > 0) {
            const monthlyProfit = profit / 12;
            breakEven = (capital / monthlyProfit).toFixed(1) + ' Mos';
        }

        document.getElementById('sim-yields-profit').innerText = '$' + profit.toLocaleString();
        document.getElementById('sim-yields-roi').innerText = roi + '%';
        document.getElementById('sim-yields-breakeven').innerText = breakEven;
    }

    // Uniqueness Simulator
    function runUniquenessSimulation() {
        const text = document.getElementById('sim-uniqueness-text').value.trim();
        const resultsBox = document.getElementById('sim-uniqueness-results');
        
        if (!text) {
            alert('Please paste or write a proposal idea abstract first.');
            return;
        }

        resultsBox.innerHTML = `
            <div class="py-6 text-center text-slate-400 flex flex-col items-center">
                <div class="w-6 h-6 border-2 border-cyan-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                <span class="text-xs font-semibold">Comparing terms with global registry...</span>
            </div>
        `;
        resultsBox.classList.remove('hidden');

        setTimeout(() => {
            const scores = [92.4, 94.8, 96.2, 98.6];
            const score = scores[Math.floor(Math.random() * scores.length)];
            resultsBox.innerHTML = `
                <div class="bg-cyan-50 border border-cyan-100 rounded-2xl p-4.5 space-y-2 text-xs animate-fade-in text-left">
                    <div class="flex items-center justify-between font-bold text-slate-700">
                        <span>Originality Rating</span>
                        <span class="text-cyan-700 text-sm font-black">${score}% Original</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div class="bg-cyan-500 h-full transition-all duration-500" style="width: ${score}%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5 leading-relaxed">Validation passes. Excellent uniqueness index – proposal abstract contains no copy matches in existing database.</p>
                </div>
            `;
        }, 1000);
    }

    // Scoring Simulator
    function calculateScoring() {
        const market = parseFloat(document.getElementById('sim-score-market').value);
        const innovation = parseFloat(document.getElementById('sim-score-innovation').value);
        const feasibility = parseFloat(document.getElementById('sim-score-feasibility').value);
        const finance = parseFloat(document.getElementById('sim-score-finance').value);

        document.getElementById('sim-score-mval').innerText = market.toFixed(1);
        document.getElementById('sim-score-ival').innerText = innovation.toFixed(1);
        document.getElementById('sim-score-fval').innerText = feasibility.toFixed(1);
        document.getElementById('sim-score-fnval').innerText = finance.toFixed(1);

        const finalScore = (market * 0.3 + innovation * 0.3 + feasibility * 0.2 + finance * 0.2).toFixed(1);
        document.getElementById('sim-score-result').innerText = finalScore + ' / 10';

        let desc = 'Strong Concept: Meets platform guidelines with active regional validation parameters.';
        if (finalScore >= 8.5) {
            desc = 'Exceptional Concept: High profitability quotient, distinct barrier to entry, and feasible setup.';
        } else if (finalScore < 6.0) {
            desc = 'Needs Improvement: Capital requirements or feasibility barriers indicate high operational risk.';
        }
        document.getElementById('sim-score-desc').innerText = desc;
    }

    // Watermark Simulator
    function updateWatermarkOverlay() {
        const nameInput = document.getElementById('sim-watermark-name').value.trim() || 'Confidential';
        const overlay = document.getElementById('sim-watermark-overlay');
        const now = new Date().toISOString().substring(0, 10);
        
        let words = '';
        for (let i = 0; i < 4; i++) {
            words += `<span class="inline-block p-2 border border-slate-400/20 rounded m-1">EIISS PROT &bull; ${nameInput} &bull; ${now}</span>`;
        }
        overlay.innerHTML = words;
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
