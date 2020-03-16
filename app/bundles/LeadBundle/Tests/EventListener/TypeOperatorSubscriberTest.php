<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\EventListener\TypeOperatorSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\StageBundle\Entity\StageRepository;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class TypeOperatorSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|LeadModel
     */
    private $leadModel;

    /**
     * @var MockObject|ListModel
     */
    private $listModel;

    /**
     * @var MockObject|campaignModel
     */
    private $campaignModel;

    /**
     * @var MockObject|emailModel
     */
    private $emailModel;

    /**
     * @var MockObject|StageModel
     */
    private $stageModel;

    /**
     * @var MockObject|StageRepostory
     */
    private $stageRepository;

    /**
     * @var MockObject|CategoryModel
     */
    private $categoryModel;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|FormInterface
     */
    private $form;

    /**
     * @var TypeOperatorSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leadModel       = $this->createMock(LeadModel::class);
        $this->listModel       = $this->createMock(ListModel::class);
        $this->campaignModel   = $this->createMock(CampaignModel::class);
        $this->emailModel      = $this->createMock(EmailModel::class);
        $this->stageModel      = $this->createMock(StageModel::class);
        $this->stageRepository = $this->createMock(StageRepository::class);
        $this->categoryModel   = $this->createMock(CategoryModel::class);
        $this->translator      = $this->createMock(TranslatorInterface::class);
        $this->form            = $this->createMock(FormInterface::class);
        $this->subscriber      = new TypeOperatorSubscriber(
            $this->leadModel,
            $this->listModel,
            $this->campaignModel,
            $this->emailModel,
            $this->stageModel,
            $this->categoryModel,
            $this->translator
        );

        $this->stageModel->method('getRepository')->willReturn($this->stageRepository);
        $this->translator->method('trans')->willReturnArgument(0);
    }

    public function testOnTypeOperatorsCollect(): void
    {
        $event = new TypeOperatorsEvent();

        $this->subscriber->onTypeOperatorsCollect($event);

        $operators = $event->getOperatorsForAllFieldTypes();

        // Test for random operators:
        $this->assertContains('=', $operators['text']['include']);
        $this->assertNotContains('in', $operators['text']['include']);
        $this->assertContains('=', $operators['boolean']['include']);
        $this->assertNotContains('in', $operators['boolean']['include']);
        $this->assertContains('in', $operators['date']['exclude']);
        $this->assertNotContains('=', $operators['date']['exclude']);
        $this->assertContains('=', $operators['number']['include']);
        $this->assertNotContains('in', $operators['number']['include']);
        $this->assertContains('empty', $operators['country']['include']);
        $this->assertContains('in', $operators['country']['include']);
        $this->assertNotContains('startsWith', $operators['country']['include']);
    }

    public function testOnTypeListCollect(): void
    {
        $event = new ListFieldChoicesEvent();

        $this->campaignModel->expects($this->once())
            ->method('getPublishedCampaigns')
            ->with(true)
            ->willReturn([['name' => 'Campaign A', 'id' => 22]]);

        $this->listModel->expects($this->once())
            ->method('getUserLists')
            ->willReturn([['name' => 'Segment B', 'id' => 33]]);

        $this->leadModel->expects($this->once())
            ->method('getTagList')
            ->willReturn([['label' => 'Tag C', 'value' => 44]]);

        $this->stageRepository->expects($this->once())
            ->method('getSimpleList')
            ->willReturn([['label' => 'Stage D', 'value' => 55]]);

        $this->categoryModel->expects($this->once())
            ->method('getLookupResults')
            ->with('global')
            ->willReturn([['title' => 'Category E', 'id' => 66]]);

        $this->emailModel->expects($this->once())
            ->method('getLookupResults')
            ->with('email', '', 0, 0)
            ->willReturn(['Email F' => 77]);

        $this->subscriber->onTypeListCollect($event);

        $choicesForAliases = $event->getChoicesForAllListFieldAliases();
        $choicesForTypes   = $event->getChoicesForAllListFieldTypes();

        // Test for random choices:
        $this->assertSame(['Campaign A' => 22], $choicesForAliases['campaign']);
        $this->assertSame(['Segment B' => 33], $choicesForAliases['leadlist']);
        $this->assertSame(['Tag C' => 44], $choicesForAliases['tags']);
        $this->assertSame(['Stage D' => 55], $choicesForAliases['stage']);
        $this->assertSame(['Category E' => 66], $choicesForAliases['globalcategory']);
        $this->assertSame(['Email F' => 77], $choicesForAliases['lead_email_received']);
        $this->assertSame('smartphone', $choicesForAliases['device_type']['smartphone']);
        $this->assertSame('Samsung', $choicesForAliases['device_brand']['SA']);
        $this->assertSame('Android', $choicesForAliases['device_os']['Android']);
        $this->assertArrayHasKey('Europe', $choicesForTypes['timezone']);
        $this->assertArrayHasKey('France', $choicesForTypes['region']);
    }

    public function testOnSegmentFilterFormHandleTagsIfNotTag(): void
    {
        $alias    = 'unicorn';
        $object   = 'lead';
        $operator = '=';
        $details  = [];
        $event    = new FilterPropertiesTypeEvent($this->form, $alias, $object, $operator, $details);

        $this->form->expects($this->never())
            ->method('add');

        $this->subscriber->onSegmentFilterFormHandleTags($event);
    }

    public function testOnSegmentFilterFormHandleTagsIfTag(): void
    {
        $alias    = 'tags';
        $object   = 'lead';
        $operator = '=';
        $details  = [
            'properties' => [
                'list' => [
                    'Tag A' => 'Tag A',
                ],
            ],
        ];
        $event = new FilterPropertiesTypeEvent($this->form, $alias, $object, $operator, $details);

        $this->form->expects($this->once())
            ->method('add')
            ->with(
                'filter',
                ChoiceType::class,
                [
                    'label'                     => false,
                    'data'                      => [],
                    'choices'                   => ['Tag A' => 'Tag A'],
                    'multiple'                  => true,
                    'choice_translation_domain' => false,
                    'disabled'                  => false,
                    'attr'                      => [
                        'class'                => 'form-control',
                        'data-placeholder'     => 'mautic.lead.tags.select_or_create',
                        'data-no-results-text' => 'mautic.lead.tags.enter_to_create',
                        'data-allow-add'       => true,
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                ]
            );

        $this->subscriber->onSegmentFilterFormHandleTags($event);
    }

    public function testOnSegmentFilterFormHandleLookupIdIfNotLookupId(): void
    {
        $alias    = 'owner';
        $object   = 'lead';
        $operator = '=';
        $details  = ['properties' => ['type' => 'unicorn']];
        $event    = new FilterPropertiesTypeEvent($this->form, $alias, $object, $operator, $details);

        $this->form->expects($this->never())
            ->method('add');

        $this->subscriber->onSegmentFilterFormHandleLookupId($event);
    }

    public function testOnSegmentFilterFormHandleLookupIdIfLookupId(): void
    {
        $alias    = 'owner';
        $object   = 'lead';
        $operator = '=';
        $details  = ['properties' => ['type' => 'lookup_id']];
        $event    = new FilterPropertiesTypeEvent($this->form, $alias, $object, $operator, $details);

        $this->form->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    'display',
                    TextType::class,
                    $this->callback(function (array $options) {
                        $this->assertSame('', $options['data']);
                        $this->assertSame([
                            'class'               => 'form-control',
                            'data-field-callback' => 'activateSegmentFilterTypeahead',
                            'data-target'         => 'owner',
                        ], $options['attr']);

                        return true;
                    }),
                ],
                [
                    'filter',
                    HiddenType::class,
                    $this->callback(function (array $options) {
                        $this->assertSame('', $options['data']);
                        $this->assertSame(['class' => 'form-control'], $options['attr']);

                        return true;
                    }),
                ]
            );

        $this->subscriber->onSegmentFilterFormHandleLookupId($event);
    }
}
