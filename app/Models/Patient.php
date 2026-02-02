<?php

// app/Models/Patient.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    // Définir les attributs que l'on peut remplir
    protected $fillable = [
        'code', 'prenom', 'nom', 'age', 'adresse', 'telephone','sexe', 'date_prescrit', 'date_edite', 'diagnostic'
    ];
    public $timestamps = false;


    // Relation avec les analyses
    public function analyses()
    {
        return $this->hasMany(Analyse::class); // Un patient peut avoir plusieurs analyses
    }
    public function analyseArchives()
{
    return $this->hasMany(AnalyseArchive::class);
}

}
