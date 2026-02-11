<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Mail, Lock, Loader2 } from 'lucide-vue-next';
import { ref, getCurrentInstance, inject } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

// Get route helper - try inject first (provided by ZiggyVue), then global property, then fallback
const route = inject('route', null) || 
    getCurrentInstance()?.appContext.config.globalProperties.route ||
    (typeof window !== 'undefined' && window.route) ||
    ((name) => {
        console.error('Route helper not available. Route name:', name);
        return '#';
    });

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In - Tazkiyah Tarbiyah" />
    
    <div class="min-h-screen flex">
        <!-- Left Side - Brand (primary, Playfair hero + SVG pattern) -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-primary-900">
            <!-- Subtle diamond pattern from SVG -->
            <div
                class="absolute inset-0 opacity-[0.12] bg-repeat bg-left bg-[length:80px_80px] pointer-events-none"
                :style="{ backgroundImage: `url('/images/download.svg')` }"
                aria-hidden="true"
            />
            <div class="relative z-10 flex flex-col justify-between w-full p-12">
                <img
                    src="/images/logo-tazkiyah.png"
                    alt="Tazkiyah Tarbiyah"
                    class="max-w-[280px] w-full h-auto object-contain"
                />
                <div class="flex-1 flex flex-col justify-center py-8">
                    <h1 class="font-serif text-4xl md:text-5xl font-bold text-white leading-tight tracking-tight">
                        Footsteps of the<br />Prophet Muhammad<br /><span class="text-xl md:text-2xl font-semibold opacity-95">(SAW)</span>
                    </h1>
                    <p class="mt-6 text-white/85 text-base md:text-lg max-w-sm leading-relaxed">
                        To live like him is to live with purpose. Embark on your spiritual journey of inner purification and Islamic guidance.
                    </p>
                </div>
                <div class="mt-auto pt-8 text-xs text-white/50">
                    © 2026 Tazkiyah Tarbiyah. All Rights Reserved.
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-neutral-50">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden mb-8 text-center">
                    <img src="/images/logo-tazkiyah.png" alt="Tazkiyah Tarbiyah" class="h-16 mx-auto object-contain" />
                </div>
                
                <!-- Header -->
                <div class="text-center mb-8">
                    <h2 class="font-serif text-3xl font-bold text-primary-900 mb-2">
                        {{ $page.props.locale === 'ur' ? 'خوش آمدید' : ($page.props.locale === 'en_roman' ? 'Dobara Ahlan o Sahlan' : 'Welcome Back') }}
                    </h2>
                    <p class="text-neutral-600">
                        {{
                          $page.props.locale === 'ur'
                            ? 'اپنے روحانی سفر کو جاری رکھنے کے لئے سائن اِن کریں'
                            : $page.props.locale === 'en_roman'
                              ? 'Apni roohani safar jari rakhne ke liye sign in karein'
                              : 'Sign in to continue your spiritual journey'
                        }}
                    </p>
                </div>
                
                <!-- Status Message -->
                <div v-if="status" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
                    {{ status }}
                </div>
                
                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Mail class="h-5 w-5 text-neutral-400" />
                            </div>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                class="w-full pl-12 pr-4 py-3 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 transition-colors bg-white"
                                placeholder="your@email.com"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-neutral-700 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-neutral-400" />
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                class="w-full pl-12 pr-12 py-3 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 transition-colors bg-white"
                                placeholder="••••••••"
                                required
                            />
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-neutral-400 hover:text-neutral-600"
                            >
                                <EyeOff v-if="showPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>
                    
                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="form.remember"
                                class="h-4 w-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500"
                            />
                            <span class="ml-2 text-sm text-neutral-600">Remember me</span>
                        </label>
                        
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                        >
                            Forgot password?
                        </Link>
                    </div>
                    
                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 px-6 bg-primary-900 text-white rounded-xl font-semibold hover:bg-primary-800 focus:ring-4 focus:ring-primary-100 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                        {{ form.processing ? 'Signing in...' : 'Sign In' }}
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="my-8 flex items-center">
                    <div class="flex-1 border-t border-neutral-200"></div>
                    <span class="px-4 text-sm text-neutral-400">New to Tazkiyah Tarbiyah?</span>
                    <div class="flex-1 border-t border-neutral-200"></div>
                </div>
                
                <!-- Register Link -->
                <Link
                    :href="route('register')"
                    class="block w-full py-3.5 px-6 bg-white border-2 border-primary-200 text-primary-900 rounded-xl font-semibold hover:bg-primary-50 hover:border-primary-300 transition-all text-center"
                >
                    Create an Account
                </Link>
                
                <!-- Footer Quote (Playfair, primary color) -->
                <div class="mt-10 text-center">
                    <p class="text-primary-900 italic font-serif text-base">
                        "When the Heart is Pure, Shari'ah Flows Naturally"
                    </p>
                    <p class="mt-2 text-sm text-neutral-500 max-w-sm mx-auto">
                        Enhance your understanding of the Sunnah through concise, authentic lessons delivered by qualified scholars.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
