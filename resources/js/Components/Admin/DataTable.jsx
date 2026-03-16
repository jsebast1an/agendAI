export default function DataTable({ columns, data, emptyMessage = 'No hay datos.' }) {
    if (!data || data.length === 0) {
        return (
            <div className="bg-white rounded-xl border border-cream-200 shadow-sm p-8 text-center">
                <p className="text-gray-400 text-sm">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl border border-cream-200 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-cream-200 bg-cream-50">
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    className="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"
                                >
                                    {col.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-cream-100">
                        {data.map((row, i) => (
                            <tr key={row.id || i} className="hover:bg-cream-50 transition-colors">
                                {columns.map((col) => (
                                    <td key={col.key} className="px-5 py-3.5 text-gray-700">
                                        {col.render ? col.render(row) : row[col.key]}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
