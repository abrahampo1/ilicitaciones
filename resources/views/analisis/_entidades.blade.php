@php
    $hayEntidades = $article->empresas->isNotEmpty() || $article->organismos->isNotEmpty()
        || $article->licitaciones->isNotEmpty() || $article->categorias->isNotEmpty();
@endphp

@if ($hayEntidades)
    <div class="bg-neutral-900/50 border border-neutral-800 rounded-2xl p-6 lg:sticky lg:top-6">
        <h2 class="text-sm font-medium text-neutral-300 mb-4">En este análisis</h2>

        @if ($article->empresas->isNotEmpty())
            <div class="mb-5">
                <p class="text-xs uppercase tracking-wider text-neutral-500 mb-2">Empresas</p>
                <div class="space-y-1.5">
                    @foreach ($article->empresas as $empresa)
                        <a href="{{ route('empresa.show', $empresa->id) }}"
                            class="block text-sm text-neutral-300 hover:text-emerald-400 transition-colors truncate">{{ $empresa->nombre }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($article->organismos->isNotEmpty())
            <div class="mb-5">
                <p class="text-xs uppercase tracking-wider text-neutral-500 mb-2">Organismos</p>
                <div class="space-y-1.5">
                    @foreach ($article->organismos as $organismo)
                        <a href="{{ route('organismo.show', $organismo->id) }}"
                            class="block text-sm text-neutral-300 hover:text-cyan-400 transition-colors truncate">{{ $organismo->nombre }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($article->licitaciones->isNotEmpty())
            <div class="mb-5">
                <p class="text-xs uppercase tracking-wider text-neutral-500 mb-2">Licitaciones</p>
                <div class="space-y-1.5">
                    @foreach ($article->licitaciones as $licitacion)
                        <a href="{{ route('licitacion.show', $licitacion->id) }}"
                            class="block text-sm text-neutral-300 hover:text-sky-400 transition-colors line-clamp-2">{{ Str::limit($licitacion->titulo, 70) }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($article->categorias->isNotEmpty())
            <div>
                <p class="text-xs uppercase tracking-wider text-neutral-500 mb-2">Categorías CPV</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($article->categorias as $categoria)
                        <a href="{{ route('empresas', ['categoria_id' => $categoria->id]) }}"
                            class="px-2 py-0.5 text-xs rounded-full bg-neutral-800 text-neutral-400 hover:text-neutral-200 transition-colors">{{ Str::limit($categoria->nombre, 28) }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
