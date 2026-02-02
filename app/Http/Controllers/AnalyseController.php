<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Analyse;
use App\Models\DetailAnalyse;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Services\PdfService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\AnalyseArchive;

class AnalyseController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * ✅ MODIFIÉ : Exclure les analyses archivées de la liste
     */
    public function index(Request $request)
    {
        $query = Analyse::with(['patient', 'departement'])
                        ->whereNotNull('patient_id') // Seulement les analyses avec un patient
                        ->whereDoesntHave('archive'); // ✅ NOUVEAU : Exclure les analyses archivées
        
        // Filtrage par patient si l'ID est fourni
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        
        // Filtrage par département si l'ID est fourni
        if ($request->has('departement_id')) {
            $query->where('departement_id', $request->departement_id);
        }
        
        // Recherche par nom si le terme est fourni
        if ($request->has('nom')) {
            $query->where('nom', 'like', '%' . $request->nom . '%');
        }
        
        // Pagination des résultats (10 par page par défaut)
        $perPage = $request->input('per_page', 100);
        $analyses = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'data' => $analyses->items(),
            'pagination' => [
                'total' => $analyses->total(),
                'per_page' => $analyses->perPage(),
                'current_page' => $analyses->currentPage(),
                'last_page' => $analyses->lastPage()
            ]
        ]);
    }

    public function store(Request $request, Departement $departement, Patient $patient)
    {
        $validator = Validator::make($request->all(), [
            'nom' => [
                'required','string','max:255',
                Rule::unique('analyses')->where(function ($q) use ($departement, $patient) {
                    return $q->where('departement_id', $departement->id)
                             ->where('patient_id', $patient->id);
                })
            ],
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $analyse = new Analyse();
        $analyse->nom = $request->nom;
        $analyse->departement_id = $departement->id;
        $analyse->patient_id = $patient->id;
        $analyse->save();
    
        return response()->json([
            'message' => 'Analyse ajoutée avec succès',
            'analyse' => $analyse
        ], 201);
    }

    /**
     * ✅ MODIFIÉ : Exclure les analyses archivées
     */
  public function storeWithoutPatient(Request $request, Departement $departement)
{
    $rules = [
        'nom' => ['required','string','max:255'],
        'patient_id' => 'nullable|exists:patients,id',
    ];

    // ✅ Validation SANS whereDoesntHave
    if ($request->filled('patient_id')) {
        $rules['nom'][] = Rule::unique('analyses')->where(function ($q) use ($departement, $request) {
            return $q->where('departement_id', $departement->id)
                     ->where('patient_id', $request->input('patient_id'));
            // ❌ PAS de ->whereDoesntHave('archive') ici !
        });
    } else {
        $rules['nom'][] = Rule::unique('analyses')->where(function ($q) use ($departement) {
            return $q->where('departement_id', $departement->id)
                     ->whereNull('patient_id');
            // ❌ PAS de ->whereDoesntHave('archive') ici !
        });
    }

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // ✅ Vérification MANUELLE des analyses archivées
    $existingNonArchived = Analyse::where('nom', $request->nom)
        ->where('departement_id', $departement->id)
        ->when($request->filled('patient_id'), function ($q) use ($request) {
            return $q->where('patient_id', $request->input('patient_id'));
        }, function ($q) {
            return $q->whereNull('patient_id');
        })
        ->whereDoesntHave('archive') // ✅ OK ici car c'est Eloquent, pas Rule::unique
        ->exists();

    if ($existingNonArchived) {
        return response()->json([
            'errors' => [
                'nom' => ['Une analyse non archivée avec ce nom existe déjà dans ce département.']
            ]
        ], 422);
    }

    // ✅ Créer l'analyse
    $analyse = new Analyse();
    $analyse->nom = $request->nom;
    $analyse->departement_id = $departement->id;
    $analyse->patient_id = $request->input('patient_id');
    $analyse->save();

    return response()->json([
        'message' => 'Analyse créée avec succès',
        'analyse' => $analyse,
    ], 201);
}
    /**
     * ✅ MODIFIÉ : Exclure les analyses archivées
     */
    public function getWithoutPatient(Request $request)
    {
        $query = Analyse::with('departement')
                        ->whereNull('patient_id')
                        ->whereDoesntHave('archive'); // ✅ Exclure les archivées

        if ($request->has('departement_id')) {
            $query->where('departement_id', $request->departement_id);
        }

        if ($request->has('nom')) {
            $query->where('nom', 'like', '%' . $request->nom . '%');
        }

        $perPage = $request->input('per_page', 50);
        $analyses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $analyses->items(),
            'pagination' => [
                'total' => $analyses->total(),
                'per_page' => $analyses->perPage(),
                'current_page' => $analyses->currentPage(),
                'last_page' => $analyses->lastPage()
            ]
        ]);
    }

    public function assignPatient(Request $request, Analyse $analyse)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (is_null($analyse->patient_id)) {
            $analyse->patient_id = $request->patient_id;
            $analyse->save();
            
            return response()->json([
                'message' => 'Patient assigné avec succès', 
                'analyse' => $analyse,
                'action' => 'assigned'
            ]);
        }

        if ($analyse->patient_id != $request->patient_id) {
            $exists = Analyse::where('nom', $analyse->nom)
                ->where('departement_id', $analyse->departement_id)
                ->where('patient_id', $request->patient_id)
                ->exists();
                
            if ($exists) {
                return response()->json([
                    'errors' => ['nom' => ['Cette analyse existe déjà pour ce patient dans ce département.']]
                ], 422);
            }

            $newAnalyse = new Analyse();
            $newAnalyse->nom = $analyse->nom;
            $newAnalyse->departement_id = $analyse->departement_id;
            $newAnalyse->patient_id = $request->patient_id;
            $newAnalyse->save();

            return response()->json([
                'message' => 'Nouvelle analyse créée pour le patient', 
                'analyse' => $newAnalyse,
                'action' => 'created'
            ]);
        }

        return response()->json([
            'message' => 'Cette analyse est déjà assignée à ce patient', 
            'analyse' => $analyse,
            'action' => 'already_assigned'
        ]);
    }

    public function storeDetails(Request $request, $analyseId)
    {
        $validator = Validator::make($request->all(), [
            'details' => 'required|array',
            'details.*.nom' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $analyse = Analyse::find($analyseId);
        if (!$analyse) {
            return response()->json(['error' => 'Analyse non trouvée'], 404);
        }

        $existingDetail = DetailAnalyse::where('analyse_id', $analyseId)->first();
        
        if ($existingDetail) {
            $existingDetail->donnees = $request->details;
            $existingDetail->save();
            
            return response()->json([
                'message' => 'Détails de l\'analyse mis à jour avec succès',
                'detail' => $existingDetail
            ], 200);
        } else {
            $detail = DetailAnalyse::create([
                'analyse_id' => $analyseId,
                'donnees' => $request->details
            ]);
            
            return response()->json([
                'message' => 'Détails de l\'analyse ajoutés avec succès',
                'detail' => $detail
            ], 201);
        }
    }

    public function assignPatientWithDetails(Request $request, Analyse $analyse)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'details' => 'required|array',
            'details.*.nom' => 'required|string|max:255',
            'details.*.resultat' => 'nullable|string',
            'details.*.intervalle' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $analyseToUse = null;
        $action = '';    
        
        $existingAnalyse = Analyse::where('nom', $analyse->nom)
            ->where('departement_id', $analyse->departement_id)
            ->where('patient_id', $request->patient_id)
            ->whereDoesntHave('archive') // ✅ Exclure les archivées
            ->first();
        
        if ($existingAnalyse) {
            $analyseToUse = $existingAnalyse;
            $action = 'updated';
        } else {
            $newAnalyse = new Analyse();
            $newAnalyse->nom = $analyse->nom;
            $newAnalyse->departement_id = $analyse->departement_id;
            $newAnalyse->patient_id = $request->patient_id;
            $newAnalyse->save();
            
            $analyseToUse = $newAnalyse;
            $action = 'created';
        }
        
        $existingDetail = DetailAnalyse::where('analyse_id', $analyseToUse->id)->first();
        
        if ($existingDetail) {
            $existingDetail->donnees = $request->details;
            $existingDetail->save();
        } else {
            DetailAnalyse::create([
                'analyse_id' => $analyseToUse->id,
                'donnees' => $request->details
            ]);
        }
        
        $analyseToUse->load(['patient', 'departement', 'details']);
        $pdfData = null;
        $pdfError = null;
        
        try {
            $this->pdfService->setLogos(
                'images/logo.png',
                'images/logo-complet.png'
            );
            
            $pdfData = $this->pdfService->generateAnalysePdf($analyseToUse->id);
        } catch (\Exception $e) {
            $pdfError = $e->getMessage();
            \Log::error('Erreur génération PDF pour analyse ' . $analyseToUse->id . ': ' . $e->getMessage());
        }
        
        $response = [
            'message' => $this->getSuccessMessage($action),
            'analyse' => $analyseToUse,
            'action' => $action,
            'catalog_preserved' => true 
        ];
        
        if ($pdfData) {
            $response['pdf'] = $pdfData;
            $response['pdf_generated'] = true;
        } else {
            $response['pdf_generated'] = false;
            if ($pdfError) {
                $response['pdf_error'] = $pdfError;
            }
        }
        
        return response()->json($response, $action === 'created' ? 201 : 200);
    }

    private function getSuccessMessage($action)
    {
        switch ($action) {
            case 'assigned':
                return 'Patient assigné, détails ajoutés et PDF généré avec succès';
            case 'created':
                return 'Nouvelle analyse créée pour le patient avec détails et PDF généré';
            case 'updated':
                return 'Détails de l\'analyse mis à jour et PDF généré avec succès';
            default:
                return 'Opération effectuée avec succès';
        }
    }

    public function getDetails($analyseId)
    {
        $detail = DetailAnalyse::where('analyse_id', $analyseId)->first();
        
        if (!$detail) {
            return response()->json(['error' => 'Aucun détail trouvé pour cette analyse'], 404);
        }

        return response()->json([
            'analyse_id' => $analyseId,
            'details' => $detail->donnees
        ]);
    }

    public function showDetails(Analyse $analyse)
    {
        $details = DetailAnalyse::where('analyse_id', $analyse->id)->first();

        if (!$details) {
            return response()->json(['message' => 'Aucun détail trouvé pour cette analyse.'], 404);
        }

        $patient = $analyse->patient;
        
        return response()->json([
            'details' => $details,
            'patient' => $patient
        ]);
    }

    public function updateDetails(Request $request, Analyse $analyse)
    {
        $validator = Validator::make($request->all(), [
            'donnees' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $detail = $analyse->details;

        if ($detail) {
            $detail->donnees = $request->donnees;
            $detail->save();
        } else {
            $detail = new DetailAnalyse();
            $detail->analyse_id = $analyse->id;
            $detail->donnees = $request->donnees;
            $detail->save();
        }
    }

    public function destroy(Analyse $analyse)
    {
        $analyse->details()->delete();
        $analyse->delete();

        return response()->json(['message' => 'Analyse et détails supprimés avec succès']);
    }

    /**
     * ✅ MODIFIÉ : Amélioration de l'archivage
     */
    public function archiveAnalyse(Analyse $analyse)
    {
        // Vérifier si l'analyse est déjà archivée
        if ($analyse->archive) {
            return response()->json([
                'message' => 'Cette analyse est déjà archivée'
            ], 400);
        }

        $detail = $analyse->details;

        if (!$detail) {
            return response()->json([
                'message' => 'Aucun détail à archiver'
            ], 404);
        }

        // Créer l'archive
        AnalyseArchive::create([
            'analyse_id' => $analyse->id,
            'patient_id' => $analyse->patient_id,
            'donnees' => $detail->donnees,
            'pdf_path' => 'storage/pdfs/analyse_'.$analyse->id.'.pdf',
            'archived_at' => now(),
        ]);

        return response()->json([
            'message' => 'Analyse archivée avec succès'
        ], 200);
    }

    public function getAllAnalyses()
    {
        $archives = AnalyseArchive::with(['analyse.departement', 'patient'])
            ->orderBy('archived_at', 'desc')
            ->get();

        return response()->json([
            'archives' => $archives
        ]);
    }

  public function getArchivedAnalysePdf($archiveId)
    {
        try {
            $archive = AnalyseArchive::find($archiveId);
            
            if (!$archive) {
                return response()->json([
                    'error' => 'Archive non trouvée'
                ], 404);
            }

            // Vérifier si le PDF existe
            if ($archive->pdf_path && \Storage::exists($archive->pdf_path)) {
                $url = \Storage::url($archive->pdf_path);
                
                return response()->json([
                    'pdf_url' => $url,
                    'data' => [
                        'url' => $url,
                        'path' => $archive->pdf_path,
                        'filename' => basename($archive->pdf_path)
                    ]
                ]);
            }

            // Si le PDF n'existe pas, essayer de le régénérer depuis les données archivées
            $analyse = $archive->analyse;
            
            if (!$analyse) {
                return response()->json([
                    'error' => 'Analyse originale non trouvée'
                ], 404);
            }

            // Régénérer le PDF
            $this->pdfService->setLogos(
                'images/logo.png',
                'images/logo-complet.png'
            );
            
            $pdfData = $this->pdfService->generateAnalysePdf($analyse->id);
            
            return response()->json([
                'pdf_url' => $pdfData['url'],
                'data' => $pdfData
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur récupération PDF archive: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Erreur lors de la récupération du PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateDepartementPdf(Request $request, Departement $departement)
    {
        try {
            $patientId = $request->query('patient_id');
            
            // Valider le patient_id s'il est fourni
            if ($patientId) {
                $patient = \App\Models\Patient::find($patientId);
                if (!$patient) {
                    return response()->json([
                        'error' => 'Patient non trouvé'
                    ], 404);
                }
            }

            $this->pdfService->setLogos(
                'images/logo.png',
                'images/logo-complet.png'
            );
            
            $result = $this->pdfService->generateDepartementAnalysesPdf(
                $departement->id,
                $patientId
            );
            
            return response()->json([
                'success' => true,
                'message' => $patientId 
                    ? 'PDF généré pour toutes les analyses du patient dans ce département'
                    : 'PDF généré pour toutes les analyses du département',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF département: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getArchivedAnalyses()
    {
        $archives = AnalyseArchive::with([
            'analyse.departement',
            'patient'
        ])
        ->orderBy('archived_at', 'desc')
        ->get()
        ->map(function ($archive) {
            return [
                'id' => $archive->id, // ✅ Utiliser l'ID de l'ARCHIVE, pas de l'analyse
                'analyse_id' => $archive->analyse_id, // ID de l'analyse originale
                'nom' => $archive->analyse->nom ?? '—',
                'patient' => $archive->patient,
                'patient_code' => $archive->patient->code ?? '—',
                'patient_nom' => $archive->patient->nom ?? '—',
                'patient_prenom' => $archive->patient->prenom ?? '—',
                'patient_telephone' => $archive->patient->telephone ?? null,
                'donnees' => $archive->donnees,
                'pdf_path' => $archive->pdf_path,
                'departementNom' => $archive->analyse->departement->nom ?? '—',
                'departement_nom' => $archive->analyse->departement->nom ?? '—',
                'created_at' => $archive->analyse->created_at ?? null,
                'archived_at' => $archive->archived_at,
            ];
        });

        return response()->json($archives);
    }

    public function generatePdf($id)
    {
        try {
            $pdfService = new PdfService();
            
            $pdfService->setLogos(
                'images/logo.png',
                'images/logo-complet.png'
            );
            
            $result = $pdfService->generateAnalysePdf($id);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function showDepartementAnalysesDetails(Departement $departement)
    {
        $analyses = $departement->analyses()
            ->with(['details', 'patient'])
            ->whereDoesntHave('archive') // ✅ Exclure les archivées
            ->get();

        if ($analyses->isEmpty()) {
            return response()->json([
                'message' => 'Aucune analyse trouvée pour ce département.'
            ], 404);
        }

        $data = [
            'departement' => $departement->nom,
            'analyses' => $analyses->map(function ($analyse) {
                return [
                    'nom' => $analyse->nom,
                    'details' => $analyse->details ? $analyse->details->donnees : null,
                    'patient' => $analyse->patient ? [
                        'id' => $analyse->patient->id,
                        'nom' => $analyse->patient->nom,
                        'prenom' => $analyse->patient->prenom,
                        'date_naissance' => $analyse->patient->date_naissance,
                    ] : null
                ];
            })
        ];

        return response()->json($data);
    }
}