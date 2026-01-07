<template>
  <div class="position-relative searchable-select" ref="containerRef">
    <!-- Main Input Container -->
    <div 
      class="form-control d-flex flex-wrap align-items-center gap-1"
      :class="{ 'focus-ring': isOpen, 'is-invalid': error }"
      @click="openDropdown"
      style="min-height: 38px; cursor: text;"
    >
      <!-- Selected Tags -->
      <span 
        v-for="(item, index) in selectedItems" 
        :key="getItemValue(item)"
        class="badge bg-light text-dark border d-flex align-items-center gap-1 fw-normal"
      >
        {{ getItemLabel(item) }}
        <i 
          class="bi bi-x cursor-pointer text-muted hover-danger" 
          @click.stop="removeItem(index)"
          role="button"
        ></i>
      </span>

      <!-- Search Input -->
      <input
        ref="inputRef"
        v-model="searchQuery"
        @input="onInput"
        @keydown.down.prevent="navigate(1)"
        @keydown.up.prevent="navigate(-1)"
        @keydown.enter.prevent="selectHighlighted"
        @keydown.backspace="onBackspace"
        @focus="openDropdown"
        type="text"
        class="border-0 bg-transparent flex-grow-1 p-0 m-0 shadow-none"
        :placeholder="selectedItems.length === 0 ? placeholder : ''"
        style="outline: none; min-width: 60px;"
      />
      
      <!-- Loading Indicator -->
      <div v-if="loading" class="spinner-border spinner-border-sm text-muted ms-auto" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Dropdown Menu -->
    <ul 
      v-if="isOpen" 
      class="dropdown-menu show w-100 mt-1 shadow-sm overflow-auto"
      style="max-height: 250px; z-index: 1050;"
    >
      <!-- Options -->
      <li v-for="(option, index) in filteredOptions" :key="getItemValue(option)">
        <a 
          class="dropdown-item d-flex align-items-center justify-content-between"
          :class="{ 'active': index === highlightedIndex, 'disabled': isSelected(option) }"
          href="#"
          @click.prevent="selectItem(option)"
          @mouseover="highlightedIndex = index"
        >
          <span>
             <slot name="option" :option="option">
                {{ getItemLabel(option) }}
             </slot>
          </span>
          <i v-if="isSelected(option)" class="bi bi-check-lg"></i>
        </a>
      </li>

      <!-- No Results -->
      <li v-if="!loading && filteredOptions.length === 0" class="dropdown-item text-muted disabled text-center small">
        Nu au fost găsite rezultate.
      </li>
      
      <!-- Remote Search Prompt -->
      <li v-if="remote && searchQuery.length < minSearchLength && !loading && filteredOptions.length === 0" class="dropdown-item text-muted disabled text-center small">
        Tastează pentru a căuta...
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Array, String, Number, Object],
    default: () => []
  },
  options: {
    type: Array,
    default: () => []
  },
  multiple: {
    type: Boolean,
    default: true
  },
  label: {
    type: String,
    default: 'name'
  },
  trackBy: {
    type: String,
    default: 'id'
  },
  placeholder: {
    type: String,
    default: 'Selectează...'
  },
  remote: {
    type: Boolean,
    default: false
  },
  remoteMethod: {
    type: Function,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  },
  minSearchLength: {
    type: Number,
    default: 2
  },
  error: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'search']);

const containerRef = ref(null);
const inputRef = ref(null);
const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const internalOptions = ref([]); // Used for remote results or local filtering copy

// Helpers
const getItemLabel = (item) => {
  return typeof item === 'object' ? item[props.label] : item;
};

const getItemValue = (item) => {
  return typeof item === 'object' ? item[props.trackBy] : item;
};

