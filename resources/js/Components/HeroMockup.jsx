export default function HeroMockup({ className = '' }) {
    return (
        <div className={`relative ${className}`}>
            {/* Glow behind */}
            <div
                className="pointer-events-none absolute inset-0"
                style={{
                    background: 'radial-gradient(circle at 50% 50%, rgba(127,119,221,0.1) 0%, transparent 70%)',
                }}
            />

            {/* Laptop */}
            <div className="relative w-full max-w-[520px] mx-auto">
                <Laptop />

                {/* Phone overlapping top-right */}
                <div className="absolute -right-4 sm:-right-6 -top-4 sm:-top-6 w-[38%] max-w-[180px] z-10">
                    <Phone />
                </div>
            </div>
        </div>
    );
}

function Laptop() {
    return (
        <div className="relative">
            {/* Screen bezel */}
            <div className="rounded-t-xl border border-[#2a2a2a] bg-[#111] p-1.5 sm:p-2">
                {/* Screen */}
                <div className="rounded-lg bg-[#0a0a0f] overflow-hidden">
                    {/* Dashboard header */}
                    <div className="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-2.5 border-b border-[#1e1e2e]">
                        <span className="text-[8px] sm:text-[10px] text-neutral-400 font-medium">AgendAI — Dashboard</span>
                        <span className="text-[6px] sm:text-[8px] px-1.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 font-medium">En vivo</span>
                    </div>

                    {/* Metric cards */}
                    <div className="grid grid-cols-3 gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 sm:py-3">
                        <MetricCard label="Citas hoy" value="12" color="purple" />
                        <MetricCard label="Confirmadas" value="9" color="green" />
                        <MetricCard label="Ausencias" value="1" color="red" />
                    </div>

                    {/* Table */}
                    <div className="px-3 sm:px-4 pb-3 sm:pb-4">
                        <div className="rounded-md border border-[#1e1e2e] overflow-hidden">
                            {/* Header row */}
                            <div className="grid grid-cols-4 gap-1 px-2 sm:px-3 py-1 sm:py-1.5 text-[5px] sm:text-[7px] text-neutral-500 font-medium bg-[#0d0d18] border-b border-[#1e1e2e]">
                                <span>Paciente</span>
                                <span>Hora</span>
                                <span>Servicio</span>
                                <span>Estado</span>
                            </div>
                            <TableRow name="María López" time="10:00am" service="Limpieza" status="Confirmada" statusColor="green" />
                            <TableRow name="Carlos Vera" time="11:30am" service="Ortodoncia" status="Confirmada" statusColor="green" />
                            <TableRow name="Ana Torres" time="3:00pm" service="Consulta" status="Pendiente" statusColor="yellow" />
                            <TableRow name="Luis Méndez" time="4:30pm" service="Blanqueo" status="Confirmada" statusColor="green" />
                        </div>
                    </div>
                </div>
            </div>

            {/* Laptop base */}
            <div className="h-2 sm:h-3 bg-[#111] border-x border-b border-[#2a2a2a] rounded-b-lg mx-6 sm:mx-10" />
            <div className="h-1 bg-[#1a1a1a] rounded-b-xl mx-2 sm:mx-4 border-x border-b border-[#2a2a2a]" />
        </div>
    );
}

function MetricCard({ label, value, color }) {
    const colorMap = {
        purple: { bg: 'bg-purple-500/10', text: 'text-purple-300', border: 'border-purple-500/20' },
        green:  { bg: 'bg-emerald-500/10', text: 'text-emerald-300', border: 'border-emerald-500/20' },
        red:    { bg: 'bg-red-500/10', text: 'text-red-300', border: 'border-red-500/20' },
    };
    const c = colorMap[color] || colorMap.purple;

    return (
        <div className={`rounded-md ${c.bg} border ${c.border} px-2 py-1.5 sm:py-2`}>
            <p className="text-[5px] sm:text-[7px] text-neutral-500">{label}</p>
            <p className={`text-sm sm:text-lg font-bold ${c.text} leading-none mt-0.5`}>{value}</p>
        </div>
    );
}

