<template>
    <div class="navbar bg-base-100 shadow-lg">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h8m-8 6h16"></path>
                    </svg>
                </div>
                <ul tabindex="0"
                    class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                    <li v-for="item in mobileMenuItems" :key="item.name">
                        <router-link :to="item.path" class="text-base-content hover:text-primary">
                            {{ $t(item.name) }}
                        </router-link>
                    </li>
                </ul>
            </div>
            <router-link to="/" class="btn btn-ghost text-xl">
                {{ brandName }}
            </router-link>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li v-for="item in menuItems" :key="item.name">
                    <router-link :to="item.path" class="text-base-content hover:text-primary">
                        {{ $t(item.name) }}
                    </router-link>
                </li>
            </ul>
        </div>
        <div class="navbar-end">
            <slot name="end" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    brandName: {
        type: String,
        default: 'Portfolio'
    },
    menuItems: {
        type: Array,
        default: () => [
            { name: 'Home', path: '/' },
            { name: 'About', path: '/about' },
            { name: 'Contact', path: '/contact' }
        ]
    }
})

const mobileMenuItems = computed(() => props.menuItems)
</script>
