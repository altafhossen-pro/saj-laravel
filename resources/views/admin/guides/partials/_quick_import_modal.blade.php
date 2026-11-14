<!-- Quick AI Import / Paste Modal Component -->
<div x-data="quickImportGuideModal({
    importUrl: '{{ route('admin.guides.quick-import') }}',
    createUrl: '{{ route('admin.guides.create') }}',
    csrf: '{{ csrf_token() }}'
})"
     @open-quick-import-modal.window="openModal()"
     @keydown.escape.window="if (!importing) isOpen = false"
     class="relative z-50">

    <!-- Modal Backdrop -->
    <div x-show="isOpen" x-cloak
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="if (!importing) isOpen = false"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity"></div>

    <!-- Modal Dialog Window -->
    <div x-show="isOpen" x-cloak
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 overflow-y-auto">

        <div class="bg-white rounded-3xl shadow-2xl border border-gray-150 w-full max-w-3xl overflow-hidden my-auto max-h-[92vh] flex flex-col">
            <!-- Modal Header -->
            <div class="px-6 py-4.5 border-b border-gray-150 flex items-center justify-between bg-gradient-to-r from-purple-50 via-indigo-50/40 to-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center text-lg shadow-sm">
                        <i class="ph-bold ph-clipboard-text"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>Quick AI Guide Import / Paste</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700">ChatGPT • Claude • Gemini</span>
                        </h3>
                        <p class="text-xs text-gray-500">Paste raw AI output (JSON or Markdown) to auto-fill or save directly into Pending Review.</p>
                    </div>
                </div>
                <button type="button" @click="isOpen = false" :disabled="importing"
                    class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center cursor-pointer">
                    <i class="ph-bold ph-x text-sm"></i>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <!-- STEP 1: Copy Master Prompt Box -->
                <div class="p-4 rounded-2xl bg-purple-50/50 border border-purple-200/80 space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-purple-600 text-white text-[11px] font-bold flex items-center justify-center">1</span>
                            <h4 class="text-xs font-bold text-purple-950 uppercase tracking-wider">Step 1: Copy Master AI Prompt</h4>
                        </div>
                        <button type="button" @click="copyPrompt()"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-white transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                            :class="copied ? 'bg-emerald-600' : 'bg-purple-600 hover:bg-purple-700'">
                            <i class="ph-bold" :class="copied ? 'ph-check' : 'ph-copy'"></i>
                            <span x-text="copied ? 'Copied Prompt to Clipboard! ✓' : 'Copy Master Prompt'"></span>
                        </button>
                    </div>
                    <p class="text-xs text-purple-900/80 leading-relaxed">
                        প্রম্পটটি কপি করে <strong>ChatGPT</strong>, <strong>Claude</strong> বা <strong>Gemini</strong>-তে দিন এবং আপনার টপিক বা পণ্যের নাম (যেমন: "সেরা মেছতা দূর করার ক্রিম" বা "Winter Jacket") বলে দিন।
                    </p>
                    <details class="text-xs text-purple-800">
                        <summary class="cursor-pointer font-bold hover:underline select-none">View Master Prompt Template</summary>
                        <pre class="mt-2 p-3 bg-slate-900 text-purple-200 rounded-xl text-[11px] overflow-x-auto whitespace-pre-wrap font-mono leading-relaxed" x-text="masterPromptText"></pre>
                    </details>
                </div>

                <!-- STEP 2: Paste Area -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[11px] font-bold flex items-center justify-center">2</span>
                            <label class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                Step 2: Paste AI Response Here <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <button type="button" @click="pasteFromClipboard()"
                            class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer">
                            <i class="ph-bold ph-clipboard"></i>
                            <span>Paste from Clipboard</span>
                        </button>
                    </div>

                    <textarea x-model="rawContent" rows="8"
                        @input="autoCheckContent()"
                        placeholder="Paste your AI generated JSON object or Markdown text here...
