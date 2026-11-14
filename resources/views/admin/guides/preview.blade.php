@extends('layouts.app')

@section('title', '[Preview] ' . $guide->title)

@section('content')
<div x-data="guideAdminPreview({
    guideId: {{ $guide->id }},
    isPublished: {{ $guide->is_published ? 'true' : 'false' }},
    canAiEdit: {{ $guide->canBeModifiedByAi() ? 'true' : 'false' }},
    refineUrl: '{{ route('admin.guides.ai-refine', $guide->id) }}',
    publishUrl: '{{ route('admin.guides.publish', $guide->id) }}'
})" class="relative">

    <!-- ================= STICKY ADMIN PREVIEW TOP CONTROLS ================= -->
    <div class="sticky top-0 z-50 bg-slate-900 text-white shadow-xl border-b border-purple-500/30 px-4 sm:px-8 py-3">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <!-- Left Info -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-linear-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                    <i class="ph-bold ph-shield-check"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold tracking-wide uppercase text-purple-300">Admin Safe Preview</span>
                        <template x-if="!isPublished">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-slate-950">
                                🔒 Unpublished Draft
                            </span>
                        </template>
                        <template x-if="isPublished">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-400 text-slate-950">
                                ✅ Live on Website
                            </span>
                        </template>
                    </div>
                    <p class="text-[11px] text-slate-300">
                        <template x-if="!isPublished">
                            <span>Hidden from customers & Google bots until confirmed & published.</span>
                        </template>
                        <template x-if="isPublished">
                            <span>This guide is published and live on your public storefront.</span>
                        </template>
                    </p>
                </div>
            </div>

            <!-- Right Action Buttons -->
            <div class="flex items-center gap-2">
                <!-- AI Refine Trigger Button (ONLY for Pending guides!) -->
                <template x-if="canAiEdit">
                    <button type="button" @click="openAiDrawer = true"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-white bg-linear-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="ph-bold ph-sparkle text-sm"></i>
                        <span>Ask AI to Edit / Refine</span>
                    </button>
                </template>

                <template x-if="!canAiEdit">
                    <span class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 text-slate-400 border border-slate-700 flex items-center gap-1.5 cursor-not-allowed select-none"
                          title="Published guides are permanently locked from AI alteration to protect live content.">
                        <i class="ph-bold ph-lock text-xs"></i>
                        <span>AI Edit Locked (Published)</span>
                    </span>
                </template>

                <!-- Edit in Manual Editor -->
                <a href="{{ route('admin.guides.edit', $guide->id) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition flex items-center gap-1.5">
                    <i class="ph-bold ph-pencil text-xs"></i>
                    <span>Edit Manual</span>
                </a>

                <!-- Publish Button (if pending) -->
                <template x-if="!isPublished">
                    <button type="button" @click="publishGuide()" :disabled="publishing"
                        class="px-4 py-1.5 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-slate-950 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="ph-bold ph-rocket-launch text-sm"></i>
                        <span x-text="publishing ? 'Publishing...' : 'Publish Now'"></span>
                    </button>
                </template>

                <template x-if="isPublished">
                    <a href="{{ route('guides.show', $guide->slug) }}" target="_blank"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 transition flex items-center gap-1.5">
                        <i class="ph ph-arrow-square-out text-sm"></i>
                        <span>View Live</span>
                    </a>
                </template>

                <!-- Back to list -->
                <a href="{{ route('admin.guides.ai-pending') }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white transition">
                    ← Back to Pending
                </a>
            </div>
        </div>
    </div>

    <!-- ================= SLIDE-OVER AI REFINE DRAWER (FOR PENDING GUIDES) ================= -->
    <div x-show="openAiDrawer" x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         @keydown.escape.window="if(!refining) openAiDrawer = false">
        <!-- Backdrop -->
        <div x-show="openAiDrawer"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="if(!refining) openAiDrawer = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="openAiDrawer"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md bg-white shadow-2xl flex flex-col">

                <!-- Drawer Header -->
                <div class="p-5 border-b border-gray-150 flex items-center justify-between bg-linear-to-r from-purple-50 via-indigo-50/40 to-white">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center text-base shadow-xs">
                            <i class="ph-bold ph-sparkle"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">AI Guide Editor & Refiner</h3>
                            <p class="text-[11px] text-gray-500">Ask the AI to change, expand, or refine this pending guide.</p>
                        </div>
                    </div>
                    <button type="button" @click="openAiDrawer = false" :disabled="refining"
                        class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center cursor-pointer">
                        <i class="ph-bold ph-x text-sm"></i>
                    </button>
                </div>

                <!-- Drawer Body -->
                <div class="p-5 space-y-4 flex-1 overflow-y-auto">
                    <!-- Current Target Info Card -->
                    <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-1">
                        <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">Target Guide</span>
                        <div class="text-xs font-bold text-gray-900 line-clamp-1" x-text="guideData.title"></div>
                        <div class="text-[11px] text-gray-500" x-text="'Topic: ' + guideData.topic + ' • ' + guideData.reading_time_minutes + ' min read'"></div>
                    </div>

                    <!-- Instruction Input -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-800">
                            What would you like the AI to change? <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="refineInstruction" rows="4"
                            placeholder="e.g. FAQ ৩টি আরও যোগ করো, লেখার ভাষা আরও সহজ করো, দাম বিবেচনা পয়েন্ট বড় করো..."
                            class="w-full px-3.5 py-2.5 text-xs border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50/50 resize-none leading-relaxed"></textarea>

                        <!-- Quick Action Chips -->
                        <div class="space-y-1.5 pt-1">
                            <span class="text-[10px] text-gray-400 font-semibold block">Quick Prompt Suggestions:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="setInstruction('লেখার ভাষা আরও সহজ ও আকর্ষণীয় করে দাও')"
                                    class="px-2.5 py-1 rounded-full text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium transition cursor-pointer">
                                    + ভাষা সহজ করো
                                </button>
                                <button type="button" @click="setInstruction('৩টি নতুন বাস্তবসম্মত প্রশ্ন-উত্তর (FAQ) যোগ করো')"
                                    class="px-2.5 py-1 rounded-full text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium transition cursor-pointer">
                                    + ৩টি নতুন FAQ
                                </button>
                                <button type="button" @click="setInstruction('আসল ও নকল চেনার সুস্পষ্ট ৫টি বুলেট পয়েন্ট যোগ করো')"
                                    class="px-2.5 py-1 rounded-full text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium transition cursor-pointer">
                                    + আসল-নকল চেনার পয়েন্ট
                                </button>
                                <button type="button" @click="setInstruction('টাইটেলটি আরও আকর্ষণীয় ও এসইও ফ্রেন্ডলি করো')"
                                    class="px-2.5 py-1 rounded-full text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium transition cursor-pointer">
                                    + আকর্ষণীয় টাইটেল
                                </button>
                                <button type="button" @click="setInstruction('সংক্ষিপ্ত করো, অপ্রয়োজনীয় অংশ বাদ দাও')"
                                    class="px-2.5 py-1 rounded-full text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium transition cursor-pointer">
                                    + একটু ছোট করো
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Indicator -->
                    <div x-show="refining" class="p-4 rounded-xl bg-purple-50 border border-purple-200 flex items-center gap-3 text-purple-800 text-xs font-semibold">
                        <i class="ph-bold ph-circle-notch animate-spin text-base text-purple-600"></i>
                        <span>AI is revising and restructuring the guide...</span>
                    </div>

                    <!-- Refine Success Note -->
                    <div x-show="refineSuccessMsg" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                        <i class="ph-bold ph-check-circle text-base text-emerald-600"></i>
                        <span x-text="refineSuccessMsg"></span>
                    </div>

                    <!-- Security Alert -->
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900 leading-relaxed">
                        <i class="ph-bold ph-info text-amber-600 mr-1"></i>
                        <strong>Security Policy:</strong> AI editing is available only while this guide is pending review. Once you click "Publish Now", AI edits are locked to prevent accidental alteration of live store content.
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-gray-150 flex items-center justify-between bg-gray-50">
                    <button type="button" @click="openAiDrawer = false" :disabled="refining"
                        class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 transition cursor-pointer disabled:opacity-40">
                        Cancel
                    </button>
                    <button type="button" @click="submitRefinement()" :disabled="refining || !refineInstruction.trim()"
                        class="px-5 py-2.5 bg-linear-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <template x-if="refining">
                            <span class="flex items-center gap-1.5">
                                <i class="ph-bold ph-circle-notch animate-spin text-sm"></i>
                                <span>Refining Guide...</span>
                            </span>
                        </template>
                        <template x-if="!refining">
                            <span class="flex items-center gap-1.5">
                                <i class="ph-bold ph-paper-plane-tilt text-sm"></i>
                                <span>Apply Revisions</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= PUBLIC ARTICLE VIEW (EXACT STOREFRONT LAYOUT) ================= -->
    <main class="min-h-screen bg-white px-4 lg:px-8 py-6 pb-20">
        <!-- Breadcrumb Top Bar -->
        <div class="mb-5 pb-3 border-b border-gray-100">
            @include('frontend.guides.partials._breadcrumbs', ['guide' => $guide])
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
            <!-- Left Main Column (8 cols) -->
            <article class="lg:col-span-8 space-y-6">
                <!-- Topic & Title -->
                <div class="space-y-3">
                    <div x-show="guideData.topic">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700" x-text="guideData.topic"></span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 tracking-tight leading-tight" x-text="guideData.title"></h1>

                    <!-- Article Meta Bar -->
                    <div class="flex items-center justify-between flex-wrap gap-4 py-3 border-y border-gray-100 text-xs text-gray-500">
                        <div class="flex items-center gap-3 sm:gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-600 font-bold flex items-center justify-center text-xs border border-brand-100 shrink-0">
                                    {{ substr(\App\Models\Setting::get('site_name', 'SAJ'), 0, 1) }}
                                </div>
                                <div>
                                    <span class="font-bold text-gray-800">{{ \App\Models\Setting::get('site_name', 'SAJ') }} Editorial</span>
                                    <span class="block text-[10px] text-purple-600 font-semibold">AI Generated • Pending Review</span>
                                </div>
                            </div>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <i class="ph ph-calendar"></i>
                                <span>Preview Version</span>
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <i class="ph ph-clock"></i>
                                <span x-text="guideData.reading_time_minutes + ' min read'"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Featured Image -->
                @if($guide->featured_image)
                <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-xs">
                    <img src="{{ $guide->featured_image_url }}" alt="{{ $guide->title }}" class="w-full h-auto max-h-[460px] object-cover">
                </div>
                @endif

                <!-- Excerpt Card -->
                <div x-show="guideData.excerpt" class="p-4 sm:p-5 rounded-2xl bg-brand-50/40 border-l-4 border-brand-600 text-gray-700 text-sm leading-relaxed font-medium" x-text="guideData.excerpt"></div>

                <!-- Main Content HTML with Scoped Styling -->
                <div class="guide-content prose max-w-none text-gray-800 leading-relaxed space-y-4 font-sans text-sm sm:text-base" id="guideContentBody" x-html="processedPreviewContent"></div>

                <!-- Related Products Section -->
                @if($relatedProducts->count() > 0)
                <div class="pt-8 border-t border-gray-150 space-y-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph-bold ph-bag text-brand-600"></i>
                        <span>Featured Products in this Guide</span>
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($relatedProducts as $product)
                        <div class="bg-white border border-gray-150 rounded-2xl p-3 shadow-2xs space-y-2">
                            <img src="{{ $product->primary_image_url }}" class="w-full h-36 object-cover rounded-xl">
                            <h4 class="text-xs font-bold text-gray-900 line-clamp-1">{{ $product->title }}</h4>
                            <div class="text-xs font-bold text-brand-600">৳{{ number_format($product->base_price, 2) }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </article>

            <!-- Right Sidebar Column (4 cols) -->
            <aside class="lg:col-span-4 space-y-6">
                <!-- Admin Info Widget -->
                <div class="p-5 rounded-2xl bg-purple-50/60 border border-purple-200/80 space-y-3">
                    <div class="flex items-center gap-2 text-purple-900 font-bold text-xs uppercase tracking-wider">
                        <i class="ph-bold ph-sparkle text-purple-600 text-sm"></i>
                        <span>AI Assistant & Metadata</span>
                    </div>
                    <div class="text-xs space-y-1.5 text-gray-600">
                        <div>Mode: <strong class="text-gray-900 capitalize">{{ $guide->ai_metadata['mode'] ?? 'Custom' }}</strong></div>
                        <div>Generated: <strong class="text-gray-900">{{ $guide->created_at->format('d M, Y h:i A') }}</strong></div>
                        <div>SEO Title: <strong class="text-gray-900">{{ $guide->meta_title ?: 'Auto Generated' }}</strong></div>
                    </div>
                </div>

                <!-- Table of Contents Widget -->
                @if(!empty($guide->table_of_contents))
                <div class="p-5 rounded-2xl bg-gray-50/70 border border-gray-200/80 space-y-3">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="ph-bold ph-list-numbers text-brand-600"></i>
                        <span>Table of Contents</span>
                    </h3>
                    <ul class="space-y-1.5 text-xs text-gray-600">
                        @foreach($guide->table_of_contents as $item)
                        <li class="{{ $item['level'] === 3 ? 'ml-3 text-gray-500' : 'font-medium text-gray-800' }}">
                            {{ $item['title'] }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </aside>
        </div>
    </main>
</div>

<script>
    function guideAdminPreview(config) {
        return {
            guideId: config.guideId,
            isPublished: config.isPublished,
            canAiEdit: config.canAiEdit,
            refineUrl: config.refineUrl,
            publishUrl: config.publishUrl,

            openAiDrawer: false,
            refining: false,
            refineInstruction: '',
            refineSuccessMsg: '',
            publishing: false,

            guideData: {
                title: @json($guide->title),
                topic: @json($guide->topic),
                excerpt: @json($guide->excerpt),
                content: @json($guide->content),
                reading_time_minutes: {{ $guide->reading_time_minutes }}
            },

            get processedPreviewContent() {
                let raw = this.guideData.content || '';
                // 1. Normalize <p>## Heading</p>
                raw = raw.replace(/<p>\s*(#{1,6})\s+(.*?)\s*<\/p>/gi, function(match, hashes, text) {
                    const level = hashes.length;
                    return '<h' + level + '>' + text.trim() + '</h' + level + '>';
                });
                // 2. Normalize raw markdown headings on new lines ## Heading
                raw = raw.replace(/^(#{1,6})\s+(.+)$/gm, function(match, hashes, text) {
                    const level = hashes.length;
                    return '<h' + level + '>' + text.trim() + '</h' + level + '>';
                });
                // 3. Normalize **bold** text
                raw = raw.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                return raw;
            },

            setInstruction(text) {
                if (!this.refineInstruction) {
                    this.refineInstruction = text;
                } else {
                    this.refineInstruction += ', ' + text;
                }
            },

            async submitRefinement() {
                if (!this.canAiEdit) {
                    alert('Security Guard: Published guides cannot be modified by AI.');
                    return;
                }
                const instruction = this.refineInstruction.trim();
                if (!instruction || this.refining) return;

                this.refining = true;
                this.refineSuccessMsg = '';

                try {
                    const res = await fetch(this.refineUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ instruction: instruction })
                    });

                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        alert(data.message || 'AI Refinement failed.');
                        return;
                    }

                    // Update live DOM state seamlessly!
                    this.guideData.title = data.guide.title;
                    this.guideData.topic = data.guide.topic;
                    this.guideData.excerpt = data.guide.excerpt;
                    this.guideData.content = data.guide.content;
                    this.guideData.reading_time_minutes = data.guide.reading_time_minutes;

                    this.refineSuccessMsg = 'Guide updated and preview refreshed with your requested changes!';
                    this.refineInstruction = '';
                } catch (e) {
                    alert('Network error during AI refinement: ' + e.message);
                } finally {
                    this.refining = false;
                }
            },

            async publishGuide() {
                if (!confirm('Are you sure you want to publish this guide? Once published, it will be visible on your live website and permanently locked from further AI edits.')) {
                    return;
                }

                this.publishing = true;
                try {
                    const res = await fetch(this.publishUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        alert(data.message || 'Failed to publish guide.');
                        return;
                    }

                    this.isPublished = true;
                    this.canAiEdit = false;
                    this.openAiDrawer = false;
                    alert('🎉 Buying Guide published successfully and is now live on the storefront!');
                } catch (e) {
                    alert('Network error during publishing: ' + e.message);
                } finally {
                    this.publishing = false;
                }
            }
        };
    }
