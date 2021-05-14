<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\Form\Type;

use Mautic\IntegrationsBundle\Exception\InvalidFormOptionException;
use Mautic\IntegrationsBundle\Form\Type\IntegrationSyncSettingsObjectFieldType;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class IntegrationSyncSettingsObjectFieldTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var IntegrationSyncSettingsObjectFieldType
     */
    private $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->form        = new IntegrationSyncSettingsObjectFieldType();
    }

    public function testBuildFormForWrongField(): void
    {
        $options = ['field' => 'unicorn'];
        $this->expectException(InvalidFormOptionException::class);
        $this->form->buildForm($this->formBuilder, $options);
    }

    public function testBuildFormForMappedField(): void
    {
        $field   = $this->createMock(MappedFieldInfoInterface::class);
        $options = [
            'field'        => $field,
            'placeholder'  => 'Placeholder ABC',
            'object'       => 'Object A',
            'integration'  => 'Integration A',
            'mauticFields' => [
                'mautic_field_a' => 'Mautic Field A',
                'mautic_field_b' => 'Mautic Field B',
            ],
        ];

        $field->method('showAsRequired')->willReturn(true);
        $field->method('getName')->willReturn('Integration Field A');
        $field->method('isBidirectionalSyncEnabled')->willReturn(false);
        $field->method('isToIntegrationSyncEnabled')->willReturn(true);
        $field->method('isToMauticSyncEnabled')->willReturn(true);

        $this->formBuilder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    'mappedField',
                    ChoiceType::class,
                    [
                        'label'          => false,
                        'choices'        => [
                            'Mautic Field A' => 'mautic_field_a',
                            'Mautic Field B' => 'mautic_field_b',
                        ],
                        'required'       => true,
                        'placeholder'    => '',
                        'error_bubbling' => false,
                        'attr'           => [
                            'class'            => 'form-control integration-mapped-field',
                            'data-placeholder' => $options['placeholder'],
                            'data-object'      => $options['object'],
                            'data-integration' => $options['integration'],
                            'data-field'       => 'Integration Field A',
                        ],
                    ],
                ],
                [
                    'syncDirection',
                    ChoiceType::class,
                    [
                        'choices' => [
                            'mautic.integration.sync_direction_integration' => ObjectMappingDAO::SYNC_TO_INTEGRATION,
                            'mautic.integration.sync_direction_mautic'      => ObjectMappingDAO::SYNC_TO_MAUTIC,
                        ],
                        'label'      => false,
                        'empty_data' => ObjectMappingDAO::SYNC_TO_INTEGRATION,
                        'attr'       => [
                            'class'            => 'integration-sync-direction',
                            'data-object'      => 'Object A',
                            'data-integration' => 'Integration A',
                            'data-field'       => 'Integration Field A',
                        ],
                    ],
                ]
            );

        $this->form->buildForm($this->formBuilder, $options);
    }
}
