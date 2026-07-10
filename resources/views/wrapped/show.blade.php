{{--
    Wrapped anual del gasto público. Vista standalone (sin layout app): experiencia
    inmersiva tipo "stories" con CSS/JS propios inline para no depender del build
    de assets ni cargar publicidad que rompa la inmersión.
--}}
@php
    use App\Support\Formato;

    $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $mesesCortos = [1 => 'E', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'];

    $fmtInt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $maxMes = max(1.0, max($wrapped['porMes']));

    $enCurso = ! empty($wrapped['enCurso']);
    $tieneComparativa = $wrapped['deltaPct'] !== null;
    $tieneCategorias = ! empty($wrapped['topCategorias']);
    $tieneSalseo = $wrapped['urgentes']['num'] > 0 || $wrapped['sinCompetencia']['num'] > 0;
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-R7XWTC83X8"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-R7XWTC83X8');
    </script>

    <title>Wrapped {{ $year }} · El año del gasto público - I-Licitaciones</title>
    <meta name="description"
        content="El Wrapped {{ $year }} del gasto público español: {{ Formato::eurosCompactos($wrapped['total']) }} adjudicados en {{ $fmtInt($wrapped['numAdjudicaciones']) }} contratos. Descubre quién gastó y quién ganó.">
    <link rel="canonical" href="{{ route('wrapped.show', ['year' => $year]) }}" />
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ route('wrapped.show', ['year' => $year]) }}" />
    <meta property="og:title" content="Wrapped {{ $year }} · El año del gasto público" />
    <meta property="og:description"
        content="{{ Formato::eurosCompactos($wrapped['total']) }} en contratos públicos. Así sonó el {{ $year }} del dinero público español." />
    <meta property="og:site_name" content="I-Licitaciones" />
    <meta property="og:locale" content="es_ES" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #0a0a0a;
            color: #fff;
            overflow: hidden;
        }

        .wrapped {
            position: fixed;
            inset: 0;
            display: flex;
            flex-direction: column;
            user-select: none;
            -webkit-user-select: none;
        }

        /* ---- Cabecera: progreso y controles ---- */
        .topbar {
            position: absolute;
            top: 0; left: 0; right: 0;
            z-index: 40;
            padding: calc(env(safe-area-inset-top, 0px) + 12px) 14px 14px;
            /* Scrim: garantiza contraste del progreso y controles sobre fondos claros. */
            background: linear-gradient(to bottom, rgba(0, 0, 0, .45), transparent);
        }

        .progress {
            display: flex;
            gap: 5px;
        }

        .progress span {
            flex: 1;
            height: 3px;
            border-radius: 99px;
            background: rgba(255, 255, 255, .25);
            overflow: hidden;
            position: relative;
        }

        .progress span i {
            position: absolute;
            inset: 0;
            background: #fff;
            border-radius: 99px;
            transform: scaleX(0);
            transform-origin: left;
        }

        .controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
        }

        .brand {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .85);
            text-decoration: none;
            text-shadow: 0 1px 8px rgba(0, 0, 0, .4);
        }

        .controls .btns { display: flex; gap: 8px; }

        .ctrl {
            width: 38px; height: 38px;
            display: grid;
            place-items: center;
            border: none;
            border-radius: 50%;
            background: rgba(0, 0, 0, .3);
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            backdrop-filter: blur(6px);
            text-decoration: none;
            transition: background .2s;
        }

        .ctrl:hover { background: rgba(0, 0, 0, .55); }

        /* ---- Zonas de navegación tap ---- */
        .tapzone {
            position: absolute;
            top: 0; bottom: 0;
            z-index: 20;
            background: transparent;
            border: none;
            cursor: pointer;
        }

        .tapzone.prev { left: 0; width: 32%; }
        .tapzone.next { right: 0; width: 68%; }

        /* ---- Slides ---- */
        .slide {
            position: absolute;
            inset: 0;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 84px 24px calc(env(safe-area-inset-bottom, 0px) + 48px);
            overflow: hidden;
        }

        .slide.active { display: flex; }

        .slide .inner {
            /* Sin z-index propio: crearía un stacking context que atraparía el
               z-index de .acciones por debajo de las tapzones (botones inclicables). */
            position: relative;
            max-width: 640px;
            width: 100%;
        }

        /* Decoración: blobs flotantes */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .5;
            pointer-events: none;
            animation: flotar 14s ease-in-out infinite alternate;
        }

        @keyframes flotar {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(40px, -50px) scale(1.15); }
        }

        /* Animaciones de entrada, retriggereables al activar el slide */
        .slide.active .up {
            animation: subir .7s cubic-bezier(.2, .8, .2, 1) both;
        }

        .slide.active .pop {
            animation: pop .8s cubic-bezier(.2, .9, .3, 1.3) both;
        }

        .d1 { animation-delay: .15s !important; }
        .d2 { animation-delay: .35s !important; }
        .d3 { animation-delay: .55s !important; }
        .d4 { animation-delay: .75s !important; }
        .d5 { animation-delay: .95s !important; }
        .d6 { animation-delay: 1.15s !important; }

        @keyframes subir {
            from { opacity: 0; transform: translateY(34px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pop {
            from { opacity: 0; transform: scale(.6); }
            to { opacity: 1; transform: scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .slide.active .up, .slide.active .pop, .blob { animation: none !important; opacity: 1; }
            .slide.active .up, .slide.active .pop { opacity: 1; }
        }

        /* ---- Tipografía de slides ---- */
        .kicker {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            opacity: .85;
            margin-bottom: 18px;
        }

        .mega {
            font-size: clamp(44px, 11vw, 108px);
            font-weight: 900;
            line-height: .95;
            letter-spacing: -.02em;
        }

        .big {
            font-size: clamp(30px, 6.5vw, 58px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -.015em;
        }

        .sub {
            font-size: clamp(16px, 2.6vw, 21px);
            font-weight: 500;
            opacity: .9;
            margin-top: 18px;
            line-height: 1.45;
        }

        .hint {
            position: absolute;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 18px);
            left: 0; right: 0;
            text-align: center;
            font-size: 13px;
            letter-spacing: .08em;
            opacity: .6;
            z-index: 10;
            animation: latir 2s ease-in-out infinite;
        }

        @keyframes latir {
            0%, 100% { opacity: .35; }
            50% { opacity: .8; }
        }

        /* ---- Rankings ---- */
        .ranking {
            list-style: none;
            text-align: left;
            margin-top: 26px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ranking li {
            display: flex;
            align-items: baseline;
            gap: 14px;
        }

        .ranking .pos {
            font-size: 22px;
            font-weight: 900;
            opacity: .65;
            min-width: 26px;
        }

        .ranking .nom {
            flex: 1;
            font-weight: 800;
            font-size: clamp(15px, 2.4vw, 20px);
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .ranking li:first-child .nom { font-size: clamp(20px, 3.4vw, 30px); }
        .ranking li:first-child .pos { font-size: 30px; opacity: 1; }

        .ranking .imp {
            font-weight: 700;
            font-size: clamp(13px, 2vw, 17px);
            opacity: .85;
            white-space: nowrap;
        }

        /* ---- Gráfico de meses ---- */
        .meses {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 180px;
            margin-top: 30px;
        }

        .meses .mes {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            height: 100%;
            gap: 6px;
        }

        .meses .barra {
            border-radius: 6px 6px 3px 3px;
            background: rgba(255, 255, 255, .35);
            transform: scaleY(0);
            transform-origin: bottom;
        }

        .slide.active .meses .barra {
            animation: crecer .9s cubic-bezier(.2, .8, .2, 1) both;
        }

        @keyframes crecer {
            from { transform: scaleY(0); }
            to { transform: scaleY(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .slide.active .meses .barra { animation: none; transform: scaleY(1); }
        }

        .meses .mes.top .barra {
            background: #fff;
            box-shadow: 0 0 24px rgba(255, 255, 255, .55);
        }

        .meses .etiqueta {
            font-size: 11px;
            font-weight: 700;
            opacity: .7;
        }

        /* ---- Tarjeta final ---- */
        .card {
            background: #0d0d0d;
            border-radius: 22px;
            padding: 26px 24px;
            text-align: left;
            box-shadow: 0 22px 70px rgba(0, 0, 0, .45);
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .card h2 {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .card .anio {
            font-size: 56px;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(90deg, #e879f9, #fbbf24, #34d399);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .card .cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin: 20px 0;
        }

        .card .cols h3 {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            opacity: .6;
            margin-bottom: 8px;
        }

        .card .cols ol {
            list-style: none;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .card .cols ol li {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card .cols ol li b { opacity: .55; margin-right: 6px; }

        .card .totalfila {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-top: 1px solid rgba(255, 255, 255, .14);
            padding-top: 16px;
            gap: 10px;
        }

        .card .totalfila .lbl {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            opacity: .6;
        }

        .card .totalfila .val {
            font-size: clamp(20px, 4vw, 30px);
            font-weight: 900;
        }

        .acciones {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 22px;
            position: relative;
            z-index: 30;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 99px;
            border: none;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            transition: transform .15s;
        }

        .btn:hover { transform: scale(1.04); }
        .btn.primario { background: #fff; color: #111; }
        .btn.secundario { background: rgba(255, 255, 255, .16); color: #fff; backdrop-filter: blur(6px); }

        /* ---- Selector de año ---- */
        .years-menu {
            position: absolute;
            top: 92px;
            right: 14px;
            z-index: 50;
            background: rgba(10, 10, 10, .92);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 16px;
            padding: 8px;
            display: none;
            flex-direction: column;
            gap: 2px;
            max-height: 55vh;
            overflow-y: auto;
        }

        .years-menu.open { display: flex; }

        .years-menu a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            padding: 8px 18px;
            border-radius: 10px;
        }

        .years-menu a:hover { background: rgba(255, 255, 255, .12); }
        .years-menu a.actual { background: rgba(255, 255, 255, .2); }

        .toast {
            position: fixed;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 24px);
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #fff;
            color: #111;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 99px;
            opacity: 0;
            transition: all .3s;
            z-index: 60;
            pointer-events: none;
        }

        .toast.visible { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* Fondos por slide */
        .bg-intro { background: linear-gradient(150deg, #4c1d95 0%, #6d28d9 45%, #db2777 100%); }
        .bg-total { background: #d4f549; color: #111; }
        .bg-ritmo { background: #0a0a0a; }
        .bg-compara { background: linear-gradient(160deg, #ea580c 0%, #db2777 70%, #9d174d 100%); }
        .bg-organismos { background: linear-gradient(160deg, #312e81 0%, #4338ca 50%, #7c3aed 100%); }
        .bg-empresas { background: linear-gradient(160deg, #9f1239 0%, #e11d48 55%, #fb7185 130%); }
        .bg-hit { background: radial-gradient(120% 120% at 50% 0%, #422006 0%, #0a0a0a 65%); }
        .bg-meses { background: linear-gradient(160deg, #0e7490 0%, #0369a1 55%, #1e3a8a 100%); }
        .bg-categorias { background: linear-gradient(160deg, #065f46 0%, #0d9488 60%, #155e75 100%); }
        .bg-salseo { background: linear-gradient(160deg, #7f1d1d 0%, #991b1b 50%, #450a0a 100%); }
        .bg-final { background: linear-gradient(150deg, #1e1b4b 0%, #581c87 50%, #9d174d 100%); }

        .lima { color: #d4f549; }
        .oro { color: #fbbf24; }
        .fucsia { color: #e879f9; }
    </style>
</head>

<body>
    <div class="wrapped" id="wrapped">

        <div class="topbar">
            <div class="progress" id="progress"></div>
            <div class="controls">
                <a class="brand" href="{{ route('home') }}">I-Licitaciones · Wrapped</a>
                <div class="btns">
                    <button class="ctrl" id="btnPausa" aria-label="Pausar" title="Pausar">⏸</button>
                    <button class="ctrl" id="btnYears" aria-label="Cambiar de año" title="Cambiar de año">{{ $year }}</button>
                    <a class="ctrl" href="{{ route('wrapped.index') }}" aria-label="Salir" title="Salir">✕</a>
                </div>
            </div>
        </div>

        <div class="years-menu" id="yearsMenu">
            @foreach ($years as $y)
                <a href="{{ route('wrapped.show', ['year' => $y]) }}" @class(['actual' => $y === $year])>Wrapped {{ $y }}</a>
            @endforeach
        </div>

        <button class="tapzone prev" id="tapPrev" aria-label="Anterior"></button>
        <button class="tapzone next" id="tapNext" aria-label="Siguiente"></button>

        {{-- 1 · Intro --}}
        <section class="slide bg-intro" data-slide>
            <div class="blob" style="width:420px;height:420px;background:#f0abfc;top:-90px;left:-110px"></div>
            <div class="blob" style="width:360px;height:360px;background:#38bdf8;bottom:-100px;right:-90px;animation-delay:-6s"></div>
            <div class="inner">
                <p class="kicker up">Tu dinero también estuvo aquí</p>
                <h1 class="mega pop d1">Wrapped<br>{{ $year }}</h1>
                <p class="sub up d3">El año del gasto público español,<br>contado en {{ $fmtInt($wrapped['numAdjudicaciones']) }} contratos.</p>
            </div>
            <p class="hint">Toca para continuar →</p>
        </section>

        {{-- 2 · Total adjudicado --}}
        <section class="slide bg-total" data-slide>
            <div class="inner">
                <p class="kicker up">En {{ $enCurso ? 'lo que va de' : '' }} {{ $year }} el sector público adjudicó</p>
                <p class="mega pop d1" style="font-size:clamp(34px,8.5vw,86px)">
                    <span data-countup="{{ (int) round($wrapped['total']) }}">0</span> €
                </p>
                <p class="sub up d3">
                    @if ($wrapped['numAdjudicaciones'] === $wrapped['numLicitaciones'])
                        Es decir, {{ Formato::eurosCompactos($wrapped['total']) }} repartidos en
                        <strong>{{ $fmtInt($wrapped['numAdjudicaciones']) }}</strong> contratos públicos.
                    @else
                        Es decir, {{ Formato::eurosCompactos($wrapped['total']) }} repartidos en
                        <strong>{{ $fmtInt($wrapped['numAdjudicaciones']) }}</strong> adjudicaciones
                        de <strong>{{ $fmtInt($wrapped['numLicitaciones']) }}</strong> licitaciones.
                    @endif
                </p>
            </div>
        </section>

        {{-- 3 · Ritmo de gasto --}}
        <section class="slide bg-ritmo" data-slide>
            <div class="blob" style="width:380px;height:380px;background:#d4f549;top:-140px;right:-120px;opacity:.25"></div>
            <div class="inner">
                <p class="kicker up">El gasto no paró de sonar</p>
                <p class="big up d1">Cada día{{ $enCurso ? '' : ' del año' }}:<br><span class="lima"><span data-countup="{{ (int) round($wrapped['porDia']) }}">0</span> €</span></p>
                <p class="big up d3" style="margin-top:26px">Cada segundo:<br><span class="fucsia"><span data-countup="{{ (int) round($wrapped['porSegundo']) }}">0</span> €</span></p>
                <p class="sub up d5">Unos <strong>{{ $fmtInt(round($wrapped['porHabitante'])) }} €</strong> por cada habitante de España.</p>
            </div>
        </section>

        {{-- 4 · Comparativa con el año anterior --}}
        @if ($tieneComparativa)
            <section class="slide bg-compara" data-slide>
                <div class="blob" style="width:400px;height:400px;background:#fde047;bottom:-140px;left:-120px"></div>
                <div class="inner">
                    <p class="kicker up">¿Más o menos que en {{ $year - 1 }}?</p>
                    <p class="mega pop d1">{{ $wrapped['deltaPct'] > 0 ? '+' : '' }}{{ str_replace('.', ',', (string) $wrapped['deltaPct']) }}%</p>
                    <p class="sub up d3">
                        @php $periodoAnterior = $enCurso ? 'al mismo periodo de '.($year - 1) : 'a '.($year - 1); @endphp
                        @if ($wrapped['deltaPct'] >= 0)
                            El volumen adjudicado subió respecto {{ $periodoAnterior }}
                            ({{ Formato::eurosCompactos($wrapped['prevTotal']) }}).
                            El gasto público no conoce el modo repetición… o sí.
                        @else
                            El volumen adjudicado bajó respecto {{ $periodoAnterior }}
                            ({{ Formato::eurosCompactos($wrapped['prevTotal']) }}).
                            Un año más tranquilo en la lista de reproducción del gasto.
                        @endif
                    </p>
                </div>
            </section>
        @endif

        {{-- 5 · Top organismos --}}
        @if (! empty($wrapped['topOrganismos']))
            <section class="slide bg-organismos" data-slide>
                <div class="blob" style="width:360px;height:360px;background:#a5b4fc;top:-120px;right:-100px"></div>
                <div class="inner">
                    <p class="kicker up">Tus organismos más escuchados</p>
                    <p class="big up d1">{{ $fmtInt($wrapped['numOrganismos']) }} organismos licitaron.<br>Estos pusieron la música:</p>
                    <ol class="ranking">
                        @foreach ($wrapped['topOrganismos'] as $i => $org)
                            <li class="up d{{ $i + 2 }}">
                                <span class="pos">{{ $i + 1 }}</span>
                                <span class="nom">{{ $org['nombre'] ?? 'Organismo sin nombre' }}</span>
                                <span class="imp">{{ Formato::eurosCompactos((float) $org['total']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        {{-- 6 · Top empresas --}}
        @if (! empty($wrapped['topEmpresas']))
            <section class="slide bg-empresas" data-slide>
                <div class="blob" style="width:380px;height:380px;background:#fecdd3;bottom:-130px;right:-110px"></div>
                <div class="inner">
                    <p class="kicker up">Las empresas en bucle</p>
                    <p class="big up d1">{{ $fmtInt($wrapped['numEmpresas']) }} empresas se llevaron contrato.<br>Estas sonaron sin parar:</p>
                    <ol class="ranking">
                        @foreach ($wrapped['topEmpresas'] as $i => $emp)
                            <li class="up d{{ $i + 2 }}">
                                <span class="pos">{{ $i + 1 }}</span>
                                <span class="nom">{{ $emp['nombre'] ?? 'Empresa sin nombre' }}</span>
                                <span class="imp">{{ Formato::eurosCompactos((float) $emp['total']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        {{-- 7 · El contrato del año --}}
        @if ($wrapped['mayorAdjudicacion'])
            <section class="slide bg-hit" data-slide>
                <div class="blob" style="width:420px;height:420px;background:#f59e0b;top:-160px;left:50%;margin-left:-210px;opacity:.3"></div>
                <div class="inner">
                    <p class="kicker up oro">🏆 El hit del año</p>
                    <p class="mega pop d1 oro" style="font-size:clamp(34px,8vw,80px)">
                        {{ Formato::eurosCompactos((float) $wrapped['mayorAdjudicacion']['importe']) }}
                    </p>
                    <p class="sub up d3" style="font-weight:700">
                        «{{ \Illuminate\Support\Str::limit($wrapped['mayorAdjudicacion']['titulo'] ?? 'Licitación sin título', 140) }}»
                    </p>
                    <p class="sub up d4" style="opacity:.75">
                        @if ($wrapped['mayorAdjudicacion']['organismo'])
                            {{ $wrapped['mayorAdjudicacion']['organismo'] }}
                        @endif
                        @if ($wrapped['mayorAdjudicacion']['empresa'])
                            → {{ $wrapped['mayorAdjudicacion']['empresa'] }}
                        @endif
                    </p>
                    <div class="acciones up d5">
                        <a class="btn secundario" href="{{ route('licitacion.show', ['id' => $wrapped['mayorAdjudicacion']['licitacion_id']]) }}">Ver la licitación →</a>
                    </div>
                </div>
            </section>
        @endif

        {{-- 8 · Mes pico --}}
        <section class="slide bg-meses" data-slide>
            <div class="inner">
                <p class="kicker up">El ritmo de los meses</p>
                <p class="big up d1">
                    En {{ $meses[$wrapped['mesTop']['mes']] }} se dispararon las adjudicaciones:
                    {{ Formato::eurosCompactos($wrapped['mesTop']['total']) }}
                </p>
                <div class="meses up d2" role="img"
                    aria-label="Gráfico de adjudicaciones por mes; el máximo fue {{ $meses[$wrapped['mesTop']['mes']] }}">
                    @foreach ($wrapped['porMes'] as $mes => $importeMes)
                        <div class="mes {{ $mes === $wrapped['mesTop']['mes'] ? 'top' : '' }}">
                            <div class="barra" style="height:{{ max(3, round($importeMes / $maxMes * 100)) }}%; animation-delay:{{ 0.3 + $mes * 0.06 }}s"></div>
                            <span class="etiqueta">{{ $mesesCortos[$mes] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 9 · Categorías --}}
        @if ($tieneCategorias)
            <section class="slide bg-categorias" data-slide>
                <div class="blob" style="width:340px;height:340px;background:#6ee7b7;top:-110px;left:-100px"></div>
                <div class="inner">
                    <p class="kicker up">Tus géneros favoritos</p>
                    <p class="big up d1">En qué se fue el dinero:</p>
                    <ol class="ranking">
                        @foreach ($wrapped['topCategorias'] as $i => $cat)
                            <li class="up d{{ $i + 2 }}">
                                <span class="pos">{{ $i + 1 }}</span>
                                <span class="nom">{{ $cat['nombre'] }}</span>
                                <span class="imp">{{ Formato::eurosCompactos((float) $cat['total']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        {{-- 10 · Urgencia y sin competencia --}}
        @if ($tieneSalseo)
            <section class="slide bg-salseo" data-slide>
                <div class="blob" style="width:380px;height:380px;background:#fca5a5;bottom:-140px;left:-120px;opacity:.35"></div>
                <div class="inner">
                    <p class="kicker up">La cara B del disco</p>
                    @if ($wrapped['urgentes']['num'] > 0)
                        <p class="big up d1">
                            <span class="oro">{{ $fmtInt($wrapped['urgentes']['num']) }}</span> contratos
                            ({{ str_replace('.', ',', (string) $wrapped['urgentes']['pct']) }}%) se adjudicaron por
                            urgencia o emergencia.
                        </p>
                    @endif
                    @if ($wrapped['sinCompetencia']['num'] > 0)
                        <p class="big up d3" style="margin-top:26px">
                            Y <span class="oro">{{ Formato::eurosCompactos($wrapped['sinCompetencia']['importe']) }}</span>
                            se repartieron sin competencia entre empresas.
                        </p>
                    @endif
                    <p class="sub up d5">Sin saltos de canción: adjudicación directa o negociado sin publicidad.</p>
                </div>
            </section>
        @endif

        {{-- 11 · Resumen final --}}
        <section class="slide bg-final" data-slide>
            <div class="blob" style="width:400px;height:400px;background:#c084fc;top:-140px;right:-120px"></div>
            <div class="inner" style="max-width:480px">
                <div class="card pop">
                    <h2>Wrapped · Gasto público</h2>
                    <p class="anio">{{ $year }}</p>
                    <div class="cols">
                        <div>
                            <h3>Top organismos</h3>
                            <ol>
                                @foreach (array_slice($wrapped['topOrganismos'], 0, 3) as $i => $org)
                                    <li><b>{{ $i + 1 }}</b>{{ $org['nombre'] ?? '—' }}</li>
                                @endforeach
                            </ol>
                        </div>
                        <div>
                            <h3>Top empresas</h3>
                            <ol>
                                @foreach (array_slice($wrapped['topEmpresas'], 0, 3) as $i => $emp)
                                    <li><b>{{ $i + 1 }}</b>{{ $emp['nombre'] ?? '—' }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                    <div class="totalfila">
                        <span class="lbl">Total adjudicado</span>
                        <span class="val">{{ Formato::eurosCompactos($wrapped['total']) }}</span>
                    </div>
                    <div class="totalfila" style="border:none;padding-top:8px">
                        <span class="lbl">Contratos</span>
                        <span class="val" style="font-size:20px">{{ $fmtInt($wrapped['numAdjudicaciones']) }}</span>
                    </div>
                </div>
                <div class="acciones up d2">
                    <button class="btn primario" id="btnCompartir">Compartir</button>
                    @if ($prevYear)
                        <a class="btn secundario" href="{{ route('wrapped.show', ['year' => $prevYear]) }}">← {{ $prevYear }}</a>
                    @endif
                    @if ($nextYear)
                        <a class="btn secundario" href="{{ route('wrapped.show', ['year' => $nextYear]) }}">{{ $nextYear }} →</a>
                    @endif
                    <a class="btn secundario" href="{{ route('wrapped.index') }}">Todos los años</a>
                </div>
            </div>
        </section>

    </div>

    <div class="toast" id="toast">Enlace copiado 🎉</div>

    <script>
        (function () {
            const DURACION = 9000; // ms por slide
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const slides = Array.from(document.querySelectorAll('[data-slide]'));
            const progress = document.getElementById('progress');
            const btnPausa = document.getElementById('btnPausa');
            const btnYears = document.getElementById('btnYears');
            const yearsMenu = document.getElementById('yearsMenu');
            const toast = document.getElementById('toast');

            let actual = 0;
            let pausado = false;
            let inicioSlide = performance.now();
            let transcurridoPausa = 0;
            let rafId = null;

            // Una barra de progreso por slide.
            const barras = slides.map(() => {
                const s = document.createElement('span');
                const i = document.createElement('i');
                s.appendChild(i);
                progress.appendChild(s);
                return i;
            });

            function pintarBarras(elapsed) {
                barras.forEach((b, i) => {
                    if (i < actual) b.style.transform = 'scaleX(1)';
                    else if (i > actual) b.style.transform = 'scaleX(0)';
                    else b.style.transform = 'scaleX(' + Math.min(1, elapsed / DURACION) + ')';
                });
            }

            function tick(now) {
                if (!pausado) {
                    const elapsed = transcurridoPausa + (now - inicioSlide);
                    pintarBarras(elapsed);
                    if (elapsed >= DURACION) {
                        if (actual < slides.length - 1) {
                            irA(actual + 1);
                        } else {
                            pintarBarras(DURACION);
                            rafId = null; // permite rearmar el bucle si se navega atrás
                            return;
                        }
                    }
                }
                rafId = requestAnimationFrame(tick);
            }

            function animarContadores(slide) {
                slide.querySelectorAll('[data-countup]').forEach(el => {
                    const objetivo = parseInt(el.dataset.countup, 10) || 0;
                    const fmt = new Intl.NumberFormat('es-ES');
                    if (reducedMotion) { el.textContent = fmt.format(objetivo); return; }
                    const t0 = performance.now();
                    const dur = 1700;
                    (function paso(t) {
                        const p = Math.min(1, (t - t0) / dur);
                        const eased = 1 - Math.pow(1 - p, 3);
                        el.textContent = fmt.format(Math.round(objetivo * eased));
                        if (p < 1) requestAnimationFrame(paso);
                    })(t0);
                });
            }

            function irA(i) {
                actual = Math.max(0, Math.min(slides.length - 1, i));
                slides.forEach((s, j) => {
                    s.classList.toggle('active', j === actual);
                    s.setAttribute('aria-hidden', j === actual ? 'false' : 'true');
                });
                inicioSlide = performance.now();
                transcurridoPausa = 0;
                pintarBarras(0);
                animarContadores(slides[actual]);
                if (rafId === null) rafId = requestAnimationFrame(tick);
            }

            function togglePausa(forzar) {
                const nuevo = typeof forzar === 'boolean' ? forzar : !pausado;
                if (nuevo === pausado) return;
                if (nuevo) {
                    transcurridoPausa += performance.now() - inicioSlide;
                } else {
                    inicioSlide = performance.now();
                }
                pausado = nuevo;
                btnPausa.textContent = pausado ? '▶' : '⏸';
                btnPausa.title = btnPausa.ariaLabel = pausado ? 'Reanudar' : 'Pausar';
            }

            // Navegación por zonas de tap (con pausa al mantener pulsado).
            let pulsadoEn = 0;
            let pulsadoX = 0;
            let pausadoPorPulsacion = false;

            function alPulsar(e) {
                // Captura: el pointerup llega a la zona aunque se suelte fuera de ella.
                try { e.currentTarget.setPointerCapture(e.pointerId); } catch (_) { }
                pulsadoEn = performance.now();
                pulsadoX = e.clientX;
                if (!pausado) {
                    pausadoPorPulsacion = true;
                    togglePausa(true);
                }
            }

            function reanudarTrasPulsacion() {
                if (pausadoPorPulsacion) {
                    pausadoPorPulsacion = false;
                    togglePausa(false);
                }
            }

            function alSoltar(e, delta) {
                // Un flick (desplazamiento) no cuenta como tap: lo navega el handler de swipe.
                const fueTap = performance.now() - pulsadoEn < 250 && Math.abs(e.clientX - pulsadoX) < 10;
                reanudarTrasPulsacion();
                if (fueTap) irA(actual + delta);
            }

            const tapPrev = document.getElementById('tapPrev');
            const tapNext = document.getElementById('tapNext');
            tapPrev.addEventListener('pointerdown', alPulsar);
            tapNext.addEventListener('pointerdown', alPulsar);
            tapPrev.addEventListener('pointerup', e => alSoltar(e, -1));
            tapNext.addEventListener('pointerup', e => alSoltar(e, 1));
            tapPrev.addEventListener('pointercancel', reanudarTrasPulsacion);
            tapNext.addEventListener('pointercancel', reanudarTrasPulsacion);

            // Teclado.
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') { yearsMenu.classList.remove('open'); return; }
                if (yearsMenu.classList.contains('open')) return; // navegar el menú, no los slides
                if (e.key === ' ' && e.target.closest && e.target.closest('button, a')) return; // espacio activa el control con foco
                if (e.key === 'ArrowRight' || e.key === ' ') { e.preventDefault(); irA(actual + 1); }
                if (e.key === 'ArrowLeft') { e.preventDefault(); irA(actual - 1); }
                if (e.key.toLowerCase() === 'p') togglePausa();
            });

            // Swipe táctil.
            let touchX = null;
            document.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
            document.addEventListener('touchend', e => {
                if (touchX === null) return;
                const dx = e.changedTouches[0].clientX - touchX;
                if (Math.abs(dx) > 60) irA(actual + (dx < 0 ? 1 : -1));
                touchX = null;
            }, { passive: true });

            btnPausa.addEventListener('click', () => togglePausa());
            btnYears.addEventListener('click', () => yearsMenu.classList.toggle('open'));
            document.addEventListener('click', e => {
                if (!yearsMenu.contains(e.target) && e.target !== btnYears) yearsMenu.classList.remove('open');
            });

            // Compartir (Web Share API con fallback a copiar enlace).
            const btnCompartir = document.getElementById('btnCompartir');
            if (btnCompartir) {
                btnCompartir.addEventListener('click', async () => {
                    const datos = {
                        title: document.title,
                        text: 'El Wrapped {{ $year }} del gasto público: {{ Formato::eurosCompactos($wrapped['total']) }} en contratos. 🎧💸',
                        url: window.location.href,
                    };
                    if (navigator.share) {
                        try { await navigator.share(datos); } catch (e) { /* cancelado */ }
                    } else {
                        try {
                            await navigator.clipboard.writeText(datos.url);
                            toast.classList.add('visible');
                            setTimeout(() => toast.classList.remove('visible'), 2200);
                        } catch (e) { /* portapapeles no disponible */ }
                    }
                });
            }

            irA(0);
        })();
    </script>
</body>

</html>
