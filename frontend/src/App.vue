<!-- App.vue-->
<template>
  <div class="layout">
    <!-- Общая шапка -->
    <Header />

    <!-- Контент страниц -->
    <main class="layout__main">
      <router-view />
    </main>

    <!-- Общий подвал -->
    <Footer />
  </div>
</template>

<script setup>
// import { useRouter } from 'vue-router'
import Header from '@/components/Header.vue'
import Footer from '@/components/Footer.vue'

/* обработчик клика "Связаться" из Header */
// const router = useRouter()
// function goContact() {
//   router.push({ path: '/', hash: '#contacts' })
// }
</script>


