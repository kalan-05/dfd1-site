<template>
  <section class="container notfound">
    <h1>404 - Страница не найдена</h1>
  </section>
</template>

<script setup>
import { useHead } from '@unhead/vue'

useHead({
  title: '404 - Страница не найдена | ОФД №1',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
  link: [
    { rel: 'canonical', href: 'https://ofd1-pavlovmed.ru/' },
  ],
})
</script>
