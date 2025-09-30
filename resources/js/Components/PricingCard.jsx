import { Link } from '@inertiajs/react'


const Check = ({ className = '' }) => (
  <svg viewBox="0 0 24 24" className={`h-4 w-4 ${className}`} fill="currentColor" aria-hidden="true">
    <path d="M9 12l2 2 4-4M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10z" />
  </svg>
);


/**
 * Props:
 * - title: string
 * - price: number|string
 * - period: string  (ej: "/mes")
 * - blurb: string
 * - features: Array<{ label: string, enabled?: boolean }>
 * - cta: { label: string, href?: string, onClick?: Function, variant?: "solid"|"ghost" }
 * - variant: "default" | "featured"
 * - accent: "fuchsia" | "indigo" | "emerald"
 * - badge?: string (texto como "Recomendado")
 */
export default function PricingCard({
  title,
  price,
  period = '/mes',
  blurb,
  features = [],
  cta = { label: 'Empezar', href: '/register', variant: 'solid' },
  variant = 'default',
  accent = 'indigo',
  badge,
}) {
  // Paletas predefinidas (clases estáticas para Tailwind 3.x)
const accents = {
    fuchsia: {
        icon: 'text-fuchsia-300',
        iconBg: 'bg-fuchsia-500/20',
        ring: 'from-fuchsia-500/40 to-indigo-500/40',
        cta: 'from-fuchsia-400 to-indigo-400',
    },
    indigo: {
        icon: 'text-indigo-300',
        iconBg: 'bg-indigo-500/20',
        ring: 'from-indigo-500/40 to-fuchsia-500/40',
        cta: 'from-indigo-400 to-fuchsia-400',
    },
    emerald: {
        icon: 'text-emerald-300',
        iconBg: 'bg-emerald-500/20',
        ring: 'from-emerald-500/40 to-teal-500/40',
        cta: 'from-emerald-400 to-teal-400',
    },
  };
  const a = accents[accent] ?? accents.indigo;

  const CardInner = (
    <div className="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur flex flex-col h-full">
      <div>
            <h3 className="text-lg font-semibold">{title}</h3>
            <div className="mt-1 text-3xl font-bold">
                {price}<span className="text-sm font-normal">{period}</span>
            </div>
            {blurb && <p className="mt-1 text-xs text-neutral-400">{blurb}</p>}
      </div>

      <ul className="mt-4 space-y-2 text-sm text-neutral-200">
            {features.map((f, i) => (
            <li key={i} className={`flex items-center gap-2 ${f.enabled === false ? 'opacity-60' : ''}`}>
                <Check className={f.enabled === false ? 'text-neutral-500' : a.icon} />
                {f.label}
            </li>
            ))}
      </ul>

      {/* CTA */}
      {cta?.href ? (
        <Link
            href={cta.href}
            onClick={cta.onClick}
            className={
                cta.variant === 'featured'
                ? 'mt-4 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10 transition'
                : `mt-4 rounded-xl bg-gradient-to-r ${a.cta} px-4 py-2 text-sm font-semibold text-neutral-900 hover:opacity-90 transition shadow-lg`
            }
        >
            {cta.label}
        </Link>
      ) : (
        <button
            onClick={cta?.onClick}
            className={
                cta.variant === 'ghost'
                ? 'mt-auto rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10 transition'
                : `mt-auto rounded-xl bg-gradient-to-r ${a.cta} px-4 py-2 text-sm font-semibold text-neutral-900 hover:opacity-90 transition shadow-lg`
            }
        >
            {cta.label}
        </button>
      )}
    </div>
  );

  // Envoltura para "featured" con borde degradado + badge
  if (variant === 'featured') {
    return (
        <div className={`relative rounded-2xl p-[1px] bg-gradient-to-r ${a.ring} h-full z-50`}>
            {badge && (
                <span className="absolute -top-3 left-5 text-[10px] bg-gradient-to-r from-fuchsia-500 to-indigo-500 text-white px-2 py-1 rounded-md shadow z-50">
                    {badge}
                </span>
            )}
            <div className="rounded-2xl bg-neutral-950">{CardInner}</div>
        </div>
    );
  }

  return CardInner;
}
