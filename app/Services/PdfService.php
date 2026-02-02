<?php

namespace App\Services;

use App\Models\Analyse;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private $leftLogoPath;
    private $rightLogoPath;

    public function __construct()
    {
        $this->leftLogoPath = 'images/image-logo.png';
        $this->rightLogoPath = 'images/logo-complet.png';
    }

    /**
     * Générer un PDF pour UNE SEULE analyse (existant)
     */
    public function generateAnalysePdf($analyseId)
    {
        $analyse = Analyse::with(['patient', 'departement', 'details'])->find($analyseId);

        if (!$analyse) {
            throw new \Exception('Analyse non trouvée');
        }

        if (!$analyse->details) {
            throw new \Exception('Aucun détail trouvé pour cette analyse');
        }

        $html = $this->generateHtml($analyse);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', public_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $storagePath = storage_path('app/public/pdfs');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filename = 'analyse_' . $analyseId . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $filePath = $storagePath . '/' . $filename;

        file_put_contents($filePath, $dompdf->output());

        return [
            'file_path' => $filePath,
            'filename' => $filename,
            'url' => url('storage/pdfs/' . $filename)
        ];
    }

    /**
     * ✅ NOUVEAU : Générer un PDF pour PLUSIEURS analyses d'un département
     * 
     * @param int $departementId - ID du département
     * @param int|null $patientId - ID du patient (optionnel)
     * @return array
     */
    public function generateDepartementAnalysesPdf($departementId, $patientId = null)
    {
        $query = Analyse::with(['patient', 'departement', 'details'])
            ->where('departement_id', $departementId)
            ->whereDoesntHave('archive'); // Exclure les archivées
        
        if ($patientId) {
            $query->where('patient_id', $patientId);
        }
        
        $analyses = $query->get();

        if ($analyses->isEmpty()) {
            throw new \Exception('Aucune analyse trouvée pour ce département');
        }

        // Récupérer le département et le patient
        $departement = \App\Models\Departement::find($departementId);
        $patient = $patientId ? \App\Models\Patient::find($patientId) : $analyses->first()->patient;

        $html = $this->generateMultiAnalysesHtml($analyses, $departement, $patient);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', public_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $storagePath = storage_path('app/public/pdfs');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filename = 'departement_' . $departementId . ($patientId ? '_patient_' . $patientId : '') . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $filePath = $storagePath . '/' . $filename;

        file_put_contents($filePath, $dompdf->output());

        return [
            'file_path' => $filePath,
            'filename' => $filename,
            'url' => url('storage/pdfs/' . $filename),
            'analyses_count' => $analyses->count()
        ];
    }

    /**
     * Générer le HTML pour UNE analyse (existant)
     */
    private function generateHtml($analyse)
    {
        $patient = $analyse->patient;
        $departement = $analyse->departement;
        $detailsData = $analyse->details->donnees ?? [];

        $leftLogoHtml = $this->getLogoHtml($this->leftLogoPath, 'LOGO CLINIQUE');
        $rightLogoHtml = $this->getLogoHtml($this->rightLogoPath, 'LOGO COMPLET');

        $html = $this->getHtmlHeader($leftLogoHtml, $rightLogoHtml, $patient);
        
        $html .= '
            <div class="results-section">
                <div class="results-header">
                    <div class="results-title">ANALYSE</div>
                    <div class="results-title">RÉSULTAT</div>
                    <div class="results-title">Intervalle de référence</div>
                    <div class="results-title">Antériorités</div>
                </div>
                
                <table class="results-table">
                    <tbody>
                        <tr class="section-row">
                            <th colspan="4">' . htmlspecialchars(  $departement->nom) . '</th>
                            <th colspan="4">' . htmlspecialchars($analyse->nom . ' - ' . $departement->nom) . '</th>
                        </tr>'
                        ;

        if (!empty($detailsData) && is_array($detailsData)) {
            foreach ($detailsData as $detail) {
                $nom = $detail['nom'] ?? 'Non spécifié';
                $resultat = $detail['resultat'] ?? 'N/A';
                $intervalle = $detail['intervalle'] ?? '';

                $html .= '
                        <tr>
                            <td class="parameter-name">' . htmlspecialchars($nom) . '</td>
                            <td class="normal-value">' . htmlspecialchars($resultat) . '</td>
                            <td class="reference-range">' . htmlspecialchars($intervalle) . '</td>
                            <td>-</td>
                        </tr>';
            }
        } else {
            $html .= '
                        <tr>
                            <td colspan="4" class="no-results">Aucun résultat d\'analyse disponible</td>
                        </tr>';
        }

        $html .= '
                    </tbody>
                </table>
            </div>';

        $html .= $this->getHtmlFooter();

        return $html;
    }

    /**
     * ✅ NOUVEAU : Générer le HTML pour PLUSIEURS analyses
     */
    private function generateMultiAnalysesHtml($analyses, $departement, $patient)
    {
        $leftLogoHtml = $this->getLogoHtml($this->leftLogoPath, 'LOGO CLINIQUE');
        $rightLogoHtml = $this->getLogoHtml($this->rightLogoPath, 'LOGO COMPLET');

        $html = $this->getHtmlHeader($leftLogoHtml, $rightLogoHtml, $patient);

        $html .= '
            <div class="results-section">
                <div class="results-header">
                    <div class="results-title">ANALYSE</div>
                    <div class="results-title">RÉSULTAT</div>
                    <div class="results-title">Intervalle de référence</div>
                    <div class="results-title">Antériorités</div>
                </div>';

        // ✅ Boucle sur TOUTES les analyses
        foreach ($analyses as $index => $analyse) {
            $detailsData = $analyse->details->donnees ?? [];
            
            $html .= '
                <table class="results-table">
                    <tbody>
                        <tr class="section-row">
                            <th colspan="4">' . htmlspecialchars($analyse->nom) . '</th>
                        </tr>';

            if (!empty($detailsData) && is_array($detailsData)) {
                foreach ($detailsData as $detail) {
                    $nom = $detail['nom'] ?? 'Non spécifié';
                    $resultat = $detail['resultat'] ?? 'N/A';
                    $intervalle = $detail['intervalle'] ?? '';

                    $html .= '
                        <tr>
                            <td class="parameter-name">' . htmlspecialchars($nom) . '</td>
                            <td class="normal-value">' . htmlspecialchars($resultat) . '</td>
                            <td class="reference-range">' . htmlspecialchars($intervalle) . '</td>
                            <td>-</td>
                        </tr>';
                }
            } else {
                $html .= '
                        <tr>
                            <td colspan="4" class="no-results">Aucun résultat disponible</td>
                        </tr>';
            }

            $html .= '
                    </tbody>
                </table>';

            // Séparateur entre analyses (sauf pour la dernière)
            if ($index < $analyses->count() - 1) {
                $html .= '<div style="margin: 15px 0;"></div>';
            }
        }

        $html .= '</div>';
        $html .= $this->getHtmlFooter();

        return $html;
    }

    /**
     * Générer l'en-tête HTML (réutilisable)
     */
    private function getHtmlHeader($leftLogoHtml, $rightLogoHtml, $patient)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rapport d\'Analyses - ' . htmlspecialchars($patient ? $patient->nom . ' ' . $patient->prenom : 'Analyses') . '</title>
            ' . $this->getStyles() . '
        </head>
        <body>
            <div class="header-container">
                <div class="header-top">
                    <div class="header-left-section">
                        ' . $leftLogoHtml . '
                    </div>
                    
                    <div class="header-center-section">
                        <div class="main-title">Laboratoire d\'analyses médicales-LF/Bg LABO</div>
                        <div class="contact-info"><strong>Tel :</strong> 78 500 31 01 ; <strong>Email :</strong> thiamcheikhidrissa@gmail.com</div>
                        <div class="contact-info"><strong>Horaires :</strong> Du lundi au lundi 24h/24h</div>
                        <div class="contact-info"><strong>Dr Cheikh Idrissa THIAM</strong> N° ONMS : <strong>B2246</strong></div>
                        <div class="contact-info">Bignona BP 31 RN 1, Près de la gare routière,</div>
                        <div class="contact-info">En face de la banque CBAO</div>
                    </div>
                    
                    <div class="header-right-section">
                        ' . $rightLogoHtml . '
                        <div class="complet-text">COMPLET</div>
                    </div>
                </div>
            </div>';

        if ($patient) {
            $html .= '
            <div class="patient-info-boxes">
                <div class="patient-box">
                    <div class="patient-detail">' . htmlspecialchars($patient->prenom . ' ' . $patient->nom . '-' . $patient->code) . '</div>
                    <div class="patient-detail">' . $patient->age . ' ans - ' . ($patient->sexe ?? 'Non spécifié') . ' - ' . htmlspecialchars($patient->adresse) . '</div>
                    <div class="patient-detail"><strong>Examen prescrit le:</strong> ' . Carbon::parse($patient->date_prescrit ?? now())->format('d/m/Y') . '</div>
                    <div class="patient-detail"><strong>Examen édité le:</strong> ' . Carbon::parse($patient->date_edite ?? now())->format('d/m/Y') . '</div>
                </div>
                <div class="patient-box">
                    <div class="patient-detail" style="text-align: center; font-weight: bold; font-size: 12px;">
                        ' . strtoupper(htmlspecialchars($patient->nom . ' ' . $patient->prenom)) . '
                    </div>
                    <div class="patient-detail" style="text-align: center; margin-top: 10px;">
                        <em>' . htmlspecialchars($patient->diagnostic ?? '') . '</em>
                    </div>
                </div>
            </div>';
        }

        return $html;
    }

    /**
     * Générer le pied de page HTML (réutilisable)
     */
    private function getHtmlFooter()
    {
        return '
            <div class="footer-section">
                <div style="margin-bottom: 50px;">Le Responsable</div>
            </div>
            
            <div style="margin-top: 30px; font-size: 10px; text-align: center; color: #666;">
                <p>Rapport généré le ' . Carbon::now()->format('d/m/Y à H:i') . '</p>
                <p>Laboratoire d\'Analyses Médicales - Tous droits réservés</p>
            </div>
        </body>
        </html>';
    }

    /**
     * Styles CSS (réutilisables)
     */
    private function getStyles()
    {
        return '<style>
                @page {
                    margin: 15mm;
                    size: A4;
                }
                
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 0; 
                    font-size: 12px;
                    line-height: 1.3;
                    color: #000;
                }
                
                .header-container {
                    width: 100%;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                
                .header-top {
                    display: table;
                    width: 100%;
                    margin-bottom: 5px;
                }
                
                .header-left-section {
                    display: table-cell;
                    width: 15%;
                    vertical-align: top;
                    text-align: center;
                }
                
                .header-center-section {
                    display: table-cell;
                    width: 70%;
                    vertical-align: top;
                    text-align: center;
                    padding: 0 10px;
                }
                
                .header-right-section {
                    display: table-cell;
                    width: 15%;
                    vertical-align: top;
                    text-align: center;
                }
                
                .logo-image {
                    width: 80px;
                    height: 80px;
                    object-fit: contain;
                    margin: 0 auto;
                    display: block;
                }
                
                .logo-placeholder {
                    width: 80px;
                    height: 80px;
                    border: 2px dashed #ccc;
                    margin: 0 auto;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    color: #666;
                    background-color: #f9f9f9;
                }
                
                .main-title {
                    font-weight: bold;
                    font-size: 16px;
                    margin-bottom: 8px;
                    text-transform: uppercase;
                }
                
                .contact-info {
                    font-size: 11px;
                    line-height: 1.4;
                    margin-bottom: 3px;
                }
                
                .contact-info strong {
                    font-weight: bold;
                }
                
                .complet-text {
                    font-weight: bold;
                    font-size: 18px;
                    margin-top: 5px;
                }
                
                .patient-info-boxes {
                    display: table;
                    width: 100%;
                    margin-bottom: 20px;
                }
                
                .patient-box {
                    display: table-cell;
                    width: 48%;
                    border: 2px solid #000;
                    padding: 10px;
                    margin-right: 4%;
                }
                
                .patient-box:last-child {
                    margin-right: 0;
                }
                
                .patient-detail {
                    margin-bottom: 3px;
                    font-size: 10px;
                }
                
                .results-section {
                    margin-top: 20px;
                }
                
                .results-header {
                    display: table;
                    width: 100%;
                    border: 2px solid #000;
                    background-color: #f0f0f0;
                    margin-bottom: 0;
                }
                
                .results-title {
                    display: table-cell;
                    width: 25%;
                    padding: 8px;
                    font-weight: bold;
                    font-size: 11px;
                    text-align: center;
                    border-right: 1px solid #000;
                }
                
                .results-title:last-child {
                    border-right: none;
                }
                
                .results-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    border: 2px solid #000; 
                    border-top: none;
                    table-layout: fixed;
                    margin-bottom: 15px;
                }
                
                .results-table th, .results-table td { 
                    padding: 6px 4px; 
                    border: 1px solid #000; 
                    font-size: 10px; 
                    text-align: center;
                    width: 25%;
                }
                
                .results-table th { 
                    background-color: #e0e0e0; 
                    font-weight: bold;
                }
                
                .section-row th {
                    background-color: #f0f0f0;
                    text-align: center;
                    font-weight: bold;
                    font-size: 12px;
                }
                
                .parameter-name {
                    text-align: left !important;
                    padding-left: 8px !important;
                    font-size: 10px;
                }
                
                .normal-value {
                    color: #000;
                }
                
                .abnormal-value {
                    color: #000;
                    font-weight: bold;
                }
                
                .reference-range {
                    font-size: 9px;
                }
                
                .footer-section {
                    margin-top: 30px;
                    text-align: right;
                    font-size: 11px;
                    font-weight: bold;
                }
                
                .no-results {
                    text-align: center;
                    padding: 20px;
                    font-style: italic;
                    color: #666;
                    border: 2px solid #000;
                }
            </style>';
    }

    public function setLogos($leftLogoPath = null, $rightLogoPath = null)
    {
        if ($leftLogoPath) {
            $this->leftLogoPath = $leftLogoPath;
        }
        if ($rightLogoPath) {
            $this->rightLogoPath = $rightLogoPath;
        }
    }

    private function getLogoHtml($logoPath, $alt = 'Logo')
    {
        $fullPath = public_path($logoPath);

        if (file_exists($fullPath)) {
            $imageData = file_get_contents($fullPath);
            $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
            $base64 = base64_encode($imageData);
            $mimeType = 'image/' . $imageType;

            return '<img src="data:' . $mimeType . ';base64,' . $base64 . '" alt="' . htmlspecialchars($alt) . '" class="logo-image">';
        }

        return '<div class="logo-placeholder">' . htmlspecialchars($alt) . '</div>';
    }
}