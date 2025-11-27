<script setup>
import { useLanguageStore } from '../stores/language.js'
import { onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'

const store = useLanguageStore()
const { currentLocale } = storeToRefs(store)
const availableLocales = store.availableLocales
const { setLocale, initializeLocale } = store

onMounted(() => {
    initializeLocale()
})

const selectedCode = ref('pt-BR')

watch(currentLocale, (val) => {
    if (val?.code) selectedCode.value = val.code
}, { immediate: true })

watch(selectedCode, (val) => {
    const current = currentLocale?.value?.code
    if (val && val !== current) setLocale(val)
})
</script>

<template>
    <div class="language-selector">
        <div class="flex items-center gap-2">
            <img v-if="currentLocale?.img" :src="currentLocale.img" :alt="currentLocale?.name"
                class="w-5 h-5 rounded-full transition-all duration-300" />
            <span v-else class="text-lg">{{ currentLocale?.flag }}</span>
            <select class="select select-sm select-bordered" v-model="selectedCode">
                <option v-for="locale in availableLocales" :key="locale.code" :value="locale.code">
                    {{ locale.name }}
                </option>
            </select>
        </div>
    </div>
</template>

<style scoped>
.language-selector .dropdown-content .active {
    background-color: hsl(var(--a));
    color: hsl(var(--ac));
}
</style>