</script>

<style>
/* Scoped Typography & Headings for Buying Guide Preview */
.guide-content h1 {
    display: block !important;
    font-size: 1.875rem !important; /* 30px */
    font-weight: 800 !important;
    color: #0f172a !important;
    margin-top: 2.25rem !important;
    margin-bottom: 1rem !important;
    line-height: 1.3 !important;
    letter-spacing: -0.02em !important;
}
.guide-content h2 {
    display: block !important;
    font-size: 1.5rem !important; /* 24px */
    font-weight: 800 !important;
    color: #1e293b !important;
    margin-top: 2.25rem !important;
    margin-bottom: 0.85rem !important;
    line-height: 1.35 !important;
    border-bottom: 2px solid #f1f5f9 !important;
    padding-bottom: 0.5rem !important;
}
.guide-content h3 {
    display: block !important;
    font-size: 1.25rem !important; /* 20px */
    font-weight: 700 !important;
    color: #334155 !important;
    margin-top: 1.6rem !important;
    margin-bottom: 0.6rem !important;
    line-height: 1.4 !important;
}
.guide-content h4 {
    display: block !important;
    font-size: 1.125rem !important; /* 18px */
    font-weight: 700 !important;
    color: #475569 !important;
    margin-top: 1.25rem !important;
    margin-bottom: 0.5rem !important;
}
.guide-content p {
    margin-top: 0.85rem !important;
    margin-bottom: 0.85rem !important;
    line-height: 1.85 !important;
    color: #374151 !important;
    font-size: 1rem !important;
}
.guide-content strong, .guide-content b {
    font-weight: 700 !important;
    color: #0f172a !important;
}
.guide-content ul {
    list-style-type: disc !important;
    padding-left: 1.6rem !important;
    margin-top: 0.85rem !important;
    margin-bottom: 0.85rem !important;
}
.guide-content ol {
    list-style-type: decimal !important;
    padding-left: 1.6rem !important;
    margin-top: 0.85rem !important;
    margin-bottom: 0.85rem !important;
}
.guide-content li {
    margin-top: 0.4rem !important;
    margin-bottom: 0.4rem !important;
    line-height: 1.7 !important;
    color: #374151 !important;
}
.guide-content blockquote {
    border-left: 4px solid #e11d48 !important;
    background-color: #fff1f2 !important;
    padding: 0.75rem 1.25rem !important;
    border-radius: 0 0.75rem 0.75rem 0 !important;
    margin: 1.25rem 0 !important;
    color: #4b5563 !important;
    font-style: italic !important;
}
.guide-content table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin-top: 1.5rem !important;
    margin-bottom: 1.5rem !important;
}
.guide-content table th, .guide-content table td {
    border: 1px solid #e5e7eb !important;
    padding: 0.75rem 1rem !important;
    text-align: left !important;
}
.guide-content table th {
    background-color: #f9fafb !important;
    font-weight: 700 !important;
}
</style>
@endsection
