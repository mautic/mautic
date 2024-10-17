<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Bounce\Mapper;

use Mautic\EmailBundle\MonitoredEmail\Exception\CategoryNotFound;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category as Definition;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\CategoryMapper;

class CategoryMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Test that the Category object is returned
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\CategoryMapper::map
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category
     */
    public function testCategoryIsMapped(): void
    {
        $category = CategoryMapper::map(Definition::ANTISPAM);

        $this->assertSame(Definition::ANTISPAM, $category->getCategory());
    }

    /**
     * @testdox Test that exception is thrown if a category is not found
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\CategoryMapper::map
     */
    public function testExceptionIsThrownWithUnrecognizedCategory(): void
    {
        $this->expectException(CategoryNotFound::class);

        CategoryMapper::map('bippitybop');
    }
}
