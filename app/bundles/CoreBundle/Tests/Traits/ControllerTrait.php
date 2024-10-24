<?php

namespace Mautic\CoreBundle\Tests\Traits;

use Mautic\PageBundle\Tests\Controller\PageControllerTest;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

trait ControllerTrait
{
    protected function getControllerColumnTests(
        string $urlAlias,
        string $routeAlias,
        string $column,
        string $tableAlias,
        string $column2
    ): void {
        $crawler         = $this->client->request('GET', '/s/'.$urlAlias);
        $clientResponse  = $this->client->getResponse();
        $responseContent = $clientResponse->getContent();
        PageControllerTest::assertTrue($clientResponse->isOk());

        PageControllerTest::assertStringContainsString(
            'col-'.$routeAlias.'-dateAdded',
            $responseContent,
            'The return must contain the created at date column'
        );
        PageControllerTest::assertStringContainsString(
            'col-'.$routeAlias.'-'.$column,
            $responseContent,
            'The return must contain the modified date column'
        );

        PageControllerTest::assertEquals(
            1,
            $crawler->filterXPath(
                "//th[contains(@class,'col-".$routeAlias.'-'.$column."')]//i[contains(@class, 'ri-sort-desc')]"
            )->count(),
            'The order must be desc'
        );

        $crawler = $this->client->request(
            'GET',
            '/s/'.$urlAlias.'?tmpl=list&name='.$routeAlias.'&orderby='.$tableAlias.$column
        );
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        PageControllerTest::assertEquals(
            1,
            $crawler->filterXPath(
                "//th[contains(@class,'col-".$routeAlias.'-'.$column."')]//i[contains(@class, 'ri-sort-asc')]"
            )->count(),
            'The order must be asc'
        );

        $crawler = $this->client->request(
            'GET',
            '/s/'.$urlAlias.'?tmpl=list&name='.$routeAlias.'&orderby='.$tableAlias.$column2
        );
        PageControllerTest::assertEquals(
            1,
            $crawler->filterXPath(
                "//th[contains(@class,'col-".$routeAlias.'-'.$column2."')]//i[contains(@class, 'ri-sort-asc')]"
            )->count(),
            'The order must be asc'
        );

        $crawler = $this->client->request(
            'GET',
            '/s/'.$urlAlias.'?tmpl=list&name='.$routeAlias.'&orderby='.$tableAlias.$column2
        );
        PageControllerTest::assertEquals(
            1,
            $crawler->filterXPath(
                "//th[contains(@class,'col-".$routeAlias.'-'.$column2."')]//i[contains(@class, 'ri-sort-desc')]"
            )->count(),
            'The order must be desc'
        );
    }
}