function TableRow({ name, time, service, status, statusColor }) {
    const badgeColors = {
        green:  'bg-emerald-500/15 text-emerald-300',
        yellow: 'bg-amber-500/15 text-amber-300',
    };

    return (
        <div className="grid grid-cols-4 gap-1 px-2 sm:px-3 py-1 sm:py-1.5 text-[5px] sm:text-[7px] text-neutral-300 bg-[#13131f] border-b border-[#1e1e2e] last:border-b-0">
            <span className="truncate">{name}</span>
            <span className="text-neutral-400">{time}</span>
            <span className="text-neutral-400">{service}</span>
            <span className={`inline-flex self-center w-fit px-1 sm:px-1.5 py-0.5 rounded-full text-[4px] sm:text-[6px] font-medium ${badgeColors[statusColor]}`}>
                {status}
            </span>
        </div>
    );
}

function Phone() {
    return (
        <div className="relative rounded-[16px] sm:rounded-[20px] border border-[#2a2a2a] bg-[#0d0d0d] overflow-hidden shadow-2xl">
            {/* Notch */}
            <div className="flex justify-center pt-1.5 sm:pt-2 pb-0">
                <div className="w-12 sm:w-16 h-1 sm:h-1.5 bg-[#1a1a1a] rounded-full" />
            </div>

            {/* Chat header */}
            <div className="flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-1.5 sm:py-2 border-b border-[#1e1e2e]">
                <div className="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-linear-to-br from-fuchsia-500 to-indigo-500 flex items-center justify-center text-[5px] sm:text-[7px] font-bold text-white flex-shrink-0">
                    AI
                </div>
                <div>
                    <p className="text-[6px] sm:text-[8px] font-semibold text-neutral-200 leading-none">AgendAI</p>
                    <p className="text-[5px] sm:text-[6px] text-emerald-400 leading-none mt-0.5">en línea</p>
                </div>
            </div>

            {/* Messages */}
            <div className="px-1.5 sm:px-2 py-2 sm:py-3 space-y-1 sm:space-y-1.5">
                <ChatBubble side="right" text="hola necesito cita para limpieza" />
                <ChatBubble side="left" text={'¡Hola! Con gusto \u{1F60A} ¿Tienes preferencia de horario?'} />
                <ChatBubble side="right" text="miercoles por la tarde" />
                <ChatBubble side="left" text={'Tenemos disponible:\nMiér 19 — 3:00pm\nMiér 19 — 5:00pm'} />
                <ChatBubble side="right" text="las 3 perfecto" />
                {/* Confirmation card */}
                <div className="flex justify-start">
                    <div className="max-w-[85%] rounded-lg bg-emerald-900/50 border border-emerald-500/30 px-2 py-1.5 sm:px-2.5 sm:py-2">
                        <p className="text-[5px] sm:text-[7px] font-semibold text-emerald-400 leading-none mb-1">✓ Cita confirmada</p>
                        <p className="text-[5px] sm:text-[6px] text-emerald-200 leading-relaxed">Miér 19, 3:00pm</p>
                    </div>
                </div>
            </div>

            {/* Home bar */}
            <div className="flex justify-center pb-1 sm:pb-1.5 pt-0.5">
                <div className="w-8 sm:w-10 h-0.5 sm:h-1 bg-[#2a2a2a] rounded-full" />
            </div>
        </div>
    );
}

function ChatBubble({ side, text }) {
    const isRight = side === 'right';
    return (
        <div className={`flex ${isRight ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[82%] rounded-lg px-1.5 sm:px-2 py-1 sm:py-1.5 text-[5px] sm:text-[7px] leading-relaxed whitespace-pre-line ${
                isRight
                    ? 'bg-[#2a2a4a] text-neutral-200 rounded-br-sm'
                    : 'bg-[#1e2a1e] text-neutral-200 rounded-bl-sm'
            }`}>
                {text}
            </div>
        </div>
    );
}
