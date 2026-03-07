<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="font-serif text-3xl font-bold text-neutral-900">Roles & Permissions</h1>
          <p class="text-neutral-600 mt-1">Manage modules, permissions, and roles</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link
            href="/admin/roles"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 transition-colors"
          >
            <Shield class="w-4 h-4" />
            Roles
          </Link>
          <button
            type="button"
            @click="showModuleModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-500 transition-colors"
          >
            <Plus class="w-4 h-4" />
            Add Module
          </button>
          <button
            type="button"
            @click="showPermissionModal = true"
            :disabled="modules.length === 0"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <KeyRound class="w-4 h-4" />
            Add Permission
          </button>
        </div>
      </div>

      <!-- Modules & Permissions -->
      <div class="space-y-6">
        <div
          v-for="module in modules"
          :key="module.id"
          class="bg-white rounded-xl border border-neutral-200 overflow-hidden"
        >
          <div class="px-6 py-4 bg-neutral-50 border-b border-neutral-200 flex items-start justify-between gap-4">
            <div>
              <h2 class="font-semibold text-neutral-900">{{ module.name }}</h2>
              <p v-if="module.description" class="text-sm text-neutral-500 mt-1">{{ module.description }}</p>
              <code class="text-xs text-neutral-600 mt-1 inline-block">{{ module.slug }}</code>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button
                type="button"
                @click="openEditModule(module)"
                class="p-2 text-neutral-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                title="Edit module"
              >
                <Pencil class="w-4 h-4" />
              </button>
              <button
                type="button"
                @click="openAddPermission(module)"
                class="p-2 text-neutral-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                title="Add permission to this module"
              >
                <Plus class="w-4 h-4" />
              </button>
              <button
                type="button"
                @click="confirmDeleteModule(module)"
                :disabled="module.permissions?.length > 0"
                class="p-2 text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                title="Delete module"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
              <div
                v-for="perm in module.permissions"
                :key="perm.id"
                class="flex items-center justify-between gap-2 p-3 bg-neutral-50 rounded-lg group"
              >
                <div class="flex items-center gap-2 min-w-0">
                  <Check class="w-4 h-4 text-green-600 flex-shrink-0" />
                  <div class="min-w-0">
                    <span class="font-medium text-neutral-800">{{ perm.name }}</span>
                    <code class="block text-xs text-neutral-500 truncate">{{ perm.slug }}</code>
                  </div>
                </div>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                  <button
                    type="button"
                    @click="openEditPermission(perm, module)"
                    class="p-1.5 text-neutral-500 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
                    title="Edit permission"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    type="button"
                    @click="confirmDeletePermission(perm)"
                    class="p-1.5 text-neutral-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                    title="Delete permission"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              <div
                v-if="!module.permissions?.length"
                class="col-span-full text-sm text-neutral-500 italic py-2"
              >
                No permissions yet. Click + to add one.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Module Modal -->
      <Modal :show="showModuleModal" @close="showModuleModal = false">
        <form @submit.prevent="submitModule" class="p-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Add Module</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Name</label>
              <input
                v-model="moduleForm.name"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                placeholder="e.g. Reports"
                required
              />
              <p v-if="moduleForm.errors.name" class="mt-1 text-sm text-red-600">{{ moduleForm.errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Slug (optional)</label>
              <input
                v-model="moduleForm.slug"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                placeholder="e.g. admin.reports (auto-generated if empty)"
              />
              <p v-if="moduleForm.errors.slug" class="mt-1 text-sm text-red-600">{{ moduleForm.errors.slug }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Description</label>
              <input
                v-model="moduleForm.description"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                placeholder="Optional description"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="showModuleModal = false" class="px-4 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </button>
            <button
              type="submit"
              :disabled="moduleForm.processing"
              class="px-4 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 disabled:opacity-50"
            >
              {{ moduleForm.processing ? 'Creating...' : 'Create Module' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Add Permission Modal -->
      <Modal :show="showPermissionModal" @close="closePermissionModal">
        <form @submit.prevent="submitPermission" class="p-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Add Permission</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Module</label>
              <select
                v-model="permissionForm.module_id"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                required
              >
                <option value="">Select module</option>
                <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.name }} ({{ m.slug }})</option>
              </select>
              <p v-if="permissionForm.errors.module_id" class="mt-1 text-sm text-red-600">{{ permissionForm.errors.module_id }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Name</label>
              <input
                v-model="permissionForm.name"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                placeholder="e.g. index, create, edit"
                required
              />
              <p v-if="permissionForm.errors.name" class="mt-1 text-sm text-red-600">{{ permissionForm.errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Slug (optional)</label>
              <input
                v-model="permissionForm.slug"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                placeholder="e.g. admin.reports.index (auto-generated if empty)"
              />
              <p v-if="permissionForm.errors.slug" class="mt-1 text-sm text-red-600">{{ permissionForm.errors.slug }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Description</label>
              <input
                v-model="permissionForm.description"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                placeholder="Optional description"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="closePermissionModal" class="px-4 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </button>
            <button
              type="submit"
              :disabled="permissionForm.processing"
              class="px-4 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 disabled:opacity-50"
            >
              {{ permissionForm.processing ? 'Creating...' : 'Create Permission' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Edit Module Modal -->
      <Modal :show="showEditModuleModal" @close="showEditModuleModal = false">
        <form v-if="editingModule" @submit.prevent="submitEditModule" class="p-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Edit Module</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Name</label>
              <input
                v-model="editModuleForm.name"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Slug</label>
              <input
                v-model="editModuleForm.slug"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Description</label>
              <input
                v-model="editModuleForm.description"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="showEditModuleModal = false" class="px-4 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </button>
            <button
              type="submit"
              :disabled="editModuleForm.processing"
              class="px-4 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 disabled:opacity-50"
            >
              {{ editModuleForm.processing ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Edit Permission Modal -->
      <Modal :show="showEditPermissionModal" @close="showEditPermissionModal = false">
        <form v-if="editingPermission" @submit.prevent="submitEditPermission" class="p-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-4">Edit Permission</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Name</label>
              <input
                v-model="editPermissionForm.name"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Slug</label>
              <input
                v-model="editPermissionForm.slug"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Description</label>
              <input
                v-model="editPermissionForm.description"
                type="text"
                class="w-full px-4 py-2 border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-300"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="showEditPermissionModal = false" class="px-4 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </button>
            <button
              type="submit"
              :disabled="editPermissionForm.processing"
              class="px-4 py-2 bg-primary-900 text-white rounded-lg font-medium hover:bg-primary-800 disabled:opacity-50"
            >
              {{ editPermissionForm.processing ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Delete Confirm Modals -->
      <Modal :show="deleteModuleTarget" @close="deleteModuleTarget = null">
        <div v-if="deleteModuleTarget" class="p-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-2">Delete Module?</h3>
          <p class="text-neutral-600 mb-4">
            Are you sure you want to delete "{{ deleteModuleTarget.name }}"? This cannot be undone.
          </p>
          <div class="flex justify-end gap-2">
            <button type="button" @click="deleteModuleTarget = null" class="px-4 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </button>
            <button
              type="button"
              @click="deleteModule(deleteModuleTarget)"
              class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-500"
            >
              Delete
            </button>
          </div>
        </div>
      </Modal>

      <Modal :show="deletePermissionTarget" @close="deletePermissionTarget = null">
        <div v-if="deletePermissionTarget" class="p-6">
          <h3 class="text-lg font-semibold text-neutral-900 mb-2">Delete Permission?</h3>
          <p class="text-neutral-600 mb-4">
            Are you sure you want to delete "{{ deletePermissionTarget.name }}" ({{ deletePermissionTarget.slug }})?
          </p>
          <div class="flex justify-end gap-2">
            <button type="button" @click="deletePermissionTarget = null" class="px-4 py-2 text-neutral-600 hover:text-neutral-900">
              Cancel
            </button>
            <button
              type="button"
              @click="deletePermission(deletePermissionTarget)"
              class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-500"
            >
              Delete
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AppShell>
</template>

<script setup>
import { ref } from 'vue';
import AppShell from '@/Layouts/AppShell.vue';
import Modal from '@/Components/Modal.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Check, KeyRound, Plus, Pencil, Trash2, Shield } from 'lucide-vue-next';

defineProps({
  modules: Array,
});

const showModuleModal = ref(false);
const showPermissionModal = ref(false);
const showEditModuleModal = ref(false);
const showEditPermissionModal = ref(false);
const editingModule = ref(null);
const editingPermission = ref(null);
const deleteModuleTarget = ref(null);
const deletePermissionTarget = ref(null);

const moduleForm = useForm({
  name: '',
  slug: '',
  description: '',
});

const permissionForm = useForm({
  module_id: '',
  name: '',
  slug: '',
  description: '',
});

const editModuleForm = useForm({
  name: '',
  slug: '',
  description: '',
});

const editPermissionForm = useForm({
  name: '',
  slug: '',
  description: '',
});

function submitModule() {
  moduleForm.post(route('admin.permissions.modules.store'), {
    onSuccess: () => {
      moduleForm.reset();
      showModuleModal.value = false;
    },
  });
}

function closePermissionModal() {
  showPermissionModal.value = false;
  permissionForm.reset();
}

function openAddPermission(module) {
  permissionForm.module_id = module.id;
  showPermissionModal.value = true;
}

function submitPermission() {
  permissionForm.post(route('admin.permissions.permissions.store'), {
    onSuccess: () => {
      permissionForm.reset();
      closePermissionModal();
    },
  });
}

function openEditModule(module) {
  editingModule.value = module;
  editModuleForm.name = module.name;
  editModuleForm.slug = module.slug;
  editModuleForm.description = module.description ?? '';
  showEditModuleModal.value = true;
}

function submitEditModule() {
  if (!editingModule.value) return;
  editModuleForm.put(route('admin.permissions.modules.update', { rbac_module: editingModule.value.id }), {
    onSuccess: () => {
      showEditModuleModal.value = false;
      editingModule.value = null;
    },
  });
}

function openEditPermission(perm, module) {
  editingPermission.value = { ...perm, module };
  editPermissionForm.name = perm.name;
  editPermissionForm.slug = perm.slug;
  editPermissionForm.description = perm.description ?? '';
  showEditPermissionModal.value = true;
}

function submitEditPermission() {
  if (!editingPermission.value) return;
  editPermissionForm.put(route('admin.permissions.permissions.update', { permission: editingPermission.value.id }), {
    onSuccess: () => {
      showEditPermissionModal.value = false;
      editingPermission.value = null;
    },
  });
}

function confirmDeleteModule(module) {
  deleteModuleTarget.value = module;
}

function deleteModule(module) {
  router.delete(route('admin.permissions.modules.destroy', { rbac_module: module.id }), {
    onSuccess: () => {
      deleteModuleTarget.value = null;
    },
  });
}

function confirmDeletePermission(perm) {
  deletePermissionTarget.value = perm;
}

function deletePermission(perm) {
  router.delete(route('admin.permissions.permissions.destroy', { permission: perm.id }), {
    onSuccess: () => {
      deletePermissionTarget.value = null;
    },
  });
}
</script>
