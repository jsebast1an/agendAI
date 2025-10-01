import { Head, Link } from '@inertiajs/react';
import Pricing from '@/Components/Pricing';
import Reviews, { demoReviews } from '@/Components/Reviews';

export default function Landing() {
    return (
        <>
        <Head title="AgendAI — Agenda automática con WhatsApp" />
        <div className="w-full bg-neutral-950 text-neutral-100 relative">

                {/* Contenedor principal */}
            <div className="mx-auto flex h-full max-w-6xl flex-col px-6">
                {/* Navbar mini */}
                <header className="flex items-center justify-between py-5">
                    <div className="flex items-center gap-2">
                        <span className="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-neutral-900 font-black">A</span>
                        <span className="tracking-tight font-semibold">Agend<span className="text-fuchsia-400">AI</span></span>
                    </div>
                    <nav className="flex items-center gap-3 text-sm">
                        <Link href="/login" className="text-neutral-300 hover:text-white transition">Iniciar sesión</Link>
                        <Link
                            href="/register"
                            className="rounded-xl bg-white px-4 py-2 text-neutral-900 font-medium hover:bg-neutral-200 transition"
                        >
                            Crear cuenta
                        </Link>
                    </nav>
                </header>

                {/* Hero centrado (sin scroll) */}
                <main className="grid flex-1 place-items-center">
                    <section className="w-full">
                        <div className="mx-auto max-w-3xl text-center">
                            {/* Títulos (3) */}
                            <h1 className="text-4xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                            Agenda automática con&nbsp;
                            <span className="bg-clip-text text-transparent bg-gradient-to-r from-fuchsia-400 to-indigo-400">
                                WhatsApp
                            </span>
                            </h1>

                            <h2 className="mt-3 text-xl text-neutral-300">
                            Aumenta citas. Reduce ausencias, confirma en un tap y gana tiempo para tu negocio.
                            </h2>

                            <h3 className="mt-1 text-base text-neutral-400">
                            Desde una sola bandeja: reservas, recordatorios y confirmaciones en tiempo real.
                            </h3>

                            {/* Descripción corta */}
                            <p className="mt-6 text-sm text-neutral-300">
                            AgendAI conecta tu agenda con WhatsApp para enviar recordatorios automáticos, recibir
                            confirmaciones y mantener tu calendario siempre al día. Sin apps extra, sin fricción.
                            </p>

                            {/* Features (3) */}
                            <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                                    <div className="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-fuchsia-500/20">
                                        {/* Icono */}
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-fuchsia-300" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2z"/>
                                        </svg>
                                    </div>
                                    <p className="text-sm font-semibold">Confirmaciones por WhatsApp</p>
                                    <p className="mt-1 text-xs text-neutral-300">Tus clientes responden 1/2 y tu agenda se actualiza sola.</p>
                                </div>

                                <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                                    <div className="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-indigo-300" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9 12l2 2 4-4M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                        </svg>
                                    </div>
                                    <p className="text-sm font-semibold">Menos no-shows</p>
                                    <p className="mt-1 text-xs text-neutral-300">Recordatorios automáticos que sí se leen y responden.</p>
                                </div>

                                <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                                    <div className="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-emerald-300" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 3l8 6v12H4V9l8-6zM9 22V12h6v10"/>
                                        </svg>
                                    </div>
                                    <p className="text-sm font-semibold">Listo en minutos</p>
                                    <p className="mt-1 text-xs text-neutral-300">Sin instalaciones complejas. Entra y empieza a agendar.</p>
                                </div>
                            </div>

                            {/* CTA */}
                            <div className="mt-10 flex items-center justify-center gap-3">
                                <Link
                                    href="/register"
                                    className="rounded-2xl bg-gradient-to-r from-fuchsia-400 to-indigo-400 px-6 py-3 text-sm font-semibold text-neutral-900 hover:opacity-90 transition shadow-lg"
                                >
                                    Comenzar
                                </Link>
                                <Link
                                    href="/login"
                                    className="rounded-2xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition"
                                >
                                    Ya tengo cuenta
                                </Link>
                            </div>

                        </div>
                    </section>

                    {/* Pricing Component */}
                    <Pricing onClose={() => {}} />

                    <Reviews items={demoReviews} cols={3} className="py-2" />

                    <article class="bg-red-500 p-4 min-h-[100px]">
                    Contenido de prueba
                    </article>

                </main>

                {/* Footer mini */}
                <footer className="py-4 text-center text-xs text-neutral-500">
                    © {new Date().getFullYear()} AgendAI — Hecho con Laravel + React + Tailwind
                </footer>
            </div>
        </div>
        </>
    );
}