// Computed
const selectedItems = computed(() => {
  // If options are objects, we need to find the full objects based on IDs in modelValue
  // OR if modelValue contains objects, use them directly.
  // Assumption: modelValue contains IDs (standard for forms).
  // But we need to display labels. So we need to look up in `options` (or `internalOptions` for remote).
  
  // Strategy: Maintain a cache of selected objects or rely on props.options to have them.
  // For remote search, this is tricky because `options` changes.
  // Solution: Expect `modelValue` to be array of IDs, but we might need `initialSelected` prop or logic to fetch labels.
  // SIMPLIFICATION: For now, let's assume `modelValue` is array of IDs.
  // But we need to display the NAME.
  // If it's a remote select, the parent usually passes the "initial loaded options" or "selected objects".
  // Let's change contract: modelValue should be array of IDs. Parent is responsible for ensuring `options` contains the selected items OR we pass `selectedObjects` prop.
  // Actually, usually in these components, if `modelValue` has IDs, we try to find them in `options`. If not found, we show ID (fallback).
  
  // Better approach for this project: Pass the full objects in `modelValue`? 
  // No, standard HTML forms use IDs. 
  // Let's stick to: Parent passes `options` which should include the selected items initially.
  
  let ids = [];
  if (props.multiple) {
      ids = Array.isArray(props.modelValue) ? props.modelValue : [];
  } else {
      ids = (props.modelValue !== null && props.modelValue !== undefined) ? [props.modelValue] : [];
  }

  return ids.map(id => {
    const opts = props.options || [];
    const internal = internalOptions.value || [];
    const found = opts.find(opt => opt && getItemValue(opt) === id) || internal.find(opt => opt && getItemValue(opt) === id);
    // If not found, return a dummy object with ID as label (better than crashing)
    return found || { [props.trackBy]: id, [props.label]: `ID: ${id}` }; 
  });
});

const filteredOptions = computed(() => {
  if (props.remote) {
    return props.options; // Options are already filtered by parent via remote search
  }
  
  if (!searchQuery.value) return props.options;
  
  const query = searchQuery.value.toLowerCase();
  return props.options.filter(opt => 
    getItemLabel(opt).toLowerCase().includes(query)
  );
});

// Methods
const openDropdown = () => {
  isOpen.value = true;
  nextTick(() => inputRef.value?.focus());
};

const closeDropdown = () => {
  isOpen.value = false;
  highlightedIndex.value = -1;
  searchQuery.value = ''; // Clear search on close? Maybe.
};

const onInput = () => {
  isOpen.value = true;
  highlightedIndex.value = 0;
  
  if (props.remote) {
    if (props.remoteMethod) {
        props.remoteMethod(searchQuery.value);
    }
    emit('search', searchQuery.value);
  }
};

const selectItem = (item) => {
  const value = getItemValue(item);
  
  if (props.multiple) {
      const current = Array.isArray(props.modelValue) ? props.modelValue : [];
      if (current.includes(value)) return; // Already selected
      
      const newValue = [...current, value];
      emit('update:modelValue', newValue);
  } else {
      emit('update:modelValue', value);
  }
  
  searchQuery.value = '';
  if (!props.remote) {
      if (props.multiple) {
         inputRef.value?.focus();
      } else {
         closeDropdown();
      }
  } else {
      // For remote, maybe clear search results?
      // Let's keep it open but clear search query so user can type again.
      if (props.remoteMethod) {
          props.remoteMethod('');
      }
      emit('search', ''); 
      
      if (props.multiple) {
        inputRef.value?.focus();
      } else {
        closeDropdown();
      }
  }
};

const removeItem = (index) => {
  if (props.multiple) {
      const current = Array.isArray(props.modelValue) ? props.modelValue : [];
      const newValue = [...current];
      newValue.splice(index, 1);
      emit('update:modelValue', newValue);
  } else {
      emit('update:modelValue', null);
  }
};

const isSelected = (item) => {
  const value = getItemValue(item);
  if (props.multiple) {
      const current = Array.isArray(props.modelValue) ? props.modelValue : [];
      return current.includes(value);
  }
  return props.modelValue === value;
};

const selectHighlighted = () => {
  if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
    selectItem(filteredOptions.value[highlightedIndex.value]);
  }
};

const navigate = (direction) => {
  if (!isOpen.value) {
    openDropdown();
    return;
  }
  
  const max = filteredOptions.value.length - 1;
  let next = highlightedIndex.value + direction;
  
  if (next > max) next = 0;
  if (next < 0) next = max;
  
  highlightedIndex.value = next;
  
  // Scroll into view logic could be added here
};

const onBackspace = () => {
  if (searchQuery.value === '' && selectedItems.value.length > 0) {
    // Remove last item
    removeItem(selectedItems.value.length - 1);
  }
};

// Click Outside
const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

watch(isOpen, (val) => {
    if (!val) searchQuery.value = '';
});

</script>

<style scoped>
.searchable-select .focus-ring {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.hover-danger:hover {
    color: #dc3545 !important;
}
</style>