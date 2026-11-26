<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
import {
    LayoutDashboard,
    UserRound,
    ShieldCheck,
    LogOut,
    ChartNoAxesCombined,
    Menu,
} from "@lucide/vue";
import { ref } from "vue";
const page = usePage(),
    open = ref(false);
defineProps({
    title: String,
    section: { type: String, default: "Личный кабинет" },
});
const nav = [
    { label: "Обзор", url: "/dashboard", icon: LayoutDashboard },
    { label: "Мой профиль", url: "/profile", icon: UserRound },
];
</script>
<template>
    <div class="app-shell">
        <aside class="sidebar" :class="{ opened: open }">
            <Link href="/" class="brand"
                ><span class="brand-icon"
                    ><ChartNoAxesCombined :size="23" /></span
                >капитал<span class="brand-dot">.</span></Link
            >
            <div class="sidebar-caption">ЛИЧНЫЙ КАБИНЕТ</div>
            <nav>
                <Link
                    v-for="item in nav"
                    :key="item.url"
                    :href="item.url"
                    :class="{ active: page.url.startsWith(item.url) }"
                    @click="open = false"
                    ><component :is="item.icon" :size="20" />{{ item.label
                    }}<span v-if="item.url === '/profile'" class="nav-percent"
                        >{{ page.props.completion }}%</span
                    ></Link
                ><Link
                    v-if="page.props.auth.user.is_admin"
                    href="/admin"
                    :class="{ active: page.url.startsWith('/admin') }"
                    ><ShieldCheck :size="20" />Администрирование</Link
                >
            </nav>
            <div class="sidebar-bottom">
                <div class="demo-note">
                    <div class="demo-label">
                        <span class="tiny-dot"></span>Демо-платформа
                    </div>
                    <p>Пространство для ваших<br />финансовых решений.</p>
                    <span>Только вымышленные данные</span>
                </div>
                <div class="side-user">
                    <span class="avatar"
                        >{{ page.props.auth.user.name.slice(0, 1) }}Д</span
                    >
                    <div>
                        <b
                            >{{ page.props.auth.user.name }}
                            {{ page.props.auth.user.last_name }}</b
                        ><small>{{
                            page.props.auth.user.is_admin
                                ? "Администратор"
                                : "Личный аккаунт"
                        }}</small>
                    </div>
                    <button
                        class="icon-button"
                        @click="router.post('/logout')"
                        aria-label="Выйти"
                    >
                        <LogOut :size="18" />
                    </button>
                </div>
            </div>
        </aside>
        <div v-if="open" class="scrim" @click="open = false"></div>
        <div class="workspace">
            <header class="topbar">
                <div class="breadcrumb">
                    <button
                        class="icon-button mobile-menu"
                        @click="open = !open"
                        aria-label="Меню"
                    >
                        <Menu :size="22" /></button
                    ><span>Личный кабинет</span
                    ><span class="crumb-divider">/</span><b>{{ title }}</b>
                </div>
                <div class="top-right">
                    <span class="environment"
                        ><span class="tiny-dot"></span>Демонстрационная
                        версия</span
                    ><span class="top-divider"></span
                    ><span class="avatar small"
                        >{{ page.props.auth.user.name.slice(0, 1) }}Д</span
                    >
                </div>
            </header>
            <main>
                <div
                    v-if="page.props.flash.success"
                    class="notice"
                    role="status"
                >
                    {{ page.props.flash.success }}
                </div>
                <slot />
            </main>
            <footer>
                <span
                    >© {{ new Date().getFullYear() }} Капитал. Демо-проект для
                    портфолио.</span
                ><span>Laravel · Vue · Inertia</span>
            </footer>
        </div>
    </div>
</template>
