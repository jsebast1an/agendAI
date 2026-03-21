import { Head, Link } from '@inertiajs/react';
import Pricing from '@/Components/Pricing';
import Reviews, { demoReviews } from '@/Components/Reviews';
import HowItWorks from '@/Components/HowItWorks';

const WA_DEMO_URL = 'https://wa.me/593979321219?text=Hola,%20quiero%20una%20demo%20de%20AgendAI';

export default function Landing() {
    const scrollToSection = (e, id) => {
        e.preventDefault();
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
    };

    return (
        <>
        <Head title="AgendAI — Agenda automática con WhatsApp" />
        <div className="w-full min-h-screen bg-neutral-950 text-neutral-100">

            <div className="mx-auto max-w-6xl flex flex-col px-4 sm:px-6">

                {/* Navbar */}
                <header className="flex items-center justify-between py-4 sm:py-5">
                    <div className="flex items-center gap-2">
                        <span className="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-neutral-900 font-black text-sm">A</span>
                        <span className="tracking-tight font-semibold">Agend<span className="text-fuchsia-400">AI</span></span>
                    </div>
                    <nav>
                        <a
                            href={WA_DEMO_URL}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="rounded-xl bg-white px-3 py-1.5 sm:px-4 sm:py-2 text-neutral-900 font-medium hover:bg-neutral-200 transition text-sm"
                        >
                            Pedir una demo
                        </a>
                    </nav>
                </header>

                {/* Hero */}
                <main className="flex flex-col items-center py-10 sm:py-16">
                    <section className="w-full max-w-3xl text-center">

                        <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                            Tu recepcionista virtual que{' '}
                            <span className="bg-clip-text text-transparent bg-linear-to-r from-fuchsia-400 to-indigo-400">
                                nunca duerme
                            </span>
                        </h1>

                        <p className="mt-5 text-base sm:text-lg text-neutral-300 max-w-xl mx-auto leading-relaxed">
                            Deja de perder pacientes por no contestar a tiempo. AgendAI agenda, confirma y recuerda citas por WhatsApp — las 24 horas, sin que toques el celular.
                        </p>

                        {/* CTAs */}
                        <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                            <a
                                href={WA_DEMO_URL}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="w-full sm:w-auto rounded-2xl bg-linear-to-r from-fuchsia-400 to-indigo-400 px-6 py-3 text-sm font-semibold text-neutral-900 hover:opacity-90 transition shadow-lg text-center"
                            >
                                Pedir una demo
                            </a>
                            <a
                                href="#como-funciona"
                                onClick={(e) => scrollToSection(e, 'como-funciona')}
                                className="w-full sm:w-auto rounded-2xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition text-center"
                            >
                                Ver cómo funciona
                            </a>
                        </div>

                        {/* SEO line */}
                        <p className="mt-4 text-[12px] text-neutral-500 text-center">
                            Sistema de agendamiento automático por WhatsApp para clínicas y consultorios en Ecuador.
                        </p>

                        {/* Feature cards */}
                        <div className="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm text-left">
                                <div className="mb-3 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-fuchsia-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-fuchsia-300" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2z"/>
                                    </svg>
                                </div>
                                <p className="text-sm font-semibold leading-snug">"¿Me confirmas la cita?" — tu bot lo pregunta solo</p>
                            </div>

                            <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm text-left">
                                <div className="mb-3 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-indigo-300" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 12l2 2 4-4M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                    </svg>
                                </div>
                                <p className="text-sm font-semibold leading-snug">Hasta 80% menos pacientes que no aparecen</p>
                            </div>

                            <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm text-left">
                                <div className="mb-3 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-emerald-300" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                    </svg>
                                </div>
                                <p className="text-sm font-semibold leading-snug">Funcionando hoy, no en semanas</p>
                            </div>
                        </div>
                    </section>

                    {/* How it works */}
                    <div className="w-full">
                        <HowItWorks />
                    </div>

                    {/* Pricing */}
                    <div className="w-full mt-4 sm:mt-8">
                        <Pricing />
                    </div>

                    {/* Reviews */}
                    <div className="w-full mt-12 sm:mt-16">
                        <Reviews items={demoReviews} cols={3} className="py-2" />
                    </div>
                </main>

                {/* Footer */}
                <footer className="py-6 text-center text-xs text-neutral-500 border-t border-white/5">
                    © {new Date().getFullYear()} AgendAI — Hecho con Laravel + React + Tailwind
                </footer>
            </div>
        </div>
        </>
    );
}
