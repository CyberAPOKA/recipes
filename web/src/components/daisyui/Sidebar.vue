<template>
    <div class="drawer lg:drawer-open">
        <input id="drawer-toggle" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <slot name="content" />
        </div>
        <div class="drawer-side">
            <label for="drawer-toggle" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="min-h-full w-64 bg-base-200">
                <div class="p-4">
                    <h2 class="text-lg font-bold">{{ title }}</h2>
                </div>
                <ul class="menu p-4 w-full">
                    <li v-for="item in menuItems" :key="item.name">
                        <router-link :to="item.path" class="text-base-content hover:text-primary">
                            <component :is="item.icon" v-if="item.icon" class="w-5 h-5" />
                            {{ item.name }}
                        </router-link>
                    </li>
                </ul>
                <div class="p-4 mt-auto">
                    <slot name="footer" />
                </div>
            </aside>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    title: {
        type: String,
        default: 'Navigation'
    },
    menuItems: {
        type: Array,
        default: () => [
            { name: 'Home', path: '/', icon: 'HomeIcon' },
            { name: 'About', path: '/about', icon: 'UserIcon' },
            { name: 'Contact', path: '/contact', icon: 'MailIcon' }
        ]
    }
})
</script>
