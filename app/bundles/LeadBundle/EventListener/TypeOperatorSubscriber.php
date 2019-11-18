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
use Mautic\CoreBundle\EventListener\CommonSubscriber;
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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TypeOperatorSubscriber extends CommonSubscriber
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

    public function __construct(
        LeadModel $leadModel,
        ListModel $listModel,
        CampaignModel $campaignModel,
        EmailModel $emailModel,
        StageModel $stageModel,
        CategoryModel $categoryModel
    ) {
        $this->leadModel     = $leadModel;
        $this->listModel     = $listModel;
        $this->campaignModel = $campaignModel;
        $this->emailModel    = $emailModel;
        $this->stageModel    = $stageModel;
        $this->categoryModel = $categoryModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE           => ['onTypeOperatorsCollect', 0],
            LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE => ['onTypeListCollect', 0],
            LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD          => ['onSegmentFilterForm', 0],
        ];
    }

    public function onTypeOperatorsCollect(TypeOperatorsEvent $event)
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

    public function onTypeListCollect(ListFieldChoicesEvent $event)
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

    public function onSegmentFilterForm(FilterPropertiesTypeEvent $event)
    {
        $form     = $event->getFilterPropertiesForm();
        $choices  = $event->getFieldChoices();
        $disabled = $event->operatorIsOneOf(OperatorOptions::EMPTY, OperatorOptions::NOT_EMPTY);
        $multiple = $event->operatorIsOneOf(OperatorOptions::IN, OperatorOptions::NOT_IN) || $event->fieldTypeIsOneOf('multiselect');
        $data     = $form->getData();

        if ($event->operatorIsOneOf(OperatorOptions::REGEXP, OperatorOptions::NOT_REGEXP)) {
            $form->add(
                'filter',
                TextType::class,
                [
                    'label' => false,
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ]
            );

            return;
        }

        if ($event->fieldTypeIsOneOf('select', 'multiselect', 'boolean') || $choices) {
            // Conversion between select and multiselect values.
            if ($multiple) {
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }
            }

            $form->add(
                'filter',
                ChoiceType::class,
                [
                    'label'                     => false,
                    'attr'                      => ['class' => 'form-control'],
                    'data'                      => $data['filter'],
                    'choices'                   => FormFieldHelper::parseList($choices, true, ('boolean' === $event->getFieldType())),
                    'multiple'                  => $multiple,
                    'choice_translation_domain' => false,
                    'disabled'                  => $disabled,
                ]
            );

            return;
        }

        $form->add(
            'filter',
            TextType::class,
            [
                'label'    => false,
                'attr'     => ['class' => 'form-control'],
                'disabled' => $disabled,
            ]
        );
    }

    private function getCampaignChoices(): array
    {
        $campaigns = $this->campaignModel->getPublishedCampaigns(true);
        $choices   = [];

        foreach ($campaigns as $campaign) {
            $choices[$campaign['id']] = $campaign['name'];
        }

        return $choices;
    }

    private function getSegmentChoices(): array
    {
        $segments = $this->listModel->getUserLists();
        $choices  = [];

        foreach ($segments as $segegment) {
            $choices[$segegment['id']] = $segegment['name'];
        }

        return $choices;
    }

    private function getTagChoices(): array
    {
        $tags    = $this->leadModel->getTagList();
        $choices = [];

        foreach ($tags as $tag) {
            $choices[$tag['value']] = $tag['label'];
        }

        return $choices;
    }

    private function getStageChoices(): array
    {
        $stages  = $this->stageModel->getRepository()->getSimpleList();
        $choices = [];

        foreach ($stages as $stage) {
            $choices[$stage['value']] = $stage['label'];
        }

        return $choices;
    }

    private function getCategoryChoices(): array
    {
        $categories = $this->categoryModel->getLookupResults('global');
        $choices    = [];

        foreach ($categories as $category) {
            $choices[$category['id']] = $category['title'];
        }

        return $choices;
    }
}
