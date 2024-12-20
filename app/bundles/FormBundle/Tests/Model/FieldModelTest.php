<?php

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FieldModelTest extends TestCase
{
    public function testGenerateAlias(): void
    {
        $connection = $this->createMock(Connection::class);

        $platform = new class {
            public function getReservedKeywordsList(): object
            {
                return new class {
                    public function isKeyword(): void
                    {
                    }
                };
            }

            public function isKeyword(): void
            {
            }
        };

        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $leadFieldModel = $this->createMock(\Mautic\LeadBundle\Model\FieldModel::class);
        $entityManager  = $this->createMock(EntityManager::class);
        $fieldModel     = new FieldModel(
            $leadFieldModel,
            $entityManager,
            $this->createMock(CorePermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(RequestStack::class),
        );

        $entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $aliases = [
            'existed_alias',
            'existed_alias_with_space',
        ];

        $strings = [
            'existed_alias1'            => 'existed alias',
            'not_existed'               => 'not existed',
            'existed_alias_with_space1' => 'existed alias with space',
            'alias_test'                => 'alias test',
        ];

        foreach ($strings as $expected => $string) {
            $alias = $fieldModel->generateAlias($string, $aliases);
            $this->assertEquals($expected, $alias);
        }
    }
}
