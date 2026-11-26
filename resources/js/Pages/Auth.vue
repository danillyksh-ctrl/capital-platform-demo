<script setup>
import { Head, Link, useForm, usePage, router } from "@inertiajs/vue3";
import { computed } from "vue";
import { ChartNoAxesCombined, ShieldCheck } from "@lucide/vue";
const props = defineProps({ mode: String, token: String, email: String });
const page = usePage();
const form = useForm({
    name: "",
    last_name: "",
    email: props.email || "",
    password: "",
    password_confirmation: "",
    token: props.token || "",
    remember: false,
});
const title = computed(
    () =>
        ({
            login: "С возвращением",
            register: "Начните с себя",
            forgot: "Восстановление пароля",
            reset: "Новый пароль",
            verify: "Подтвердите вашу почту",
        })[props.mode],
);
function submit() {
    form.post(
        {
            login: "/login",
            register: "/register",
            forgot: "/forgot-password",
            reset: "/reset-password",
        }[props.mode],
        {
            onFinish: () => {
                form.password = "";
                form.password_confirmation = "";
            },
        },
    );
}
function demo(admin) {
    form.email = admin ? "admin@example.test" : "demo@example.test";
    form.password = "";
}
</script>
<template>
    <Head :title="title" />
    <div class="auth-page">
        <aside class="auth-visual">
            <Link href="/" class="brand"
                ><span class="brand-icon"
                    ><ChartNoAxesCombined :size="24" /></span
                >капитал<span class="brand-dot">.</span></Link
            >
            <div class="auth-statement">
                <span class="eyebrow"
                    >ЯСНОСТЬ СЕГОДНЯ. УВЕРЕННОСТЬ ЗАВТРА.</span
                >
                <h1>Будущее начинается<br />с вашего решения.</h1>
                <p>
                    Ваши цели, обстоятельства и стратегия.<br />В одном
                    спокойном пространстве.
                </p>
                <div class="auth-circles"><i></i><i></i><i></i></div>
            </div>
            <p class="auth-foot">
                <ShieldCheck :size="18" />Демонстрационный проект · Вымышленные
                данные
            </p>
        </aside>
        <main class="auth-main">
            <Link href="/" class="back-link">На главную</Link>
            <div class="auth-form">
                <span class="eyebrow">ЛИЧНЫЙ КАБИНЕТ</span>
                <h1>{{ title }}</h1>
                <p class="muted">
                    {{
                        mode === "login"
                            ? "Войдите, чтобы продолжить свой путь."
                            : mode === "register"
                              ? "Создайте аккаунт и определите ваши ориентиры."
                              : mode === "verify"
                                ? "Мы отправили ссылку на ваш email. Откройте её, чтобы перейти в кабинет."
                                : "Используйте email, указанный при регистрации."
                    }}
                </p>
                <div
                    v-if="page.props.flash.success"
                    class="notice"
                    role="status"
                >
                    {{ page.props.flash.success }}
                </div>
                <template v-if="mode === 'verify'"
                    ><button
                        class="button primary full"
                        @click="router.post('/email/verification-notification')"
                    >
                        Отправить письмо повторно</button
                    ><button
                        class="button secondary full"
                        @click="router.post('/logout')"
                    >
                        Выйти
                    </button></template
                >
                <form v-else @submit.prevent="submit">
                    <div v-if="mode === 'register'" class="field-grid">
                        <div class="field">
                            <label for="name">Имя</label
                            ><input
                                id="name"
                                v-model="form.name"
                                required
                                autocomplete="given-name"
                                maxlength="100"
                            /><small class="error">{{
                                form.errors.name
                            }}</small>
                        </div>
                        <div class="field">
                            <label for="last_name">Фамилия</label
                            ><input
                                id="last_name"
                                v-model="form.last_name"
                                required
                                autocomplete="family-name"
                                maxlength="100"
                            /><small class="error">{{
                                form.errors.last_name
                            }}</small>
                        </div>
                    </div>
                    <div class="field">
                        <label for="email">Электронная почта</label
                        ><input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="you@example.test"
                        /><small class="error">{{ form.errors.email }}</small>
                    </div>
                    <div v-if="mode !== 'forgot'" class="field">
                        <div class="label-row">
                            <label for="password">Пароль</label
                            ><Link
                                v-if="mode === 'login'"
                                href="/forgot-password"
                                >Забыли пароль?</Link
                            >
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            :autocomplete="
                                mode === 'login'
                                    ? 'current-password'
                                    : 'new-password'
                            "
                        /><small class="error">{{ form.errors.password }}</small
                        ><small v-if="mode !== 'login'" class="muted"
                            >Минимум 12 символов, буквы и цифры.</small
                        >
                    </div>
                    <div
                        v-if="['register', 'reset'].includes(mode)"
                        class="field"
                    >
                        <label for="confirmation">Подтверждение пароля</label
                        ><input
                            id="confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                    </div>
                    <label v-if="mode === 'login'" class="checkbox"
                        ><input
                            v-model="form.remember"
                            type="checkbox"
                        />Запомнить меня</label
                    ><button
                        class="button primary full"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? "Подождите…"
                                : {
                                      login: "Войти в кабинет",
                                      register: "Создать аккаунт",
                                      forgot: "Отправить ссылку",
                                      reset: "Сохранить пароль",
                                  }[mode]
                        }}
                    </button>
                </form>
                <p class="auth-switch" v-if="mode === 'login'">
                    Ещё нет аккаунта?
                    <Link href="/register">Зарегистрироваться</Link>
                </p>
                <p class="auth-switch" v-else-if="mode !== 'verify'">
                    Уже есть аккаунт? <Link href="/login">Войти</Link>
                </p>
                <div v-if="mode === 'login'" class="demo-access">
                    <span>ПОСМОТРЕТЬ ДЕМО</span>
                    <div>
                        <button
                            @click="demo(false)"
                            :disabled="form.processing"
                        >
                            Кабинет пользователя</button
                        ><button
                            @click="demo(true)"
                            :disabled="form.processing"
                        >
                            Администратор
                        </button>
                    </div>
                    <small
                        >Выберите аккаунт и введите пароль, заданный при
                        запуске.</small
                    >
                </div>
                <p class="auth-disclaimer">
                    Учебный пример. Используйте только вымышленные данные.
                </p>
            </div>
        </main>
    </div>
</template>
