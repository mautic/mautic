<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Bounce\Mapper;

use Mautic\EmailBundle\MonitoredEmail\Exception\CategoryNotFound;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category as Definition;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\CategoryMapper;

#[\PHPUnit\Framework\Attributes\CoversClass(CategoryMapper::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(Definition::class)]
class CategoryMapperTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that the Category object is returned')]
    public function testCategoryIsMapped(): void
    {
        $category = CategoryMapper::map(Definition::ANTISPAM);

        $this->assertSame(Definition::ANTISPAM, $category->getCategory());
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that exception is thrown if a category is not found')]
    public function testExceptionIsThrownWithUnrecognizedCategory(): void
    {
        $this->expectException(CategoryNotFound::class);

        CategoryMapper::map('bippitybop');
    }
}
