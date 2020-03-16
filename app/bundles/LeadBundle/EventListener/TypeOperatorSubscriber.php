<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use DeviceDetector\Parser\Device\DeviceParserAbstract as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class TypeOperatorSubscriber implements EventSubscriberInterface
{
    use OperatorListTrait;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var StageModel
     */
    private $emailModel;

    /**
     * @var StageModel
     */
    private $stageModel;

    /**
     * @var CategoryModel
     */
    private $categoryModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        LeadModel $leadModel,
        ListModel $listModel,
        CampaignModel $campaignModel,
        EmailModel $emailModel,
        StageModel $stageModel,
        CategoryModel $categoryModel,
        TranslatorInterface $translator
    ) {
        $this->leadModel     = $leadModel;
        $this->listModel     = $listModel;
        $this->campaignModel = $campaignModel;
        $this->emailModel    = $emailModel;
        $this->stageModel    = $stageModel;
        $this->categoryModel = $categoryModel;
        $this->translator    = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE           => ['onTypeOperatorsCollect', 0],
            LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE => ['onTypeListCollect', 0],
            LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD          => [
                ['onSegmentFilterFormHandleTags', 1000],
                ['onSegmentFilterFormHandleLookupId', 800],
                ['onSegmentFilterFormHandleLookup', 600],
                ['onSegmentFilterFormHandleSelect', 400],
                ['onSegmentFilterFormHandleDefault', 0],
            ],
        ];
    }

    public function onTypeOperatorsCollect(TypeOperatorsEvent $event): void
    {
        // Subscribe basic field types.
        foreach ($this->typeOperators as $typeName => $operatorOptions) {
            $event->setOperatorsForFieldType($typeName, $operatorOptions);
        }

        // Subscribe aliases
        $event->setOperatorsForFieldType('boolean', $this->typeOperators['bool']);
        $event->setOperatorsForFieldType('datetime', $this->typeOperators['date']);

        foreach (['country', 'timezone', 'region', 'locale'] as $selectAlias) {
            $event->setOperatorsForFieldType($selectAlias, $this->typeOperators['select']);
        }

        foreach (['lookup', 'text', 'email', 'url', 'email', 'tel', 'number'] as $textAlias) {
            $event->setOperatorsForFieldType($textAlias, $this->typeOperators['text']);
        }
    }

    public function onTypeListCollect(ListFieldChoicesEvent $event): void
    {
        $event->setChoicesForFieldType(
            'boolean',
            [
                $this->translator->trans('mautic.core.form.no')  => 0,
                $this->translator->trans('mautic.core.form.yes') => 1,
            ]
        );

        $event->setChoicesForFieldAlias('campaign', $this->getCampaignChoices());
        $event->setChoicesForFieldAlias('leadlist', $this->getSegmentChoices());
        $event->setChoicesForFieldAlias('tags', $this->getTagChoices());
        $event->setChoicesForFieldAlias('stage', $this->getStageChoices());
        $event->setChoicesForFieldAlias('globalcategory', $this->getCategoryChoices());
        $event->setChoicesForFieldAlias('lead_email_received', $this->emailModel->getLookupResults('email', '', 0, 0));
        $event->setChoicesForFieldAlias('device_type', array_combine((DeviceParser::getAvailableDeviceTypeNames()), (DeviceParser::getAvailableDeviceTypeNames())));
        $event->setChoicesForFieldAlias('device_brand', DeviceParser::$deviceBrands);
        $event->setChoicesForFieldAlias('device_os', array_combine((array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())), array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())));
        $event->setChoicesForFieldType('country', FormFieldHelper::getCountryChoices());
        $event->setChoicesForFieldType('locale', FormFieldHelper::getLocaleChoices());
        $event->setChoicesForFieldType('region', FormFieldHelper::getRegionChoices());
        $event->setChoicesForFieldType('timezone', FormFieldHelper::getTimezonesChoices());
    }

    public function onSegmentFilterFormHandleTags(FilterPropertiesTypeEvent $event): void
    {
        if ('tags' !== $event->getFieldAlias()) {
            return;
        }

        $form = $event->getFilterPropertiesForm();

        $form->add(
            'filter',
            ChoiceType::class,
            [
                'label'                     => false,
                'data'                      => $form->getData()['filter'] ?? [],
                'choices'                   => FormFieldHelper::parseList($event->getFieldChoices()),
                'multiple'                  => true,
                'choice_translation_domain' => false,
                'disabled'                  => $event->filterShouldBeDisabled(),
                'attr'                      => [
                    'class'                => 'form-control',
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => true,
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
            ]
        );

        $event->stopPropagation();
    }

    /**
     * For fields where users search by label but we need the ID. Example: owner.
     */
    public function onSegmentFilterFormHandleLookupId(FilterPropertiesTypeEvent $event): void
    {
        if (!$event->fieldTypeIsOneOf('lookup_id')) {
            return;
        }

        $form = $event->getFilterPropertiesForm();

        // This field will hold the label of the lookup item.
        $form->add(
            'display',
            TextType::class,
            [
                'label'    => false,
                'required' => true,
                'data'     => $form->getData()['display'] ?? '',
                'attr'     => [
                    'class'               => 'form-control',
                    'data-field-callback' => 'activateSegmentFilterTypeahead',
                    'data-target'         => $event->getFieldAlias(),
                ],
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        // This field will hold the ID of the lookup item.
        $form->add(
            'filter',
            HiddenType::class,
            [
                'label'       => false,
                'required'    => true,
                'attr'        => ['class' => 'form-control'],
                'disabled'    => $event->filterShouldBeDisabled(),
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $event->stopPropagation();
    }

    public function onSegmentFilterFormHandleLookup(FilterPropertiesTypeEvent $event): void
    {
        if (!$event->fieldTypeIsOneOf('lookup')) {
            return;
        }

        $form = $event->getFilterPropertiesForm();

        $form->add(
            'filter',
            TextType::class,
            [
                'label'    => false,
                'disabled' => $event->filterShouldBeDisabled(),
                'data'     => $form->getData()['filter'] ?? '',
                'attr'     => [
                    'class'        => 'form-control',
                    'data-toggle'  => 'field-lookup',
                    'data-options' => $event->getFieldChoices(),
                    'data-target'  => $event->getFieldAlias(),
                    'data-action'  => 'lead:fieldList',
                    'placeholder'  => $this->translator->trans('mautic.lead.list.form.filtervalue'),
                ],
            ]
        );

        $event->stopPropagation();
    }

    public function onSegmentFilterFormHandleSelect(FilterPropertiesTypeEvent $event): void
    {
        $form       = $event->getFilterPropertiesForm();
        $data       = $form->getData();
        $multiple   = $event->operatorIsOneOf(OperatorOptions::IN, OperatorOptions::NOT_IN) || $event->fieldTypeIsOneOf('multiselect');
        $mustBeText = $event->operatorIsOneOf(OperatorOptions::REGEXP, OperatorOptions::NOT_REGEXP, OperatorOptions::STARTS_WITH, OperatorOptions::ENDS_WITH, OperatorOptions::CONTAINS, OperatorOptions::LIKE, OperatorOptions::NOT_LIKE);
        $isSelect   = $event->fieldTypeIsOneOf('select', 'multiselect', 'boolean', 'country', 'locale', 'region', 'timezone');

        if (!$mustBeText && $isSelect) {
            $filter = $data['filter'] ?? '';

            // Conversion between select and multiselect values.
            if ($multiple) {
                if (!isset($data['filter'])) {
                    $filter = [];
                } elseif (!is_array($data['filter'])) {
                    $filter = [$data['filter']];
                }
            }

            $form->add(
                'filter',
                ChoiceType::class,
                [
                    'label'                     => false,
                    'attr'                      => ['class' => 'form-control'],
                    'data'                      => $filter,
                    'choices'                   => FormFieldHelper::parseList($event->getFieldChoices(), true, ('boolean' === $event->getFieldType())),
                    'multiple'                  => $multiple,
                    'choice_translation_domain' => false,
                    'disabled'                  => $event->filterShouldBeDisabled(),
                ]
            );

            $event->stopPropagation();
        }
    }

    public function onSegmentFilterFormHandleDefault(FilterPropertiesTypeEvent $event): void
    {
        $form = $event->getFilterPropertiesForm();

        $form->add(
            'filter',
            TextType::class,
            [
                'label'    => false,
                'attr'     => ['class' => 'form-control'],
                'disabled' => $event->filterShouldBeDisabled(),
                'data'     => $form->getData()['filter'] ?? '',
            ]
        );

        $event->stopPropagation();
    }

    private function getCampaignChoices(): array
    {
        return $this->makeChoices($this->campaignModel->getPublishedCampaigns(true), 'name', 'id');
    }

    private function getSegmentChoices(): array
    {
        return $this->makeChoices($this->listModel->getUserLists(), 'name', 'id');
    }

    private function getTagChoices(): array
    {
        return $this->makeChoices($this->leadModel->getTagList(), 'label', 'value');
    }

    private function getStageChoices(): array
    {
        return $this->makeChoices($this->stageModel->getRepository()->getSimpleList(), 'label', 'value');
    }

    private function getCategoryChoices(): array
    {
        return $this->makeChoices($this->categoryModel->getLookupResults('global'), 'title', 'id');
    }

    private function makeChoices(array $items, string $labelName, string $keyName): array
    {
        $choices = [];

        foreach ($items as $item) {
            $choices[$item[$labelName]] = $item[$keyName];
        }

        return $choices;
    }
}
