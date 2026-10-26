@extends('layouts.app')

@section('title', 'Login - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
            <p class="text-gray-500 mt-2 text-sm">Sign in to your account to continue</p>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/login" class="space-y-5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="relative">
                    <i class="ph ph-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <input type="email" name="email" required class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors" placeholder="you@example.com">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <a href="#" class="text-xs font-medium text-brand-600 hover:text-brand-700 cursor-pointer">Forgot Password?</a>
                </div>
                <div class="relative">
                    <i class="ph ph-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <input type="password" name="password" required class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" 
                    :disabled="isSubmitting"
                    class="w-full bg-brand-600 disabled:opacity-50 text-white font-semibold py-2.5 rounded-xl hover:bg-brand-700 transition-colors shadow-sm cursor-pointer disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <span x-show="!isSubmitting">Sign In</span>
                <span x-show="isSubmitting" class="inline-flex items-center gap-2" style="display: none;">
                    <i class="ph ph-spinner animate-spin text-lg"></i>
                    <span>Signing In...</span>
                </span>
            </button>
        </form>

        <div class="mt-6 flex items-center justify-between">
            <span class="border-b border-gray-200 w-1/5 lg:w-1/4"></span>
            <span class="text-xs text-center text-gray-500 uppercase font-medium">or continue with</span>
            <span class="border-b border-gray-200 w-1/5 lg:w-1/4"></span>
        </div>

        <div x-data="{ loading: false }">
            <a href="/auth/google" @click="loading = true" class="mt-6 w-full flex items-center justify-center gap-2 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors text-gray-700 font-medium text-sm cursor-pointer" :class="{ 'opacity-75 cursor-wait': loading }">
                <template x-if="!loading">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google">
                </template>
                <template x-if="loading">
                    <i class="ph ph-spinner animate-spin text-lg"></i>
                </template>
                <span x-text="loading ? 'Connecting to Google...' : 'Sign in with Google'"></span>
            </a>
        </div>

        <p class="mt-8 text-center text-sm text-gray-600">
            Don't have an account? <a href="/register" class="text-brand-600 font-semibold hover:underline cursor-pointer">Register now</a>
        </p>

    </div>
</div>
@endsection
