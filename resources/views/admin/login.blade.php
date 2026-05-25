@extends('admin.layout')

@section('admin_title', 'Acceso')

@section('admin_content')
    <div class="max-w-sm mx-auto mt-12">
        <h1 class="text-2xl font-light mb-6">Acceso a la redacción</h1>
        <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-neutral-400 mb-1" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm text-neutral-400 mb-1" for="password">Contraseña</label>
                <input id="password" name="password" type="password" required
                    class="w-full px-3 py-2 rounded-lg bg-neutral-800 border border-neutral-700 focus:border-emerald-500 outline-none">
            </div>
            <label class="flex items-center gap-2 text-sm text-neutral-400">
                <input type="checkbox" name="remember" value="1" class="rounded bg-neutral-800 border-neutral-700">
                Recordarme
            </label>
            <button type="submit"
                class="w-full py-2 rounded-lg bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 transition-colors">
                Entrar
            </button>
        </form>
    </div>
@endsection
