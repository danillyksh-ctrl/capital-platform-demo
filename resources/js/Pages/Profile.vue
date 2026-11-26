<script setup>
import Shell from "../Components/Shell.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Download, Save } from "@lucide/vue";
const page = usePage(),
    user = computed(() => page.props.auth.user);
const form = useForm(
    Object.fromEntries(
        [
            "name",
            "last_name",
            "phone",
            "city",
            "birth_date",
            "situation",
            "strategy",
        ].map((k) => [
            k,
            k === "birth_date"
                ? user.value[k]?.slice(0, 10) || ""
                : user.value[k] || "",
        ]),
    ),
);
const deletion = useForm({ password: "" }),
    showDelete = ref(false);
const fields = [
    ["name", "Имя", "text"],
    ["last_name", "Фамилия", "text"],
    ["phone", "Телефон", "tel"],
    ["city", "Город", "text"],
    ["birth_date", "Дата рождения", "date"],
];
</script>
<template>
    <Head title="Мой профиль" /><Shell title="Мой профиль"
        ><div class="page-heading">
            <div>
                <div class="eyebrow">ВАША ЛИЧНАЯ ИСТОРИЯ</div>
                <h1>Мой профиль</h1>
                <p>Актуальная информация для осознанных решений.</p>
            </div>
            <a href="/profile/export" class="button secondary"
                ><Download :size="17" />Экспорт JSON</a
            >
        </div>
        <div class="profile-layout">
            <form
                @submit.prevent="
                    form.patch('/profile', { preserveScroll: true })
                "
            >
                <section class="card form-card">
                    <div class="section-heading">
                        <h2>Основная информация</h2>
                        <span class="tag">Личные данные</span>
                    </div>
                    <div class="field-grid">
                        <div
                            v-for="[key, label, type] in fields"
                            :key="key"
                            class="field"
                        >
                            <label :for="key"
                                >{{ label
                                }}{{ key === "name" ? " *" : "" }}</label
                            ><input
                                :id="key"
                                v-model="form[key]"
                                :type="type"
                                :required="key === 'name'"
                                :maxlength="key === 'phone' ? 32 : 120"
                                :aria-invalid="!!form.errors[key]"
                            /><small v-if="form.errors[key]" class="error">{{
                                form.errors[key]
                            }}</small>
                        </div>
                        <div class="field">
                            <label>Email</label
                            ><input :value="user.email" disabled /><small
                                class="muted"
                                >Подтверждён. Используется для входа.</small
                            >
                        </div>
                    </div>
                </section>
                <section class="card form-card" id="situation">
                    <h2>Моя ситуация</h2>
                    <p class="muted">
                        Опишите текущие обстоятельства, цели и обязательства. Не
                        указывайте реальные финансовые или паспортные данные.
                    </p>
                    <label for="situation-text" class="sr-only"
                        >Моя ситуация</label
                    ><textarea
                        id="situation-text"
                        v-model="form.situation"
                        rows="5"
                        maxlength="5000"
                        placeholder="С чего начинается ваш финансовый путь?"
                    />
                    <div class="textarea-meta">
                        <small class="error">{{
                            form.errors.situation
                        }}</small><small
                            >{{ form.situation.length }} / 5000</small
                        >
                    </div>
                </section>
                <section class="card form-card" id="strategy">
                    <h2>Моя инвестиционная стратегия</h2>
                    <p class="muted">
                        Зафиксируйте горизонт планирования и подход к риску.
                    </p>
                    <label for="strategy-text" class="sr-only"
                        >Моя инвестиционная стратегия</label
                    ><textarea
                        id="strategy-text"
                        v-model="form.strategy"
                        rows="5"
                        maxlength="5000"
                        placeholder="Каких принципов вы хотите придерживаться?"
                    />
                    <div class="textarea-meta">
                        <small class="error">{{
                            form.errors.strategy
                        }}</small><small
                            >{{ form.strategy.length }} / 5000</small
                        >
                    </div>
                </section>
                <div class="form-actions">
                    <span class="muted"
                        >Изменения сохраняются по нажатию кнопки.</span
                    ><button class="button primary" :disabled="form.processing">
                        <Save :size="17" />{{
                            form.processing
                                ? "Сохранение…"
                                : "Сохранить профиль"
                        }}
                    </button>
                </div>
            </form>
            <aside>
                <div class="card profile-summary">
                    <span class="avatar large"
                        >{{ user.name.slice(0, 1) }}Д</span
                    >
                    <h3>{{ user.name }} {{ user.last_name }}</h3>
                    <p>{{ user.email }}</p>
                    <div class="summary-progress">
                        <span>Заполненность</span
                        ><b>{{ page.props.completion }}%</b>
                    </div>
                    <div class="progress-track">
                        <i :style="{ width: page.props.completion + '%' }"></i>
                    </div>
                    <p class="hint">
                        Учитываются имя, фамилия, телефон, город, дата рождения,
                        два текстовых блока и подтверждение почты.
                    </p>
                </div>
                <div class="card danger-card">
                    <h3>Удаление аккаунта</h3>
                    <p>Профиль будет удалён без возможности восстановления.</p>
                    <button
                        class="danger-link"
                        @click="showDelete = !showDelete"
                    >
                        Удалить аккаунт
                    </button>
                    <form
                        v-if="showDelete"
                        @submit.prevent="deletion.delete('/profile')"
                    >
                        <label for="delete-password">Подтвердите паролем</label
                        ><input
                            id="delete-password"
                            v-model="deletion.password"
                            type="password"
                            required
                            autocomplete="current-password"
                        /><small class="error">{{
                            deletion.errors.password
                        }}</small
                        ><button
                            class="button danger"
                            :disabled="deletion.processing"
                        >
                            Удалить навсегда
                        </button>
                    </form>
                </div>
            </aside>
        </div></Shell
    >
</template>
