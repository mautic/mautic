<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Pdf;

final class PagePreviewPdfGenerator
{
    public function generate(string $htmlContent): string
    {
        if (class_exists('Dompdf\\Dompdf')) {
            return $this->generateWithDompdf($htmlContent);
        }

        return $this->generateWithStdlib($htmlContent);
    }

    private function generateWithDompdf(string $htmlContent): string
    {
        $dompdfClass  = 'Dompdf\\Dompdf';
        $optionsClass = 'Dompdf\\Options';

        if (class_exists($optionsClass)) {
            $options = new $optionsClass();
            if (method_exists($options, 'set')) {
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
            }
            $dompdf = new $dompdfClass($options);
        } else {
            $dompdf = new $dompdfClass();
        }

        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function generateWithStdlib(string $htmlContent): string
    {
        $text = $this->normalizeTextFromHtml($htmlContent);

        if ('' === $text) {
            $text = 'No content available';
        }

        $lines = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line) {
                $lines[] = '';
                continue;
            }

            foreach (explode("\n", wordwrap($line, 95, "\n", true)) as $wrappedLine) {
                $lines[] = $wrappedLine;
            }
        }

        $lineLimit = 52;
        if (count($lines) > $lineLimit) {
            $lines = array_slice($lines, 0, $lineLimit);
        }

        $stream = $this->buildTextStream($lines);

        return $this->buildSinglePagePdf($stream);
    }

    private function normalizeTextFromHtml(string $htmlContent): string
    {
        $withLineBreaks = preg_replace('/<\s*br\s*\/?>/i', "\n", $htmlContent) ?? $htmlContent;
        $withLineBreaks = preg_replace('/<\/(p|div|h[1-6]|li|tr|table)>/i', "\n", $withLineBreaks) ?? $withLineBreaks;
        $plainText      = html_entity_decode(strip_tags($withLineBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText      = preg_replace('/\R{3,}/', "\n\n", $plainText) ?? $plainText;

        return trim($plainText);
    }

    /**
     * @param array<int, string> $lines
     */
    private function buildTextStream(array $lines): string
    {
        $commands = [
            'BT',
            '/F1 11 Tf',
            '50 792 Td',
        ];

        $lineIndex = 0;
        foreach ($lines as $line) {
            if (0 !== $lineIndex) {
                $commands[] = '0 -14 Td';
            }

            if ('' !== $line) {
                $commands[] = '('.$this->escapePdfString($line).') Tj';
            }

            ++$lineIndex;
        }

        $commands[] = 'ET';

        return implode("\n", $commands)."\n";
    }

    private function escapePdfString(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    private function buildSinglePagePdf(string $stream): string
    {
        $objects   = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Count 1 /Kids [3 0 R] >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream\nendobj\n";

        $pdf     = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";

        for ($index = 1; $index <= 5; ++$index) {
            $pdf .= sprintf('%010d 00000 n '."\n", $offsets[$index]);
        }

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }
}
