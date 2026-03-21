const steps = [
    {
        n: 1,
        title: 'El paciente escribe con sus propias palabras',
        desc: 'Sin menús ni opciones. AgendAI entiende mensajes naturales, con errores ortográficos o abreviados.',
        badge: null,
    },
    {
        n: 2,
        title: 'Elige servicio y profesional',
        desc: 'El bot extrae lo que necesita de la conversación. Si no menciona profesional, asigna automáticamente.',
        badge: 'opcional',
    },
    {
        n: 3,
        title: 'El bot muestra horarios disponibles',
        desc: 'Solo los horarios reales según el profesional y servicio. El paciente elige el que más le conviene.',
        badge: null,
    },
    {
        n: 4,
        title: 'El bot pide nombre y cédula',
        desc: 'Solo los datos necesarios para registrar al paciente en el sistema.',
        badge: null,
    },
    {
        n: 5,
        title: 'Cita confirmada — tú te enteras al instante',
        desc: 'La cita aparece en tu dashboard y recibes notificación por WhatsApp. Sin hacer nada.',
        badge: null,
    },
];

const messages = [
    { role: 'user',  text: 'buenas necesito q me vea el dentista esta semana si puede ser' },
    { role: 'bot',   text: '¡Hola! Claro, con gusto 😊 ¿Sabes qué tipo de atención necesitas? Por ejemplo limpieza, revisión, ortodoncia...' },
    { role: 'user',  text: 'creo q es limpieza, hace rato no voy jaja' },
    { role: 'bot',   text: 'Jaja no hay problema, para eso estamos. ¿Tienes preferencia de doctor o te asignamos el primero disponible?' },
    { role: 'user',  text: 'cualquiera esta bien' },
    { role: 'bot',   text: 'Perfecto. Tenemos estos espacios disponibles esta semana:\n\nMartes 18 — 10:00am\nMiércoles 19 — 3:00pm\nJueves 20 — 9:00am\n\n¿Cuál te viene bien?' },
    { role: 'user',  text: 'el miercoles mejor' },
    { role: 'bot',   text: 'Listo, casi queda 🙌 Solo necesito tu nombre completo y número de cédula para registrarte.' },
    { role: 'user',  text: 'María López 1712345678' },
    { role: 'confirmed' },
];

function Bubble({ msg }) {
    if (msg.role === 'confirmed') {
        return (
            <div className="flex justify-start mb-2">
                <div className="max-w-[82%] rounded-2xl rounded-bl-sm bg-emerald-900/60 border border-emerald-500/30 px-4 py-3 text-xs text-emerald-200 leading-relaxed">
                    <p className="font-semibold text-emerald-400 mb-1.5">✓ Cita confirmada</p>
                    <p><span className="text-emerald-500">Servicio:</span> Limpieza dental</p>
                    <p><span className="text-emerald-500">Profesional:</span> Dra. Torres</p>
                    <p><span className="text-emerald-500">Fecha:</span> Miércoles 19, 3:00pm</p>
                    <p className="mt-1.5 text-emerald-300">Te recordamos 24h antes por aquí 👋</p>
                </div>
            </div>
        );
    }

    const isUser = msg.role === 'user';
    return (
        <div className={`flex mb-2 ${isUser ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[82%] rounded-2xl px-3.5 py-2 text-xs leading-relaxed whitespace-pre-line ${
                isUser
                    ? 'bg-fuchsia-600/30 border border-fuchsia-500/20 text-neutral-100 rounded-br-sm'
                    : 'bg-white/8 border border-white/10 text-neutral-200 rounded-bl-sm'
            }`}>
                {msg.text}
            </div>
        </div>
    );
}

export default function HowItWorks() {
    return (
        <section id="como-funciona" className="w-full py-16 sm:py-20">
            {/* Header */}
            <div className="text-center mb-12">
                <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Así de simple{' '}
                    <span className="bg-clip-text text-transparent bg-linear-to-r from-fuchsia-400 to-indigo-400">
                        funciona
                    </span>
                </h2>
                <p className="mt-2 text-sm text-neutral-400">
                    Sin apps extra. Sin capacitación. Solo WhatsApp.
                </p>
            </div>

            {/* Two columns */}
            <div className="flex flex-col lg:flex-row gap-10 lg:gap-16 items-start max-w-5xl mx-auto">

                {/* Left — steps */}
                <div className="flex-1 w-full">
                    {steps.map((step, i) => (
                        <div key={step.n} className="flex gap-4">
                            {/* Line + circle */}
                            <div className="flex flex-col items-center">
                                <div className="w-8 h-8 rounded-full bg-linear-to-br from-fuchsia-500 to-indigo-500 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                    {step.n}
                                </div>
                                {i < steps.length - 1 && (
                                    <div className="w-px flex-1 bg-linear-to-b from-fuchsia-500/40 to-indigo-500/10 my-1" />
                                )}
                            </div>

                            {/* Content */}
                            <div className={`pb-8 ${i === steps.length - 1 ? 'pb-0' : ''}`}>
                                <div className="flex items-center gap-2 mb-1">
                                    <p className="text-sm font-semibold text-neutral-100">{step.title}</p>
                                    {step.badge && (
                                        <span className="text-[10px] px-1.5 py-0.5 rounded-md bg-white/8 border border-white/10 text-neutral-400 font-medium">
                                            {step.badge}
                                        </span>
                                    )}
                                </div>
                                <p className="text-xs text-neutral-400 leading-relaxed">{step.desc}</p>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Right — chat mockup */}
                <div className="flex-1 w-full max-w-sm mx-auto lg:mx-0">
                    <div className="rounded-2xl border border-white/10 bg-neutral-900 overflow-hidden shadow-2xl">
                        {/* Chat header */}
                        <div className="flex items-center gap-3 px-4 py-3 bg-neutral-800 border-b border-white/8">
                            <div className="w-8 h-8 rounded-full bg-linear-to-br from-fuchsia-500 to-indigo-500 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                A
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-neutral-100">AgendAI — Clínica Dental</p>
                                <p className="text-[10px] text-emerald-400">en línea</p>
                            </div>
                        </div>

                        {/* Messages */}
                        <div className="px-3 py-4 space-y-0.5 max-h-[420px] overflow-y-auto">
                            {messages.map((msg, i) => (
                                <Bubble key={i} msg={msg} />
                            ))}
                        </div>

                        {/* Footer */}
                        <div className="px-4 py-2 border-t border-white/8 text-center">
                            <p className="text-[10px] text-neutral-500">menos de 2 minutos</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
