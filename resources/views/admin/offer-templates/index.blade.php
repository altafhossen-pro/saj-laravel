@extends('layouts.admin')

@section('header_title', 'Predefined Offer Templates')

@section('content')
<div class="space-y-6" x-data="{
    modalOpen: false,
    editing: null,
    form: {
        name: '',
        title: '',
        message: '',
        coupon_code: '',
        button_text: 'অফারটি গ্রহণ করুন',
        button_url: '/checkout',
        delay_seconds: 10,
        is_active: true
    },
    openCreate() {
        this.editing = null;
        this.form = {
            name: '',
            title: '',
            message: '',
            coupon_code: '',
            button_text: 'অফারটি গ্রহণ করুন',
            button_url: '/checkout',
            delay_seconds: 10,
            is_active: true
        };
        this.modalOpen = true;
    },
    openEdit(t) {
        this.editing = t;
        this.form = {
            name: t.name,
            title: t.title,
            message: t.message,
            coupon_code: t.coupon_code || '',
            button_text: t.button_text || 'অফারটি গ্রহণ করুন',
            button_url: t.button_url || '/checkout',
            delay_seconds: t.delay_seconds || 10,
            is_active: Boolean(t.is_active)
        };
        this.modalOpen = true;
    }
}">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">Predefined Offer Templates</h1>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-pink-50 text-pink-700 border border-pink-200">
                    <i class="ph ph-chat-circle-dots"></i> বাংলা অফার মেসেজ
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                লাইভ বা হিস্টোরিক্যাল ভিজিটরদের দ্রুত এক ক্লিকে অফার পাঠানোর জন্য পূর্বনির্ধারিত বাংলা মেসেজ টেমপ্লেট পরিচালনা করুন।
            </p>
        </div>

        <button type="button" @click="openCreate()"
            class="px-4 py-2.5 bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-600 hover:to-rose-600 text-white font-bold text-xs rounded-xl shadow-md shadow-pink-500/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
            <i class="ph ph-plus-circle text-base font-bold"></i>
            <span>নতুন অফার টেমপ্লেট তৈরি করুন</span>
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-sm font-semibold flex items-center gap-2">
            <i class="ph ph-check-circle text-lg text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($templates as $t)
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between hover:border-pink-200 hover:shadow-md transition-all">
            <div class="space-y-4">
                <!-- Top Badge & Status -->
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-pink-50 text-pink-700 border border-pink-200">
                        <i class="ph ph-tag"></i>
                        <span>{{ $t->name }}</span>
                    </span>

                    <span class="w-2.5 h-2.5 rounded-full {{ $t->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}"
                          title="{{ $t->is_active ? 'Active' : 'Inactive' }}"></span>
                </div>

                <!-- Headline -->
                <h3 class="text-base font-extrabold text-gray-900 leading-snug">
                    {{ $t->title }}
                </h3>

                <!-- Message Preview in Bengali -->
                <p class="text-xs text-gray-600 leading-relaxed bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100 whitespace-pre-line">
                    {{ $t->message }}
                </p>

                <!-- Coupon & Delay Meta -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if($t->coupon_code)
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-pink-50 border border-pink-200 text-pink-700 font-mono font-bold">
                            <i class="ph ph-ticket"></i>
                            <span>{{ $t->coupon_code }}</span>
                        </div>
                    @endif

                    <div class="inline-flex items-center gap-1 text-gray-400 text-[11px]">
                        <i class="ph ph-clock"></i>
                        <span>Delay: {{ $t->delay_seconds }}s</span>
                    </div>

                    <div class="inline-flex items-center gap-1 text-gray-400 text-[11px] truncate max-w-[120px]" title="{{ $t->button_url }}">
                        <i class="ph ph-link"></i>
                        <span>{{ $t->button_url }}</span>
                    </div>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="pt-5 mt-5 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[11px] text-gray-400 font-medium">Button: {{ $t->button_text }}</span>

                <div class="flex items-center gap-1.5">
                    <button type="button" @click="openEdit({{ json_encode($t) }})"
                        class="p-2 rounded-xl text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer"
                        title="Edit Template">
                        <i class="ph ph-pencil-simple text-base"></i>
                    </button>

                    <form action="{{ route('admin.offer-templates.destroy', $t->id) }}" method="POST"
                          onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই টেমপ্লেটটি ডিলিট করতে চান?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="p-2 rounded-xl text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                            title="Delete Template">
                            <i class="ph ph-trash text-base"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-3xl border border-gray-100 p-12 text-center text-gray-500">
            <i class="ph ph-chat-circle-dots text-4xl text-gray-300 mb-2 inline-block"></i>
            <h4 class="font-bold text-gray-900">কোনো টেমপ্লেট পাওয়া যায়নি</h4>
            <p class="text-xs text-gray-400 mt-1">উপরে "নতুন অফার টেমপ্লেট তৈরি করুন" বাটনে ক্লিক করে টেমপ্লেট তৈরি করুন।</p>
        </div>
        @endforelse
    </div>

    @if($templates->hasPages())
        <div class="pt-4">
            {{ $templates->links() }}
        </div>
    @endif

    <!-- Create / Edit Modal -->
    <div x-show="modalOpen"
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4"
        @keydown.escape.window="modalOpen = false">

        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-gray-100"
            @click.away="modalOpen = false">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-pink-50/50 to-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center text-lg">
                        <i class="ph ph-chat-circle-dots"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base" x-text="editing ? 'অফার টেমপ্লেট এডিট করুন' : 'নতুন অফার টেমপ্লেট তৈরি করুন'"></h3>
                        <p class="text-xs text-gray-500">ভিজিটরদের পাঠানোর জন্য বাংলা কন্টেন্ট সেট করুন</p>
                    </div>
                </div>

                <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>

            <form :action="editing ? '{{ url('/admin/dashboard/offer-templates') }}/' + editing.id : '{{ route('admin.offer-templates.store') }}'" method="POST" class="p-6 space-y-4">
                @csrf
                <template x-if="editing">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Template Reference Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">টেমপ্লেটের রেফারেন্স নাম (Admin Only) *</label>
                    <input type="text" name="name" x-model="form.name" required placeholder="যেমন: ফ্রি হোম ডেলিভারি অফার"
                        class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                </div>

                <!-- Headline in Bengali -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">পপআপ হেডলাইন (বাংলায়) *</label>
                    <input type="text" name="title" x-model="form.title" required placeholder="যেমন: আপনার জন্য স্পেশাল ১০% সারপ্রাইজ ডিসকাউন্ট! 🎁"
                        class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                </div>

                <!-- Message Body in Bengali -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">অফার মেসেজ / বার্তা (বাংলায়) *</label>
                    <textarea name="message" x-model="form.message" rows="3" required placeholder="বিস্তারিত অফার মেসেজ লিখুন..."
                        class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">কুপন কোড (Optional)</label>
                        <input type="text" name="coupon_code" x-model="form.coupon_code" placeholder="e.g. FREESHIP"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-mono uppercase focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">পপআপ ডিলে (সেকেন্ডে)</label>
                        <select name="delay_seconds" x-model="form.delay_seconds"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="0">তাত্ক্ষণিক (0 সেকেন্ড)</option>
                            <option value="5">৫ সেকেন্ড</option>
                            <option value="8">৮ সেকেন্ড</option>
                            <option value="10">১০ সেকেন্ড (ডিফল্ট)</option>
                            <option value="15">১৫ সেকেন্ড</option>
                            <option value="20">২০ সেকেন্ড</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">বাটন টেক্সট (বাংলায়)</label>
                        <input type="text" name="button_text" x-model="form.button_text" placeholder="অফারটি গ্রহণ করুন"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">বাটন রিডাইরেক্ট লিংক</label>
                        <input type="text" name="button_url" x-model="form.button_url" placeholder="/checkout"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="is_active_chk" name="is_active" value="1" x-model="form.is_active"
                        class="w-4 h-4 text-pink-600 rounded border-gray-300 focus:ring-pink-500">
                    <label for="is_active_chk" class="text-xs font-medium text-gray-700 cursor-pointer">এই টেমপ্লেটটি ড্রপডাউনে সক্রিয় রাখুন</label>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-50 transition cursor-pointer">
                        বাতিল
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-600 hover:to-rose-600 text-white rounded-xl text-xs font-bold shadow-md shadow-pink-500/20 transition flex items-center gap-1.5 cursor-pointer">
                        <i class="ph ph-check font-bold"></i>
                        <span x-text="editing ? 'আপডেট করুন' : 'সংরক্ষণ করুন'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

