<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\HttpFoundation\Request;

final class AjaxControllerTest extends MauticMysqlTestCase
{
    public function testGetIntegrationFieldsAcceptsPostedPayloadAndStoresPageInSession(): void
    {
        $integrationSettings = new Integration();
        $integrationSettings->setFeatureSettings([]);

        $integration = $this->createMock(AbstractIntegration::class);
        $integration->expects($this->once())
            ->method('getFormLeadFields')
            ->willReturn([
                'Email' => [
                    'label' => 'Email',
                    'type'  => 'email',
                ],
            ]);
        $integration->expects($this->once())
            ->method('getIntegrationSettings')
            ->willReturn($integrationSettings);
        $integration->expects($this->once())
            ->method('getDataPriority')
            ->willReturn(true);

        $helper = $this->createMock(IntegrationHelper::class);
        $helper->expects($this->once())
            ->method('getIntegrationObject')
            ->with('Salesforce')
            ->willReturn($integration);

        self::getContainer()->set('mautic.helper.integration', $helper);
        self::getContainer()->set(IntegrationHelper::class, $helper);

        $this->client->request(
            Request::METHOD_POST,
            '/s/ajax?action=plugin:getIntegrationFields',
            [
                'integration' => 'Salesforce',
                'settings'    => [
                    'object' => 'lead',
                ],
                'page'        => '3',
            ]
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['success']);

        $session = $this->client->getRequest()->getSession();
        $this->assertSame('3', $session->get('mautic.plugin.Salesforce.lead.page'));
    }
}
