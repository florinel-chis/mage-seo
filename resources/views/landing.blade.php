<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Automated SEO for Magento 2 & Adobe Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0d6efd',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .text-primary {
            color: #0d6efd;
        }

        .border-primary {
            border-color: #0d6efd;
        }

        @media (max-width: 767.98px) {
            body.has-sticky-cta {
                padding-bottom: 68px;
            }
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-gray-900">MageSeo</h1>
                        <p class="text-xs text-gray-500">by Magendoo Interactive</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('filament.admin.auth.login') }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded btn-primary">
                        Admin Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-gray-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl">
                    Automated SEO for Magento 2 & Adobe Commerce
                </h1>
                <p class="mt-4 text-xl text-gray-600 max-w-3xl mx-auto">
                    Generate high-quality, hallucination-free SEO metadata for your products using our Writer-Auditor AI pipeline. Save hours of manual work while improving search rankings.
                </p>
                <div class="mt-8">
                    <a href="{{ route('filament.admin.auth.login') }}"
                       class="inline-flex items-center px-6 py-3 text-base font-medium rounded btn-primary">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Benefits -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="text-4xl font-bold text-primary">10x</div>
                    <div class="mt-2 text-sm text-gray-600">Faster than manual writing</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-primary">90%+</div>
                    <div class="mt-2 text-sm text-gray-600">Auto-approval rate</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-primary">0</div>
                    <div class="mt-2 text-sm text-gray-600">Hallucinations with AI Auditor</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Service Description -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">
                AI-Powered SEO Content Generation
            </h2>

            <p class="text-lg text-gray-700 mb-6">
                MageSeo uses a dual-agent AI pipeline to automatically generate SEO-optimized metadata for your Magento products. The Writer AI creates compelling meta titles, descriptions, and keywords, while the Auditor AI validates every claim against source data to eliminate hallucinations.
            </p>

            <h4 class="text-lg font-semibold text-gray-900 mt-8 mb-4">Key Features</h4>
            <ul class="space-y-2 text-gray-700">
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Writer-Auditor Pipeline:</strong> Two-stage AI process ensures accuracy and quality</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Hallucination Detection:</strong> Auditor flags any unsupported claims with confidence scores</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Bulk Processing:</strong> Handle hundreds of products via background queue jobs</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Smart Auto-Approval:</strong> High-confidence drafts (>0.9 score) approved automatically</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Magento Integration:</strong> Direct REST/GraphQL API integration for seamless sync</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Rate Limiting:</strong> Token bucket algorithm protects your Magento and OpenAI APIs</span>
                </li>
            </ul>

            <h4 class="text-lg font-semibold text-gray-900 mt-8 mb-4">Key Benefits</h4>
            <ul class="space-y-2 text-gray-700">
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Save Time:</strong> 10x faster than manual SEO writing for large catalogs</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Reduce Costs:</strong> Eliminate external copywriting expenses and scale in-house</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Maintain Quality:</strong> Auditor ensures factual accuracy and prevents misleading claims</span>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-primary mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span><strong>Improve Rankings:</strong> Consistent, keyword-optimized metadata across your catalog</span>
                </li>
            </ul>

            <div class="mt-8">
                <a href="{{ route('filament.admin.auth.login') }}"
                   class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded btn-primary">
                    Get Started
                </a>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">How It Works</h2>

            <div class="space-y-6">
                <div class="border-l-4 border-primary pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">1. Connect Your Magento Store</h3>
                    <p class="text-gray-700">Configure your Magento 2 or Adobe Commerce store credentials via REST/GraphQL API. Select which products or categories you want to optimize.</p>
                </div>

                <div class="border-l-4 border-primary pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">2. AI Generates SEO Content</h3>
                    <p class="text-gray-700">The Writer AI analyzes product attributes, descriptions, and specifications to create compelling meta titles, descriptions, and keywords optimized for search engines.</p>
                </div>

                <div class="border-l-4 border-primary pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">3. Auditor Validates Quality</h3>
                    <p class="text-gray-700">The Auditor AI cross-references every claim against source data, flags potential hallucinations, and assigns confidence scores (0.0-1.0) to each draft.</p>
                </div>

                <div class="border-l-4 border-primary pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">4. Review & Approve</h3>
                    <p class="text-gray-700">High-confidence drafts (>0.9) are auto-approved. Flagged content is sent for human review via the Filament dashboard with clear audit warnings.</p>
                </div>

                <div class="border-l-4 border-primary pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">5. Sync to Magento</h3>
                    <p class="text-gray-700">Approved metadata is synced back to your Magento store with a single click, updating product SEO fields instantly.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Technologies -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Built With</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-lg font-semibold text-gray-900">Laravel 12</div>
                    <div class="text-sm text-gray-600">Backend Framework</div>
                </div>
                <div>
                    <div class="text-lg font-semibold text-gray-900">Filament 4.2</div>
                    <div class="text-sm text-gray-600">Admin Panel</div>
                </div>
                <div>
                    <div class="text-lg font-semibold text-gray-900">OpenAI GPT-4</div>
                    <div class="text-sm text-gray-600">AI Engine</div>
                </div>
                <div>
                    <div class="text-lg font-semibold text-gray-900">Magento 2 API</div>
                    <div class="text-sm text-gray-600">E-commerce Integration</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Services -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Services</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 hover:text-primary">SEO Generation</a></li>
                        <li><a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 hover:text-primary">Product Catalog</a></li>
                        <li><a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 hover:text-primary">Magento Integration</a></li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Resources</h3>
                    <ul class="space-y-2">
                        <li><a href="https://magendoo.ro" class="text-gray-600 hover:text-primary" target="_blank" rel="noopener">Magendoo Interactive</a></li>
                        <li><a href="https://magendoo.ro/services" class="text-gray-600 hover:text-primary" target="_blank" rel="noopener">E-commerce Services</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li><a href="tel:+40-747362500" class="text-gray-600 hover:text-primary">+40-747362500</a></li>
                        <li><a href="https://magendoo.ro/contact" class="text-gray-600 hover:text-primary" target="_blank" rel="noopener">Get in Touch</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-center text-gray-500 text-sm">
                    &copy; {{ date('Y') }} MageSeo. A Magendoo Interactive product. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Sticky Mobile CTA -->
    <div class="fixed bottom-0 left-0 right-0 bg-primary text-white p-4 md:hidden z-50" style="padding-bottom: env(safe-area-inset-bottom, 1rem);">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <a href="tel:+40-747362500" class="flex items-center text-white hover:text-gray-100">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                Call
            </a>
            <a href="{{ route('filament.admin.auth.login') }}" class="bg-white text-primary px-4 py-2 rounded font-medium hover:bg-gray-100">
                Get Started
            </a>
        </div>
    </div>

    <script>
        // Add class to body when sticky CTA is present on mobile
        if (window.innerWidth < 768) {
            document.body.classList.add('has-sticky-cta');
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth < 768) {
                document.body.classList.add('has-sticky-cta');
            } else {
                document.body.classList.remove('has-sticky-cta');
            }
        });
    </script>
</body>
</html>
