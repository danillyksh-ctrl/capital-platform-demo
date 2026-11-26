<script setup>
import Shell from "../Components/Shell.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
    ArrowUpRight,
    UserRound,
    ShieldCheck,
    Check,
    FileText,
    Compass,
    Download,
    LockKeyhole,
    ChevronRight,
    CalendarDays,
} from "@lucide/vue";
const page = usePage();
const user = page.props.auth.user;
const date = new Intl.DateTimeFormat("ru", {
    day: "numeric",
    month: "long",
    year: "numeric",
}).format(new Date());
</script>
<template>
    <Head title="Обзор" /><Shell title="Обзор"
        ><div class="page-heading">
            <div>
                <div class="eyebrow">ВАШ ФИНАНСОВЫЙ ОРИЕНТИР</div>
                <h1>
                    Здравствуйте, {{ user.name
                    }}<span class="greeting-dot">.</span>
                </h1>
                <p>Большие цели начинаются с понимания себя.</p>
            </div>
            <div class="date-label"><CalendarDays :size="16" />{{ date }}</div>
        </div>
        <section class="overview-grid">
            <div class="welcome-card">
                <div class="pill light">
                    <span class="tiny-dot"></span>ЛИЧНЫЙ КАБИНЕТ
                </div>
                <h2>Ваше будущее.<br />Ваш осознанный выбор.</h2>
                <p>
                    Соберите важное о себе в одном месте.<br />Сформулируйте
                    цели и определите свой путь.
                </p>
                <Link href="/profile" class="button white"
                    >Перейти к профилю<ChevronRight :size="17"
                /></Link>
                <div class="abstract-art" aria-hidden="true">
                    <div class="orbit o1"></div>
                    <div class="orbit o2"></div>
                    <div class="orbit o3"></div>
                    <div class="orb"></div>
                    <span class="art-spark spark1">+</span
                    ><span class="art-spark spark2">+</span>
                    <div class="floating-bar b1"></div>
                    <div class="floating-bar b2"></div>
                    <div class="floating-bar b3"></div>
                    <div class="floating-bar b4"></div>
                </div>
            </div>
            <div class="card completion-card">
                <div class="card-top">
                    <span class="icon-tile green"><UserRound :size="21" /></span
                    ><span class="tag">Ваш прогресс</span>
                </div>
                <div class="completion-row">
                    <div>
                        <h3>Профиль заполнен</h3>
                        <p>Ещё немного до полной картины</p>
                    </div>
                    <strong>{{ page.props.completion }}<span>%</span></strong>
                </div>
                <div class="progress-track">
                    <i :style="{ width: page.props.completion + '%' }"></i>
                </div>
                <div class="check-line"><Check :size="16" />Аккаунт создан</div>
                <div class="check-line">
                    <Check :size="16" />Электронная почта подтверждена
                </div>
                <Link href="/profile" class="text-link"
                    >{{
                        page.props.completion === 100
                            ? "Посмотреть профиль"
                            : "Дополнить профиль"
                    }}<ChevronRight :size="16"
                /></Link>
            </div>
        </section>
        <div class="section-heading">
            <h2>В центре внимания</h2>
            <span>Ваш личный финансовый контекст</span>
        </div>
        <section class="context-grid">
            <article class="card context-card">
                <div class="card-top">
                    <span class="icon-tile blue"><FileText :size="21" /></span
                    ><Link
                        href="/profile#situation"
                        class="icon-link"
                        aria-label="Редактировать ситуацию"
                        ><ArrowUpRight :size="20"
                    /></Link>
                </div>
                <h3>Моя ситуация</h3>
                <p>
                    {{
                        user.situation ||
                        "Расскажите о текущем положении, целях и обязательствах. Это основа вашего финансового плана."
                    }}
                </p>
                <div class="card-bottom">
                    <span class="status-label"
                        ><span class="tiny-dot"></span
                        >{{
                            user.situation
                                ? "Добавлено в профиль"
                                : "Ожидает заполнения"
                        }}</span
                    ><Link href="/profile#situation"
                        >Редактировать<ChevronRight :size="15"
                    /></Link>
                </div>
            </article>
            <article class="card context-card">
                <div class="card-top">
                    <span class="icon-tile violet"><Compass :size="21" /></span
                    ><Link
                        href="/profile#strategy"
                        class="icon-link"
                        aria-label="Редактировать стратегию"
                        ><ArrowUpRight :size="20"
                    /></Link>
                </div>
                <h3>Моя инвестиционная стратегия</h3>
                <p>
                    {{
                        user.strategy ||
                        "Определите ваш горизонт планирования, отношение к риску и принципы, которым хотите следовать."
                    }}
                </p>
                <div class="card-bottom">
                    <span class="status-label"
                        ><span class="tiny-dot"></span
                        >{{
                            user.strategy
                                ? "Добавлено в профиль"
                                : "Ожидает заполнения"
                        }}</span
                    ><Link href="/profile#strategy"
                        >Редактировать<ChevronRight :size="15"
                    /></Link>
                </div>
            </article>
        </section>
        <section class="security-strip">
            <div class="security-icon"><ShieldCheck :size="24" /></div>
            <div>
                <h3>Ваши данные под вашим контролем</h3>
                <p>
                    Информация доступна вам и администраторам демо-платформы. Вы
                    можете скачать или удалить её.
                </p>
            </div>
            <a href="/profile/export" class="button secondary"
                ><Download :size="16" />Скачать мои данные</a
            >
        </section>
        <div class="section-heading">
            <h2>Хорошее начало</h2>
            <span>Три шага к ясности</span>
        </div>
        <section class="steps-grid">
            <div>
                <span class="step-num">01</span>
                <h3>Расскажите о себе</h3>
                <p>
                    Заполните основные данные,<br />чтобы ваш профиль был
                    полным.
                </p>
            </div>
            <div>
                <span class="step-num">02</span>
                <h3>Зафиксируйте точку старта</h3>
                <p>Опишите вашу ситуацию<br />и то, что для вас важно.</p>
            </div>
            <div>
                <span class="step-num">03</span>
                <h3>Определите направление</h3>
                <p>
                    Сформулируйте стратегию,<br />к которой сможете
                    возвращаться.
                </p>
            </div>
        </section>
        <div class="disclaimer">
            <LockKeyhole :size="14" />Учебный проект. Платформа не оказывает
            финансовые услуги и не даёт инвестиционных рекомендаций.
        </div></Shell
    >
</template>
