<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\FieldRepository;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class FieldModelTest extends TestCase
{
    public function testGenerateAlias(): void
    {
        $leadFieldModel = $this->createStub(\Mautic\LeadBundle\Model\FieldModel::class);
        $entityManager  = $this->createStub(EntityManager::class);
        $schemaHelper   = $this->createStub(ColumnSchemaHelper::class);
        $fieldModel     = new FieldModel(
            $entityManager,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
        );
        $fieldModel->autowireFieldModel(
            $leadFieldModel,
            $this->createStub(RequestStack::class),
            $schemaHelper,
            $this->createStub(FieldRepository::class)
        );

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
