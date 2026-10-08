<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Entity;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class CommonRepositorySimpleListTest extends MauticMysqlTestCase
{
    /**
     * A filtered lookup binds its search term as a scalar, which is the case that has to
     * name a parameter type: the builder's token search and the entity lookup fields both
     * arrive here through BuilderTokenHelper::getTokens() and EntityLookupChoiceLoader.
     */
    public function testSimpleListAcceptsAScalarParameter(): void
    {
        foreach (['Alpha category', 'Beta category'] as $title) {
            $category = new Category();
            $category->setTitle($title);
            $category->setAlias(strtolower(str_replace(' ', '-', $title)));
            $category->setBundle('page');
            $this->em->persist($category);
        }

        $this->em->flush();
        $this->em->clear();

        $repository = self::getContainer()->get(CategoryRepository::class);
        $this->assertInstanceOf(CategoryRepository::class, $repository);

        $expressionBuilder = $this->connection->createExpressionBuilder();
        $expression        = $expressionBuilder->and(
            $expressionBuilder->like('LOWER(title)', ':label')
        );

        $list = $repository->getSimpleList($expression, ['label' => 'alpha%'], 'title');

        $this->assertCount(1, $list);
        $this->assertSame('Alpha category', $list[0]['label']);
    }
}
