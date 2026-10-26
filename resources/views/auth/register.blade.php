@extends('layouts.app')

@section('title', 'Register - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12" x-data="registerForm()">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Create Account</h1>
            <p class="text-gray-500 mt-2 text-sm" x-text="stepDescription"></p>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-lg">
                {{ $errors->first() }}
            </div>
        @endif
        
        <div x-show="errorMessage" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-lg" x-text="errorMessage" style="display: none;"></div>

        <!-- Progress Bar -->
        <div class="flex items-center justify-between mb-8 relative">
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-200"></div>
            <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-brand-500 transition-all duration-300" :style="`width: ${((step - 1) / 2) * 100}%`"></div>
            
            <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors" :class="step >= 1 ? 'bg-brand-500 border-brand-500 text-white' : 'bg-white border-gray-200 text-gray-400'">1</div>
            <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors" :class="step >= 2 ? 'bg-brand-500 border-brand-500 text-white' : 'bg-white border-gray-200 text-gray-400'">2</div>
            <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors" :class="step >= 3 ? 'bg-brand-500 border-brand-500 text-white' : 'bg-white border-gray-200 text-gray-400'">3</div>
        </div>

        <!-- Step 1: Email -->
        <div x-show="step === 1" x-transition.opacity>
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="relative">
                    <i class="ph ph-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <input type="email" 
                           x-model="form.email" 
                           @keydown.enter.prevent="if(!loading && form.email) sendOtp()"
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors" 
                           placeholder="you@example.com">
                </div>
            </div>
            <button @click="sendOtp" 
                    :disabled="loading || !form.email" 
                    class="w-full bg-brand-600 disabled:opacity-50 text-white font-semibold py-2.5 rounded-xl hover:bg-brand-700 transition-colors shadow-sm flex justify-center items-center gap-2 cursor-pointer disabled:cursor-not-allowed">
                <span x-show="!loading">Send OTP</span>
                <span x-show="loading" class="inline-flex items-center gap-2" style="display: none;">
                    <i class="ph ph-spinner animate-spin text-lg"></i>
                    <span>Sending Code...</span>
                </span>
            </button>
        </div>

        <!-- Step 2: OTP -->
        <div x-show="step === 2" x-transition.opacity style="display: none;">
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                <p class="text-xs text-gray-500 mb-3">We sent a 6-digit code to <span class="font-semibold text-gray-800" x-text="form.email"></span></p>
                <input type="text" 
                       x-model="form.otp" 
                       maxlength="6" 
                       @keydown.enter.prevent="if(!loading && form.otp.length === 6) verifyOtp()"
                       class="w-full text-center tracking-[1em] font-bold text-2xl py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors" 
                       placeholder="------">
            </div>
            <button @click="verifyOtp" 
                    :disabled="loading || form.otp.length !== 6" 
                    class="w-full bg-brand-600 disabled:opacity-50 text-white font-semibold py-2.5 rounded-xl hover:bg-brand-700 transition-colors shadow-sm flex justify-center items-center gap-2 cursor-pointer disabled:cursor-not-allowed">
                <span x-show="!loading">Verify Code</span>
                <span x-show="loading" class="inline-flex items-center gap-2" style="display: none;">
                    <i class="ph ph-spinner animate-spin text-lg"></i>
                    <span>Verifying...</span>
                </span>
            </button>
            <div class="mt-4 flex items-center justify-between text-sm">
                <button type="button" @click="step = 1; errorMessage = ''" class="text-gray-500 hover:text-gray-800 cursor-pointer transition-colors">
                    &larr; Change Email
                </button>
                <button type="button" @click="resendOtp" :disabled="resendCooldown > 0 || loading" class="text-brand-600 hover:text-brand-700 font-medium cursor-pointer disabled:cursor-not-allowed disabled:text-gray-400 transition-colors">
                    <span x-show="resendCooldown > 0" x-text="`Resend in ${resendCooldown}s`"></span>
                    <span x-show="resendCooldown <= 0">Resend Code</span>
                </button>
            </div>
        </div>

        <!-- Step 3: Final Details -->
        <div x-show="step === 3" x-transition.opacity style="display: none;">
            <form @submit.prevent="completeRegistration" autocomplete="off" class="space-y-4">
                <!-- Hidden inputs to fool browser password managers -->
                <input type="text" style="display:none">
                <input type="password" style="display:none">

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <div class="relative">
                        <i class="ph ph-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" 
                               x-model="form.name" 
                               autocomplete="off"
                               autocorrect="off"
                               spellcheck="false"
                               required 
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 transition-colors" 
                               placeholder="e.g. John Doe">
                    </div>
                </div>

                <!-- Phone Number with Real-time Validation -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <span class="text-[11px] font-mono text-gray-400" x-text="form.phone ? form.phone.length + '/11 digits' : ''"></span>
                    </div>
                    <div class="relative">
                        <i class="ph ph-phone absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg" :class="{'text-red-500': phoneError, 'text-emerald-500': isPhoneValid}"></i>
                        <input type="tel" 
                               x-model="form.phone" 
                               @input="validatePhone()"
                               autocomplete="new-password"
                               autocorrect="off"
                               spellcheck="false"
                               required 
                               :class="{'border-red-500 focus:ring-red-500': phoneError, 'border-emerald-500 focus:ring-emerald-500': isPhoneValid}"
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 transition-colors" 
                               placeholder="01XXXXXXXXX">
                    </div>
                    <!-- Inline Phone Error -->
                    <p x-show="phoneError" x-text="phoneError" class="text-xs text-red-600 mt-1 font-medium" style="display: none;"></p>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <span class="text-[11px] text-gray-400">Min. 6 characters</span>
                    </div>
                    <div class="relative">
                        <i class="ph ph-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input :type="showPassword ? 'text' : 'password'" 
                               x-model="form.password" 
                               autocomplete="new-password"
                               autocorrect="off"
                               spellcheck="false"
                               required 
                               minlength="6" 
                               class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 transition-colors" 
                               placeholder="••••••••">
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                            <i class="ph" :class="showPassword ? 'ph-eye-slash' : 'ph-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" 
                        :disabled="loading || !isFormValid" 
                        class="w-full bg-brand-600 disabled:opacity-50 text-white font-semibold py-2.5 rounded-xl hover:bg-brand-700 transition-colors shadow-sm mt-4 flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed">
                    <span x-show="!loading">Complete Registration</span>
                    <span x-show="loading" class="inline-flex items-center gap-2" style="display: none;">
                        <i class="ph ph-spinner animate-spin text-lg"></i>
                        <span>Creating Account...</span>
                    </span>
                </button>
            </form>
        </div>

        <div x-show="step === 1" class="mt-6 flex items-center justify-between">
            <span class="border-b border-gray-200 w-1/5 lg:w-1/4"></span>
            <span class="text-xs text-center text-gray-500 uppercase font-medium">or continue with</span>
            <span class="border-b border-gray-200 w-1/5 lg:w-1/4"></span>
        </div>

        <div x-show="step === 1" class="mt-6 w-full">
            <div x-data="{ googleLoading: false }">
                <a href="/auth/google" @click="googleLoading = true" class="flex items-center justify-center gap-2 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors text-gray-700 font-medium text-sm cursor-pointer" :class="{ 'opacity-75 cursor-wait': googleLoading }">
                    <template x-if="!googleLoading">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google">
                    </template>
                    <template x-if="googleLoading">
                        <i class="ph ph-spinner animate-spin text-lg"></i>
                    </template>
                    <span x-text="googleLoading ? 'Connecting to Google...' : 'Sign in with Google'"></span>
                </a>
            </div>
        </div>

        <p x-show="step === 1" class="mt-8 text-center text-sm text-gray-600">
            Already have an account? <a href="/login" class="text-brand-600 font-semibold hover:underline cursor-pointer">Login</a>
        </p>

    </div>
</div>

<script>
    function registerForm() {
        return {
            step: 1,
            loading: false,
            errorMessage: '',
            phoneError: '',
            showPassword: false,
            resendCooldown: 0,
            cooldownTimer: null,
            form: {
                email: '',
                otp: '',
                name: '',
                phone: '',
                password: ''
            },
            get stepDescription() {
                if (this.step === 1) return 'Enter your email to get started';
                if (this.step === 2) return 'Verify your email address';
                if (this.step === 3) return 'Almost done! Set up your profile';
            },
            get isPhoneValid() {
                const phone = (this.form.phone || '').trim();
                return /^01[0-9]{9}$/.test(phone);
            },
            get isFormValid() {
                return (this.form.name || '').trim().length > 0 &&
                       this.isPhoneValid &&
                       (this.form.password || '').length >= 6;
            },
            validatePhone() {
                const phone = (this.form.phone || '').trim();
                if (!phone) {
                    this.phoneError = '';
                    return;
                }
                if (!phone.startsWith('01')) {
                    this.phoneError = 'Phone number must start with 01';
                } else if (phone.length < 11) {
                    this.phoneError = `Must be 11 digits (currently ${phone.length})`;
                } else if (phone.length > 11) {
                    this.phoneError = `Cannot exceed 11 digits (currently ${phone.length})`;
                } else if (!/^[0-9]+$/.test(phone)) {
                    this.phoneError = 'Only numbers are allowed';
                } else {
                    this.phoneError = '';
                }
            },
            startCooldown(seconds = 60) {
                this.resendCooldown = seconds;
                if (this.cooldownTimer) clearInterval(this.cooldownTimer);
                this.cooldownTimer = setInterval(() => {
                    if (this.resendCooldown > 0) {
                        this.resendCooldown--;
                    } else {
                        clearInterval(this.cooldownTimer);
                    }
                }, 1000);
            },
            async sendOtp() {
                this.loading = true;
                this.errorMessage = '';
                try {
                    const res = await axios.post('/register/send-otp', { email: this.form.email });
                    if (res.data.success) {
                        this.step = 2;
                        this.startCooldown(60);
                    }
                } catch (error) {
                    this.errorMessage = error.response?.data?.message || error.response?.data?.errors?.email?.[0] || 'Failed to send OTP';
                } finally {
                    this.loading = false;
                }
            },
            async resendOtp() {
                if (this.resendCooldown > 0 || this.loading) return;
                await this.sendOtp();
            },
            async verifyOtp() {
                this.loading = true;
                this.errorMessage = '';
                try {
                    const res = await axios.post('/register/verify-otp', { 
                        email: this.form.email, 
                        otp: this.form.otp 
                    });
                    if (res.data.success) {
                        this.step = 3;
                    }
                } catch (error) {
                    this.errorMessage = error.response?.data?.message || 'Invalid or expired OTP';
                } finally {
                    this.loading = false;
                }
            },
            async completeRegistration() {
                this.validatePhone();
                if (!this.isFormValid) {
                    return;
                }
                this.loading = true;
                this.errorMessage = '';
                try {
                    const res = await axios.post('/register', {
                        email: this.form.email,
                        name: this.form.name,
                        phone: this.form.phone,
                        password: this.form.password
                    }, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (res.data.success) {
                        if (typeof window.trackFbEvent === 'function') {
                            window.trackFbEvent('CompleteRegistration', {
                                content_name: 'User Registration',
                                status: 'success'
                            });
                        }

                        const targetUrl = res.data.redirect_url || '/dashboard';
                        setTimeout(() => {
                            window.location.href = targetUrl;
                        }, 250);
                    } else {
                        window.location.href = '/dashboard';
                    }
                } catch (error) {
                    if (error.response?.data?.errors) {
                        const errs = error.response.data.errors;
                        if (errs.phone) {
                            this.phoneError = errs.phone[0];
                        }
                        this.errorMessage = Object.values(errs).flat().join(' • ');
                    } else if (error.response?.data?.message) {
                        let msg = error.response.data.message;
                        // Prevent leaking raw SQL / database error traces to end users
                        if (msg.includes('SQLSTATE') || msg.includes('Duplicate entry')) {
                            if (msg.includes('users_phone_unique')) {
                                msg = 'This phone number is already registered with another account.';
                                this.phoneError = msg;
                            } else if (msg.includes('users_email_unique')) {
                                msg = 'This email address is already registered.';
                            } else {
                                msg = 'An unexpected database error occurred. Please try again.';
                            }
                        }
                        this.errorMessage = msg;
                    } else {
                        this.errorMessage = 'Registration failed. Please try again.';
                    }
                    this.loading = false;
                }
            }
        }
    }
</script>
@endsection
