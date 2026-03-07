<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth?.user ?? {};
const avatarPreview = ref(null);

const form = useForm({
    name: user.name,
    email: user.email,
    avatar: null,
    avatar_remove: false,
    gender: user.gender || '',
    whatsapp_number: user.whatsapp_number || '',
    whatsapp_opt_in: user.whatsapp_opt_in ?? false,
    email_reminders_opt_in: user.email_reminders_opt_in ?? true,
});

const onAvatarChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        form.avatar = file;
        form.avatar_remove = false;
        const reader = new FileReader();
        reader.onload = (ev) => { avatarPreview.value = ev.target?.result; };
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    form.avatar_remove = true;
    form.clearErrors('avatar');
    avatarPreview.value = null;
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information, photo, and contact details.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel value="Profile Picture" />

                <div class="mt-2 flex items-center gap-4">
                    <div class="relative">
                        <img
                            :src="avatarPreview || (form.avatar_remove ? 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name || 'U') + '&background=059669&color=fff&size=200' : user.avatar_url)"
                            alt="Avatar"
                            class="h-24 w-24 rounded-full object-cover border-2 border-gray-200"
                        />
                        <label
                            class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/40 opacity-0 transition hover:opacity-100"
                        >
                            <span class="text-xs font-medium text-white">Change</span>
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                @change="onAvatarChange"
                            />
                        </label>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Upload photo
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                class="hidden"
                                @change="onAvatarChange"
                            />
                        </label>
                        <button
                            v-if="form.avatar || user.avatar"
                            type="button"
                            class="text-sm text-red-600 hover:text-red-700"
                            @click="removeAvatar"
                        >
                            Remove photo
                        </button>
                        <p class="text-xs text-gray-500">JPG, PNG, GIF or WebP. Max 2MB.</p>
                    </div>
                </div>

                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

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
                <InputLabel for="whatsapp_number" value="Phone / WhatsApp Number" />

                <TextInput
                    id="whatsapp_number"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="form.whatsapp_number"
                    placeholder="+92 300 1234567"
                    autocomplete="tel"
                />

                <p class="mt-1 text-xs text-gray-500">
                    Optional. Include country code (e.g., +92 300 1234567) for WhatsApp notifications.
                </p>

                <InputError class="mt-2" :message="form.errors.whatsapp_number" />
            </div>

            <div>
                <label class="inline-flex items-center gap-2">
                    <input
                        id="whatsapp_opt_in"
                        type="checkbox"
                        v-model="form.whatsapp_opt_in"
                        :disabled="!form.whatsapp_number"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                    <span class="text-sm text-gray-700">
                        Opt in to WhatsApp notifications
                        <span v-if="!form.whatsapp_number" class="text-gray-400">(requires WhatsApp number)</span>
                    </span>
                </label>

                <InputError class="mt-2" :message="form.errors.whatsapp_opt_in" />
            </div>

            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-900">Email Reminders</h3>
                <p class="text-xs text-gray-500">Receive email reminders about your learning journey.</p>

                <label class="inline-flex items-center gap-2">
                    <input
                        id="email_reminders_opt_in"
                        type="checkbox"
                        v-model="form.email_reminders_opt_in"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <span class="text-sm text-gray-700">Enable email reminders</span>
                </label>

                <InputError class="mt-2" :message="form.errors.email_reminders_opt_in" />
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
