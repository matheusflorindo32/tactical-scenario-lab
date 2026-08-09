<?php

namespace App\Http\Controllers;

use App\Models\ScenarioExecution;
use App\Reporting\ExecutionReportDataBuilder;
use App\Reporting\Pdf\ExecutionPdfRenderer;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExecutionReportController extends Controller
{
    public function __invoke(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
        ExecutionReportDataBuilder $builder,
        ExecutionPdfRenderer $renderer,
    ): Response {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::REPORTS_VIEW);
        abort_unless($execution->organization_id === $organizationId, 403);

        $pdf = $renderer->render($builder->build($execution, $organizationId));
        $filename = 'execution-'.$execution->uuid.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename='.$filename,
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
