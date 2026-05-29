<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Pdf;

use Mautic\EmailBundle\Pdf\EmailPreviewPdfGenerator;
use PHPUnit\Framework\TestCase;

final class EmailPreviewPdfGeneratorTest extends TestCase
{
    public function testGenerateReturnsPdfContent(): void
    {
        $generator = new EmailPreviewPdfGenerator();
        $pdf       = $generator->generate('<html><body><h1>Email preview</h1><p>This is a PDF export test.</p></body></html>');

        self::assertStringStartsWith('%PDF', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
    }
}