Example JSON:
{
  &quot;title&quot;: &quot;সেরা মেছতা দূর করার ক্রিম ২০২৬: বিশেষজ্ঞদের পরামর্শ ও কেনার গাইড&quot;,
  &quot;topic&quot;: &quot;Skincare&quot;,
  &quot;excerpt&quot;: &quot;মেছতা দূর করতে কোন উপাদানগুলো কার্যকর এবং কোন ক্রিমটি বেছে নেবেন...&quot;,
  &quot;content&quot;: &quot;&lt;h2&gt;১. মেছতার কারণ&lt;/h2&gt;&lt;p&gt;...&lt;/p&gt;&quot;
}"
                        class="w-full px-4 py-3 text-xs border border-gray-200 rounded-2xl focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50/40 resize-y font-mono leading-relaxed"></textarea>

                    <div class="flex items-center justify-between text-[11px] text-gray-500">
                        <span>Accepts valid JSON or Markdown with headings (# Title, ## Section, ### Sub).</span>
                        <button type="button" @click="parseContent()" :disabled="!rawContent.trim() || parsing"
                            class="text-xs font-bold text-purple-600 hover:text-purple-700 disabled:opacity-40 flex items-center gap-1 cursor-pointer">
                            <i class="ph-bold ph-arrows-clockwise" :class="{ 'animate-spin': parsing }"></i>
                            <span x-text="parsing ? 'Parsing...' : 'Verify / Parse Structure'"></span>
                        </button>
                    </div>
                </div>

                <!-- Error Alert -->
                <div x-show="errorMessage" x-cloak
                    class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5">
                    <i class="ph-bold ph-warning-circle text-rose-600 text-base shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <strong class="font-bold block">Import Error</strong>
                        <span x-text="errorMessage"></span>
                    </div>
                </div>

                <!-- STEP 3: Parsed Preview Card -->
                <div x-show="parsedData" x-cloak class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph-bold ph-check-circle text-emerald-600 text-sm"></i>
                            <span>Detected Guide Structure</span>
                        </span>
                        <span class="text-[11px] font-bold text-emerald-700" x-text="parsedData?.reading_time_minutes + ' min read'"></span>
                    </div>
                    <div class="bg-white p-3.5 rounded-xl border border-emerald-150 space-y-2">
                        <h4 class="text-sm font-bold text-gray-900" x-text="parsedData?.title"></h4>
                        <div class="flex items-center gap-2 text-[11px] text-gray-500">
                            <span class="px-2 py-0.5 rounded-md bg-gray-100 font-bold text-gray-700" x-text="'Topic: ' + (parsedData?.topic || 'General')"></span>
                            <span x-show="parsedData?.meta_title" x-text="'SEO Title: ' + parsedData?.meta_title"></span>
                        </div>
                        <p class="text-xs text-gray-600 line-clamp-2 italic" x-text="parsedData?.excerpt"></p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Controls -->
            <div class="px-6 py-4 border-t border-gray-150 bg-gray-50 flex items-center justify-between flex-wrap gap-3 shrink-0">
                <button type="button" @click="isOpen = false" :disabled="importing"
                    class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-200/60 rounded-xl transition cursor-pointer">
                    Cancel
                </button>

                <div class="flex items-center gap-2.5">
                    <!-- Button A: Load into Manual Create Form -->
                    <button type="button" @click="autofillCreateForm()" :disabled="!rawContent.trim() || importing"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-gray-700 bg-white hover:bg-gray-100 border border-gray-200 shadow-2xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="ph-bold ph-pencil-simple text-sm text-gray-500"></i>
                        <span>Load into Create Form</span>
                    </button>

                    <!-- Button B: Save directly to AI Pending & Preview (Recommended) -->
                    <button type="button" @click="saveAndPreview()" :disabled="!rawContent.trim() || importing"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-sm hover:shadow transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <template x-if="importing">
                            <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        </template>
                        <template x-if="!importing">
                            <i class="ph-bold ph-rocket-launch text-sm"></i>
                        </template>
                        <span x-text="importing ? 'Importing Guide...' : 'Save as AI Pending & Safe Preview'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.SAJ_BUYING_GUIDE_PROMPT = `You are a senior ecommerce buying guide writer, product specialist, and SEO expert for the Bangladeshi ecommerce brand "Saj.bd" (সাজ).
Write an engaging, authoritative, and comprehensive buying guide in natural, fluent Bengali (বাংলা) for Bangladeshi shoppers.

SUBJECT / TOPIC: [এখানে আপনার কাঙ্ক্ষিত বিষয় বা প্রোডাক্টের নাম লিখুন, যেমন: "সেরা মেছতা দূর করার ক্রিম" বা "Winter Jacket Guide"]

GUIDELINES:
1. Tone: Friendly, authoritative, transparent, and trustworthy.
2. Structure in HTML:
   - Use <h2> for main section headers (e.g. ভূমিকা, কেন এটি কিনবেন, কেনার আগে যা খেয়াল রাখবেন, সুবিধা ও সীমাবদ্ধতা, ব্যবহারবিধি, FAQ).
   - Use <h3> for sub-points.
   - Use <p> for readable, conversational paragraphs.
   - Use <ul> and <li> for feature bullet points, checklists, or tips.
   - Include a <h2>প্রায়শই জিজ্ঞাসিত প্রশ্ন (FAQ)</h2> section with 3 to 5 clear Q&A pairs using <strong> for questions.
3. Keep content clean HTML (no <html>, <body>, or markdown code blocks).

OUTPUT FORMAT:
Return ONLY a valid JSON object without markdown code blocks, backticks, or extra commentary.
JSON Structure:
{
  "title": "আকর্ষণীয় ও এসইও ফ্রেন্ডলি বাংলা শিরোনাম",
  "topic": "ক্যাটাগরি বা টপিকের নাম (যেমন: Skincare, Electronics)",
  "excerpt": "২-৩ বাক্যে আকর্ষণীয় সারসংক্ষেপ (১৫০-২৫০ অক্ষর)",
  "reading_time_minutes": 5,
  "meta_title": "SEO Meta Title (max 60 chars)",
  "meta_description": "SEO Meta Description (max 155 chars)",
  "meta_keywords": "buying guide, bangla, keywords...",
  "content": "<h2>...</h2><p>...</p>..."
}`;

    window.copyBuyingGuideMasterPrompt = function(btnElement) {
        if (!navigator.clipboard) {
            window.dispatchEvent(new CustomEvent('open-quick-import-modal'));
            return;
        }
        navigator.clipboard.writeText(window.SAJ_BUYING_GUIDE_PROMPT).then(function() {
            if (btnElement) {
                const originalHtml = btnElement.innerHTML;
                btnElement.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
                btnElement.classList.remove('bg-white', 'text-gray-700');
                btnElement.innerHTML = '<i class="ph-bold ph-check text-emerald-600 text-base"></i><span class="font-bold text-emerald-700">Copied! ✓</span>';
                setTimeout(function() {
                    btnElement.innerHTML = originalHtml;
                    btnElement.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
                    btnElement.classList.add('bg-white', 'text-gray-700');
                }, 2500);
            }
        }).catch(function() {
            window.dispatchEvent(new CustomEvent('open-quick-import-modal'));
        });
    };

    function quickImportGuideModal(config) {
        return {
            isOpen: false,
            importUrl: config.importUrl,
            createUrl: config.createUrl,
            csrf: config.csrf,

            rawContent: '',
            copied: false,
            parsing: false,
            importing: false,
            errorMessage: '',
            parsedData: null,

            masterPromptText: window.SAJ_BUYING_GUIDE_PROMPT,

            openModal() {
                this.isOpen = true;
                this.errorMessage = '';
                this.copied = false;
            },

            async copyPrompt() {
                try {
                    await navigator.clipboard.writeText(this.masterPromptText);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 3000);
                } catch (e) {
                    alert('Clipboard access denied. Please manually copy the prompt from template.');
                }
            },

            async pasteFromClipboard() {
                try {
                    const text = await navigator.clipboard.readText();
                    if (text) {
                        this.rawContent = text;
                        this.autoCheckContent();
                    }
                } catch (e) {
                    alert('Could not paste from clipboard. Please paste manually into the box.');
                }
            },

            autoCheckContent() {
                this.errorMessage = '';
                if (this.rawContent.trim().length > 30 && !this.parsedData) {
                    // Try client-side JSON parse first
                    try {
                        let text = this.rawContent.trim();
                        if (text.startsWith('```json')) text = text.replace(/^```json\s*/i, '').replace(/\s*```$/, '');
                        else if (text.startsWith('```')) text = text.replace(/^```\s*/, '').replace(/\s*```$/, '');
                        const firstB = text.indexOf('{');
                        const lastB = text.lastIndexOf('}');
                        if (firstB !== -1 && lastB !== -1 && lastB > firstB) {
                            const parsed = JSON.parse(text.substring(firstB, lastB + 1));
                            if (parsed.title && (parsed.content || parsed.body)) {
                                this.parsedData = {
                                    title: parsed.title,
                                    topic: parsed.topic || 'Buying Guide',
                                    excerpt: parsed.excerpt || '',
                                    reading_time_minutes: parsed.reading_time_minutes || 4,
                                    meta_title: parsed.meta_title || parsed.title,
                                    content: parsed.content || parsed.body
                                };
                            }
                        }
                    } catch (e) {
                        // ignore client parse error until user clicks parse
                    }
                }
            },

            async parseContent() {
                if (!this.rawContent.trim()) return;
                this.parsing = true;
                this.errorMessage = '';
                try {
                    const res = await fetch(this.importUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            raw_text: this.rawContent,
                            action: 'parse'
                        })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        this.errorMessage = data.message || 'Failed to parse content.';
                        return;
                    }
                    this.parsedData = data.data;
                } catch (e) {
                    this.errorMessage = 'Network error while parsing: ' + e.message;
                } finally {
                    this.parsing = false;
                }
            },

            async saveAndPreview() {
                if (!this.rawContent.trim()) return;
                this.importing = true;
                this.errorMessage = '';
                try {
                    const res = await fetch(this.importUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            raw_text: this.rawContent,
                            action: 'save'
                        })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        this.errorMessage = data.message || 'Import failed. Please verify format.';
                        this.importing = false;
                        return;
                    }

                    // Success! Redirect directly to Safe Preview
                    if (data.preview_url) {
                        window.location.href = data.preview_url;
                    } else {
                        window.location.reload();
                    }
                } catch (e) {
                    this.errorMessage = 'Network error while saving: ' + e.message;
                    this.importing = false;
                }
            },

            async autofillCreateForm() {
                if (!this.parsedData) {
                    await this.parseContent();
                    if (!this.parsedData) return;
                }

                // If currently on create page, autofill directly
                if (window.location.pathname.includes('/guides/create')) {
                    this.applyToCurrentCreateForm(this.parsedData);
                    this.isOpen = false;
                    return;
                }

                // Otherwise save to sessionStorage and redirect to create page
                sessionStorage.setItem('saj_guide_autofill', JSON.stringify(this.parsedData));
                window.location.href = this.createUrl;
            },

            applyToCurrentCreateForm(data) {
                window.dispatchEvent(new CustomEvent('guide-autofill', { detail: data }));
                alert('Form fields populated with AI Guide data!');
            }
        };
    }
</script>
