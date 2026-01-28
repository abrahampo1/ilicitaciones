@extends('layouts.app')

@section('contenido')

    <div>
        <div class="flex items-start gap-2">
            <a href="{{ route('home') }}"><-- </a>
                    <h1 class="mb-4">Detalles de una licitación</h1>
        </div>
        <div class="flex gap-2">
            <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                <p class="text-xs font-thin">Licitación</p>
                <hr class="border-neutral-700">
                <p class="pt-2">{{ $licitacion->identificador }}</p>
            </div>
            <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                <p class="text-xs font-thin">Organismo</p>
                <hr class="border-neutral-700">
                <p class="text-lg pt-2">{{ $licitacion->organismo->nombre }}</p>
            </div>
        </div>

        <div class="flex gap-2 mt-2">
            <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                <p class="text-xs font-thin">Importe Presupuestado</p>
                <hr class="border-neutral-700">
                <p class="text-lg pt-2">{{ number_format($licitacion->importe_estimado, 2, ',', '.') ?? '--' }}€</p>
            </div>
            <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                <p class="text-xs font-thin">Importe Sin Impuestos</p>
                <hr class="border-neutral-700">
                <p class="text-lg pt-2">{{ number_format($licitacion->importe_total, 2, ',', '.') ?? '--' }}€</p>
            </div>
            <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                <p class="text-xs font-thin">Importe Total</p>
                <hr class="border-neutral-700">
                <p class="text-lg pt-2">{{ number_format($licitacion->importe_final, 2, ',', '.') ?? '--' }}€</p>
            </div>
        </div>

        <div class="flex mt-2">
            <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                <p class="text-xs font-thin">Titulo</p>
                <hr class="border-neutral-700">
                <p class="text-lg pt-2">{{ $licitacion->titulo }}</p>
            </div>
        </div>

        @if ($licitacion->descripcion)
            <div class="flex mt-2">

                <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                    <p class="text-xs font-thin">Descripción</p>
                    <hr class="border-neutral-700">
                    <p class="text-lg pt-2">{{ $licitacion->descripcion }}</p>
                </div>
            </div>
        @endif


        <div class="mt-2">
            <div class="bg-neutral-800 p-4 border border-neutral-700 rounded-xl">
                <p class="text-xs">Adjudicaciones</p>
                <hr class="border-neutral-700">

                <table class="w-full text-white mt-2">
                    <tr class="text-left font-thin text-xs">
                        <th>Empresa</th>
                        <th>Razon de la adjudicación</th>
                        <th>Importe</th>
                    </tr>

                    @foreach ($licitacion->empresas as $empresa)
                        <tr>
                            <td>{{ $empresa->nombre }}</td>
                            <td class="text-left">{{ $empresa->pivot->descripcion }}</td>
                            <td>{{  number_format($empresa->pivot->importe, 2, ',', '.') ?? '--' }}€</td>
                        </tr>
                    @endforeach

                </table>
            </div>
        </div>
    </div>
@endsection