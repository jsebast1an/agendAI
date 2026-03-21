import { Link } from '@inertiajs/react';

const Check = () => (
    <svg viewBox="0 0 16 16" className="h-3.5 w-3.5 flex-shrink-0 text-fuchsia-400" fill="currentColor">
        <path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/>
    </svg>
);

export default function PricingCard({ title, price, period, setup, blurb, features, limits, cta, recommended }) {
    const card = (
        <div className="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm p-5 flex flex-col h-full">

            {/* Header */}
            <div>
                <p className="text-xs text-neutral-400 font-medium uppercase tracking-wider mb-1">{blurb}</p>
                <h3 className="text-xl font-bold">{title}</h3>

                {/* Price */}
                <div className="mt-3">
                    {price ? (
                        <>
                            <span className="text-3xl font-extrabold">{price}</span>
                            <span className="text-sm text-neutral-400 ml-1">{period}</span>
                        </>
                    ) : (
                        <span className="text-2xl font-bold text-neutral-300">A consultar</span>
                    )}
                </div>

                {/* Setup */}
                <div className="mt-1.5 text-xs text-neutral-400">
                    {setup ? (
                        <>
                            Setup: <span className="line-through">{setup}</span>{' '}
                            <span className="text-fuchsia-400 font-medium">Incluido</span>
                        </>
                    ) : (
                        <span className="text-neutral-500">Setup incluido en cotización</span>
                    )}
                </div>
            </div>

            {/* Limit chips */}
            {limits?.length > 0 && (
                <div className="flex flex-wrap gap-1.5 mt-4">
                    {limits.map((l, i) => (
                        <span
                            key={i}
                            className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-white/8 border border-white/10 text-neutral-300"
                        >
                            {l.label}
                        </span>
                    ))}
                </div>
            )}

            {/* Divider */}
            <div className="border-t border-white/8 my-4" />

            {/* Features */}
            <ul className="space-y-2.5 flex-1">
                {features.map((f, i) => (
                    <li key={i} className="flex items-start gap-2 text-sm text-neutral-200">
                        <Check />
                        <span>{f}</span>
                    </li>
                ))}
            </ul>

            {/* CTA */}
            <div className="mt-5">
                {cta.variant === 'gradient' ? (
                    <Link
                        href={cta.href}
                        className="block w-full text-center rounded-xl bg-linear-to-r from-fuchsia-400 to-indigo-400 px-4 py-2.5 text-sm font-semibold text-neutral-900 hover:opacity-90 transition shadow-lg"
                    >
                        {cta.label}
                    </Link>
                ) : (
                    <Link
                        href={cta.href}
                        className="block w-full text-center rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition"
                    >
                        {cta.label}
                    </Link>
                )}
            </div>
        </div>
    );

    if (recommended) {
        return (
            <div className="relative rounded-2xl p-px bg-linear-to-b from-fuchsia-500 via-indigo-500 to-fuchsia-500/20 h-full z-10 overflow-visible mt-3 md:mt-0">
                <span className="absolute -top-3 left-1/2 -translate-x-1/2 text-[11px] font-semibold bg-linear-to-r from-fuchsia-500 to-indigo-500 text-white px-3 py-1 rounded-full shadow-sm whitespace-nowrap z-20">
                    Recomendado
                </span>
                <div className="rounded-2xl bg-neutral-950 h-full">{card}</div>
            </div>
        );
    }

    return card;
}
