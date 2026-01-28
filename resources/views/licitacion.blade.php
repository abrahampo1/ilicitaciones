@extends('layouts.app')

@section('contenido')

    <div>
        <div class="flex items-start gap-2">
            <a href="{{ route('home') }}"><-- </a>
                    <h1 class="mb-4">Detalles de una licitación</h1>
        </div>
        <div class="flex gap-2">
            <x-card :titulo="'Licitación'" :valor="$licitacion->identificador" />
            <x-card :titulo="'Organismo'" :valor="$licitacion->organismo->nombre" />
        </div>

        <div class="flex gap-2 mt-2">

            <x-card :titulo="'Importe Presupuesto'"
                valor="{{ number_format($licitacion->importe_estimado, 2, ',', '.') . '€' ?? '--'}}" />
            <x-card :titulo="'Importe Sin Impuestos'"
                valor="{{ number_format($licitacion->importe_total, 2, ',', '.') . '€' ?? '--'}}" />
            <x-card :titulo="'Importe Total'"
                valor="{{ number_format($licitacion->importe_final, 2, ',', '.') . '€' ?? '--' }}" />

        </div>

        <div class="flex mt-2">
            <x-card :titulo="'Titulo'" :valor="$licitacion->titulo" />
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
                        @if ($empresa->nombre)
                            <tr>
                                <td>{{ $empresa->nombre }}</td>
                                <td class="text-left">{{ $empresa->pivot->descripcion }}</td>
                                <td>{{  number_format($empresa->pivot->importe, 2, ',', '.') ?? '--' }}€</td>
                            </tr>
                        @endif
                    @endforeach

                </table>
            </div>
        </div>
    </div>
@endsection