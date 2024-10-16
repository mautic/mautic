<?php

namespace Mautic\CoreBundle\Tests\Helper\Clickthrough;

use Mautic\CoreBundle\Helper\Clickthrough\ClickthroughKeyConverter;
use PHPUnit\Framework\TestCase;

class ClickthroughKeyConverterTest extends TestCase
{
    private ClickthroughKeyConverter $clickthroughKeyConverter;

    protected function setUp(): void
    {
        $this->clickthroughKeyConverter = new ClickthroughKeyConverter();
    }

    public function testPackUnpack(): void
    {
        $input = [
            'email'       => 'example@example.com',
            'source'      => 'newsletter',
            'stat'        => '12345',
            'lead'        => '123',
            'channel'     => 'email',
            'utmTags'     => [
                'utm_source'   => 'source_test',
                'utm_medium'   => 'medium_test',
                'utm_campaign' => 'campaign_test',
                'utm_content'  => 'content_test',
            ],
        ];

        $packed   = $this->clickthroughKeyConverter->pack($input);
        $unpacked = $this->clickthroughKeyConverter->unpack($packed);

        $this->assertSame($input, $unpacked);
    }
}
