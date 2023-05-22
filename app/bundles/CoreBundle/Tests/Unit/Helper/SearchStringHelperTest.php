<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\SearchStringHelper;

class SearchStringHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testNegativeGroup(): void
    {
        $result = SearchStringHelper::parseSearchString('email:!(test@example.%)');

        $this->assertArrayHasKey('email', $result->commands);
        $this->assertEquals('email', $result->root[0]->command);
        $this->assertEquals('test@example.%', $result->root[0]->string);
        $this->assertEquals(1, $result->root[0]->not);
    }
}
