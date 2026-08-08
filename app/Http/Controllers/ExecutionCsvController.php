<?php

namespace App\Http\Controllers;

use App\Reporting\ExecutionCsvExporter;
use App\Reporting\InstitutionalFilter;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutionCsvController extends Controller
{
    public function __invoke(
        Request $request,
        ActiveOrganization $activeOrganization,
        ExecutionCsvExporter $exporter,
    ): StreamedResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::REPORTS_VIEW);
        $filter = InstitutionalFilter::fromRequest(
            $request,
            $organizationId,
            ['draft', 'running', 'completed', 'cancelled'],
        );

        return $exporter->stream($filter);
    }
}
