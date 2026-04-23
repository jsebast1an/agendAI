import { Head, Link } from '@inertiajs/react';
import Pricing from '@/Components/Pricing';
import Reviews, { demoReviews } from '@/Components/Reviews';
import HowItWorks from '@/Components/HowItWorks';
import ScrollReveal from '@/Components/ScrollReveal';
import HeroMockup from '@/Components/HeroMockup';

const WA_DEMO_URL = 'https://wa.me/593979321219?text=Hola,%20quiero%20una%20demo%20de%20AgendAI';

export default function Landing() {
    const scrollToSection = (e, id) => {
        e.preventDefault();
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
    };

    return (
        <>
        <Head>
            <title>AgendAI | Recepcionista Virtual por WhatsApp para Clínicas en Ecuador</title>
            <meta name="description" content="Agenda, confirma y recuerda citas por WhatsApp, 24/7, sin que toques el celular. Activo desde el primer día para consultorios médicos en Ecuador." />
            <meta property="og:title" content="AgendAI | Recepcionista Virtual por WhatsApp para Clínicas en Ecuador" />
            <meta property="og:description" content="Agenda, confirma y recuerda citas por WhatsApp, 24/7, sin que toques el celular. Activo desde el primer día para consultorios médicos en Ecuador." />
            <meta property="og:type" content="website" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="AgendAI | Recepcionista Virtual por WhatsApp para Clínicas en Ecuador" />
            <meta name="twitter:description" content="Agenda, confirma y recuerda citas por WhatsApp, 24/7, sin que toques el celular. Activo desde el primer día para consultorios médicos en Ecuador." />
        </Head>
        <div className="w-full min-h-screen bg-neutral-950 text-neutral-100">

            {/* Sticky Navbar — full width */}
            <header className="sticky top-0 z-50 w-full backdrop-blur-sm bg-neutral-950/80 border-b border-white/5">
                <div className="mx-auto max-w-6xl flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5">
                    <div className="flex items-center gap-2">
                        <span className="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-neutral-900 font-black text-sm">A</span>
                        <span className="tracking-tight font-semibold">Agend<span className="text-fuchsia-400">AI</span></span>
                    </div>
                    <nav className="flex items-center gap-4">
                        <a
                            href="#como-funciona"
                            onClick={(e) => scrollToSection(e, 'como-funciona')}
                            className="hidden sm:block text-sm text-neutral-400 hover:text-neutral-100 transition"
                        >
                            Cómo funciona
                        </a>
                        <a
                            href={WA_DEMO_URL}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="rounded-xl bg-white px-3 py-1.5 sm:px-4 sm:py-2 text-neutral-900 font-medium hover:bg-neutral-200 transition text-sm"
                        >
                            Pedir una demo
                        </a>
                    </nav>
                </div>
            </header>

            {/* Hero — full-bleed with ambient blob */}
            <section className="relative w-full overflow-hidden">
                {/* Ambient gradient blob */}
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{
                        background: 'radial-gradient(ellipse 80% 60% at 50% -10%, rgba(232,121,249,0.15) 0%, transparent 70%)',
                    }}
                />

                <div className="relative mx-auto max-w-6xl px-4 sm:px-6 pt-16 pb-20 sm:pt-24 sm:pb-28 lg:pt-28 lg:pb-32">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:gap-12">

                        {/* Left — text */}
                        <div className="flex-1 flex flex-col items-center lg:items-start text-center lg:text-left">
                            {/* Eyebrow */}
                            <p
                                className="animate-fade-up text-xs font-semibold tracking-[0.2em] text-fuchsia-400/70 uppercase mb-6"
                                style={{ animationDelay: '0ms' }}
                            >
                                WhatsApp · IA · Ecuador
                            </p>

                            {/* H1 */}
                            <h1
                                className="animate-fade-up text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black tracking-tight leading-none"
                                style={{ animationDelay: '75ms' }}
                            >
                                Tu recepcionista virtual que{' '}
                                <span className="brand-gradient-text">
                                    nunca duerme
                                </span>
                            </h1>

                            {/* Subheadline */}
                            <p
                                className="animate-fade-up mt-6 text-base sm:text-lg text-neutral-400 max-w-md leading-relaxed"
                                style={{ animationDelay: '150ms' }}
                            >
                                Agenda, confirma y recuerda citas por WhatsApp — 24 horas, sin que toques el celular.
                            </p>

                            {/* CTAs */}
                            <div
                                className="animate-fade-up mt-8 flex flex-col sm:flex-row items-center lg:items-start gap-3"
                                style={{ animationDelay: '300ms' }}
                            >
                                <a
                                    href={WA_DEMO_URL}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="animate-glow-pulse w-full sm:w-auto rounded-2xl bg-linear-to-r from-fuchsia-400 to-indigo-400 px-6 py-3 text-sm font-semibold text-neutral-900 hover:opacity-90 transition text-center"
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

                            {/* Stat pills */}
                            <p
                                className="animate-fade-up mt-6 text-xs text-neutral-500"
                                style={{ animationDelay: '500ms' }}
                            >
                                80% menos ausencias · 2 min para agendar · Activo desde el día 1
                            </p>

                            {/* SEO line */}
                            <p
                                className="animate-fade-up mt-2 text-[11px] text-neutral-600"
                                style={{ animationDelay: '600ms' }}
                            >
                                Sistema de agendamiento automático por WhatsApp para clínicas y consultorios en Ecuador.
                            </p>
                        </div>

                        {/* Right — mockup */}
                        <div
                            className="animate-fade-up flex-1 mt-12 lg:mt-0 w-full max-w-lg mx-auto lg:mx-0"
                            style={{ animationDelay: '400ms' }}
                        >
                            <HeroMockup />
                        </div>
                    </div>
                </div>
            </section>

            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                {/* How it works */}
                <HowItWorks />

                {/* Pricing */}
                <div className="w-full mt-4 sm:mt-8">
                    <Pricing />
                </div>

                {/* Reviews */}
                <div className="w-full mt-16 sm:mt-24">
                    <Reviews items={demoReviews} cols={3} className="py-2" />
                </div>

                {/* CTA final */}
                <ScrollReveal animation="up" as="section" className="w-full py-20 sm:py-28 mt-16 sm:mt-24">
                    <div className="flex flex-col items-center text-center max-w-2xl mx-auto">
                        <p className="text-xs font-semibold tracking-[0.2em] text-neutral-500 uppercase mb-4">
                            Empieza hoy
                        </p>
                        <h2 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                            ¿Listo para dejar de{' '}
                            <span className="brand-gradient-text">perder citas</span>?
                        </h2>
                        <p className="mt-4 text-sm sm:text-base text-neutral-400 max-w-md leading-relaxed">
                            Cuéntanos cómo funciona tu clínica y te mostramos AgendAI en acción — sin compromiso.
                        </p>
                        <a
                            href={WA_DEMO_URL}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="animate-glow-pulse mt-8 inline-flex items-center gap-2 rounded-2xl bg-linear-to-r from-fuchsia-400 to-indigo-400 px-7 py-3.5 text-sm font-semibold text-neutral-900 hover:opacity-90 transition"
                        >
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            Pedir una demo
                        </a>
                        <p className="mt-4 text-[11px] text-neutral-600">
                            Sin contratos. Sin tarjeta de crédito. Solo WhatsApp.
                        </p>
                    </div>
                </ScrollReveal>

                {/* Footer */}
                <footer className="py-8 text-center text-xs text-neutral-600 border-t border-white/5">
                    © {new Date().getFullYear()} AgendAI — Hecho con Laravel + React + Tailwind
                </footer>
            </div>
        </div>
        </>
    );
}
