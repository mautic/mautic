<?php

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Controller\CampaignController;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Model\CampaignModel;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CampaignControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->requestStack = new RequestStack();
        $this->corePermissionsMock = $this->createMock(CorePermissions::class);
        $helperUserMock = $this->createMock(UserHelper::class);
        $this->translator = $this->createMock(Translator::class);
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
            new DateHelper(),
            $this->createMock(ManagerRegistry::class),
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testGetExportHeader(): void
    {
        $campaign        = $this->createMock(Campaign::class);
        $campaignNoEmail = $this->createMock(Campaign::class);

        $translator = $this->createMock(Translator::class);
        $translator->expects($this->exactly(7))
            ->method('trans')
            ->withConsecutive(
                ['mautic.lead.lead.thead.country'],
                ['mautic.lead.leads'],
                ['mautic.lead.lead.thead.country'],
                ['mautic.lead.leads'],
                ['mautic.email.graph.line.stats.sent'],
                ['mautic.email.graph.line.stats.read'],
                ['mautic.email.clicked']
            )
            ->willReturnOnConsecutiveCalls('Country', 'Contacts', 'Country', 'Contacts', 'Sent', 'Read', 'Clicked');

        $this->assertSame(['Country', 'Contacts'], $this->controller->getExportHeader($campaignNoEmail));
        $this->assertSame(['Country', 'Contacts', 'Sent', 'Read', 'Clicked'], $this->controller->getExportHeader($campaign));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    public function testExportAction(): void
    {
        $campaign = $this->createMock(Campaign::class);

        $campaignModelMock       = $this->createMock(CampaignModel::class);
        $campaignModelMock->expects($this->once())
            ->method('getEntity')
            ->with($campaign->getId())
            ->willReturn($campaign);

        $exportHelper = $this->createMock(ExportHelper::class);
        $exportHelper->expects($this->exactly(0))
            ->method('exportDataAs')
            ->willReturn(new StreamedResponse());

        try {
            $this->client->request('GET', '/email/countries-stats/export/'.$campaign->getId().'/csv');
        } catch (NotFoundHttpException|\Exception $e) {
            $this->assertTrue($e instanceof NotFoundHttpException);
        }

        $this->fail();
    }
}
