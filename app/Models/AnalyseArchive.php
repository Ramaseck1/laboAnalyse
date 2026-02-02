<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyseArchive extends Model
{
    protected $fillable = [
        'analyse_id',
        'patient_id',
        'donnees',
        'pdf_path',
        'archived_at'
    ];

    protected $casts = [
        'donnees' => 'array',
        'archived_at' => 'datetime',
    ];

    public function analyse()
    {
        return $this->belongsTo(Analyse::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
