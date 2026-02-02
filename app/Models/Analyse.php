<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analyse extends Model
{
    use HasFactory;
    protected $fillable = ['departement_id', 'patient_id', 'nom'];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Une analyse a UN SEUL enregistrement de détails
     */
    public function details()
    {
        return $this->hasOne(DetailAnalyse::class, 'analyse_id');
    }
   public function archive()
    {
        return $this->hasOne(AnalyseArchive::class, 'analyse_id');
    }

}