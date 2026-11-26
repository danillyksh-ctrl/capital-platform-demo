<script setup>
import Shell from "../Components/Shell.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    Search,
    Users,
    ShieldCheck,
    LockKeyhole,
    ChevronLeft,
    ChevronRight,
} from "@lucide/vue";
import { ref } from "vue";
const props = defineProps({
    users: Object,
    filters: Object,
    actions: Array,
    totals: Object,
});
const search = ref(props.filters.search || ""),
    sort = ref(props.filters.sort || "created_at"),
    direction = ref(props.filters.direction || "desc"),
    pending = ref(null);
function filter() {
    router.get(
        "/admin",
        { search: search.value, sort: sort.value, direction: direction.value },
        { preserveState: true, replace: true },
    );
}
function toggle(user) {
    pending.value = user;
}
function apply() {
    router.patch(
        "/admin/users/" + pending.value.id + "/block",
        {},
        { onSuccess: () => (pending.value = null) },
    );
}
const date = (v) =>
    new Date(v).toLocaleString("ru", { timeZone: "Europe/Moscow" });
</script>
<template>
    <Head title="Администрирование" /><Shell title="Администрирование"
        ><div class="page-heading">
            <div>
                <div class="eyebrow">УПРАВЛЕНИЕ ПЛАТФОРМОЙ</div>
                <h1>Пользователи</h1>
                <p>Доступ, статусы и прозрачная история действий.</p>
            </div>
            <span class="pill admin-pill"
                ><ShieldCheck :size="16" />Администратор</span
            >
        </div>
        <div class="admin-stats">
            <div class="card">
                <Users :size="22" />
                <div>
                    <span>Всего пользователей</span
                    ><strong>{{ totals.users }}</strong>
                </div>
            </div>
            <div class="card">
                <LockKeyhole :size="22" />
                <div>
                    <span>Заблокировано</span
                    ><strong>{{ totals.blocked }}</strong>
                </div>
            </div>
        </div>
        <section class="card users-card">
            <form class="table-toolbar" @submit.prevent="filter">
                <div class="search-field">
                    <Search :size="18" /><input
                        v-model="search"
                        placeholder="Имя, фамилия или email"
                        aria-label="Поиск пользователей"
                        maxlength="100"
                    />
                </div>
                <select
                    v-model="sort"
                    @change="filter"
                    aria-label="Поле сортировки"
                >
                    <option value="created_at">Дата регистрации</option>
                    <option value="name">Имя</option>
                    <option value="email">Email</option></select
                ><select
                    v-model="direction"
                    @change="filter"
                    aria-label="Порядок сортировки"
                >
                    <option value="desc">По убыванию</option>
                    <option value="asc">По возрастанию</option></select
                ><button class="button primary">Найти</button>
            </form>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Дата регистрации</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td>
                                <div class="table-person">
                                    <span class="avatar">{{
                                        user.name.slice(0, 1)
                                    }}</span>
                                    <div>
                                        <b
                                            >{{ user.name }}
                                            {{ user.last_name }}</b
                                        ><small>{{ user.email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{
                                    user.is_admin
                                        ? "Администратор"
                                        : "Пользователь"
                                }}
                            </td>
                            <td>
                                <span
                                    class="status-badge"
                                    :class="{ blocked: user.is_blocked }"
                                    >{{
                                        user.is_blocked
                                            ? "Заблокирован"
                                            : "Активен"
                                    }}</span
                                >
                            </td>
                            <td>
                                {{
                                    new Date(
                                        user.created_at,
                                    ).toLocaleDateString("ru")
                                }}
                            </td>
                            <td>
                                <button
                                    v-if="!user.is_admin"
                                    class="table-action"
                                    @click="toggle(user)"
                                >
                                    {{
                                        user.is_blocked
                                            ? "Разблокировать"
                                            : "Заблокировать"
                                    }}</button><span v-else class="muted"
                                    >Защищён</span
                                >
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="5" class="empty-state">
                                Пользователи не найдены. Измените запрос.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <span
                    >{{ users.from || 0 }}–{{ users.to || 0 }} из
                    {{ users.total }}</span
                >
                <div>
                    <Link
                        v-if="users.prev_page_url"
                        :href="users.prev_page_url"
                        class="button secondary"
                        aria-label="Предыдущая страница"
                        ><ChevronLeft :size="16" /></Link
                    ><span
                        >Страница {{ users.current_page }} из
                        {{ users.last_page }}</span
                    ><Link
                        v-if="users.next_page_url"
                        :href="users.next_page_url"
                        class="button secondary"
                        aria-label="Следующая страница"
                        ><ChevronRight :size="16"
                    /></Link>
                </div>
            </div>
        </section>
        <section class="card audit-card">
            <div class="section-heading">
                <h2>Журнал действий</h2>
                <span>Последние 50 записей</span>
            </div>
            <p v-if="!actions.length" class="muted">
                Действий пока нет. Изменения статусов появятся здесь.
            </p>
            <div v-for="action in actions" :key="action.id" class="audit-row">
                <ShieldCheck :size="18" />
                <div>
                    <b
                        >{{
                            action.action === "blocked"
                                ? "Блокировка"
                                : "Разблокировка"
                        }}
                        пользователя #{{ action.subject_id }}</b
                    ><small
                        >{{ action.actor_name || "Удалённый администратор" }} ·
                        {{ date(action.created_at) }}</small
                    >
                </div>
            </div>
        </section>
        <div v-if="pending" class="modal-overlay" @click.self="pending = null">
            <section
                class="card modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
            >
                <h2 id="confirm-title">
                    {{
                        pending.is_blocked ? "Разблокировать" : "Заблокировать"
                    }}
                    пользователя?
                </h2>
                <p>{{ pending.name }} · {{ pending.email }}</p>
                <p class="muted">
                    Действие будет записано в журнал. Блокировка прервёт доступ
                    к кабинету.
                </p>
                <div class="form-actions">
                    <button class="button secondary" @click="pending = null">
                        Отмена</button
                    ><button class="button primary" @click="apply">
                        Подтвердить
                    </button>
                </div>
            </section>
        </div></Shell
    >
</template>
