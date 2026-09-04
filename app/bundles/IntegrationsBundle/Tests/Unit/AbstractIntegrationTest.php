<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit;

use Doctrine\ORM\EntityManager;
use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
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

final class AbstractIntegrationTest extends TestCase
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
        $this->assertArrayHasKey('webinars', $json);
    }

    private function buildAbstractIntegrationDouble(): AbstractIntegration
    {
        // creating a double since we can't instantiate
        // we also need to expose some things for better unit test coverage
        return new class($this->createStub(EventDispatcherInterface::class), $this->createStub(CacheProviderInterface::class), $this->createStub(EntityManager::class), $this->createStub(RequestStack::class), $this->createStub(Router::class), $this->createStub(TranslatorInterface::class), $this->createStub(Logger::class), $this->createStub(EncryptionHelper::class), $this->createStub(LeadModel::class), $this->createStub(CompanyModel::class), $this->createStub(PathsHelper::class), $this->createStub(NotificationModel::class), $this->createStub(FieldModel::class), $this->createStub(IntegrationEntityModel::class), $this->createStub(DoNotContactModel::class), $this->createStub(FieldsWithUniqueIdentifier::class), new IdentifyCompanyHelper($this->createStub(CompanyModel::class), $this->createStub(CompanyLeadRepository::class))) extends AbstractIntegration {
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
