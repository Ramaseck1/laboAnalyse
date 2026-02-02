<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailAnalyse extends Model
{
    use HasFactory;
    protected $fillable = ['analyse_id','donnees'];

    protected $casts = [
        'donnees' => 'array', // Convertit automatiquement JSON en tableau PHP
    ];

    public function analyse()
    {
        return $this->belongsTo(Analyse::class);
    }
}
