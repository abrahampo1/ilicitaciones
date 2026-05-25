<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;

class LicitacionController extends Controller
{
    public function show($id)
    {
        $licitacion = cache()->remember("licitacion_{$id}", 1800, function () use ($id) {
            return Licitacion::with(['organismo', 'categoria', 'empresas'])->findOrFail($id);
        });

        $parsedData = $this->parseDatosRaiz($licitacion->datos_raiz);

        $analisis = cache()->remember("licitacion_analisis_{$id}", 1800, fn () => $licitacion->articles()
            ->published()->latest('published_at')->limit(5)->get());

        return view('licitacion', compact('licitacion', 'parsedData', 'analisis'));
    }

    private function parseDatosRaiz($datosRaiz): array
    {
        $datos = is_string($datosRaiz) ? json_decode($datosRaiz, true) : $datosRaiz;

        if (! $datos) {
            return [
                'statusCode' => null,
                'docs' => [],
                'criteria' => [],
                'duration' => null,
                'extensions' => null,
                'location' => null,
                'cpvs' => [],
                'financialSolvency' => null,
                'technicalSolvency' => [],
                'contact' => null,
                'deadline' => null,
            ];
        }

        $rawStatus = $datos['ContractFolderStatus']['ContractFolderStatusCode'] ?? null;
        $statusCode = is_array($rawStatus) ? ($rawStatus['value'] ?? $rawStatus['#text'] ?? $rawStatus[0] ?? null) : $rawStatus;

        // Extract documents
        $docs = [];
        $addDoc = function ($ref, $type, $label) use (&$docs) {
            if (! $ref) {
                return;
            }
            $items = isset($ref[0]) ? $ref : [$ref];
            foreach ($items as $item) {
                if (isset($item['Attachment']['ExternalReference']['URI'])) {
                    $docs[] = [
                        'type' => $type,
                        'label' => $label,
                        'name' => $item['Attachment']['ExternalReference']['FileName'] ?? $item['ID'] ?? 'Documento',
                        'url' => $item['Attachment']['ExternalReference']['URI'],
                    ];
                }
            }
        };

        if (isset($datos['ContractFolderStatus']['TechnicalDocumentReference'])) {
            $addDoc($datos['ContractFolderStatus']['TechnicalDocumentReference'], 'technical', 'Pliego Prescripciones Técnicas');
        }
        if (isset($datos['ContractFolderStatus']['LegalDocumentReference'])) {
            $addDoc($datos['ContractFolderStatus']['LegalDocumentReference'], 'legal', 'Pliego Cláusulas Administrativas');
        }
        if (isset($datos['ContractFolderStatus']['AdditionalDocumentReference'])) {
            $addDoc($datos['ContractFolderStatus']['AdditionalDocumentReference'], 'other', 'Documentación Adicional');
        }
        if (isset($datos['ContractFolderStatus']['GeneralDocument'])) {
            $generalDocs = $datos['ContractFolderStatus']['GeneralDocument'];
            if (isset($generalDocs['GeneralDocumentDocumentReference'])) {
                $addDoc($generalDocs['GeneralDocumentDocumentReference'], 'general', 'Documento General');
            } elseif (is_array($generalDocs)) {
                foreach ($generalDocs as $gDoc) {
                    if (isset($gDoc['GeneralDocumentDocumentReference'])) {
                        $addDoc($gDoc['GeneralDocumentDocumentReference'], 'general', 'Documento General');
                    }
                }
            }
        }

        // Extract Awarding Criteria
        $criteria = [];
        if (isset($datos['ContractFolderStatus']['TenderingTerms']['AwardingTerms']['AwardingCriteria'])) {
            $rawCriteria = $datos['ContractFolderStatus']['TenderingTerms']['AwardingTerms']['AwardingCriteria'];
            $criteria = isset($rawCriteria[0]) ? $rawCriteria : [$rawCriteria];
        }

        // Extract Duration
        $duration = null;
        $extensions = null;
        if (isset($datos['ContractFolderStatus']['ProcurementProject']['PlannedPeriod']['DurationMeasure'])) {
            $dur = $datos['ContractFolderStatus']['ProcurementProject']['PlannedPeriod']['DurationMeasure'];
            $unit = $dur['@attributes']['unitCode'] ?? '';
            $val = $dur['value'] ?? '';
            if ($val) {
                $unitMap = ['ANN' => 'Años', 'MON' => 'Meses', 'DAY' => 'Días'];
                $duration = $val.' '.($unitMap[$unit] ?? $unit);
            }
        }
        if (isset($datos['ContractFolderStatus']['ProcurementProject']['ContractExtension']['OptionValidityPeriod']['Description'])) {
            $extensions = $datos['ContractFolderStatus']['ProcurementProject']['ContractExtension']['OptionValidityPeriod']['Description'];
        }

        // Extract Location
        $location = null;
        if (isset($datos['ContractFolderStatus']['ProcurementProject']['RealizedLocation']['CountrySubentity'])) {
            $location = $datos['ContractFolderStatus']['ProcurementProject']['RealizedLocation']['CountrySubentity'];
        } elseif (isset($datos['ContractFolderStatus']['ProcurementProject']['RealizedLocation']['Address']['CityName'])) {
            $location = $datos['ContractFolderStatus']['ProcurementProject']['RealizedLocation']['Address']['CityName'];
        }

        // Extract CPVs
        $cpvs = [];
        if (isset($datos['ContractFolderStatus']['ProcurementProject']['RequiredCommodityClassification'])) {
            $rawCpvs = $datos['ContractFolderStatus']['ProcurementProject']['RequiredCommodityClassification'];
            $items = isset($rawCpvs[0]) ? $rawCpvs : [$rawCpvs];
            foreach ($items as $item) {
                if (isset($item['ItemClassificationCode']['value'])) {
                    $cpvs[] = $item['ItemClassificationCode']['value'];
                }
            }
        }

        // Extract Solvency
        $financialSolvency = null;
        $technicalSolvency = [];
        if (isset($datos['ContractFolderStatus']['TenderingTerms']['TendererQualificationRequest'])) {
            $qual = $datos['ContractFolderStatus']['TenderingTerms']['TendererQualificationRequest'];
            if (isset($qual['FinancialEvaluationCriteria']['Description'])) {
                $financialSolvency = $qual['FinancialEvaluationCriteria']['Description'];
            }
            if (isset($qual['TechnicalEvaluationCriteria'])) {
                $techItems = isset($qual['TechnicalEvaluationCriteria'][0]) ? $qual['TechnicalEvaluationCriteria'] : [$qual['TechnicalEvaluationCriteria']];
                foreach ($techItems as $item) {
                    if (isset($item['Description'])) {
                        $technicalSolvency[] = $item['Description'];
                    }
                }
            }
        }

        // Extract Contact Info
        $contact = null;
        if (isset($datos['ContractFolderStatus']['LocatedContractingParty']['Party']['Contact'])) {
            $contact = $datos['ContractFolderStatus']['LocatedContractingParty']['Party']['Contact'];
        }

        // Extract Submission Deadline
        $deadline = null;
        if (isset($datos['ContractFolderStatus']['TenderingProcess']['TenderSubmissionDeadlinePeriod'])) {
            $deadline = $datos['ContractFolderStatus']['TenderingProcess']['TenderSubmissionDeadlinePeriod'];
        }

        return compact(
            'statusCode', 'docs', 'criteria', 'duration', 'extensions',
            'location', 'cpvs', 'financialSolvency', 'technicalSolvency',
            'contact', 'deadline'
        );
    }
}
