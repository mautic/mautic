<?php

namespace Mautic\IntegrationsBundle\Tests\Unit;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractIntegrationTest extends TestCase
{
    public function testParseCallbackResponseWithUTF8StringThatContainsControlChars(): void
    {
        $integrationDouble = $this->buildAbstractIntegrationDouble();

        $jsonString = <<<JSON
        {
          "webinars": [
            {
              "topic": "【】 "
            }
          ]
        }
JSON;

        $json = $integrationDouble->parseCallbackResponse($jsonString);
        self::assertArrayHasKey('webinars', $json);
    }

    private function buildAbstractIntegrationDouble(): AbstractIntegration
    {
        // creating a double since we can't instantiate
        // we also need to expose some things for better unit test coverage
        return new class($this->createMock(EventDispatcherInterface::class), $this->createMock(CacheStorageHelper::class), $this->createMock(EntityManager::class), $this->createMock(RequestStack::class), $this->createMock(Router::class), $this->createMock(TranslatorInterface::class), $this->createMock(Logger::class), $this->createMock(EncryptionHelper::class), $this->createMock(LeadModel::class), $this->createMock(CompanyModel::class), $this->createMock(PathsHelper::class), $this->createMock(NotificationModel::class), $this->createMock(FieldModel::class), $this->createMock(IntegrationEntityModel::class), $this->createMock(DoNotContactModel::class), $this->createMock(FieldsWithUniqueIdentifier::class)) extends AbstractIntegration {
            public function getName(): string
            {
                return 'double';
            }

            public function getAuthenticationType(): string
            {
                return 'none';
            }
        };
    }
}
