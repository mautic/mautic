<?php

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Controller\CampaignController;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CampaignControllerTest extends MauticMysqlTestCase
{
    private MockObject|Translator $translator;

    private MockObject|DateHelper $dateHelper;

    private CampaignController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $helperUserMock             = $this->createMock(UserHelper::class);
        $this->translator           = $this->createMock(Translator::class);
        $this->dateHelper           = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->translator,
            $this->createMock(CoreParametersHelper::class)
        );

        $helperUserMock->method('getUser')
            ->willReturn(new User(false));

        $this->controller = new CampaignController(
            $this->createMock(FormFactory::class),
            $this->createMock(FormFieldHelper::class),
            $this->createMock(EventCollector::class),
            $this->dateHelper,
            $this->createMock(ManagerRegistry::class),
            // @phpstan-ignore-next-line
            $this->createMock(MauticFactory::class),
            $this->createMock(ModelFactory::class),
            $helperUserMock,
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->translator,
            $this->createMock(FlashBag::class),
            new RequestStack(),
            $this->createMock(CorePermissions::class),
            $this->createMock(ExportHelper::class)
        );
    }

    /**
     * Index should return status code 200.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/campaigns');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/campaigns?search=has%3Aresults&tmpl=list');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
    }

    /**
     * Get campaign's create page.
     */
    public function testNewActionCampaign(): void
    {
        $this->client->request('GET', '/s/campaigns/new/');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /**
     * Test cancelling new campaign does not give a 500 error.
     *
     * @see https://github.com/mautic/mautic/issues/11181
     */
    public function testNewActionCampaignCancel(): void
    {
        $crawler                = $this->client->request('GET', '/s/campaigns/new/');
        $clientResponse         = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        $form = $crawler->filter('form[name="campaign"]')->selectButton('campaign_buttons_cancel')->form();
        $this->client->submit($form);
        $clientResponse         = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
    }
}
