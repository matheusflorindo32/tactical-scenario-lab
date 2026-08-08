<?php

namespace App\Reporting\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

final class ExecutionPdfRenderer
{
    public function render(array $report): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            view('reports.execution-pdf', ['report' => $report])->render(),
            'UTF-8',
        );
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
