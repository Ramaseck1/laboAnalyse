<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartementController extends Controller
{
    //
    public function createDepartement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:roles',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $departement = Departement::create([
            'nom' => $request->nom,
        ]);

        return response()->json(['message' => 'Departement created successfully', 'departement' => $departement], 201);
    }

    public function getAllDepartements(){
        $departements = Departement::all();
        return response()->json(['departements' => $departements], 200);
    }

 public function showDepartementAnalyses(Departement $departement)
{
    $analyses = $departement->analyses()
        ->with('details')
        ->get();

    if ($analyses->isEmpty()) {
        return response()->json([
            'message' => 'Aucune analyse trouvée pour ce département.'
        ], 404);
    }

    $data = [
        'departement' => [
            'id' => $departement->id,
            'nom' => $departement->nom,
        ],
        'analyses' => $analyses->map(function ($analyse) {
            return [
                'id' => $analyse->id,
                'nom' => $analyse->nom,
                'details' => $analyse->details
                    ? $analyse->details->donnees
                    : null
            ];
        })
    ];

    return response()->json($data, 200);
}

public function showDepartementAnalysesCatalog(Departement $departement)
{
    // Récupérer le patient_id depuis la requête (optionnel)
    $patientId = request()->query('patient_id');
    
    // Récupérer toutes les analyses (avec ou sans patient)
    $analyses = $departement->analyses()
        ->with(['details'])
        ->get();

    // Si un patient_id est fourni, l'inclure dans la réponse
    $response = [
        'departement' => [
            'id' => $departement->id,
            'nom' => $departement->nom,
        ],
        'patient_id' => $patientId ? (int)$patientId : null,
        'analyses' => $analyses
    ];

    return response()->json($response, 200);
}

}

