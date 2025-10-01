import PricingCard from './PricingCard';

export default function Pricing({ onClose }) {
    // Fuente de verdad: solo cambias este array
    const plans = [
        {
            key: 'basic',
            title: 'Básico',
            price: '$15',
            period: '/mes',
            blurb: 'Para profesionales individuales con bajo volumen.',
            features: [
                { label: '1 agenda' },
                { label: 'Recordatorios por WhatsApp' },
                { label: 'Confirmar/Cancelar con 1/2' },
                { label: 'Branding personalizado', enabled: false },
                { label: 'Multi-agenda', enabled: false },
            ],
            cta: { label: 'Empezar', href: '/register', variant: 'solid' },
            variant: 'featured',
            accent: 'fuchsia',
        },
        {
            key: 'standard',
            title: 'Estándar',
            price: '$25',
            period: '/mes',
            blurb: 'Para salones y consultorios con varias citas al día.',
            features: [
                { label: 'Hasta 3 agendas' },
                { label: 'Recordatorios por WhatsApp' },
                { label: 'Confirmar/Cancelar con 1/2' },
                { label: 'Branding básico' },
                { label: 'Soporte prioritario', enabled: false },
            ],
            cta: { label: 'Probar Estándar', href: '/register', variant: 'solid' },
            variant: 'featured',
            accent: 'indigo',
            badge: 'Recomendado',
        },
        {
            key: 'premium',
            title: 'Premium',
            price: '$40',
            period: '/mes',
            blurb: 'Para clínicas y gimnasios con alto volumen.',
            features: [
                { label: 'Multi-agenda (5+)' },
                { label: 'Recordatorios por WhatsApp' },
                { label: 'Confirmar/Cancelar con 1/2' },
                { label: 'Branding avanzado' },
                { label: 'Soporte prioritario' },
            ],
            cta: { label: 'Hablar con ventas', href: '/register', variant: 'ghost' },
            variant: 'featured',
            accent: 'emerald',
        },
    ];

    return (
        <section className="w-full h-full">
            <div className="h-full max-w-5xl mx-auto flex flex-col px-6 py-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Planes y precios</h2>
                        <p className="text-sm text-neutral-400 mt-1">
                        Precios de lanzamiento. Puedes cambiar de plan cuando quieras.
                        </p>
                    </div>
                </div>

                {/* Grid */}
                <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 flex-1 items-stretch">
                    {plans.map((p) => (
                        <PricingCard key={p.key} {...p} />
                    ))}
                </div>

                <p className="text-[11px] text-neutral-500 mt-4">
                * Precios de lanzamiento. Impuestos/locales no incluidos. Puedes cambiar o cancelar en cualquier momento.
                </p>
            </div>
        </section>
    );
}
