<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    gender: user.gender || '',
    has_bayah: user.has_bayah ?? false,
    level: user.level || 'beginner',
    notification_email_enabled: user.notification_preference?.email_enabled ?? true,
    notification_whatsapp_enabled: user.notification_preference?.whatsapp_enabled ?? false,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div>
                <InputLabel for="gender" value="Gender" />

                <select
                    id="gender"
                    v-model="form.gender"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>

                <InputError class="mt-2" :message="form.errors.gender" />
            </div>

            <div>
                <label class="inline-flex items-center gap-2">
                    <input
                        id="has_bayah"
                        type="checkbox"
                        v-model="form.has_bayah"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <span class="text-sm text-gray-700">I have bay'ah with the shaykh</span>
                </label>

                <InputError class="mt-2" :message="form.errors.has_bayah" />
            </div>

            <div>
                <InputLabel for="level" value="Level" />

                <select
                    id="level"
                    v-model="form.level"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="expert">Expert</option>
                </select>

                <InputError class="mt-2" :message="form.errors.level" />
            </div>

            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                <p class="text-xs text-gray-500">Control how we remind you about your learning journey.</p>

                <label class="inline-flex items-center gap-2">
                    <input
                        id="notification_email_enabled"
                        type="checkbox"
                        v-model="form.notification_email_enabled"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <span class="text-sm text-gray-700">Email reminders</span>
                </label>

                <label class="inline-flex items-center gap-2">
                    <input
                        id="notification_whatsapp_enabled"
                        type="checkbox"
                        v-model="form.notification_whatsapp_enabled"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <span class="text-sm text-gray-700">WhatsApp reminders (coming soon)</span>
                </label>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
