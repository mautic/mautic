<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\PlainTextHelper;
use PHPUnit\Framework\TestCase;

class PlainTextHelperTest extends TestCase
{
    /**
     * @dataProvider emailContentProvider
     */
    public function testGetText(string $htmlContent, string $expectedPlainText): void
    {
        $plainTextHelper = new PlainTextHelper();
        $plainTextHelper->setHtml($htmlContent);
        $actualPlainText = $plainTextHelper->getText();

        $this->assertEquals($expectedPlainText, $actualPlainText);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function emailContentProvider(): array
    {
        return [
            // Test case 1: Simple paragraph
            [
                '<p>This is a simple paragraph.</p>',
                'This is a simple paragraph.',
            ],
            // Test case 2: Line breaks
            [
                '<p>This is line one.<br>This is line two.</p>',
                "This is line one.\nThis is line two.",
            ],
            // Test case 3: Links
            [
                '<p>Check this <a href="http://example.com">link</a>.</p>',
                'Check this link [http://example.com].',
            ],
            // Test case 4: Bold text
            [
                '<p>This is <strong>bold</strong> text.</p>',
                'This is bold text.',
            ],
            // Test case 5: Full html body
            [
                '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Email</title>
</head>
<body>
    <h1>Welcome to Our Newsletter</h1>
    <p>This is an example paragraph in our email content.</p>
    <!-- More HTML content here -->
</body>
</html>',
                "WELCOME TO OUR NEWSLETTER\n\nThis is an example paragraph in our email content.",
            ],
        ];
    }

    /**
     * @dataProvider getPreviewProvider
     */
    public function testGetPreview(?int $previewLength, string $htmlContent, string $expectedPlainText): void
    {
        $options = [];
        if ($previewLength) {
            $options['preview_length'] = $previewLength;
        }
        $plainTextHelper = new PlainTextHelper($options);
        $plainTextHelper->setHtml($htmlContent);
        $actualPlainText = $plainTextHelper->getPreview();

        $this->assertEquals($expectedPlainText, $actualPlainText);
    }

    /**
     * @return array<int, array<int, string|int|null>>
     */
    public function getPreviewProvider(): array
    {
        return [
            // Test case 1: Simple paragraph, with default options
            [
                null,
                '<p>This is a simple paragraph.</p>',
                'This is a simple paragraph.',
            ],
            // Test case 2: Simple paragraph, with length set to 10 (whitespace truncated)
            [
                10,
                '<p>This is a simple paragraph.</p>',
                'This is a...',
            ],
            // Test case 3: Full html body
            [
                25,
                '<h1>Welcome to Our Newsletter</h1>
    <p>This is an example paragraph in our email content.</p>',
                'WELCOME TO OUR NEWSLETTER...',
            ],
        ];
    }
}
