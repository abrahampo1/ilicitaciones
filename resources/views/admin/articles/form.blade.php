@extends('admin.layout')

@section('admin_title', $article->exists ? 'Editar' : 'Nuevo')

@php
    $action = $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store');
    $val = fn ($campo, $def = '') => old($campo, $article->$campo ?? $def);
@endphp

@section('admin_content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-light">{{ $article->exists ? 'Editar artículo' : 'Nuevo artículo' }}</h1>
        @if ($article->exists)
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.articles.preview', $article) }}"
                    class="px-3 py-1.5 text-sm rounded-lg bg-neutral-800 text-neutral-300 hover:bg-neutral-700 transition-colors">Vista previa</a>
                @if ($article->isPublished())
                    <form method="POST" action="{{ route('admin.articles.unpublish', $article) }}">
                        @csrf
                        <button class="px-3 py-1.5 text-sm rounded-lg bg-amber-500/15 text-amber-300 hover:bg-amber-500/25 transition-colors">Despublicar</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.articles.publish', $article) }}">
                        @csrf
                        <button class="px-3 py-1.5 text-sm rounded-lg bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 transition-colors">Publicar</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <form method="POST" action="{{ $action }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @if ($article->exists)
            @method('PUT')
        @endif

        {{-- Columna principal --}}
        <div class="lg:col-span-2 space-y-4">
            <div>
                <label class="block text-sm text-neutral-400 mb-1" for="title">Titular</label>
                <input id="title" name="title" value="{{ $val('title') }}" required
                    class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-neutral-400 mb-1" for="dek">Entradilla</label>
                <textarea id="dek" name="dek" rows="2"
                    class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 focus:border-emerald-500 outline-none">{{ $val('dek') }}</textarea>
            </div>
            <div>
                <label class="block text-sm text-neutral-400 mb-1" for="body">
                    Cuerpo
                    <span class="text-neutral-600">— soporta shortcodes [[chart:clave]], [[table:clave]], [[kpi:clave]], [[callout:clave]]</span>
                </label>
                <textarea id="body" name="body" rows="18"
                    class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 focus:border-emerald-500 outline-none font-mono text-sm">{{ $val('body') }}</textarea>
            </div>
            <div>
                <label class="block text-sm text-neutral-400 mb-1" for="data">Datos para shortcodes (JSON)</label>
                <textarea id="data" name="data" rows="6"
                    class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 focus:border-emerald-500 outline-none font-mono text-xs">{{ old('data', $article->data ? json_encode($article->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
            </div>
        </div>

        {{-- Barra lateral --}}
        <div class="space-y-4">
            <div class="p-4 rounded-2xl bg-neutral-800/30 border border-neutral-700/30 space-y-4">
                <div>
                    <label class="block text-sm text-neutral-400 mb-1" for="section">Sección</label>
                    <select id="section" name="section"
                        class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 outline-none">
                        @foreach ($secciones as $s)
                            <option value="{{ $s->value }}" @selected($val('section', $article->section?->value) == $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-neutral-400 mb-1" for="status">Estado</label>
                    <select id="status" name="status"
                        class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 outline-none">
                        @foreach ($estados as $e)
                            <option value="{{ $e->value }}" @selected($val('status', $article->status?->value) == $e->value)>{{ $e->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-neutral-400 mb-1" for="body_format">Formato del cuerpo</label>
                    <select id="body_format" name="body_format"
                        class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 outline-none">
                        <option value="markdown" @selected($val('body_format', 'markdown') == 'markdown')>Markdown</option>
                        <option value="html" @selected($val('body_format') == 'html')>HTML</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-neutral-400 mb-1" for="categoria_id">CPV principal</label>
                    <select id="categoria_id" name="categoria_id"
                        class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 outline-none">
                        <option value="">—</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}" @selected($val('categoria_id', $article->categoria_id) == $c->id)>{{ Str::limit($c->nombre, 40) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-neutral-400 mb-1" for="provincia">Provincia</label>
                    <input id="provincia" name="provincia" value="{{ $val('provincia') }}"
                        class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 outline-none">
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-neutral-800/30 border border-neutral-700/30 space-y-3">
                <p class="text-sm text-neutral-300">Entidades relacionadas <span class="text-neutral-600">(IDs separados por coma)</span></p>
                @foreach (['empresas' => 'Empresas', 'organismos' => 'Organismos', 'licitaciones' => 'Licitaciones', 'categorias' => 'CPV'] as $campo => $label)
                    <div>
                        <label class="block text-xs text-neutral-500 mb-1" for="{{ $campo }}">{{ $label }}</label>
                        <input id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $entidadesActuales[$campo]) }}"
                            class="w-full px-3 py-1.5 rounded-lg bg-neutral-800 border border-neutral-700 outline-none text-sm font-mono">
                    </div>
                @endforeach
            </div>

            <div class="p-4 rounded-2xl bg-neutral-800/30 border border-neutral-700/30 space-y-3">
                <p class="text-sm text-neutral-300">SEO</p>
                <input name="meta_title" value="{{ $val('meta_title') }}" placeholder="Meta título"
                    class="w-full px-3 py-1.5 rounded-lg bg-neutral-800 border border-neutral-700 outline-none text-sm">
                <textarea name="meta_description" rows="2" placeholder="Meta descripción"
                    class="w-full px-3 py-1.5 rounded-lg bg-neutral-800 border border-neutral-700 outline-none text-sm">{{ $val('meta_description') }}</textarea>
                <input name="og_image" value="{{ $val('og_image') }}" placeholder="URL imagen OG"
                    class="w-full px-3 py-1.5 rounded-lg bg-neutral-800 border border-neutral-700 outline-none text-sm">
            </div>

            <button type="submit"
                class="w-full py-2.5 rounded-lg bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 transition-colors">
                Guardar
            </button>
        </div>
    </form>

    @if ($article->exists)
        <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" class="mt-6"
            onsubmit="return confirm('¿Eliminar este artículo de forma permanente?')">
            @csrf
            @method('DELETE')
            <button class="text-sm text-red-400/70 hover:text-red-400 transition-colors">Eliminar artículo</button>
        </form>
    @endif
@endsection
