import PricingCard from './PricingCard';
import ScrollReveal from './ScrollReveal';

const plans = [
    {
        key: 'receptionist',
        title: 'Recepcionista',
        price: '$29',
        period: '/mes',
        setup: '$99',
        blurb: 'Profesionales independientes',
        features: [
            'Bot de agendamiento 24/7 por WhatsApp',
            'Agenda, cancela y reagenda citas',
            'Recordatorios automáticos',
            'Base de datos de pacientes',
            'Dashboard con métricas',
        ],
        limits: [
            { label: 'Hasta 5 profesionales' },
            { label: '300 conversaciones/mes' },
        ],
        cta: { label: 'Empezar', href: '/register', variant: 'ghost' },
        recommended: false,
    },
    {
        key: 'clinic',
        title: 'Clínica',
        price: '$49',
        period: '/mes',
        setup: '$149',
        blurb: 'Consultorios en crecimiento',
        features: [
            'Todo del plan Recepcionista',
            'Historial completo de conversaciones',
            'Reportes avanzados',
            'Soporte prioritario',
        ],
        limits: [
            { label: 'Hasta 10 profesionales' },
            { label: '1.000 conversaciones/mes' },
        ],
        cta: { label: 'Empezar', href: '/register', variant: 'gradient' },
        recommended: true,
    },
    {
        key: 'medical-center',
        title: 'Centro médico',
        price: null,
        period: null,
        setup: null,
        blurb: 'Clínicas grandes y multi-sede',
        features: [
            'Todo del plan Clínica',
            'Profesionales ilimitados',
            'Multi-sede',
            'Onboarding personalizado',
            'Integraciones a medida',
        ],
        limits: [
            { label: 'Ilimitado' },
        ],
        cta: { label: 'Hablar con ventas', href: '/register', variant: 'ghost' },
        recommended: false,
    },
];

export default function Pricing() {
    return (
        <section className="w-full">
            <div className="max-w-5xl mx-auto px-6 py-4">
                <ScrollReveal animation="up" className="mb-8">
                    <p className="text-xs font-semibold tracking-[0.2em] text-fuchsia-400/70 uppercase mb-3">
                        Precios de lanzamiento
                    </p>
                    <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight">Planes y precios</h2>
                    <p className="text-sm text-neutral-400 mt-1">
                        Puedes cambiar de plan cuando quieras.
                    </p>
                </ScrollReveal>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-stretch">
                    {plans.map((p, i) => (
                        <ScrollReveal key={p.key} animation="up" delay={i * 150}>
                            <PricingCard {...p} />
                        </ScrollReveal>
                    ))}
                </div>

                <p className="text-[11px] text-neutral-500 mt-4">
                    * Impuestos locales no incluidos. Puedes cambiar o cancelar en cualquier momento.
                </p>
            </div>
        </section>
    );
}
