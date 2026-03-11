<?php

namespace Modules\Contratos\Services;

use SimpleXMLElement;

class PlacspParser
{
    private const NS_ATOM = 'http://www.w3.org/2005/Atom';
    private const NS_CBC = 'urn:dgpe:names:draft:codice:schema:xsd:CommonBasicComponents-2';
    private const NS_CAC = 'urn:dgpe:names:draft:codice:schema:xsd:CommonAggregateComponents-2';
    private const NS_CAC_EXT = 'urn:dgpe:names:draft:codice-place-ext:schema:xsd:CommonAggregateComponents-2';
    private const NS_CBC_EXT = 'urn:dgpe:names:draft:codice-place-ext:schema:xsd:CommonBasicComponents-2';

    public function parseAtomFile(string $xmlContent): array
    {
        $xml = new SimpleXMLElement($xmlContent);
        $contracts = [];

        foreach ($xml->children(self::NS_ATOM)->entry as $entry) {
            try {
                $parsed = $this->parseEntry($entry);
                if ($parsed) {
                    $contracts[] = $parsed;
                }
            } catch (\Throwable $e) {
                logger()->warning('Error parsing PLACSP entry', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $contracts;
    }

    protected function parseEntry(SimpleXMLElement $entry): ?array
    {
        // Atom fields
        $atomChildren = $entry->children(self::NS_ATOM);
        $id = trim((string) $atomChildren->id);
        $title = trim((string) $atomChildren->title);
        $updated = trim((string) $atomChildren->updated);

        $link = '';
        foreach ($entry->link as $l) {
            $href = (string) ($l->attributes()['href'] ?? '');
            if ($href) {
                $link = $href;
                break;
            }
        }
        if (!$link) {
            foreach ($entry->children(self::NS_ATOM)->link as $l) {
                $href = (string) ($l->attributes()['href'] ?? '');
                if ($href) {
                    $link = $href;
                    break;
                }
            }
        }

        if (!$id) return null;

        $folder = $entry->children(self::NS_CAC_EXT)->ContractFolderStatus;
        if (!$folder || !$folder->count()) {
            return null;
        }

        $data = [
            'external_id' => $id,
            'link' => $link ?: null,
            'updated_at_source' => $updated ?: null,
        ];

        // Expediente
        $cbcChildren = $folder->children(self::NS_CBC);
        $data['expediente'] = trim((string) $cbcChildren->ContractFolderID) ?: mb_substr($title, 0, 490);

        // Estado
        $cbcExtChildren = $folder->children(self::NS_CBC_EXT);
        $data['status_code'] = trim((string) $cbcExtChildren->ContractFolderStatusCode) ?: 'PUB';

        // Órgano de contratación
        $cacExtChildren = $folder->children(self::NS_CAC_EXT);
        $locatedParty = $cacExtChildren->LocatedContractingParty;

        $organoData = [];
        if ($locatedParty && $locatedParty->count()) {
            $party = $locatedParty->children(self::NS_CAC)->Party;
            if ($party && $party->count()) {
                $partyName = $party->children(self::NS_CAC)->PartyName;
                $data['organo_contratante'] = trim((string) $partyName->children(self::NS_CBC)->Name);

                // DIR3
                foreach ($party->children(self::NS_CAC)->PartyIdentification as $pid) {
                    $idEl = $pid->children(self::NS_CBC)->ID;
                    if ((string) $idEl['schemeName'] === 'DIR3') {
                        $data['organo_dir3'] = trim((string) $idEl);
                        break;
                    }
                }

                // Datos del organismo (dirección, contacto, web) - de ilicitaciones
                $postal = $party->children(self::NS_CAC)->PostalAddress;
                if ($postal && $postal->count()) {
                    $postalCbc = $postal->children(self::NS_CBC);
                    $organoData['direccion'] = trim((string) $postal->children(self::NS_CAC)->AddressLine?->children(self::NS_CBC)->Line) ?: null;
                    $organoData['provincia'] = trim((string) $postalCbc->CityName) ?: null;
                    $organoData['codigo_postal'] = trim((string) $postalCbc->PostalZone) ?: null;
                    $country = $postal->children(self::NS_CAC)->Country;
                    $organoData['pais'] = $country ? trim((string) $country->children(self::NS_CBC)->Name) : null;
                }

                $contact = $party->children(self::NS_CAC)->Contact;
                if ($contact && $contact->count()) {
                    $contactCbc = $contact->children(self::NS_CBC);
                    $organoData['contacto_nombre'] = trim((string) $contactCbc->Name) ?: null;
                    $organoData['contacto_telefono'] = trim((string) $contactCbc->Telephone) ?: null;
                    $organoData['contacto_fax'] = trim((string) $contactCbc->Telefax) ?: null;
                    $organoData['contacto_email'] = trim((string) $contactCbc->ElectronicMail) ?: null;
                }

                $organoData['sitio_web'] = trim((string) $party->children(self::NS_CBC)->WebsiteURI) ?: null;
            }

            // Órgano superior
            $parentParty = $locatedParty->children(self::NS_CAC_EXT)->ParentLocatedParty;
            if ($parentParty && $parentParty->count()) {
                $ppCac = $parentParty->children(self::NS_CAC);
                if ($ppCac->Party && $ppCac->Party->count()) {
                    $ppName = $ppCac->Party->children(self::NS_CAC)->PartyName;
                    $data['organo_superior'] = trim((string) $ppName->children(self::NS_CBC)->Name);
                }
            }
        }

        if (empty($data['organo_contratante'])) {
            $data['organo_contratante'] = '';
        }

        // Incluir datos del organismo en la respuesta
        if (! empty(array_filter($organoData, fn($v) => $v !== null))) {
            $data['_organismo_data'] = array_filter($organoData, fn($v) => $v !== null);
        }

        // ProcurementProject
        $project = $folder->children(self::NS_CAC)->ProcurementProject;
        if ($project && $project->count()) {
            $projCbc = $project->children(self::NS_CBC);
            $data['objeto'] = trim((string) $projCbc->Name) ?: $title;
            $data['tipo_contrato_code'] = trim((string) $projCbc->TypeCode) ?: null;

            $projCbcExt = $project->children(self::NS_CBC_EXT);
            $data['subtipo_contrato_code'] = trim((string) $projCbcExt->SubTypeCode) ?: null;

            // Budget
            $budget = $project->children(self::NS_CAC)->BudgetAmount;
            if ($budget && $budget->count()) {
                $budgetCbc = $budget->children(self::NS_CBC);
                $data['valor_estimado'] = $this->decimal($budgetCbc->EstimatedOverallContractAmount);
                $data['importe_con_iva'] = $this->decimal($budgetCbc->TotalAmount);
                $data['importe_sin_iva'] = $this->decimal($budgetCbc->TaxExclusiveAmount);
            }

            // CPV
            $cpvCodes = [];
            foreach ($project->children(self::NS_CAC)->RequiredCommodityClassification as $cpvClass) {
                $code = trim((string) $cpvClass->children(self::NS_CBC)->ItemClassificationCode);
                if ($code) $cpvCodes[] = $code;
            }
            if ($cpvCodes) $data['cpv_codes'] = $cpvCodes;

            // Location
            $location = $project->children(self::NS_CAC)->RealizedLocation;
            if ($location && $location->count()) {
                $locCbc = $location->children(self::NS_CBC);
                $data['comunidad_autonoma'] = trim((string) $locCbc->CountrySubentity) ?: null;
                $data['nuts_code'] = trim((string) $locCbc->CountrySubentityCode) ?: null;

                $addr = $location->children(self::NS_CAC)->Address;
                if ($addr && $addr->count()) {
                    $data['lugar_ejecucion'] = trim((string) $addr->children(self::NS_CBC)->CityName) ?: null;
                }
            }

            // Duration
            $period = $project->children(self::NS_CAC)->PlannedPeriod;
            if ($period && $period->count()) {
                $periodCbc = $period->children(self::NS_CBC);
                $dur = $periodCbc->DurationMeasure;
                if (trim((string) $dur)) {
                    $data['duracion'] = (float) trim((string) $dur);
                    $data['duracion_unidad'] = (string) ($dur['unitCode'] ?? 'MON');
                }
                $data['fecha_inicio'] = $this->dateVal($periodCbc->StartDate);
                $data['fecha_fin'] = $this->dateVal($periodCbc->EndDate);
            }
        } else {
            $data['objeto'] = $title;
        }

        // TenderingProcess
        $process = $folder->children(self::NS_CAC)->TenderingProcess;
        if ($process && $process->count()) {
            $procCbc = $process->children(self::NS_CBC);
            $data['procedimiento_code'] = trim((string) $procCbc->ProcedureCode) ?: null;
            $data['urgencia_code'] = trim((string) $procCbc->UrgencyCode) ?: null;

            $deadline = $process->children(self::NS_CAC)->TenderSubmissionDeadlinePeriod;
            if ($deadline && $deadline->count()) {
                $data['fecha_presentacion_limite'] = $this->dateVal($deadline->children(self::NS_CBC)->EndDate);
            }
        }

        // TenderResult
        $result = $folder->children(self::NS_CAC)->TenderResult;
        if ($result && $result->count()) {
            $resCbc = $result->children(self::NS_CBC);
            $data['resultado_code'] = trim((string) $resCbc->ResultCode) ?: null;
            $data['fecha_adjudicacion'] = $this->dateVal($resCbc->AwardDate);
            $qty = trim((string) $resCbc->ReceivedTenderQuantity);
            if ($qty !== '') $data['num_ofertas'] = (int) $qty;

            // Winner
            $winner = $result->children(self::NS_CAC)->WinningParty;
            if ($winner && $winner->count()) {
                $wName = $winner->children(self::NS_CAC)->PartyName;
                $data['adjudicatario_nombre'] = trim((string) $wName->children(self::NS_CBC)->Name) ?: null;

                $wId = $winner->children(self::NS_CAC)->PartyIdentification;
                if ($wId && $wId->count()) {
                    $data['adjudicatario_nif'] = trim((string) $wId->children(self::NS_CBC)->ID) ?: null;
                }
            }

            // Awarded amount
            $awarded = $result->children(self::NS_CAC)->AwardedTenderedProject;
            if ($awarded && $awarded->count()) {
                $lmt = $awarded->children(self::NS_CAC)->LegalMonetaryTotal;
                if ($lmt && $lmt->count()) {
                    $lmtCbc = $lmt->children(self::NS_CBC);
                    $data['importe_adjudicacion_sin_iva'] = $this->decimal($lmtCbc->TaxExclusiveAmount);
                    $data['importe_adjudicacion_con_iva'] = $this->decimal($lmtCbc->PayableAmount);
                }
            }

            // Contract formalization date
            $contract = $result->children(self::NS_CAC)->Contract;
            if ($contract && $contract->count()) {
                $data['fecha_formalizacion'] = $this->dateVal($contract->children(self::NS_CBC)->IssueDate);
            }
        }

        return array_filter($data, fn($v) => $v !== null && $v !== '' && $v !== []);
    }

    private function decimal(SimpleXMLElement $el): ?float
    {
        $val = trim((string) $el);
        return $val !== '' ? (float) $val : null;
    }

    private function dateVal(SimpleXMLElement $el): ?string
    {
        $val = trim((string) $el);
        return $val !== '' ? $val : null;
    }
}
