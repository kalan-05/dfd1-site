<template>
  <section class="services-section container" :id="$attrs.id || 'services'">
    <h2 class="services-section__title">{{ sectionTitle }}</h2>
    <p v-if="sectionDescription" class="services-section__description" :style="descriptionStyle">
      {{ sectionDescription }}
    </p>

    <div class="columns">
      <div
        v-for="column in columns"
        :key="column.group"
        class="column"
      >
        <ol class="diagnostics-list" :start="column.startIndex">
          <li v-for="item in column.items" :key="item.id || item.title">
            {{ item.title }}
          </li>
        </ol>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Диагностика',
  },
  description: {
    type: String,
    default: '',
  },
  items: {
    type: Array,
    default: () => [],
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
});

const sectionTitle = computed(() => props.title || 'Диагностика');
const sectionDescription = computed(() => props.description || '');
const descriptionStyle = computed(() => ({
  textAlign: ['left', 'center', 'right', 'justify'].includes(String(props.meta?.content_alignment || '').trim().toLowerCase())
    ? String(props.meta.content_alignment).trim().toLowerCase()
    : 'left',
}));
const sourceItems = computed(() => (Array.isArray(props.items) ? props.items : []));

const columns = computed(() => {
  const groupsMap = new Map();
  const sorted = [...sourceItems.value].sort((a, b) => (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0));

  sorted.forEach((item) => {
    const group = item.group || 'Услуги';
    if (!groupsMap.has(group)) {
      groupsMap.set(group, []);
    }
    groupsMap.get(group).push(item);
  });

  let currentIndex = 1;
  return Array.from(groupsMap.entries()).map(([group, items]) => {
    const payload = {
      group,
      startIndex: currentIndex,
      items,
    };
    currentIndex += items.length;
    return payload;
  });
});
</script>
