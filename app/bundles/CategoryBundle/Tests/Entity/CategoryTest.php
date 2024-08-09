<?php

namespace Mautic\CategoryBundle\Tests\Entity;

use Mautic\CategoryBundle\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryUpdatesReflectsInChanges(): void
    {
        $category = new Category();
        $category->setTitle('New Category');
        $category->setAlias('category');
        $category->setBundle('bundle');

        $category->setTitle('Title Changed of Category');
        $category->setAlias('changed alias of category');
        $category->setBundle('campaigns');
        $category->setColor('Blue');
        $category->setDescription('My Description');

        $this->assertIsArray($category->getChanges());
        $this->assertNotEmpty($category->getChanges());
    }
}
