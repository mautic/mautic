<?php

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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\TranslatorInterface;

class TypeOperatorSubscriber implements EventSubscriberInterface
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
            LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD          => ['onSegmentFilterForm', 0],
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
                0 => $this->translator->trans('mautic.core.form.no'),
                1 => $this->translator->trans('mautic.core.form.yes'),
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

    public function onSegmentFilterForm(FilterPropertiesTypeEvent $event): void
    {
        $form       = $event->getFilterPropertiesForm();
        $choices    = $event->getFieldChoices();
        $disabled   = $event->operatorIsOneOf(OperatorOptions::EMPTY, OperatorOptions::NOT_EMPTY);
        $multiple   = $event->operatorIsOneOf(OperatorOptions::IN, OperatorOptions::NOT_IN) || $event->fieldTypeIsOneOf('multiselect');
        $mustBeText = $event->operatorIsOneOf(OperatorOptions::REGEXP, OperatorOptions::NOT_REGEXP, OperatorOptions::STARTS_WITH, OperatorOptions::ENDS_WITH, OperatorOptions::CONTAINS, OperatorOptions::LIKE, OperatorOptions::NOT_LIKE);
        $isLookup   = $event->fieldTypeIsOneOf('lookup');
        $isSelect   = $event->fieldTypeIsOneOf('select', 'multiselect', 'boolean', 'country', 'locale', 'region', 'timezone');
        $data       = $form->getData();
        $attr       = ['class' => 'form-control'];

        if ('tags' === $event->getFieldAlias()) {
            $form->add(
                'filter',
                ChoiceType::class,
                [
                    'label'                     => false,
                    'data'                      => $data['filter'] ?? [],
                    'choices'                   => FormFieldHelper::parseList($choices, true, ('boolean' === $event->getFieldType())),
                    'multiple'                  => $multiple,
                    'choice_translation_domain' => false,
                    'disabled'                  => $disabled,
                    'attr'                      => array_merge(
                        $attr,
                        [
                            'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                            'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                            'data-allow-add'       => true,
                            'onchange'             => 'Mautic.createLeadTag(this)',
                            'data-field-callback'  => '',
                        ]
                    ),
                ]
            );

            return;
        }

        if (!$isLookup && !$mustBeText && $isSelect) {
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
                    'attr'                      => $attr,
                    'data'                      => $filter,
                    'choices'                   => FormFieldHelper::parseList($choices, true, ('boolean' === $event->getFieldType())),
                    'multiple'                  => $multiple,
                    'choice_translation_domain' => false,
                    'disabled'                  => $disabled,
                ]
            );

            return;
        }

        if ($isLookup) {
            $attr['data-toggle']  = 'field-lookup';
            $attr['data-options'] = $choices;
            $attr['data-target']  = $event->getFieldAlias();
            $attr['data-action']  = 'lead:fieldList';
            $attr['placeholder']  = $this->translator->trans('mautic.lead.list.form.filtervalue');
        }

        // $attr['data-field-callback'] = 'activateLeadFieldTypeahead'; // We'll need this in some case.

        $form->add(
            'filter',
            TextType::class,
            [
                'label'    => false,
                'attr'     => $attr,
                'disabled' => $disabled,
            ]
        );
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
