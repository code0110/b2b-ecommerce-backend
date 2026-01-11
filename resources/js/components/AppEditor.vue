<template>
  <div class="app-editor-wrapper">
    <div ref="editorContainer" :style="{ height: height }"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  height: {
    type: String,
    default: '300px'
  }
});

const emit = defineEmits(['update:modelValue']);

const editorContainer = ref(null);
let quill = null;

onMounted(() => {
  if (!editorContainer.value) return;

  quill = new Quill(editorContainer.value, {
    theme: 'snow',
    placeholder: props.placeholder,
    modules: {
      toolbar: [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'header': 1 }, { 'header': 2 }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'script': 'sub'}, { 'script': 'super' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        [{ 'direction': 'rtl' }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'font': [] }],
        [{ 'align': [] }],
        ['clean'],
        ['link', 'image', 'video']
      ]
    }
  });

  if (props.modelValue) {
    quill.root.innerHTML = props.modelValue;
  }

  quill.on('text-change', () => {
    const html = quill.root.innerHTML;
    // Quill defaults empty to <p><br></p>, handle that
    const content = html === '<p><br></p>' ? '' : html;
    emit('update:modelValue', content);
  });
});

// Watch for external changes to update editor content
watch(() => props.modelValue, (newVal) => {
  if (quill) {
    const currentContent = quill.root.innerHTML;
    // Avoid updating if content is effectively the same to prevent cursor jumps
    if (newVal !== currentContent && (newVal !== '' || currentContent !== '<p><br></p>')) {
      quill.root.innerHTML = newVal;
    }
  }
});

onBeforeUnmount(() => {
  quill = null;
});
</script>

<style scoped>
.app-editor-wrapper :deep(.ql-toolbar) {
  border-top-left-radius: 0.375rem;
  border-top-right-radius: 0.375rem;
  border-color: #dee2e6;
}
.app-editor-wrapper :deep(.ql-container) {
  border-bottom-left-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
  border-color: #dee2e6;
}
</style>
