<?php

namespace Mautic\PageBundle\Tests\Controller;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\BrowserKit\Client;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PageControllerFunctionalTest.
 */
class PageControllerFunctionalTest extends MauticWebTestCase
{
    public function testPageRedirection()
    {
        //create landing page
        $date       = (new \DateTime())->format('Y-m-d H:i:s');
        $pageObject = new Page();
        $pageObject->setIsPublished(false);
        $pageObject->getDateAdded($date);
        $pageObject->setTitle('Page:Page:Redirection');
        $pageObject->setAlias('page-page-redirection');
        $pageObject->setTemplate('Blank');
        $pageObject->setCustomHtml('Test Html');
        $pageObject->setLanguage('en');
        $pageObject->setRedirectType(301);
        $pageObject->setRedirectUrl('https://www.google.com/');
        $this->em->persist($pageObject);
        $this->em->flush();

        $client = $this->getClient();
        // list landing page
        $crawler = $client->request(Request::METHOD_GET, '/s/pages');

        // check added landing page is listed or not
        $this->assertStringContainsString('Page:Page:Redirection (page-page-redirection)', $crawler->filterXPath('//*[@id="pageTable"]/tbody/tr[1]/td[2]/a')->text());

        // check page content if logged-in user accessed the landing page
        $redirectPageContent = $client->request(Request::METHOD_GET, '/page-page-redirection');
        $this->assertStringContainsString('Test Html', $redirectPageContent->text());

        // Open new browser and visit the landing page. It should now be redirected as per the configuration.
        $landingPageClient   = $this->getClient();
        $redirectPageContent = $landingPageClient->request(Request::METHOD_GET, '/page-page-redirection');
        $this->assertStringContainsString('Google', $redirectPageContent->text());
    }
}
