<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  min:      { type: Number, default: 0 },
  max:      { type: Number, default: 100 },
  modelMin: { type: Number, default: null },
  modelMax: { type: Number, default: null },
  suffix:   { type: String, default: '' },
  step:     { type: Number, default: 1 },
})

const emit = defineEmits(['update:modelMin', 'update:modelMax'])

const localMin = ref(props.modelMin ?? props.min)
const localMax = ref(props.modelMax ?? props.max)

watch(() => props.modelMin, (v) => { if (v !== null) localMin.value = v })
watch(() => props.modelMax, (v) => { if (v !== null) localMax.value = v })
watch(() => props.min, (v) => { if (localMin.value < v) { localMin.value = v; emit('update:modelMin', v) } })
watch(() => props.max, (v) => { if (localMax.value > v) { localMax.value = v; emit('update:modelMax', v) } })

function onMinInput(e) {
  const v = Math.min(Number(e.target.value), localMax.value - props.step)
  localMin.value = v
  emit('update:modelMin', v)
}

function onMaxInput(e) {
  const v = Math.max(Number(e.target.value), localMin.value + props.step)
  localMax.value = v
  emit('update:modelMax', v)
}

const fillStyle = computed(() => {
  const range = props.max - props.min
  if (range === 0) return { left: '0%', width: '100%' }
  const l = ((localMin.value - props.min) / range) * 100
  const r = ((localMax.value - props.min) / range) * 100
  return { left: l + '%', width: (r - l) + '%' }
})
</script>

<template>
  <div>
    <!-- Track + inputs wrapper. Height = thumb diameter so inputs fill it exactly -->
    <div class="rs-wrap">
      <div class="rs-track">
        <div class="rs-fill" :style="fillStyle" />
      </div>
      <input class="rs-input" type="range"
        :min="min" :max="max" :step="step" :value="localMin"
        @input="onMinInput"
      />
      <input class="rs-input" type="range"
        :min="min" :max="max" :step="step" :value="localMax"
        @input="onMaxInput"
      />
    </div>
    <!-- Labels -->
    <div class="rs-labels">
      <span class="rs-label">От <b>{{ localMin }}</b> {{ suffix }}</span>
      <span class="rs-label">До <b>{{ localMax }}</b> {{ suffix }}</span>
    </div>
  </div>
</template>

<style scoped>
/* Outer wrapper — height equals thumb size so inputs fit exactly */
.rs-wrap {
  position: relative;
  height: 20px;
}

/* Static track line, vertically centered */
.rs-track {
  position: absolute;
  left: 0;
  right: 0;
  top: 50%;
  height: 4px;
  transform: translateY(-50%);
  border-radius: 2px;
  background: var(--muted);
  pointer-events: none;
}

/* Colored fill between the two thumbs */
.rs-fill {
  position: absolute;
  top: 0;
  height: 100%;
  border-radius: 2px;
  background: var(--primary);
}

/* Both range inputs stacked on top of each other, covering the full wrapper */
.rs-input {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%; /* 20px — same as wrapper */
  margin: 0;
  padding: 0;
  background: transparent;
  -webkit-appearance: none;
  appearance: none;
  pointer-events: none;
  border: none;
  outline: none;
}

/* WebKit: hide the internal track — we draw our own */
.rs-input::-webkit-slider-runnable-track {
  -webkit-appearance: none;
  background: transparent;
  height: 4px;
}

/* WebKit thumb.
   margin-top = -((thumb_h - track_h) / 2) = -((20 - 4) / 2) = -8px
   This centers the 20px thumb on the 4px track. */
.rs-input::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--primary);
  border: 2px solid var(--background);
  box-shadow: 0 0 0 1.5px var(--muted-foreground);
  cursor: pointer;
  pointer-events: all;
  margin-top: -8px;
}

/* Firefox: hide the internal track */
.rs-input::-moz-range-track {
  background: transparent;
  height: 4px;
}

/* Firefox thumb */
.rs-input::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--primary);
  border: 2px solid var(--background);
  box-shadow: 0 0 0 1.5px var(--muted-foreground);
  cursor: pointer;
  pointer-events: all;
  box-sizing: border-box;
}

/* Labels row */
.rs-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
}
.rs-label {
  font-size: 0.75rem;
  color: var(--muted-foreground);
  display: flex;
  align-items: baseline;
  gap: 3px;
}
.rs-label b {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--foreground);
}
</style>
