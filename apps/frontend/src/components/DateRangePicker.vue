<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  dateFrom:    { type: String,  default: '' },
  dateTo:      { type: String,  default: '' },
  allowPast:   { type: Boolean, default: false },
  placeholder: { type: String,  default: 'Дата отправления' },
})
const emit = defineEmits(['update:dateFrom', 'update:dateTo'])

const isOpen    = ref(false)
const pickerRef = ref(null)
const hoverDate = ref(null)

const today = new Date()
const viewYear  = ref(today.getFullYear())
const viewMonth = ref(today.getMonth())

const MONTHS   = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь']
const WEEKDAYS = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс']

const todayStr = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`

function prevMonth() {
  if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value-- }
  else viewMonth.value--
}
function nextMonth() {
  if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++ }
  else viewMonth.value++
}

const calendarDays = computed(() => {
  const y = viewYear.value
  const m = viewMonth.value
  const daysInMonth = new Date(y, m + 1, 0).getDate()
  let firstDow = new Date(y, m, 1).getDay()
  firstDow = firstDow === 0 ? 6 : firstDow - 1 // Mon-first

  const prevDays = new Date(y, m, 0).getDate()
  const days = []

  for (let i = firstDow - 1; i >= 0; i--) {
    days.push({ dateStr: null, day: prevDays - i, thisMonth: false })
  }
  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`
    days.push({ dateStr, day: d, thisMonth: true, past: dateStr < todayStr })
  }
  const remaining = 42 - days.length
  for (let d = 1; d <= remaining; d++) {
    days.push({ dateStr: null, day: d, thisMonth: false })
  }
  return days
})

function selectDay(day) {
  if (!day.thisMonth) return
  if (!props.allowPast && day.past) return
  const df = props.dateFrom
  const dt = props.dateTo

  if (!df || (df && dt)) {
    emit('update:dateFrom', day.dateStr)
    emit('update:dateTo', '')
  } else {
    if (day.dateStr < df) {
      emit('update:dateTo', df)
      emit('update:dateFrom', day.dateStr)
    } else if (day.dateStr === df) {
      // noop
    } else {
      emit('update:dateTo', day.dateStr)
      isOpen.value = false
    }
  }
}

function dayClass(day) {
  const base = 'h-8 w-full flex items-center justify-center text-sm rounded-md transition-colors select-none'
  if (!day.thisMonth) return `${base} text-muted-foreground/30 cursor-default`
  if (!props.allowPast && day.past) return `${base} text-muted-foreground/30 cursor-default`

  const df = props.dateFrom
  const dt = props.dateTo || hoverDate.value
  const isStart = day.dateStr === props.dateFrom
  const isEnd   = day.dateStr === props.dateTo
  const inRange = df && dt && day.dateStr > df && day.dateStr < dt

  if (isStart || isEnd)  return `${base} bg-primary text-primary-foreground font-semibold cursor-pointer`
  if (inRange)           return `${base} bg-primary/15 text-primary cursor-pointer`
  return `${base} hover:bg-muted cursor-pointer`
}

function formatDisplay(d) {
  if (!d) return ''
  const [y, m, day] = d.split('-')
  return `${day}.${m}.${y}`
}

function clear() {
  emit('update:dateFrom', '')
  emit('update:dateTo', '')
}

function onClickOutside(e) {
  if (pickerRef.value && !pickerRef.value.contains(e.target)) isOpen.value = false
}

onMounted(()       => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="pickerRef" class="relative">
    <!-- Trigger -->
    <div
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 h-8 px-2 rounded-md border border-input bg-background text-sm cursor-pointer select-none transition-colors hover:border-ring"
      :class="isOpen ? 'border-ring ring-1 ring-ring' : ''"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted-foreground shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="4" width="18" height="18" rx="2"/>
        <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
      </svg>
      <span v-if="dateFrom" class="text-foreground whitespace-nowrap">
        {{ formatDisplay(dateFrom) }}<span class="text-muted-foreground"> — {{ dateTo ? formatDisplay(dateTo) : '...' }}</span>
      </span>
      <span v-else class="text-muted-foreground whitespace-nowrap">{{ placeholder }}</span>
      <button
        v-if="dateFrom"
        type="button"
        @click.stop="clear"
        class="ml-auto text-muted-foreground hover:text-foreground transition-colors"
        aria-label="Сбросить дату"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Dropdown calendar -->
    <div
      v-if="isOpen"
      class="absolute top-full left-0 mt-1 bg-card border border-border rounded-xl shadow-lg z-50 p-3 sm:p-4 w-72 max-w-[calc(100vw-2rem)]"
    >
      <!-- Month navigation -->
      <div class="flex items-center justify-between mb-3">
        <button
          type="button"
          @click="prevMonth"
          class="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
          aria-label="Предыдущий месяц"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <span class="text-sm font-medium">{{ MONTHS[viewMonth] }} {{ viewYear }}</span>
        <button
          type="button"
          @click="nextMonth"
          class="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
          aria-label="Следующий месяц"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>

      <!-- Weekday headers -->
      <div class="grid grid-cols-7 mb-1">
        <span
          v-for="wd in WEEKDAYS" :key="wd"
          class="h-8 flex items-center justify-center text-xs text-muted-foreground font-medium"
        >{{ wd }}</span>
      </div>

      <!-- Days grid -->
      <div class="grid grid-cols-7">
        <button
          v-for="(day, i) in calendarDays" :key="i"
          type="button"
          :disabled="!day.thisMonth || (!props.allowPast && day.past)"
          :class="dayClass(day)"
          @click="selectDay(day)"
          @mouseenter="day.thisMonth && !day.past ? hoverDate = day.dateStr : null"
          @mouseleave="hoverDate = null"
        >{{ day.day }}</button>
      </div>

      <!-- Hint -->
      <p class="mt-3 pt-2 border-t border-border text-xs text-muted-foreground">
        <template v-if="!dateFrom">Выберите дату начала</template>
        <template v-else-if="!dateTo">Выберите дату окончания или нажмите «Применить»</template>
        <template v-else>Диапазон выбран</template>
      </p>
    </div>
  </div>
</template>
