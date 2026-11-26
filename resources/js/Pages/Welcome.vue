<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
    ChartNoAxesCombined,
    ShieldCheck,
    Compass,
    UserRound,
    ChevronRight,
    Check,
    ArrowUpRight,
} from "@lucide/vue";
const page = usePage();
</script>
<template>
    <Head title="Финансовая ясность начинается с вас" />
    <div class="landing">
        <header class="landing-header">
            <Link href="/" class="brand dark-brand"
                ><span class="brand-icon"
                    ><ChartNoAxesCombined :size="23" /></span
                >капитал<span class="brand-dot">.</span></Link
            >
            <nav>
                <a href="#approach">Наш подход</a
                ><a href="#possibilities">Возможности</a
                ><a href="#technology">О проекте</a>
            </nav>
            <div>
                <Link
                    :href="page.props.auth.user ? '/dashboard' : '/login'"
                    class="login-link"
                    >{{
                        page.props.auth.user ? "Личный кабинет" : "Войти"
                    }}</Link><Link href="/register" class="button primary"
                    >Создать аккаунт</Link
                >
            </div>
        </header>
        <main>
            <section class="hero">
                <div class="hero-copy">
                    <div class="eyebrow">
                        <span class="tiny-dot"></span>ПРОСТРАНСТВО ОСОЗНАННЫХ
                        РЕШЕНИЙ
                    </div>
                    <h1>
                        Ваши цели.<br />Ваш путь.<br /><span>Ваш капитал.</span>
                    </h1>
                    <p>
                        Начните с главного: поймите свою ситуацию,<br
                            class="desktop-br"
                        />
                        определите ориентиры и сохраните стратегию<br
                            class="desktop-br"
                        />
                        в личном финансовом кабинете.
                    </p>
                    <div class="hero-actions">
                        <Link href="/register" class="button primary"
                            >Создать личный кабинет<ChevronRight
                                :size="18" /></Link
                        ><Link href="/login" class="text-link"
                            >Посмотреть демо<ArrowUpRight :size="17"
                        /></Link>
                    </div>
                    <div class="hero-trust">
                        <span><Check :size="15" />Бесплатный демо-доступ</span
                        ><span
                            ><ShieldCheck :size="15" />Контроль ваших
                            данных</span
                        >
                    </div>
                </div>
                <div class="hero-preview">
                    <div class="preview-window">
                        <div class="preview-top">
                            <span class="brand mini"
                                >капитал<span class="brand-dot">.</span></span
                            ><span class="preview-user">АД</span>
                        </div>
                        <div class="preview-body">
                            <small>ЛИЧНЫЙ КАБИНЕТ</small>
                            <h3>Здравствуйте, Алексей.</h3>
                            <p>Всё важное о вашем будущем в одном месте.</p>
                            <div class="preview-green">
                                <div>
                                    <span>Профиль заполнен</span
                                    ><strong>88<small>%</small></strong>
                                </div>
                                <div class="progress-track">
                                    <i style="width: 88%"></i>
                                </div>
                                <span><Check :size="12" />Отличное начало</span>
                            </div>
                            <div class="preview-cards">
                                <div>
                                    <span class="icon-tile blue"
                                        ><UserRound :size="19" /></span
                                    ><b>Моя ситуация</b>
                                    <p>
                                        Ясная точка старта<br />для ваших
                                        решений.
                                    </p>
                                    <span class="preview-status"
                                        >Добавлено в профиль</span
                                    >
                                </div>
                                <div>
                                    <span class="icon-tile violet"
                                        ><Compass :size="19" /></span
                                    ><b>Моя стратегия</b>
                                    <p>Ваши принципы.<br />Ваше направление.</p>
                                    <span class="preview-status"
                                        >Добавлено в профиль</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="floating-security">
                        <span class="icon-tile green"
                            ><ShieldCheck :size="22"
                        /></span>
                        <div>
                            <b>Под вашим контролем</b
                            ><small>Данные можно скачать или удалить</small>
                        </div>
                        <Check :size="16" />
                    </div>
                    <span class="preview-caption"
                        >Пример интерфейса · Все данные вымышлены</span
                    >
                </div>
            </section>
            <div class="landing-divider">
                <span>МЕНЬШЕ ШУМА. БОЛЬШЕ ЯСНОСТИ.</span>
                <div></div>
                <span>Спокойный подход к важным решениям</span>
            </div>
            <section class="landing-features" id="possibilities">
                <div class="section-intro" id="approach">
                    <span class="eyebrow">ОТ ПОНИМАНИЯ К ДЕЙСТВИЮ</span>
                    <h2>Всё начинается с вас</h2>
                    <p>
                        Не сложные графики. А контекст, который имеет значение.
                    </p>
                </div>
                <div class="feature-grid">
                    <article>
                        <span class="icon-tile blue"
                            ><UserRound :size="24"
                        /></span>
                        <h3>Ваша точка старта</h3>
                        <p>
                            Соберите информацию о себе и опишите текущую
                            ситуацию. Посмотрите на картину целиком.
                        </p>
                    </article>
                    <article>
                        <span class="icon-tile green"
                            ><Compass :size="24"
                        /></span>
                        <h3>Ваше направление</h3>
                        <p>
                            Сформулируйте личную стратегию и принципы.
                            Возвращайтесь к ним, когда это необходимо.
                        </p>
                    </article>
                    <article>
                        <span class="icon-tile violet"
                            ><ShieldCheck :size="24"
                        /></span>
                        <h3>Ваш контроль</h3>
                        <p>
                            Редактируйте профиль, выгружайте данные или удаляйте
                            аккаунт. Решение остаётся за вами.
                        </p>
                    </article>
                </div>
            </section>
            <section class="project-banner" id="technology">
                <div>
                    <span class="eyebrow">СОЗДАНО С ВНИМАНИЕМ К ДЕТАЛЯМ</span>
                    <h2>Простой снаружи.<br />Продуманный внутри.</h2>
                    <p>
                        Самостоятельный технический проект для портфолио
                        разработчика.<br />Без реальных финансовых операций и
                        инвестиционных рекомендаций.
                    </p>
                </div>
                <div class="tech-tags">
                    <span>Laravel 13</span><span>Vue 3</span
                    ><span>Inertia.js</span><span>PostgreSQL</span
                    ><span>Redis</span><span>Docker</span>
                </div>
            </section>
        </main>
        <footer class="landing-footer">
            <span class="brand dark-brand"
                >капитал<span class="brand-dot">.</span></span
            >
            <p>
                © {{ new Date().getFullYear() }} Демонстрационный проект. Только
                вымышленные данные.
            </p>
            <Link href="/login">Личный кабинет</Link>
        </footer>
    </div>
</template>
