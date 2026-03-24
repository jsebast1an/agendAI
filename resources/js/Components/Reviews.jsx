import ReviewCard from './ReviewCard';
import ScrollReveal from './ScrollReveal';

/**
 * Reviews
 * Props:
 *  - items: Array<{
 *      rating: number, quote: string, name: string,
 *      subtitle?: string, avatar?: string
 *    }>
 *  - title?: string
 *  - cols?: 1|2|3|4 (default 3 en md)
 *  - className?: string
 */
export default function Reviews({
        items = [],
        title = 'Opiniones reales',
        cols = 3,
        className = '',
    }) {
    const gridCols = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 md:grid-cols-2',
        3: 'grid-cols-1 md:grid-cols-3',
        4: 'grid-cols-1 md:grid-cols-4',
    }[cols] || 'grid-cols-1 md:grid-cols-3';

    return (
        <section className={`w-full ${className}`}>
        <div className="mx-auto max-w-6xl">
            <ScrollReveal animation="up" className="mb-8">
                <p className="text-xs font-semibold tracking-[0.2em] text-fuchsia-400/70 uppercase mb-3">
                    Testimonios
                </p>
                <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight">{title}</h2>
            </ScrollReveal>

            <div className={`grid ${gridCols} gap-4`}>
            {items.map((r, i) => (
                <ScrollReveal key={i} animation="scale" delay={i * 120}>
                    <ReviewCard {...r} className="h-full" />
                </ScrollReveal>
            ))}
            </div>
        </div>
        </section>
    );
}

// Opcional: exporta un set de demo listo para usar
export const demoReviews = [
    {
        rating: 5,
        quote: 'Antes perdía 1 de cada 5 citas. Con AgendAI, los recordatorios por WhatsApp nos bajaron las inasistencias y ahora confirmamos todo en 1 toque.',
        name: 'Ana',
        subtitle: 'Bella Studio (Quito)',
        avatar: '/images/reviews/ana.jpg',
    },
    {
        rating: 5,
        quote: 'Se acabaron los cruces de agenda. La asistente ahora se enfoca en pacientes, no en perseguir confirmaciones.',
        name: 'Dr. Pérez',
        subtitle: 'Odontología (Cuenca)',
        avatar: '/images/reviews/perez.jpg',
    },
    {
        rating: 5,
        quote: 'El link de auto-reserva me llena la agenda sin estar pegado al celular.',
        name: 'Andrés',
        subtitle: 'Gym Coach (Guayaquil)',
        avatar: '/images/reviews/andres.jpg',
    },
];
