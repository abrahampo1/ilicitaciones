<html>
<header>
    <title>I-Licitaciones</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Roboto+Serif:ital,opsz,wght@0,8..144,100..900;1,8..144,100..900&family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">

</header>

<body class="bg-neutral-900 font-serif">
    <div class="w-full h-full text-neutral-100 rounded-xl p-4">
        <div class="text-neutral-100 flex items-center justify-between">
            <h1 class="italic">I-Licitaciones</h1>
            <nav class="flex gap-6 text-sm">
                <a href="{{ route('home') }}" class="text-neutral-400 hover:text-neutral-100 transition-colors">Home</a>
                <a href="{{ route('organismos') }}" class="text-neutral-400 hover:text-neutral-100 transition-colors">Organismos</a>
                <a href="{{ route('empresas') }}" class="text-neutral-400 hover:text-neutral-100 transition-colors">Empresas</a>
            </nav>
        </div>
        <div class="p-4">
            @yield('contenido')
        </div>
    </div>
</body>

</html>