import { useMemo } from 'react';

const Star = ({ filled }) => (
  <svg viewBox="0 0 20 20" className="h-4 w-4" aria-hidden="true" fill="currentColor">
    {filled ? (
      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.17 3.6a1 1 0 00.95.69h3.784c.967 0 1.371 1.24.588 1.81l-3.061 2.224a1 1 0 00-.364 1.118l1.17 3.6c.3.921-.755 1.688-1.54 1.118l-3.06-2.223a1 1 0 00-1.177 0l-3.06 2.223c-.784.57-1.84-.197-1.54-1.118l1.17-3.6a1 1 0 00-.364-1.118L2.557 9.027c-.783-.57-.379-1.81.588-1.81h3.784a1 1 0 00.95-.69l1.17-3.6z" />
    ) : (
      <path d="M10 2.69l1.1 3.382a2 2 0 001.9 1.381h3.557l-2.877 2.09a2 2 0 00-.728 2.236l1.1 3.382-2.877-2.09a2 2 0 00-2.356 0l-2.877 2.09 1.1-3.382a2 2 0 00-.728-2.236L3.443 7.453H7a2 2 0 001.9-1.381L10 2.69zm0-1.69a1 1 0 01.949.684l1.17 3.6a1 1 0 00.95.69h3.784c.967 0 1.371 1.24.588 1.81l-3.061 2.224a1 1 0 00-.364 1.118l1.17 3.6c.3.921-.755 1.688-1.54 1.118l-3.06-2.223a1 1 0 00-1.177 0l-3.06 2.223c-.784.57-1.84-.197-1.54-1.118l1.17-3.6a1 1 0 00-.364-1.118L2.557 7.885c-.783-.57-.379-1.81.588-1.81h3.784a1 1 0 00.95-.69l1.17-3.6A1 1 0 0110 1z" />
    )}
  </svg>
);

function Initials({ name }) {
  const initials = useMemo(
    () => (name || '')
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map(p => p[0]?.toUpperCase())
      .join(''),
    [name]
  );
  return (
    <div className="h-8 w-8 rounded-full bg-white/10 text-white/80 text-[10px] font-semibold flex items-center justify-center">
      {initials || '•'}
    </div>
  );
}

/**
 * ReviewCard
 * Props:
 *  - rating: number 0..5
 *  - quote: string
 *  - name: string
 *  - subtitle?: string   (ej. "Bella Studio (Quito)" / "Odontología (Cuenca)")
 *  - avatar?: string     (path relativo a /public, ej: "/images/reviews/ana.jpg")
 *  - className?: string
 */
export default function ReviewCard({ rating = 5, quote, name, subtitle, avatar, className = '' }) {
  const stars = Array.from({ length: 5 }, (_, i) => i < Number(rating));

    return (
        <article
            className={
                `rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur
                shadow-sm hover:shadow transition-shadow ${className}`
            }
        >
            {/* Stars */}
            <div className="mb-3 flex items-center gap-1 text-amber-300">
                {stars.map((filled, i) => <Star key={i} filled={filled} />)}
                <span className="sr-only">{rating} de 5 estrellas</span>
            </div>

            {/* Quote */}
            <p className="text-sm text-neutral-100 leading-relaxed">
                <span className="text-neutral-300">“</span>{quote}<span className="text-neutral-300">”</span>
            </p>

            {/* Author */}
            <div className="mt-4 flex items-center gap-3">
                {avatar ? (
                <img
                    src={avatar}
                    alt={name}
                    className="h-8 w-8 rounded-full object-cover"
                    onError={(e) => { e.currentTarget.style.display = 'none'; }}
                />
                ) : null}
                {!avatar && <Initials name={name} />}
                <div className="text-sm">
                    <div className="font-semibold">{name}</div>
                    {subtitle && <div className="text-neutral-400">{subtitle}</div>}
                </div>
            </div>
        </article>
    );
}
