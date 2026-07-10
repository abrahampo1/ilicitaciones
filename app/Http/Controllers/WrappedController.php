<?php

namespace App\Http\Controllers;

use App\Services\WrappedData;

class WrappedController extends Controller
{
    public function __construct(private WrappedData $wrappedData)
    {
    }

    public function index()
    {
        $years = $this->wrappedData->years();

        if ($years->isEmpty()) {
            return view('wrapped.index', ['years' => collect(), 'totalesPorYear' => []]);
        }

        return view('wrapped.index', [
            'years' => $years,
            'totalesPorYear' => $this->wrappedData->indexTotales(),
        ]);
    }

    public function show(int $year)
    {
        $years = $this->wrappedData->years();

        abort_unless($years->contains($year), 404);

        $wrapped = $this->wrappedData->paquete($year);

        $prevYear = $years->filter(fn ($y) => $y < $year)->max();
        $nextYear = $years->filter(fn ($y) => $y > $year)->min();

        return view('wrapped.show', compact('wrapped', 'years', 'year', 'prevYear', 'nextYear'));
    }
}
