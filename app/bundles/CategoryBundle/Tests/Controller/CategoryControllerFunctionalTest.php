<?php

namespace Mautic\CategoryBundle\Tests\Controller;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Create two new categories.
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $categoriesData = [
            [
                'title'  => 'TestTitleCategoryController1',
                'bundle' => 'page',
                ],
            [
                'title'  => 'TestTitleCategoryController2',
                'bundle' => 'global',
            ],
        ];
        /** @var CategoryModel $model */
        $model      = self::$container->get('mautic.category.model.category');

        foreach ($categoriesData as $categoryData) {
            $category = new Category();
            $category->setIsPublished(true)
                ->setTitle($categoryData['title'])
                ->setBundle($categoryData['bundle']);
            $model->saveEntity($category);
        }
    }

    /**
     * Get all results without filtering.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/categories?tmpl=list&bundle=category');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('TestTitleCategoryController1', $clientResponseContent, 'The return must contain TestTitleCategoryController1');
        $this->assertStringContainsString('TestTitleCategoryController2', $clientResponseContent, 'The return must contain TestTitleCategoryController2');
    }

    /**
     * Get a result with filter.
     */
    public function testIndexActionWhenFiltered(): void
    {
        $this->client->request('GET', '/s/categories/page?tmpl=list&bundle=page');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('TestTitleCategoryController1', $clientResponseContent, 'The return must contain TestTitleCategoryController1');
        $this->assertStringNotContainsString('TestTitleCategoryController2', $clientResponseContent, 'The return must not contain TestTitleCategoryController2');
    }

    public function testNewActionWithInForm()
    {
        $csrfToken = $this->getCsrfToken('category_form');

        $payload = ['category_form' => [
                'bundle'      => 'category',
                '_token'      => $csrfToken,
                'title'       => 'Test',
                'isPublished' => 1,
                'inForm'      => 1,
                'buttons'     => ['save' => 1],
            ],
        ];

        $this->client->request('POST', 's/categories/category/new', $payload);
        $clientResponse = $this->client->getResponse();
        $body           = json_decode($clientResponse->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertArrayHasKey('categoryId', $body);
        $this->assertArrayHasKey('categoryName', $body);
    }
}
