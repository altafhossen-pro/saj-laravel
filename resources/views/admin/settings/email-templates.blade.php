@extends('layouts.admin')

@section('title', 'Email System & History - Admin Panel')
@section('header_title', 'Email System & History')

@section('content')
<div class="max-w-7xl mx-auto space-y-8" x-data="{ activeTab: 'templates' }">

    <!-- Top Navigation Tabs & Quick Action -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-2 border-b border-gray-200">
        <div class="flex items-center gap-2 bg-gray-100 p-1.5 rounded-2xl border border-gray-200">
            <button @click="activeTab = 'templates'" 
                    :class="activeTab === 'templates' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2">
                <i class="ph ph-envelope-simple text-base"></i>
                Email Templates
            </button>
            <button @click="activeTab = 'compose'" 
                    :class="activeTab === 'compose' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2">
                <i class="ph ph-paper-plane-tilt text-base text-purple-600"></i>
                Send Custom Email
            </button>
            <button @click="activeTab = 'history'" 
                    :class="activeTab === 'history' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2">
                <i class="ph ph-clock-counter-clockwise text-base text-brand-600"></i>
                Sent History Logs ({{ $logs->total() }})
            </button>
        </div>

        <button @click="activeTab = 'compose'" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
            <i class="ph ph-plus-circle text-base"></i>
            Compose Email
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold flex items-center gap-2">
            <i class="ph ph-check-circle text-lg"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- TAB 1: TEMPLATES -->
    <div x-show="activeTab === 'templates'" class="space-y-6">
        
        <div class="bg-purple-50/70 border border-purple-200/80 rounded-2xl p-5 flex items-start gap-4 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center flex-shrink-0 text-xl font-bold">
                <i class="ph ph-envelope-open"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-bold text-purple-900">Automated Notification System</h3>
                <p class="text-xs text-purple-700 mt-1 leading-relaxed">
                    Control automated transactional emails. Toggle state, edit subject, headings, or messages. Click <strong>Live Preview</strong> to inspect email design or <strong>Send Test Mail</strong> to test inboxes.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            @foreach($templates as $tpl)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                    <div class="p-6 md:p-7">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-gray-100">
                            <div class="flex items-center gap-3.5">
                                <div class="w-11 h-11 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-700 text-xl font-bold">
                                    @if($tpl['key'] === 'order_placed')
                                        <i class="ph ph-shopping-cart-simple text-brand-600"></i>
                                    @elseif($tpl['key'] === 'order_delivered')
                                        <i class="ph ph-package text-emerald-600"></i>
                                    @elseif($tpl['key'] === 'otp_verification')
                                        <i class="ph ph-shield-check text-blue-600"></i>
                                    @elseif($tpl['key'] === 'welcome_user')
                                        <i class="ph ph-hand-waving text-amber-500"></i>
                                    @else
                                        <i class="ph ph-bell text-rose-500"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-bold text-gray-900">{{ $tpl['name'] }}</h3>
                                        <span class="px-2.5 py-0.5 text-[11px] font-bold rounded-full border {{ $tpl['badge_color'] }}">
                                            {{ $tpl['badge'] }}
                                        </span>
                                        <span class="text-xs text-gray-400 font-medium">· Recipient: {{ $tpl['recipient'] }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $tpl['description'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <button type="button" 
                                        onclick="openPreview('{{ $tpl['key'] }}', '{{ $tpl['name'] }}')"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors">
                                    <i class="ph ph-eye text-sm"></i>
                                    Live Preview
                                </button>
                                <button type="button" 
                                        onclick="openTestMailModal('{{ $tpl['key'] }}', '{{ $tpl['name'] }}')"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-bold rounded-xl transition-colors">
                                    <i class="ph ph-paper-plane-tilt text-sm"></i>
                                    Send Test Mail
                                </button>
                            </div>
                        </div>

                        <form action="{{ route('admin.settings.email-templates.update', $tpl['key']) }}" method="POST" class="mt-5 space-y-4">
                            @csrf
                            <div class="flex items-center justify-between bg-gray-50/80 p-3.5 rounded-xl border border-gray-100">
                                <div>
                                    <span class="text-xs font-bold text-gray-800">Trigger Status</span>
                                    <p class="text-[11px] text-gray-500">Enable or disable sending this automated notification.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="active" value="1" class="sr-only peer" {{ $tpl['active'] ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Email Subject</label>
                                    <input type="text" name="subject" value="{{ $tpl['subject'] }}" required class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                                    <span class="text-[10px] text-gray-400 mt-1 block">Placeholders: <code>{order_id}</code>, <code>{site_name}</code>, <code>{customer_name}</code></span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Greeting / Main Heading</label>
                                    <input type="text" name="heading" value="{{ $tpl['heading'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Intro Message / Content</label>
                                <textarea name="message" rows="2" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors resize-none">{{ $tpl['message'] }}</textarea>
                            </div>

                            <div class="flex justify-end pt-1">
                                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                                    Save Changes
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            @endforeach
        </div>

    </div>

    <!-- TAB 2: COMPOSE CUSTOM EMAIL -->
    <div x-show="activeTab === 'compose'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 max-w-3xl mx-auto">
            <div class="flex items-center gap-3 pb-5 border-b border-gray-100 mb-6">
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold">
                    <i class="ph ph-paper-plane-tilt"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Send Custom Email Message</h3>
                    <p class="text-xs text-gray-500">Send an official announcement, invoice follow-up, or custom note to any email address.</p>
                </div>
            </div>

            <form id="customMailForm" onsubmit="submitCustomMail(event)" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Recipient Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="custom_recipient_email" required placeholder="customer@example.com" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Subject Line <span class="text-red-500">*</span></label>
                        <input type="text" id="custom_subject" required placeholder="Important update regarding your order" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Banner Heading</label>
                        <input type="text" id="custom_heading" placeholder="Hello Mohammad Rahim," class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Email Body Message <span class="text-red-500">*</span></label>
                    <textarea id="custom_message" rows="5" required placeholder="Write your email content here..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Action Button Text (Optional)</label>
                        <input type="text" id="custom_action_text" placeholder="e.g. View Account / Shop Now" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Action Button URL (Optional)</label>
                        <input type="url" id="custom_action_url" placeholder="https://yourstore.com" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white">
                    </div>
                </div>

                <div id="customMailAlert" class="hidden text-xs p-3.5 rounded-xl"></div>

                <div class="flex justify-end pt-3">
                    <button type="submit" id="customMailSubmitBtn" class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        <i class="ph ph-paper-plane-tilt text-sm"></i>
                        Dispatch Email Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 3: SENT HISTORY LOGS -->
    <div x-show="activeTab === 'history'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Email Outbox History</h3>
                    <p class="text-xs text-gray-500">Chronological history of all automated, test, and custom emails dispatched.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                            <th class="py-3 px-4">Date & Time</th>
                            <th class="py-3 px-4">Recipient</th>
                            <th class="py-3 px-4">Subject</th>
                            <th class="py-3 px-4">Template</th>
                            <th class="py-3 px-4">Sender</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-3.5 px-4 text-gray-500 whitespace-nowrap">
                                    {{ $log->created_at->format('d M Y, h:i A') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-gray-900">{{ $log->recipient_email }}</div>
                                    @if($log->recipient_name)
                                        <div class="text-[11px] text-gray-400">{{ $log->recipient_name }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 max-w-xs truncate text-gray-800 font-medium" title="{{ $log->subject }}">
                                    {{ $log->subject }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ str_replace('_', ' ', $log->template_key) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-gray-600 capitalize">
                                    {{ $log->sender_type }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sent
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $log->error_message }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Failed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 text-xs">
                                    No email activity recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

<!-- Live Preview Modal -->
<div id="previewModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-4xl h-[90vh] flex flex-col shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold">
                    <i class="ph ph-envelope-simple text-lg"></i>
                </div>
                <div>
                    <h3 id="previewTitle" class="text-sm font-bold text-gray-900">Email Preview</h3>
                    <p class="text-[11px] text-gray-500">Real visual rendering</p>
                </div>
            </div>
            <button onclick="closePreview()" class="w-8 h-8 rounded-lg bg-white border border-gray-200 hover:bg-gray-100 text-gray-500 flex items-center justify-center transition-colors">
                <i class="ph ph-x text-base font-bold"></i>
            </button>
        </div>
        <div class="flex-1 bg-gray-100 p-2 sm:p-4 overflow-hidden">
            <iframe id="previewIframe" src="about:blank" class="w-full h-full bg-white rounded-xl border border-gray-200 shadow-sm"></iframe>
        </div>
    </div>
</div>

<!-- Send Test Mail Modal -->
<div id="testMailModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                    <i class="ph ph-paper-plane-tilt text-lg"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-900" id="testMailTitle">Send Test Mail</h3>
            </div>
            <button onclick="closeTestMailModal()" class="w-7 h-7 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-400 flex items-center justify-center">
                <i class="ph ph-x text-sm font-bold"></i>
            </button>
        </div>

        <form id="testMailForm" onsubmit="submitTestMail(event)" class="space-y-4">
            <input type="hidden" id="testMailKey" value="">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Recipient Email Address</label>
                <input type="email" id="testMailAddress" required placeholder="your.name@example.com" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-colors">
            </div>

            <div id="testMailAlert" class="hidden text-xs p-3 rounded-xl"></div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeTestMailModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" id="testMailSubmitBtn" class="inline-flex items-center gap-1.5 px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                    <i class="ph ph-paper-plane-tilt"></i>
                    Send Now
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const baseUrl = "{{ route('admin.settings.email-templates') }}";

    function openPreview(key, name) {
        document.getElementById('previewTitle').innerText = name + ' — Preview';
        const iframe = document.getElementById('previewIframe');
        iframe.src = baseUrl + "/" + key + "/preview";
        document.getElementById('previewModal').classList.remove('hidden');
    }

    function closePreview() {
        document.getElementById('previewModal').classList.add('hidden');
        document.getElementById('previewIframe').src = 'about:blank';
    }

    function openTestMailModal(key, name) {
        document.getElementById('testMailKey').value = key;
        document.getElementById('testMailTitle').innerText = 'Test ' + name;
        const alertBox = document.getElementById('testMailAlert');
        alertBox.className = 'hidden text-xs p-3 rounded-xl';
        alertBox.innerText = '';
        document.getElementById('testMailModal').classList.remove('hidden');
    }

    function closeTestMailModal() {
        document.getElementById('testMailModal').classList.add('hidden');
    }

    async function submitTestMail(e) {
        e.preventDefault();
        const key = document.getElementById('testMailKey').value;
        const email = document.getElementById('testMailAddress').value;
        const btn = document.getElementById('testMailSubmitBtn');
        const alertBox = document.getElementById('testMailAlert');

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Sending...';
        alertBox.className = 'hidden text-xs p-3 rounded-xl';

        try {
            const response = await fetch(baseUrl + "/" + key + "/send-test", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: email })
            });

            const data = await response.json();

            if (data.success) {
                alertBox.className = 'block bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs p-3 rounded-xl';
                alertBox.innerText = data.message;
            } else {
                alertBox.className = 'block bg-rose-50 text-rose-800 border border-rose-200 text-xs p-3 rounded-xl';
                alertBox.innerText = data.message || 'Failed to send test email.';
            }
        } catch (err) {
            alertBox.className = 'block bg-rose-50 text-rose-800 border border-rose-200 text-xs p-3 rounded-xl';
            alertBox.innerText = 'Network error: ' + err.message;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-paper-plane-tilt"></i> Send Now';
        }
    }

    async function submitCustomMail(e) {
        e.preventDefault();
        const btn = document.getElementById('customMailSubmitBtn');
        const alertBox = document.getElementById('customMailAlert');

        const payload = {
            recipient_email: document.getElementById('custom_recipient_email').value,
            subject: document.getElementById('custom_subject').value,
            heading: document.getElementById('custom_heading').value,
            message: document.getElementById('custom_message').value,
            action_text: document.getElementById('custom_action_text').value,
            action_url: document.getElementById('custom_action_url').value,
        };

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Sending...';
        alertBox.className = 'hidden text-xs p-3 rounded-xl';

        try {
            const response = await fetch("{{ route('admin.settings.email-templates.send-custom') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                alertBox.className = 'block bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs p-3.5 rounded-xl';
                alertBox.innerText = data.message;
                document.getElementById('customMailForm').reset();
            } else {
                alertBox.className = 'block bg-rose-50 text-rose-800 border border-rose-200 text-xs p-3.5 rounded-xl';
                alertBox.innerText = data.message || 'Failed to send custom email.';
            }
        } catch (err) {
            alertBox.className = 'block bg-rose-50 text-rose-800 border border-rose-200 text-xs p-3.5 rounded-xl';
            alertBox.innerText = 'Network error: ' + err.message;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-paper-plane-tilt text-sm"></i> Dispatch Email Now';
        }
    }
</script>
@endsection
