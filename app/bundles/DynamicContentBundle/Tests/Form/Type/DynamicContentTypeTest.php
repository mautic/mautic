<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Form\Type;

use DeviceDetector\Parser\Device\AbstractDeviceParser as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Doctrine\ORM\EntityManager;
use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Form\Type\DynamicContentListType;
use Mautic\DynamicContentBundle\Form\Type\DynamicContentType;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class DynamicContentTypeTest extends TestCase
{
    public function testFormBuild(): void
    {
        $entityManagerMock       = $this->createMock(EntityManager::class);
        $listModelMock           = $this->createMock(ListModel::class);
        $translatorInterfaceMock = $this->createMock(TranslatorInterface::class);
        $leadModelMock           = $this->createMock(LeadModel::class);

        $listModelMock->expects($this->once())
            ->method('getChoiceFields')
            ->willReturn($this->getMockChoiceFields());

        $leadRepositoryMock = $this->createMock(LeadRepository::class);

        $leadModelMock->expects($this->once())
            ->method('getRepository')
            ->willReturn($leadRepositoryMock);

        $leadRepositoryMock->expects($this->once())
            ->method('getCustomFieldList')
            ->with('lead')
            ->willReturn($this->getMockCustomFieldList());

        $tags = $this->getMockTagList();
        $leadModelMock->expects($this->once())
            ->method('getTagList')
            ->willReturn($tags);

        $dynamicContentType = new DynamicContentType(
            $entityManagerMock,
            $listModelMock,
            $translatorInterfaceMock,
            $leadModelMock,
            new TypeList(),
        );

        $formBuilderInterfaceMock = $this->createMock(FormBuilderInterface::class);
        $options['data']          = new DynamicContent();

        $tagChoices = [];

        foreach ($tags as $tag) {
            $tagChoices[$tag['value']] = $tag['label'];
        }
        $matcher = $this->exactly(2);

        $formBuilderInterfaceMock->expects($matcher)
            ->method('create')->willReturnCallback(function (...$parameters) use ($matcher, $tagChoices, $formBuilderInterfaceMock) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('translationParent', $parameters[0]);
                    $this->assertSame(DynamicContentListType::class, $parameters[1]);
                    $this->assertSame([
                        'label'       => 'mautic.core.form.translation_parent',
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.core.form.translation_parent.help',
                        ],
                        'required'    => false,
                        'multiple'    => false,
                        'placeholder' => 'mautic.core.form.translation_parent.empty',
                        'top_level'   => 'translation',
                        'ignore_ids'  => [0 => 0],
                    ], $parameters[2]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('filters', $parameters[0]);
                    $this->assertSame(CollectionType::class, $parameters[1]);
                    $this->assertSame([
                        'entry_type'     => \Mautic\DynamicContentBundle\Form\Type\DwcEntryFiltersType::class,
                        'entry_options'  => [
                            'countries'    => FormFieldHelper::getCountryChoices(),
                            'regions'      => FormFieldHelper::getRegionChoices(),
                            'timezones'    => FormFieldHelper::getTimezonesChoices(),
                            'locales'      => FormFieldHelper::getLocaleChoices(),
                            'fields'       => $this->getMockChoiceFields(),
                            'deviceTypes'  => array_combine(
                                DeviceParser::getAvailableDeviceTypeNames(),
                                DeviceParser::getAvailableDeviceTypeNames()
                            ),
                            'deviceBrands' => DeviceParser::$deviceBrands,
                            'deviceOs'     => array_combine(
                                array_keys(OperatingSystem::getAvailableOperatingSystemFamilies()),
                                array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())
                            ),
                            'tags'         => $tagChoices,
                        ],
                        'error_bubbling' => false,
                        'mapped'         => true,
                        'allow_add'      => true,
                        'allow_delete'   => true,
                    ], $parameters[2]);
                }

                return $formBuilderInterfaceMock;
            });
        $matcher = $this->exactly(3);

        $formBuilderInterfaceMock->expects($matcher)
            ->method('addEventListener')->willReturnCallback(function (...$parameters) use ($matcher, $formBuilderInterfaceMock) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SUBMIT, $parameters[0]);
                    $callback = function ($listener) {
                        $reflection = new \ReflectionFunction($listener);
                        $parameters = $reflection->getParameters();

                        return FormEvent::class === (string) $parameters[0]->getType();
                    };
                    $this->assertTrue($callback($parameters[1]));
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SET_DATA, $parameters[0]);
                    $callback = function ($listener) {
                        $reflection = new \ReflectionFunction($listener);
                        $parameters = $reflection->getParameters();

                        return FormEvent::class === (string) $parameters[0]->getType();
                    };
                    $this->assertTrue($callback($parameters[1]));
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::POST_SUBMIT, $parameters[0]);
                    $callback = function ($listener) {
                        $reflection = new \ReflectionFunction($listener);
                        $parameters = $reflection->getParameters();

                        return FormEvent::class === (string) $parameters[0]->getType();
                    };
                    $this->assertTrue($callback($parameters[1]));
                }

                return $formBuilderInterfaceMock;
            });

        $formBuilderInterfaceMock->expects($this->once())
            ->method('get')
            ->with('type')
            ->willReturn($formBuilderInterfaceMock);

        $dynamicContentType->buildForm($formBuilderInterfaceMock, $options);
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function getMockChoiceFields(): array
    {
        return [
            'lead' => [
                'email' => [
                    'label'      => 'Email',
                    'properties' => ['type' => 'email'],
                    'object'     => 'lead',
                    'operators'  => [
                        'equals'      => '=',
                        'not equal'   => '!=',
                        'empty'       => 'empty',
                        'not empty'   => '!empty',
                        'like'        => 'like',
                        'not like'    => '!like',
                        'regexp'      => 'regexp',
                        'not regexp'  => '!regexp',
                        'starts with' => 'startsWith',
                        'ends with'   => 'endsWith',
                        'contains'    => 'contains',
                    ],
                ],
                'firstname' => [
                    'label'      => 'First Name',
                    'properties' => ['type' => 'text'],
                    'object'     => 'lead',
                    'operators'  => [
                        'equals'      => '=',
                        'not equal'   => '!=',
                        'empty'       => 'empty',
                        'not empty'   => '!empty',
                        'like'        => 'like',
                        'not like'    => '!like',
                        'regexp'      => 'regexp',
                        'not regexp'  => '!regexp',
                        'starts with' => 'startsWith',
                        'ends with'   => 'endsWith',
                        'contains'    => 'contains',
                    ],
                ],
                'lastname'  => [
                    'label'      => 'Last Name',
                    'properties' => ['type' => 'text'],
                    'object'     => 'lead',
                    'operators'  => [
                        'equals'      => '=',
                        'not equal'   => '!=',
                        'empty'       => 'empty',
                        'not empty'   => '!empty',
                        'like'        => 'like',
                        'not like'    => '!like',
                        'regexp'      => 'regexp',
                        'not regexp'  => '!regexp',
                        'starts with' => 'startsWith',
                        'ends with'   => 'endsWith',
                        'contains'    => 'contains',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, array<string,string|null>|string>>
     */
    private function getMockCustomFieldList(): array
    {
        return [
            [
                'firstname' => [
                    'id'            => '2',
                    'label'         => 'First Name',
                    'alias'         => 'firstname',
                    'type'          => 'text',
                    'group'         => 'core',
                    'object'        => 'lead',
                    'is_fixed'      => '1',
                    'properties'    => 'a:0:{}',
                    'default_value' => null,
                ],
                'lastname'  => [
                    'id'            => '3',
                    'label'         => 'Last Name',
                    'alias'         => 'lastname',
                    'type'          => 'text',
                    'group'         => 'core',
                    'object'        => 'lead',
                    'is_fixed'      => '1',
                    'properties'    => 'a:0:{}',
                    'default_value' => null,
                ],
                'email'     => [
                    'id'            => '6',
                    'label'         => 'Email',
                    'alias'         => 'email',
                    'type'          => 'email',
                    'group'         => 'core',
                    'object'        => 'lead',
                    'is_fixed'      => '1',
                    'properties'    => 'a:0:{}',
                    'default_value' => null,
                ],
            ],
            [
                'firstname' => 'firstname',
                'lastname'  => 'lastname',
                'email'     => 'email',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getMockTagList(): array
    {
        return [
            [
                'value' => '1',
                'label' => 't1',
            ],
            [
                'value' => '2',
                'label' => 't2',
            ],
            [
                'value' => '3',
                'label' => 't3',
            ],
        ];
    }
}
