@extends('layouts.admin')

@section('title', 'Site Scripts - Admin Panel')
@section('header_title', 'Site Scripts & Tracking')

@section('content')
<div class="w-full" x-data="scriptsForm()">
    <!-- Toast Notification -->
    <div x-show="showToast" x-transition class="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-gray-900 text-white px-5 py-3.5 rounded-xl shadow-xl" style="display: none;">
        <i class="ph-fill ph-check-circle text-green-400 text-xl"></i>
        <p class="text-sm font-medium" x-text="toastMessage"></p>
        <button type="button" @click="showToast = false" class="ml-2 text-gray-400 hover:text-white transition-colors">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <form @submit.prevent="submitForm" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        @csrf
        
        <div class="mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i class="ph-bold ph-code text-brand-600"></i>
                Header Scripts (<head>)
            </h2>
            <p class="text-sm text-gray-500 mb-3">Add Facebook Pixel, Google Analytics, or any other scripts that belong in the &lt;head&gt; tag.</p>
            <textarea name="site_head_scripts" rows="8" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-mono text-sm bg-gray-50" placeholder="<!-- Your Header Scripts Here -->">{{ $headScripts }}</textarea>
        </div>

        <div class="mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                <i class="ph-bold ph-code-block text-brand-600"></i>
                Body Scripts (</body>)
            </h2>
            <p class="text-sm text-gray-500 mb-3">Add chat widgets, tracking pixels, or other scripts that belong right before the closing &lt;/body&gt; tag.</p>
            <textarea name="site_body_scripts" rows="8" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-mono text-sm bg-gray-50" placeholder="<!-- Your Footer/Body Scripts Here -->">{{ $bodyScripts }}</textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl transition-colors shadow-sm shadow-brand-500/30 flex items-center gap-2 cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                <i x-show="!loading" class="ph-bold ph-floppy-disk"></i>
                <i x-show="loading" class="ph-bold ph-spinner animate-spin" style="display: none;"></i>
                <span x-text="loading ? 'Saving...' : 'Save Scripts'"></span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function scriptsForm() {
    return {
        loading: false,
        showToast: false,
        toastMessage: '',
        async submitForm(e) {
            if(this.loading) return;
            this.loading = true;
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('{{ route('admin.settings.scripts.update') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if(data.status === 'success') {
                    this.toastMessage = data.message;
                    this.showToast = true;
                    setTimeout(() => { this.showToast = false; }, 3000);
                }
            } catch (error) {
                console.error('Error saving scripts', error);
                this.toastMessage = 'An error occurred while saving.';
                this.showToast = true;
                setTimeout(() => { this.showToast = false; }, 3000);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
