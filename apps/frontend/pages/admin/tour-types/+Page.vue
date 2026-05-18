<script setup>
import { ref, onMounted } from 'vue'
import { getTourTypes, createTourType, updateTourType, deleteTourType } from '@/api/admin.js'

const types = ref([])
const loading = ref(true)
const error = ref('')

const newName = ref('')
const newIcon = ref('')
const creating = ref(false)
const createError = ref('')

const editId = ref(null)
const editName = ref('')
const editIcon = ref('')
const saving = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await getTourTypes()
    types.value = data.data ?? data
  } catch {
    error.value = 'Не удалось загрузить типы туров'
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function create() {
  createError.value = ''
  if (!newName.value.trim()) return
  creating.value = true
  try {
    const { data } = await createTourType({ name: newName.value.trim(), icon: newIcon.value.trim() || null })
    types.value.push(data)
    newName.value = ''
    newIcon.value = ''
  } catch (e) {
    createError.value = e.response?.data?.message ?? 'Ошибка при создании'
  } finally {
    creating.value = false
  }
}

function startEdit(type) {
  editId.value = type.id
  editName.value = type.name
  editIcon.value = type.icon ?? ''
}

function cancelEdit() {
  editId.value = null
  editName.value = ''
  editIcon.value = ''
}

async function saveEdit(type) {
  if (!editName.value.trim()) return
  saving.value = true
  try {
    const { data } = await updateTourType(type.id, {
      name: editName.value.trim(),
      icon: editIcon.value.trim() || null,
    })
    const idx = types.value.findIndex(t => t.id === type.id)
    if (idx !== -1) types.value[idx] = data
    cancelEdit()
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Ошибка при сохранении'
  } finally {
    saving.value = false
  }
}

async function remove(type) {
  if (!confirm(`Удалить тип «${type.name}»?`)) return
  try {
    await deleteTourType(type.id)
    types.value = types.value.filter(t => t.id !== type.id)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Ошибка при удалении'
  }
}

const fallbackIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>`
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">Типы туров</h1>

    <div v-if="loading" class="text-muted-foreground text-sm py-8">Загрузка…</div>
    <div v-else-if="error" class="text-red-500 text-sm mb-4">{{ error }}</div>

    <div v-else class="space-y-6">

      <!-- Add new type -->
      <div class="bg-card border border-border rounded-lg p-5 space-y-3">
        <h2 class="font-semibold text-sm">Добавить тип</h2>
        <div class="flex gap-2">
          <input
            v-model="newName"
            type="text"
            placeholder="Название нового типа"
            maxlength="255"
            class="flex-1 px-3 py-2 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @keydown.enter.prevent="create"
          />
          <button
            type="button"
            :disabled="creating || !newName.trim()"
            @click="create"
            class="px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
          >
            {{ creating ? 'Добавление…' : 'Добавить' }}
          </button>
        </div>

        <!-- Icon input for new type -->
        <div class="flex gap-3 items-start">
          <div class="flex-1 space-y-1">
            <label class="text-xs text-muted-foreground">SVG-иконка (вставьте код, напр. из heroicons.com)</label>
            <textarea
              v-model="newIcon"
              rows="3"
              placeholder='<svg xmlns="http://www.w3.org/2000/svg" ...>...</svg>'
              class="w-full px-3 py-2 rounded-md border border-input bg-background text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-none"
            />
          </div>
          <div class="flex flex-col items-center gap-1 pt-5">
            <div
              class="w-10 h-10 rounded-md border border-border bg-muted flex items-center justify-center text-foreground [&_svg]:w-5 [&_svg]:h-5"
              v-html="newIcon.trim() || fallbackIcon"
            />
            <span class="text-[10px] text-muted-foreground">превью</span>
          </div>
        </div>
        <p v-if="createError" class="text-red-500 text-xs">{{ createError }}</p>
      </div>

      <!-- List -->
      <div class="bg-card border border-border rounded-lg divide-y divide-border">
        <div v-if="types.length === 0" class="px-5 py-4 text-sm text-muted-foreground">
          Нет типов. Добавьте первый.
        </div>
        <div
          v-for="type in types"
          :key="type.id"
          class="px-5 py-4"
        >
          <!-- Edit mode -->
          <template v-if="editId === type.id">
            <div class="flex gap-2 mb-3">
              <input
                v-model="editName"
                type="text"
                class="flex-1 px-2 py-1.5 rounded border border-input bg-background text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                @keydown.escape="cancelEdit"
              />
              <button
                type="button"
                :disabled="saving"
                @click="saveEdit(type)"
                class="text-sm px-3 py-1.5 bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors disabled:opacity-50"
              >
                {{ saving ? '…' : 'Сохранить' }}
              </button>
              <button
                type="button"
                @click="cancelEdit"
                class="text-sm text-muted-foreground hover:text-foreground transition-colors"
              >
                Отмена
              </button>
            </div>
            <div class="flex gap-3 items-start">
              <div class="flex-1 space-y-1">
                <label class="text-xs text-muted-foreground">SVG-иконка</label>
                <textarea
                  v-model="editIcon"
                  rows="3"
                  placeholder='<svg xmlns="http://www.w3.org/2000/svg" ...>...</svg>'
                  class="w-full px-3 py-2 rounded-md border border-input bg-background text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                />
              </div>
              <div class="flex flex-col items-center gap-1 pt-5">
                <div
                  class="w-10 h-10 rounded-md border border-border bg-muted flex items-center justify-center text-foreground [&_svg]:w-5 [&_svg]:h-5"
                  v-html="editIcon.trim() || fallbackIcon"
                />
                <span class="text-[10px] text-muted-foreground">превью</span>
              </div>
            </div>
          </template>

          <!-- View mode -->
          <template v-else>
            <div class="flex items-center gap-3">
              <div
                class="w-8 h-8 rounded border border-border bg-muted flex items-center justify-center text-foreground shrink-0 [&_svg]:w-4 [&_svg]:h-4"
                v-html="type.icon || fallbackIcon"
              />
              <span class="flex-1 text-sm font-medium">{{ type.name }}</span>
              <span class="text-xs text-muted-foreground font-mono">{{ type.slug }}</span>
              <button
                type="button"
                @click="startEdit(type)"
                class="text-sm text-muted-foreground hover:text-foreground transition-colors"
              >
                Изменить
              </button>
              <button
                type="button"
                @click="remove(type)"
                class="text-sm text-red-500 hover:text-red-700 transition-colors"
              >
                Удалить
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>
