<?php

// app/Http/Controllers/PatientController.php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Validator;

class PatientController extends Controller
{
    // Afficher tous les patients
    public function index()
    {
        $patients = Patient::all(); // Récupérer tous les patients
        return response()->json($patients);
    }

    /**
     * Retourne tous les patients avec leurs analyses et départements associés
     */
    public function indexWithRelations()
    {
        $patients = Patient::with(['analyses.departement', 'analyses.details'])->get();
        return response()->json($patients);
    }

    // Afficher un patient spécifique par son ID
    public function show($id)
    {
        $patient = Patient::find($id); // Rechercher le patient par ID

        if (!$patient) {
            return response()->json(['message' => 'Patient non trouvé.'], 404);
        }

        return response()->json($patient);
    }

    // Créer un nouveau patient
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:patients,code|max:255',
            'prenom' => 'required|string',
            'nom' => 'required|string',
            'age' => 'nullable|integer',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|regex:/^[0-9+\-\s()]{8,20}$/', // ✅ Corrigé: enlevé "nullable:patients,telephone"
            'sexe' => 'nullable|in:M,F',
            'date_prescrit' => 'nullable|date',
            'date_edite' => 'nullable|date',
            'diagnostic' => 'nullable|string', // ✅ CORRIGÉ: "diagnostic" au lieu de "diagnostisc"
        ], [
            'code.unique' => 'Ce code patient existe déjà, veuillez en choisir un autre.',
            'telephone.regex' => 'Le format du numéro de téléphone est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Créer un patient
        $patient = Patient::create($request->all());
        return response()->json(['message' => 'Patient créé avec succès', 'patient' => $patient], 201);
    }

    // Mettre à jour un patient existant
    public function update(Request $request, $id)
    {
        // Rechercher le patient
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json(['message' => 'Patient non trouvé.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:patients,code,' . $id,
            'prenom' => 'required|string',
            'nom' => 'required|string',
            'age' => 'nullable|integer',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|regex:/^[0-9+\-\s()]{8,20}$/', // ✅ Corrigé: enlevé "nullable:patients,telephone," . $id
            'sexe' => 'nullable|in:M,F',
            'date_prescrit' => 'nullable|date',
            'date_edite' => 'nullable|date',
            'diagnostic' => 'nullable|string',
        ], [
            'telephone.regex' => 'Le format du numéro de téléphone est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Mise à jour des informations du patient
        $patient->update($request->all());

        return response()->json(['message' => 'Patient mis à jour avec succès', 'patient' => $patient]);
    }

    public function showPatientAnalyses($id)
    {
        $patient = Patient::find($id);  // Trouver le patient par son ID

        if (!$patient) {
            return response()->json(['message' => 'Patient non trouvé.'], 404);
        }

        // Charger les analyses associées au patient
        $analyses = $patient->analyses;  // Récupérer les analyses du patient

        return response()->json($analyses);  // Retourner les analyses du patient
    }

    // Supprimer un patient
    public function destroy($id)
    {
        // Rechercher le patient
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json(['message' => 'Patient non trouvé.'], 404);
        }

        // Supprimer le patient
        $patient->delete();

        return response()->json(['message' => 'Patient supprimé avec succès.']);
    }
}