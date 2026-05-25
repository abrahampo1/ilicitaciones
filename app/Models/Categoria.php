<?php

namespace App\Models;

use App\Models\Concerns\HasArticles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasArticles, HasFactory;

    protected $fillable = [
        'nombre',
        'xml_id',
    ];
}
