<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Créer quelques départements pour la base de données
        Departement::create(['nom' => 'BIOCHIMIE']);
        Departement::create(['nom' => 'HÉMATOLOGIE']);
        Departement::create(['nom' => 'MICROBIOLOGIE']);
        Departement::create(['nom' => 'ANATOMIE']);
    }
}
