<?php

use App\Http\Controllers\AnalyseController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques (sans authentification)
Route::post('register', [UserController::class, 'registerAdmin']);
Route::post('login', [UserController::class, 'login']);
Route::post('role', action: [Controller::class, 'createRole']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Route pour obtenir l'utilisateur connecté
    Route::get('/user', function (Request $request) {
    return $request->user();
});

    // Routes de déconnexion et profil
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('me', [UserController::class, 'me']);

    // Routes des départements
    Route::get('departements', [DepartementController::class, 'getAllDepartements']);
    Route::post('departement', [DepartementController::class, 'createDepartement']);

    // Routes des analyses
Route::get('patients/{patient}/analyses', [AnalyseController::class, 'index']);
Route::get('analyses', [AnalyseController::class, 'index']); // Alias propre
Route::post('/departements/{departement}/patients/{patient}/analyses', [AnalyseController::class, 'store']);
Route::get('/departements/{departement}/analyses', action: [DepartementController::class, 'showDepartementAnalyses']);

    // Nouvelle création d'analyse sans patient
    Route::post('/departements/{departement}/analyses', [AnalyseController::class, 'storeWithoutPatient']);
    Route::get('/analyses/without-patient', [AnalyseController::class, 'getWithoutPatient']);

    // Assigner un patient plus tard
    Route::post('/analyses/{analyse}/assign-patient', [AnalyseController::class, 'assignPatient']);
    Route::post('analyses/{analyse}/assign-patient-with-details', [AnalyseController::class, 'assignPatientWithDetails']);


    // Routes pour les détails d'analyse
    Route::post('analyse/{analyse}/details', [AnalyseController::class, 'storeDetails']);
    Route::get('analyse/{analyse}/details', [AnalyseController::class, 'getDetails']);
    Route::delete('analyse/{analyse}', [AnalyseController::class, 'destroy']);
    Route::post('analyse/{analyse}/archive', [AnalyseController::class, 'archiveAnalyse']);
    Route::get('analyse/archive', [AnalyseController::class, 'getAllAnalyses']);
    Route::get('/analyse/archive/{archiveId}/pdf', [AnalyseController::class, 'getArchivedAnalysePdf']);
    Route::get('/departements/{departement}/generate-pdf', 
    [AnalyseController::class, 'generateDepartementPdf']
);


    
    // NOUVELLE ROUTE POUR LE PDF
    Route::get('/analyse/{analyse}/pdf', [AnalyseController::class, 'generatePdf']);
    
    // Route pour récupérer les détails d'une analyse détaillée
    Route::get('/departements/{departement}/analyses/details', [AnalyseController::class, 'showDepartementAnalysesDetails']);
    Route::get('/departements/{departement}/analyses/catalog', [DepartementController::class, 'showDepartementAnalysesCatalog']);


    // Routes des patients
Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients-with-relations', [PatientController::class, 'indexWithRelations']);
Route::get('/patients/{id}', [PatientController::class, 'show']);
Route::post('/patients', [PatientController::class, 'store']);
Route::put('/patients/{id}', [PatientController::class, 'update']);
Route::delete('/patients/{id}', [PatientController::class, 'destroy']);
Route::get('/patients/{id}/analyses', [PatientController::class, 'showPatientAnalyses']);
});