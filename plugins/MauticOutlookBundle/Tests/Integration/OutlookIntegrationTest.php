<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOutlookBundle\Tests\Integration;

use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticOutlookBundle\Integration\OutlookIntegration;

final class OutlookIntegrationTest extends AbstractIntegrationTestCase
{
    private OutlookIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new OutlookIntegration(
            $this->dispatcher,
            $this->cache,
            $this->em,
            $this->request,
            $this->router,
            $this->translator,
            $this->logger,
            $this->encryptionHelper,
            $this->leadModel,
            $this->companyModel,
            $this->pathsHelper,
            $this->notificationModel,
            $this->fieldModel,
            $this->integrationEntityModel,
            $this->doNotContact,
            $this->fieldsWithUniqueIdentifier,
        );
    }

    public function testGetNameReturnsOutlook(): void
    {
        $this->assertSame('Outlook', $this->integration->getName());
    }

    public function testGetAuthenticationTypeWillReturnNone(): void
    {
        $this->assertSame('none', $this->integration->getAuthenticationType());
    }

    public function testGetRequiredKeyFieldsContainsSerect(): void
    {
        $this->assertArrayHasKey('secret', $this->integration->getRequiredKeyFields());
    }

    public function testGetFormNotesWillReturnTheCorrectTemplate(): void
    {
        // set server globals
        // @see Mautic\CoreBundle\Helper\UrlHelper::rel2abs
        $_SERVER['SERVER_PROTOCOL'] = 'https';
        $_SERVER['SERVER_PORT']     = '80';
        $_SERVER['SERVER_NAME']     = 'localhost';
        $_SERVER['REQUEST_URI']     = '/';

        $formNotes = $this->integration->getFormNotes('custom');

        $this->assertArrayHasKey('template', $formNotes);
        $this->assertSame('@MauticOutlook/Integration/form.html.twig', $formNotes['template']);
    }
}
