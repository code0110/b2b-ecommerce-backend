<template>
  <button
    type="button"
    class="btn btn-outline-secondary btn-sm position-relative d-flex align-items-center"
    @click="handleClick"
  >
    <span class="me-1">🔔</span>
    <span class="d-none d-md-inline">{{ label }}</span>
    <span
      v-if="count > 0"
      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
      style="font-size: 0.7rem;"
    >
      {{ count }}
    </span>
  </button>
</template>

<script setup>
import { useRouter } from 'vue-router'

const props = defineProps({
  count: {
    type: Number,
    default: 0
  },
  to: {
    type: [Object, String],
    default: null
  },
  label: {
    type: String,
    default: 'Notificări'
  }
})

const router = useRouter()

const handleClick = () => {
  if (props.to) {
    router.push(props.to)
  }
}
</script>
