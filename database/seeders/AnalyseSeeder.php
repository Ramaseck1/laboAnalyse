<?php

namespace Database\Seeders;

use App\Models\Analyse;
use App\Models\Departement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnalyseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Récupérer les départements existants
        $biochimie = Departement::where('nom', 'BIOCHIMIE')->first();
        $hematologie = Departement::where('nom', 'HÉMATOLOGIE')->first();
        $microbiologie = Departement::where('nom', 'MICROBIOLOGIE')->first();
        $anatomie = Departement::where('nom', 'ANATOMIE')->first();

        // Ajouter des analyses spécifiques à chaque département
        Analyse::create(['nom' => 'NFS', 'departement_id' => $biochimie->id]);
        Analyse::create(['nom' => 'GSRH', 'departement_id' => $biochimie->id]);
        Analyse::create(['nom' => 'TE', 'departement_id' => $hematologie->id]);
        Analyse::create(['nom' => 'TP-INR', 'departement_id' => $microbiologie->id]);
        Analyse::create(['nom' => 'TCK', 'departement_id' => $microbiologie->id]);
        Analyse::create(['nom' => 'Bactériologie', 'departement_id' => $anatomie->id]);
        Analyse::create(['nom' => 'Cytologie', 'departement_id' => $anatomie->id]);
    }
}
