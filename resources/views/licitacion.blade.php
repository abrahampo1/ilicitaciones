@extends('layouts.app')

@section('contenido')
    @section('meta_title', Str::limit($licitacion->titulo, 60) . ' - I-Licitaciones')
    @section('meta_description', Str::limit(strip_tags($licitacion->descripcion ?? 'Detalles de la licitación ' . $licitacion->titulo), 155))
    
    @push('json-ld')
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "GovernmentService",
      "name": "{{ str_replace('"', '\"', $licitacion->titulo) }}",
      "description": "{{ str_replace('"', '\"', Str::limit(strip_tags($licitacion->descripcion), 150)) }}",
      "provider": {
        "@type": "GovernmentOrganization",
        "name": "{{ str_replace('"', '\"', $licitacion->organismo->nombre ?? 'Organismo Público') }}"
      },
      "datePublished": "{{ $licitacion->created_at }}",
      "dateModified": "{{ $licitacion->updated_at }}"
    }
    </script>
    @endpush

    <div class="flex gap-8">
        <!-- Left Column - Licitación Details -->
        <div class="flex-1 min-w-0">
        <!-- Back navigation -->
        <a href="{{ url()->previous() }}" 
           class="inline-flex items-center gap-2 text-neutral-500 hover:text-neutral-200 transition-colors mb-6 group">
            <span class="group-hover:-translate-x-1 transition-transform">←</span>
            <span class="text-sm">Volver</span>
        </a>

        <!-- Header Section -->
        <div class="relative mb-8">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent rounded-3xl blur-3xl"></div>
            <div class="relative">
                <!-- Estado Badge -->
                <div class="mb-4">
                    <span class="px-3 py-1.5 text-xs rounded-full font-medium
                        @if($licitacion->estado == 'Adjudicada') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                        @elseif($licitacion->estado == 'Evaluación') bg-amber-500/20 text-amber-400 border border-amber-500/30
                        @elseif($licitacion->estado == 'Publicada') bg-sky-500/20 text-sky-400 border border-sky-500/30
                        @else bg-neutral-500/20 text-neutral-400 border border-neutral-500/30 @endif">
                        {{ $licitacion->estado ?? 'Sin estado' }}
                    </span>
                </div>
                
                <!-- Título -->
                <h1 class="text-2xl md:text-3xl font-light leading-tight mb-4 text-neutral-100">
                    {{ $licitacion->titulo }}
                </h1>
                
                <!-- Identificador y Organismo -->
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <span class="font-mono text-neutral-500">{{ $licitacion->identificador }}</span>
                    @if($licitacion->organismo)
                        <span class="text-neutral-700">•</span>
                        <a href="{{ route('organismo.show', $licitacion->organismo->id) }}" 
                           class="text-cyan-400 hover:text-cyan-300 transition-colors">
                            {{ Str::limit($licitacion->organismo->nombre, 50) }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Importes Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-amber-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Presupuesto Base</p>
                    <p class="text-2xl font-mono text-amber-400">
                        {{ $licitacion->importe_estimado ? number_format($licitacion->importe_estimado, 2, ',', '.') . '€' : '--' }}
                    </p>
                </div>
            </div>
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-br from-teal-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-teal-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Sin Impuestos</p>
                    <p class="text-2xl font-mono text-teal-400">
                        {{ $licitacion->importe_total ? number_format($licitacion->importe_total, 2, ',', '.') . '€' : '--' }}
                    </p>
                </div>
            </div>
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 to-transparent rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6 bg-neutral-800/50 border border-neutral-700/50 rounded-2xl group-hover:border-emerald-500/30 transition-colors">
                    <p class="text-neutral-500 text-xs uppercase tracking-wider mb-2">Importe Total</p>
                    <p class="text-2xl font-mono text-emerald-400">
                        {{ $licitacion->importe_final ? number_format($licitacion->importe_final, 2, ',', '.') . '€' : '--' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Fechas -->
        @if($licitacion->fecha_contratacion || $licitacion->fecha_actualizacion)
            <div class="flex flex-wrap gap-6 mb-8 text-sm">
                @if($licitacion->fecha_contratacion)
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                        <span class="text-neutral-500">Contratación:</span>
                        <span class="text-neutral-300">{{ Carbon\Carbon::parse($licitacion->fecha_contratacion)->format('d/m/Y') }}</span>
                    </div>
                @endif
                @if($licitacion->fecha_actualizacion)
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-neutral-500"></span>
                        <span class="text-neutral-500">Actualización:</span>
                        <span class="text-neutral-300">{{ Carbon\Carbon::parse($licitacion->fecha_actualizacion)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Descripción -->
        @if ($licitacion->descripcion)
            <div class="mb-8">
                <h2 class="text-lg font-light mb-4 text-neutral-300">
                    <span class="text-neutral-600">◈</span> Descripción
                </h2>
                <div class="p-6 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl">
                    <p class="text-neutral-300 leading-relaxed whitespace-pre-line">{{ $licitacion->descripcion }}</p>
                </div>
            </div>
        @endif

        <!-- Adjudicaciones -->
        <div class="mb-8">
            <h2 class="text-lg font-light mb-4 text-neutral-300">
                <span class="text-emerald-400">⬥</span> Adjudicaciones
            </h2>
            
            @if($licitacion->empresas->count() > 0)
                <div class="space-y-3">
                    @foreach ($licitacion->empresas as $empresa)
                        @if ($empresa->nombre)
                            <a href="{{ route('empresa.show', $empresa->id) }}" 
                               class="group block p-5 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl hover:bg-neutral-800/60 hover:border-emerald-500/30 transition-all duration-300">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex-1">
                                        <p class="font-medium text-neutral-200 group-hover:text-white transition-colors">
                                            {{ $empresa->nombre }}
                                        </p>
                                        @if($empresa->identificador)
                                            <p class="text-xs font-mono text-neutral-500 mt-1">{{ $empresa->identificador }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-mono text-xl text-emerald-400">
                                            {{ number_format($empresa->pivot->importe, 2, ',', '.') }}€
                                        </p>
                                        @if($empresa->pivot->importe_final && $empresa->pivot->importe_final != $empresa->pivot->importe)
                                            <p class="text-xs text-neutral-500 mt-1">
                                                Final: {{ number_format($empresa->pivot->importe_final, 2, ',', '.') }}€
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($empresa->pivot->descripcion)
                                    <div class="p-3 bg-neutral-900/50 rounded-xl mt-3">
                                        <p class="text-sm text-neutral-400 leading-relaxed">{{ $empresa->pivot->descripcion }}</p>
                                    </div>
                                @endif
                                
                                <div class="flex flex-wrap gap-3 mt-3 text-xs">
                                    @if($empresa->pivot->tipo_procedimiento)
                                        <span class="px-2 py-1 bg-neutral-700/50 rounded-lg text-neutral-400">
                                            {{ $empresa->pivot->tipo_procedimiento }}
                                        </span>
                                    @endif
                                    @if($empresa->pivot->fecha_adjudicacion)
                                        <span class="px-2 py-1 bg-neutral-700/50 rounded-lg text-neutral-400">
                                            📅 {{ Carbon\Carbon::parse($empresa->pivot->fecha_adjudicacion)->format('d/m/Y') }}
                                        </span>
                                    @endif
                                    @if($empresa->pivot->urgencia)
                                        <span class="px-2 py-1 bg-amber-500/20 rounded-lg text-amber-400">
                                            ⚡ {{ $empresa->pivot->urgencia }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="p-8 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl text-center">
                    <p class="text-neutral-500">No hay adjudicaciones registradas para esta licitación</p>
                </div>
            @endif
        </div>

        <!-- Categoría si existe -->
        @if($licitacion->categoria)
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-800/50 rounded-full text-sm">
                <span class="text-neutral-500">Categoría:</span>
                <span class="text-neutral-300">{{ $licitacion->categoria->nombre }}</span>
            </div>
        @endif
        </div>

        <!-- Right Column - Raw Data (datos_raiz) -->
        <div class="flex-1 min-w-0">
            <div class="sticky top-8">
                @if($licitacion->datos_raiz)
                    <!-- Toggle Header -->
                    <button onclick="toggleJsonPanel()" 
                            class="w-full mb-4 p-4 bg-neutral-800/50 hover:bg-neutral-800/80 border border-neutral-700/50 hover:border-purple-500/30 rounded-2xl transition-all duration-300 group">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-light text-neutral-300 flex items-center gap-2">
                                <span class="text-purple-400">◇</span> 
                                Datos Raw (JSON)
                                <span class="text-xs text-neutral-500 font-normal">(clic para ver)</span>
                            </h2>
                            <span id="jsonToggleIcon" class="text-purple-400 transition-transform duration-300">▶</span>
                        </div>
                    </button>

                    <!-- Collapsible JSON Panel -->
                    <div id="jsonPanel" class="hidden">
                        <div class="mb-3 flex items-center justify-end gap-2">
                            <button onclick="expandAllJson()" 
                                    class="px-3 py-1.5 text-xs bg-neutral-700/50 hover:bg-neutral-700 text-neutral-400 hover:text-neutral-200 rounded-lg transition-colors">
                                Expandir todo
                            </button>
                            <button onclick="collapseAllJson()" 
                                    class="px-3 py-1.5 text-xs bg-neutral-700/50 hover:bg-neutral-700 text-neutral-400 hover:text-neutral-200 rounded-lg transition-colors">
                                Colapsar todo
                            </button>
                            <button onclick="copyJsonToClipboard()" 
                                    class="px-3 py-1.5 text-xs bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 hover:text-purple-300 rounded-lg transition-colors flex items-center gap-1">
                                <span id="copyIcon">📋</span>
                                <span id="copyText">Copiar</span>
                            </button>
                        </div>

                        <div class="bg-neutral-900/80 border border-neutral-700/50 rounded-2xl overflow-hidden">
                            <!-- Search bar -->
                            <div class="p-3 border-b border-neutral-700/50">
                                <input type="text" 
                                       id="jsonSearch" 
                                       placeholder="Buscar en JSON..." 
                                       onkeyup="searchJson(this.value)"
                                       class="w-full px-3 py-2 bg-neutral-800/80 border border-neutral-600/50 rounded-lg text-sm text-neutral-200 placeholder-neutral-500 focus:outline-none focus:border-purple-500/50 transition-colors">
                            </div>
                            
                            <!-- JSON viewer -->
                            <div id="jsonViewer" class="p-4 max-h-[calc(100vh-280px)] overflow-auto custom-scrollbar">
                                <pre class="text-sm font-mono leading-relaxed"></pre>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-8 bg-neutral-800/30 border border-neutral-700/30 rounded-2xl text-center">
                        <p class="text-neutral-500">No hay datos raw disponibles para esta licitación</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(38, 38, 38, 0.5);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(82, 82, 82, 0.8);
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(115, 115, 115, 0.8);
        }
        
        .json-key { color: #c084fc; }
        .json-string { color: #86efac; }
        .json-number { color: #fbbf24; }
        .json-boolean { color: #60a5fa; }
        .json-null { color: #9ca3af; }
        .json-bracket { color: #a3a3a3; }
        
        .json-collapsible {
            cursor: pointer;
            user-select: none;
        }
        .json-collapsible:hover {
            background: rgba(139, 92, 246, 0.1);
            border-radius: 4px;
        }
        .json-collapsed .json-content {
            display: none;
        }
        .json-collapsed .json-ellipsis {
            display: inline;
        }
        .json-ellipsis {
            display: none;
            color: #9ca3af;
        }
        .json-toggle {
            display: inline-block;
            width: 16px;
            text-align: center;
            color: #a78bfa;
        }
        
        .json-highlight {
            background: rgba(250, 204, 21, 0.3);
            border-radius: 2px;
            padding: 0 2px;
        }
    </style>

    <script>
        const rawJsonData = @json($licitacion->datos_raiz);
        let parsedJson = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            try {
                parsedJson = typeof rawJsonData === 'string' ? JSON.parse(rawJsonData) : rawJsonData;
                renderJson(parsedJson);
            } catch (e) {
                document.querySelector('#jsonViewer pre').textContent = 'Error parsing JSON: ' + e.message;
            }
        });
        
        function renderJson(data, searchTerm = '') {
            const container = document.querySelector('#jsonViewer pre');
            container.innerHTML = formatValue(data, 0, searchTerm);
        }
        
        function formatValue(value, indent, searchTerm = '') {
            const spaces = '  '.repeat(indent);
            
            if (value === null) {
                return `<span class="json-null">null</span>`;
            }
            
            if (typeof value === 'boolean') {
                return `<span class="json-boolean">${value}</span>`;
            }
            
            if (typeof value === 'number') {
                return `<span class="json-number">${value}</span>`;
            }
            
            if (typeof value === 'string') {
                let displayValue = escapeHtml(value);
                if (searchTerm && displayValue.toLowerCase().includes(searchTerm.toLowerCase())) {
                    displayValue = highlightText(displayValue, searchTerm);
                }
                return `<span class="json-string">"${displayValue}"</span>`;
            }
            
            if (Array.isArray(value)) {
                if (value.length === 0) {
                    return `<span class="json-bracket">[]</span>`;
                }
                
                const id = 'json-' + Math.random().toString(36).substr(2, 9);
                let html = `<span class="json-collapsible" onclick="toggleJson('${id}')"><span class="json-toggle">▼</span><span class="json-bracket">[</span></span>`;
                html += `<span class="json-ellipsis">...</span>`;
                html += `<span id="${id}" class="json-content">`;
                
                value.forEach((item, index) => {
                    html += `\n${spaces}  ${formatValue(item, indent + 1, searchTerm)}`;
                    if (index < value.length - 1) html += ',';
                });
                
                html += `\n${spaces}</span><span class="json-bracket">]</span>`;
                return html;
            }
            
            if (typeof value === 'object') {
                const keys = Object.keys(value);
                if (keys.length === 0) {
                    return `<span class="json-bracket">{}</span>`;
                }
                
                const id = 'json-' + Math.random().toString(36).substr(2, 9);
                let html = `<span class="json-collapsible" onclick="toggleJson('${id}')"><span class="json-toggle">▼</span><span class="json-bracket">{</span></span>`;
                html += `<span class="json-ellipsis">...</span>`;
                html += `<span id="${id}" class="json-content">`;
                
                keys.forEach((key, index) => {
                    let displayKey = escapeHtml(key);
                    if (searchTerm && displayKey.toLowerCase().includes(searchTerm.toLowerCase())) {
                        displayKey = highlightText(displayKey, searchTerm);
                    }
                    html += `\n${spaces}  <span class="json-key">"${displayKey}"</span>: ${formatValue(value[key], indent + 1, searchTerm)}`;
                    if (index < keys.length - 1) html += ',';
                });
                
                html += `\n${spaces}</span><span class="json-bracket">}</span>`;
                return html;
            }
            
            return String(value);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function highlightText(text, searchTerm) {
            const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
            return text.replace(regex, '<span class="json-highlight">$1</span>');
        }
        
        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        function toggleJson(id) {
            const element = document.getElementById(id);
            const parent = element.previousElementSibling.previousElementSibling;
            const toggle = parent.querySelector('.json-toggle');
            
            if (element.style.display === 'none') {
                element.style.display = 'inline';
                element.previousElementSibling.style.display = 'none';
                toggle.textContent = '▼';
            } else {
                element.style.display = 'none';
                element.previousElementSibling.style.display = 'inline';
                toggle.textContent = '▶';
            }
        }
        
        function expandAllJson() {
            document.querySelectorAll('.json-content').forEach(el => {
                el.style.display = 'inline';
                el.previousElementSibling.style.display = 'none';
            });
            document.querySelectorAll('.json-toggle').forEach(el => {
                el.textContent = '▼';
            });
        }
        
        function collapseAllJson() {
            document.querySelectorAll('.json-content').forEach(el => {
                el.style.display = 'none';
                el.previousElementSibling.style.display = 'inline';
            });
            document.querySelectorAll('.json-toggle').forEach(el => {
                el.textContent = '▶';
            });
        }
        
        function searchJson(term) {
            renderJson(parsedJson, term);
        }
        
        function copyJsonToClipboard() {
            const jsonStr = JSON.stringify(parsedJson, null, 2);
            navigator.clipboard.writeText(jsonStr).then(() => {
                const icon = document.getElementById('copyIcon');
                const text = document.getElementById('copyText');
                icon.textContent = '✓';
                text.textContent = 'Copiado!';
                setTimeout(() => {
                    icon.textContent = '📋';
                    text.textContent = 'Copiar';
                }, 2000);
            });
        }

        function toggleJsonPanel() {
            const panel = document.getElementById('jsonPanel');
            const icon = document.getElementById('jsonToggleIcon');
            
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                icon.style.transform = 'rotate(90deg)';
                // Renderizar JSON si es la primera vez (opcional, pero ya lo hacemos en DOMContentLoaded)
            } else {
                panel.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
@endsection