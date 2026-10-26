@extends('layouts.admin')

@section('title', 'Telegram Configuration - Admin Panel')
@section('header_title', 'Telegram Bot Configuration')

@section('content')
    <div class="w-full space-y-6" x-data="telegramSettings()">
        <!-- Toast Notification -->
        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl border text-sm font-medium"
            :class="toast.type === 'success' ? 'bg-gray-900 border-gray-800 text-white' : 'bg-red-900 border-red-800 text-white'"
            style="display: none;">
            <i class="text-xl"
                :class="toast.type === 'success' ? 'ph-fill ph-check-circle text-green-400' : 'ph-fill ph-warning-circle text-red-400'"></i>
            <span x-text="toast.message"></span>
            <button type="button" @click="toast.show = false" class="ml-2 text-gray-400 hover:text-white transition-colors">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <!-- Header Breadcrumbs / Back button & Top Action -->
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.settings.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <i class="ph ph-arrow-left text-lg"></i>
                <span>Back to Settings</span>
            </a>
            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-sky-700 font-medium border border-sky-100 text-xs">
                    <i class="ph-bold ph-paper-plane-tilt"></i> Telegram Bot API
                </span>
                <button type="button" @click="document.getElementById('saveTelegramBtn').click()" :disabled="saving"
                    class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                    <i x-show="!saving" class="ph-bold ph-floppy-disk text-sm text-white"></i>
                    <i x-show="saving" class="ph-bold ph-spinner animate-spin text-sm text-white" style="display: none;"></i>
                    <span class="text-white" x-text="saving ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </div>

        <!-- Quick Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Bot Status Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl"
                        :class="botStatus.success ? 'bg-emerald-50 text-emerald-600' : (botStatus.tested ? 'bg-red-50 text-red-600' : 'bg-sky-50 text-sky-600')">
                        <i
                            :class="botStatus.loading ? 'ph-bold ph-spinner animate-spin text-sky-600' : (botStatus.success ? 'ph-fill ph-check-circle' : 'ph ph-robot')"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Bot Connection</p>
                        <h4 class="text-sm font-bold text-gray-800"
                            x-text="botStatus.loading ? 'Checking Bot...' : (botStatus.botUsername ? '@' + botStatus.botUsername : (botStatus.tested ? 'Invalid Bot' : 'Untested'))">
                        </h4>
                        <p class="text-[11px] text-gray-500" x-show="botStatus.latency !== null">
                            Latency: <span class="font-semibold text-emerald-600" x-text="botStatus.latency + ' ms'"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="testBotToken()" :disabled="botStatus.loading || !botToken"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition flex items-center gap-1 cursor-pointer disabled:opacity-50">
                    <i class="ph ph-arrows-clockwise" :class="{'animate-spin': botStatus.loading}"></i>
                    <span>Check</span>
                </button>
            </div>

            <!-- Registered Chat IDs Count -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-purple-50 text-purple-600">
                        <i class="ph-bold ph-chats-circle"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Target Channels/Users</p>
                        <h4 class="text-base font-extrabold text-gray-800"
                            x-text="getParsedChatIds().length + ' Target(s)'"></h4>
                        <p class="text-[11px] text-gray-500">Group / Channel / Private</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                    :class="getParsedChatIds().length > 0 ? 'bg-purple-50 text-purple-700' : 'bg-gray-100 text-gray-500'">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                        :class="getParsedChatIds().length > 0 ? 'bg-purple-500' : 'bg-gray-400'"></span>
                    <span x-text="getParsedChatIds().length > 0 ? 'Ready' : 'No IDs'"></span>
                </span>
            </div>

            <!-- Overall Service Status Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl"
                        :class="botToken.length > 10 && getParsedChatIds().length > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                        <i
                            :class="botToken.length > 10 && getParsedChatIds().length > 0 ? 'ph-bold ph-broadcast' : 'ph-bold ph-warning'"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Service Readiness</p>
                        <h4 class="text-sm font-bold text-gray-800"
                            x-text="botToken.length > 10 && getParsedChatIds().length > 0 ? 'Fully Configured' : 'Needs Setup'">
                        </h4>
                        <p class="text-[11px] text-gray-500"
                            x-text="botToken.length > 10 && getParsedChatIds().length > 0 ? 'Ready for notifications' : 'Enter Token & Chat ID'">
                        </p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                    :class="botToken.length > 10 && getParsedChatIds().length > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                        :class="botToken.length > 10 && getParsedChatIds().length > 0 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <span x-text="botToken.length > 10 && getParsedChatIds().length > 0 ? 'Active' : 'Incomplete'"></span>
                </span>
            </div>
        </div>

        <!-- Main Configuration Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Left: 2 Columns -->
            <div class="lg:col-span-2">
                <form @submit.prevent="saveSettings"
                    class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                    @csrf
                    <div class="border-b border-gray-100 pb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <i class="ph-bold ph-sliders text-sky-600"></i>
                                Telegram Credentials & Target Recipient IDs
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Use your Telegram bot token created from @BotFather and
                                your group/channel/user IDs.</p>
                        </div>
                    </div>

                    <!-- Bot Token -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Telegram Bot Token <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-robot text-lg"></i>
                            </div>
                            <input :type="showToken ? 'text' : 'password'" name="telegram_bot_token" x-model="botToken"
                                class="w-full pl-10 pr-20 py-2.5 text-sm rounded-xl border border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 font-mono transition-colors"
                                placeholder="e.g. 7123456789:AAFlmQ..." required>
                            <button type="button" @click="showToken = !showToken"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                                <i class="ph text-lg" :class="showToken ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">
                            Obtained from <strong>@BotFather</strong> on Telegram when creating your bot. Keep this private.
                        </p>
                    </div>

                    <!-- Chat IDs (Comma or Newline separated) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Recipient Chat IDs / Channel IDs <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-gray-400">Multiple IDs supported</span>
                        </div>
                        <div class="relative rounded-xl shadow-sm">
                            <textarea name="telegram_chat_ids" x-model="chatIds" rows="4"
                                class="w-full px-4 py-3 text-sm rounded-xl border border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 font-mono transition-colors bg-gray-50 focus:bg-white"
                                placeholder="e.g. 123456789&#10;-100987654321&#10;@my_channel"></textarea>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">
                            Enter one Chat ID per line or separated by commas. For Groups or Channels, usually starts with
                            <code>-100...</code>. For private users, it's their numerical User ID.
                        </p>

                        <!-- Parsed IDs Pills -->
                        <div class="mt-3 flex flex-wrap gap-1.5" x-show="getParsedChatIds().length > 0">
                            <template x-for="id in getParsedChatIds()" :key="id">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-50 text-sky-800 text-xs font-mono font-medium border border-sky-100">
                                    <i class="ph ph-chat-circle text-xs text-sky-600"></i>
                                    <span x-text="id"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between gap-3 pt-5 border-t border-gray-100">
                        <div class="flex items-center gap-2 text-xs">
                            <span x-show="isConfigured" class="text-emerald-600 flex items-center gap-1 font-medium">
                                <i class="ph-fill ph-check-circle"></i> Configuration saved & active
                            </span>
                            <span x-show="!isConfigured" class="text-amber-600 flex items-center gap-1 font-medium">
                                <i class="ph-bold ph-warning"></i> Save credentials to enable test notifications
                            </span>
                        </div>
                        <button type="submit" id="saveTelegramBtn" :disabled="saving"
                            class="px-8 py-3 bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white font-bold text-sm rounded-xl transition-all shadow-md shadow-brand-500/25 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <i x-show="!saving" class="ph-bold ph-floppy-disk text-lg text-white"></i>
                            <i x-show="saving" class="ph-bold ph-spinner animate-spin text-lg text-white"
                                style="display: none;"></i>
                            <span class="text-white font-bold"
                                x-text="saving ? 'Saving...' : 'Save Configuration'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar Right: Instructions & Live Test Message -->
            <div class="space-y-6">
                <!-- Test Message Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <i class="ph-bold ph-paper-plane-tilt text-brand-600"></i>
                            Send Test Message
                        </h4>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                            :class="isConfigured ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                            x-text="isConfigured ? 'Ready to Test' : 'Save First'"></span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Test your Telegram bot and verify if live notifications arrive at your target group/chat instantly.
                    </p>

                    <!-- Reminder when not configured yet -->
                    <div x-show="!isConfigured"
                        class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 flex items-start gap-2">
                        <i class="ph-fill ph-warning-circle text-amber-500 text-base mt-0.5 shrink-0"></i>
                        <span>Please enter your Bot Token and Chat ID above, click <strong>Save Configuration</strong>
                            first, then you can send a test notification.</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Specific
                                Chat ID (Optional)</label>
                            <input type="text" x-model="testChatId"
                                class="w-full text-xs font-mono bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20"
                                placeholder="Leave empty to send to all saved IDs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Custom
                                Message (Optional)</label>
                            <textarea x-model="testCustomMessage" rows="2"
                                class="w-full text-xs bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20"
                                placeholder="Default test notification message"></textarea>
                        </div>

                        <button type="button" @click="sendTestMessage()" :disabled="testingMessage"
                            class="w-full py-3.5 px-6 bg-brand-600 hover:bg-brand-700 active:scale-[0.99] text-white font-bold rounded-xl text-sm flex items-center justify-center gap-2 cursor-pointer transition shadow-md shadow-brand-500/25 disabled:opacity-60 disabled:cursor-not-allowed">
                            <i class="ph-bold text-white text-lg"
                                :class="testingMessage ? 'ph-spinner animate-spin' : 'ph-paper-plane-right'"></i>
                            <span class="text-white font-bold"
                                x-text="testingMessage ? 'Sending Test Message...' : 'Send Test Notification'"></span>
                        </button>
                    </div>


                    <!-- Last diagnostic log output -->
                    <div x-show="lastResponse.text" class="mt-3 pt-3 border-t border-gray-100" style="display: none;">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500"
                                x-text="lastResponse.action + ' Response:'"></span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                :class="lastResponse.success ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                x-text="lastResponse.success ? 'Success' : 'Failed'"></span>
                        </div>
                        <pre class="bg-gray-900 text-gray-100 p-3 rounded-xl text-[11px] font-mono overflow-x-auto max-h-48 whitespace-pre-wrap"
                            x-text="lastResponse.text"></pre>
                    </div>
                </div>

                <!-- Helpful Guide Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                    <div class="flex items-center gap-2 text-sky-700 font-bold text-sm">
                        <i class="ph-bold ph-info text-lg"></i>
                        <h4>How to get credentials</h4>
                    </div>
                    <div class="space-y-2.5 text-xs text-gray-600 leading-relaxed">
                        <div class="flex items-start gap-2">
                            <span
                                class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">1</span>
                            <span>Open Telegram and search for <strong>@BotFather</strong>, send <code>/newbot</code> and
                                follow prompts to get the <strong>Bot Token</strong>.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span
                                class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">2</span>
                            <span>Add your newly created bot to your Telegram group or channel as an
                                <strong>Administrator</strong> (so it has post permission).</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span
                                class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">3</span>
                            <span>To find your Group/Channel Chat ID, add <strong>@userinfobot</strong> or
                                <strong>@RawDataBot</strong> to the group, or forward a message from the channel.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function telegramSettings() {
            return {
                botToken: @json($botToken),
                chatIds: @json($chatIds),
                showToken: false,
                saving: false,
                testingMessage: false,
                testChatId: '',
                testCustomMessage: '',
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },
                botStatus: {
                    loading: false,
                    tested: false,
                    success: false,
                    latency: null,
                    botUsername: ''
                },
                lastResponse: {
                    action: '',
                    success: false,
                    text: ''
                },

                get isConfigured() {
                    return this.botToken && this.botToken.trim().length > 10 && this.getParsedChatIds().length > 0;
                },

                triggerToast(msg, type = 'success') {
                    this.toast.message = msg;
                    this.toast.type = type;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 4000);
                },

                getParsedChatIds() {
                    if (!this.chatIds) return [];
                    return this.chatIds
                        .split(/[\r\n,]+/)
                        .map(s => s.trim())
                        .filter(s => s.length > 0);
                },

                async saveSettings() {
                    if (this.saving) return;
                    this.saving = true;

                    try {
                        const response = await fetch("{{ route('admin.settings.telegram.update') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                telegram_bot_token: this.botToken,
                                telegram_chat_ids: this.chatIds
                            })
                        });

                        const data = await response.json();
                        if (response.ok) {
                            this.triggerToast(data.message || 'Telegram settings saved successfully!');
                        } else {
                            this.triggerToast(data.message || 'Failed to save settings.', 'error');
                        }
                    } catch (err) {
                        this.triggerToast('Error saving: ' + err.message, 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                async testBotToken() {
                    if (this.botStatus.loading) return;
                    this.botStatus.loading = true;

                    try {
                        const response = await fetch("{{ route('admin.settings.telegram.test-bot') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                bot_token: this.botToken
                            })
                        });

                        const res = await response.json();
                        this.botStatus.tested = true;
                        this.botStatus.success = res.success;
                        this.botStatus.latency = res.latency;
                        this.botStatus.botUsername = res.bot && res.bot.username ? res.bot.username : '';

                        this.lastResponse = {
                            action: 'Check Bot',
                            success: res.success,
                            text: JSON.stringify(res, null, 2)
                        };

                        if (res.success) {
                            this.triggerToast(res.message || 'Bot connected successfully!');
                        } else {
                            this.triggerToast('Bot verification failed: ' + res.message, 'error');
                        }
                    } catch (err) {
                        this.botStatus.tested = true;
                        this.botStatus.success = false;
                        this.triggerToast('Connection error: ' + err.message, 'error');
                    } finally {
                        this.botStatus.loading = false;
                    }
                },

                async sendTestMessage() {
                    if (this.testingMessage) return;
                    this.testingMessage = true;

                    try {
                        const response = await fetch("{{ route('admin.settings.telegram.test-message') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                chat_id: this.testChatId || null,
                                message: this.testCustomMessage || null
                            })
                        });

                        const res = await response.json();
                        this.lastResponse = {
                            action: 'Test Message',
                            success: res.success,
                            text: JSON.stringify(res, null, 2)
                        };

                        if (res.success) {
                            this.triggerToast(res.message || 'Test message sent successfully to Telegram!');
                        } else {
                            this.triggerToast('Sending failed: ' + (res.message || 'Check logs'), 'error');
                        }
                    } catch (err) {
                        this.triggerToast('Error: ' + err.message, 'error');
                    } finally {
                        this.testingMessage = false;
                    }
                }
            }
        }
    </script>
@endpush