@php

    $latestDate = 'Hasta el ' . Carbon\Carbon::parse(App\Models\Licitacion::latest()->first()->fecha_actualizacion)->format('d/m/Y H:i');
    $totalLicitaciones = number_format(App\Models\Licitacion::sum('importe_total'), 2, ',', '.') . '€' ?? '--';
    $conteoLicitaciones = App\Models\Licitacion::count();

@endphp


@extends('layouts.app')

@section('contenido')

    <div class="flex items-center gap-4">
        <x-card :titulo="'Total de Dinero Invertido'" :descripcion="$latestDate" :valor="$totalLicitaciones" />
        <x-card :titulo="'Licitaciones Totales'" :descripcion="$latestDate" :valor="$conteoLicitaciones" />


    </div>

    <div class="mt-2">
        <h1>Top 10 Gastos de nuestro gobierno</h1>

        <div class=" border border-neutral-700 bg-neutral-800 rounded-xl p-2">
            <table class="w-full text-white mt-2">
                <tr class="text-left font-thin text-xs">
                    <th class="py-2">Identificador</th>
                    <th>Organismo</th>
                    <th>Titulo</th>
                    <th>Importe</th>
                </tr>
                @foreach (App\Models\Licitacion::orderBy('importe_total', 'desc')->limit(10)->get() as $licitacion)
                            <tr>
                                <td>
                                    <a href="{{ route('licitacion.show', [
                        'id' => $licitacion->id
                    ]) }}">{{ $licitacion->identificador }}</a>
                                </td>
                                <td>{{ Str::limit($licitacion->organismo->nombre, 50) }}</td>
                                <td class="text-left" title="{{ $licitacion->titulo }}">{{ Str::limit($licitacion->titulo, 50) }}</td>
                                <td>{{  number_format($licitacion->importe_total, 2, ',', '.') ?? '--' }}€</td>
                            </tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="mt-4">
        <h1>10 Gastos Random</h1>
        <div class=" border border-neutral-700 bg-neutral-800 rounded-xl p-2">
            <table class="w-full text-white mt-2">
                <tr class="text-left font-thin text-xs">
                    <th class="py-2">Identificador</th>
                    <th>Organismo</th>
                    <th>Titulo</th>
                    <th>Importe</th>
                </tr>
                @foreach (App\Models\Licitacion::inRandomOrder()->limit(10)->get() as $licitacion)
                            <tr>
                                <td><a href="{{ route('licitacion.show', [
                        'id' => $licitacion->id
                    ]) }}">{{ $licitacion->identificador }}</a></td>
                                <td>{{ Str::limit($licitacion->organismo->nombre, 50) }}</td>
                                <td class="text-left" title="{{ $licitacion->titulo }}">{{ Str::limit($licitacion->titulo, 50) }}</td>
                                <td>{{  number_format($licitacion->importe_total, 2, ',', '.') ?? '--' }}€</td>
                            </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection