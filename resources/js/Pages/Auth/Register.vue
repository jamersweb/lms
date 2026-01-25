<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Mail, Lock, User, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create Account - Tazkiyah Tarbiyah" />
    
    <div class="min-h-screen flex">
        <!-- Left Side - Background Image -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <img 
                src="/images/auth-bg.png" 
                alt="Islamic Architecture" 
                class="absolute inset-0 w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-br from-primary-900/80 via-primary-900/60 to-primary-950/90"></div>
            
            <!-- Overlay Content -->
            <div class="relative z-10 flex flex-col justify-between p-12 text-white">
                <div>
                    <img src="/images/logo.png" alt="Tazkiyah Tarbiyah" class="h-16" />
                </div>
                
                <div class="max-w-md">
                    <h1 class="font-serif text-4xl font-bold mb-4 leading-tight">
                        Begin Your Spiritual Journey
                    </h1>
                    <p class="text-white/80 text-lg leading-relaxed mb-6">
                        Join thousands of seekers on the path of Tazkiyah (inner purification) and Tarbiyah (character development).
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="h-6 w-6 rounded-full bg-secondary-500/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <div class="h-2 w-2 rounded-full bg-secondary-400"></div>
                            </div>
                            <div>
                                <div class="font-medium">Guided Mentorship</div>
                                <div class="text-sm text-white/60">Learn from authentic Islamic scholars</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="h-6 w-6 rounded-full bg-secondary-500/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <div class="h-2 w-2 rounded-full bg-secondary-400"></div>
                            </div>
                            <div>
                                <div class="font-medium">Structured Learning</div>
                                <div class="text-sm text-white/60">Progress through curated courses</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="h-6 w-6 rounded-full bg-secondary-500/30 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <div class="h-2 w-2 rounded-full bg-secondary-400"></div>
                            </div>
                            <div>
                                <div class="font-medium">Spiritual Growth</div>
                                <div class="text-sm text-white/60">Track your habits and progress</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-sm text-white/50">
                    © 2026 Tazkiyah Tarbiyah. All Rights Reserved.
                </div>
            </div>
        </div>
        
        <!-- Right Side - Register Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-neutral-50">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden mb-8 text-center">
                    <img src="/images/logo.png" alt="Tazkiyah Tarbiyah" class="h-14 mx-auto" />
                </div>
                
                <!-- Header -->
                <div class="text-center mb-8">
                    <h2 class="font-serif text-3xl font-bold text-primary-900 mb-2">Create Account</h2>
                    <p class="text-neutral-600">Start your journey of spiritual growth</p>
                </div>
                
                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-neutral-700 mb-2">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <User class="h-5 w-5 text-neutral-400" />
                            </div>
                            <input
                                id="name"
                                type="text"
                                v-model="form.name"
                                class="w-full pl-12 pr-4 py-3 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 transition-colors bg-white"
                                placeholder="Your full name"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    
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
                                placeholder="Create a password"
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
                    
                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-2">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-neutral-400" />
                            </div>
                            <input
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                class="w-full pl-12 pr-12 py-3 border border-neutral-200 rounded-xl focus:ring-2 focus:ring-primary-100 focus:border-primary-400 transition-colors bg-white"
                                placeholder="Confirm your password"
                                required
                            />
                            <button 
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-neutral-400 hover:text-neutral-600"
                            >
                                <EyeOff v-if="showConfirmPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
                    </div>
                    
                    <!-- Terms -->
                    <div class="text-sm text-neutral-500">
                        By creating an account, you agree to follow the principles of Tazkiyah and Tarbiyah in your spiritual journey.
                    </div>
                    
                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 px-6 bg-primary-900 text-white rounded-xl font-semibold hover:bg-primary-800 focus:ring-4 focus:ring-primary-100 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                        {{ form.processing ? 'Creating Account...' : 'Create Account' }}
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="my-8 flex items-center">
                    <div class="flex-1 border-t border-neutral-200"></div>
                    <span class="px-4 text-sm text-neutral-400">Already have an account?</span>
                    <div class="flex-1 border-t border-neutral-200"></div>
                </div>
                
                <!-- Login Link -->
                <Link
                    :href="route('login')"
                    class="block w-full py-3.5 px-6 bg-white border-2 border-primary-200 text-primary-900 rounded-xl font-semibold hover:bg-primary-50 hover:border-primary-300 transition-all text-center"
                >
                    Sign In Instead
                </Link>
                
                <!-- Footer Quote -->
                <div class="mt-10 text-center">
                    <p class="text-sm text-neutral-500 italic font-serif">
                        "Practicing Shari'ah with Excellence"
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
