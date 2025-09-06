<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    private Environment $twig;
    private string $publicPath;

    public function __construct(Environment $twig, string $publicPath)
    {
        $this->twig = $twig;
        $this->publicPath = $publicPath;
    }

    public function generatePdf(string $template, array $data = [], string $filename = 'document.pdf'): string
    {
        // Configuration DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('chroot', $this->publicPath);

        $dompdf = new Dompdf($options);

        // Rendu du template
        $html = $this->twig->render($template, $data);

        // Génération du PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateTablePdf(string $title, array $headers, array $data, string $filename = 'table.pdf'): string
    {
        return $this->generatePdf('pdf/table.html.twig', [
            'title' => $title,
            'headers' => $headers,
            'data' => $data,
            'generatedAt' => new \DateTime()
        ], $filename);
    }

    public function generateLandscapeTablePdf(string $title, array $headers, array $data, array $filters = [], string $filename = 'table.pdf'): string
    {
        return $this->generateLandscapePdf('pdf/landscape_table.html.twig', [
            'title' => $title,
            'headers' => $headers,
            'data' => $data,
            'filters' => $filters,
            'generatedAt' => new \DateTime()
        ], $filename);
    }

    public function generateLandscapePdf(string $template, array $data = [], string $filename = 'document.pdf'): string
    {
        // Configuration DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('chroot', $this->publicPath);

        $dompdf = new Dompdf($options);

        // Rendu du template
        $html = $this->twig->render($template, $data);

        // Génération du PDF en paysage
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
