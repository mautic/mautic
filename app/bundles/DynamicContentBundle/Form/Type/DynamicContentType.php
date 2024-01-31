<?php

namespace Mautic\DynamicContentBundle\Form\Type;

use DeviceDetector\Parser\Device\AbstractDeviceParser as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\PublishDownDateType;
use Mautic\CoreBundle\Form\Type\PublishUpDateType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Form\Type\EmailUtmTagsType;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<DynamicContent>
 */
class DynamicContentType extends AbstractType
{
    private $fieldChoices;

    /**
     * @var mixed[]
     */
    private array $countryChoices;

    /**
     * @var mixed[]
     */
    private array $regionChoices;

    private $timezoneChoices;

    /**
     * @var mixed[]
     */
    private array $localeChoices;

    /**
     * @var mixed[]
     */
    private array $deviceTypesChoices;

    private $deviceBrandsChoices;

    /**
     * @var mixed[]
     */
    private array $deviceOsChoices;

    /**
     * @var array<string, string>
     */
    private array $tagChoices = [];

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        private EntityManager $em,
        ListModel $listModel,
        private TranslatorInterface $translator,
        private LeadModel $leadModel
    ) {
        $this->fieldChoices    = $listModel->getChoiceFields();
        $this->timezoneChoices = FormFieldHelper::getTimezonesChoices();
        $this->countryChoices  = FormFieldHelper::getCountryChoices();
        $this->regionChoices   = FormFieldHelper::getRegionChoices();
        $this->localeChoices   = FormFieldHelper::getLocaleChoices();

        $this->filterFieldChoices();

        $tags = $leadModel->getTagList();
        foreach ($tags as $tag) {
            $this->tagChoices[$tag['value']] = $tag['label'];
        }

        $this->deviceTypesChoices  = array_combine(DeviceParser::getAvailableDeviceTypeNames(), DeviceParser::getAvailableDeviceTypeNames());
        $this->deviceBrandsChoices = DeviceParser::$deviceBrands;
        $this->deviceOsChoices     = array_combine(
            array_keys(OperatingSystem::getAvailableOperatingSystemFamilies()),
            array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('dynamicContent.dynamicContent', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.dynamicContent.form.internal.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'slotName',
            TextType::class,
            [
                'label'      => 'mautic.dynamicContent.send.slot_name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.dynamicContent.send.slot_name.tooltip',
                ],
            ]
        );

        $builder->add(
            $builder->create(
                'description',
                TextareaType::class,
                [
                    'label'      => 'mautic.dynamicContent.description',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            )
        );

        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add(
            'isCampaignBased',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.dwc.form.is_campaign_based',
                'data'  => (bool) $options['data']->isCampaignBased(),
                'attr'  => [
                    'tooltip'  => 'mautic.dwc.form.is_campaign_based.tooltip',
                    'onchange' => 'Mautic.toggleDwcFilters()',
                ],
            ]
        );

        $builder->add(
            'language',
            LocaleType::class,
            [
                'label'      => 'mautic.core.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add('publishUp', PublishUpDateType::class);
        $builder->add('publishDown', PublishDownDateType::class);

        $builder->add(
            'content',
            TextareaType::class,
            [
                'label'      => 'mautic.dynamicContent.form.content',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip'              => 'mautic.dynamicContent.form.content.help',
                    'class'                => 'form-control editor editor-advanced editor-builder-tokens',
                    'data-token-callback'  => 'email:getBuilderTokens',
                    'data-token-activator' => '{',
                    'rows'                 => '15',
                ],
                'required' => false,
            ]
        );
        $builder->add(
            'utmTags',
            EmailUtmTagsType::class,
            [
                'label'      => 'mautic.email.utm_tags',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.utm_tags.tooltip',
                ],
                'required'   => false,
            ]
        );

        $transformer = new IdToEntityModelTransformer($this->em, \Mautic\DynamicContentBundle\Entity\DynamicContent::class);
        $builder->add(
            $builder->create(
                'translationParent',
                DynamicContentListType::class,
                [
                    'label'      => 'mautic.core.form.translation_parent',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.form.translation_parent.help',
                    ],
                    'required'    => false,
                    'multiple'    => false,
                    'placeholder' => 'mautic.core.form.translation_parent.empty',
                    'top_level'   => 'translation',
                    'ignore_ids'  => [(int) $options['data']->getId()],
                ]
            )->addModelTransformer($transformer)
        );

        $builder->add(
            'category',
            CategoryListType::class,
            ['bundle' => 'dynamicContent']
        );

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                ['apply_text' => false]
            );

            $builder->add(
                'updateSelect',
                HiddenType::class,
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class
            );
        }

        $filterModalTransformer = new FieldFilterTransformer($this->translator);
        $builder->add(
            $builder->create(
                'filters',
                CollectionType::class,
                [
                    'entry_type'    => DwcEntryFiltersType::class,
                    'entry_options' => [
                        'countries'    => $this->countryChoices,
                        'regions'      => $this->regionChoices,
                        'timezones'    => $this->timezoneChoices,
                        'locales'      => $this->localeChoices,
                        'fields'       => $this->fieldChoices,
                        'deviceTypes'  => $this->deviceTypesChoices,
                        'deviceBrands' => $this->deviceBrandsChoices,
                        'deviceOs'     => $this->deviceOsChoices,
                        'tags'         => $this->tagChoices,
                    ],
                    'error_bubbling' => false,
                    'mapped'         => true,
                    'allow_add'      => true,
                    'allow_delete'   => true,
                ]
            )->addModelTransformer($filterModalTransformer)
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                // delete default prototype values
                $data = $event->getData();
                unset($data['filters']['__name__']);
                $event->setData($data);
            }
        );
    }

    /**
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'     => DynamicContent::class,
            'label'          => false,
            'error_bubbling' => false,
        ]);

        $resolver->setDefined(['update_select']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['fields']       = $this->fieldChoices;
        $view->vars['countries']    = $this->countryChoices;
        $view->vars['regions']      = $this->regionChoices;
        $view->vars['timezones']    = $this->timezoneChoices;
        $view->vars['deviceTypes']  = $this->deviceTypesChoices;
        $view->vars['deviceBrands'] = $this->deviceBrandsChoices;
        $view->vars['deviceOs']     = $this->deviceOsChoices;
        $view->vars['tags']         = $this->tagChoices;
        $view->vars['locales']      = $this->localeChoices;
    }

    private function filterFieldChoices(): void
    {
        unset($this->fieldChoices['company']);
        $customFields               = $this->leadModel->getRepository()->getCustomFieldList('lead');
        $this->fieldChoices['lead'] = array_filter($this->fieldChoices['lead'], fn ($key): bool => in_array($key, array_merge(array_keys($customFields[0]), ['date_added', 'date_modified', 'device_brand', 'device_model', 'device_os', 'device_type', 'tags']), true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'dwc';
    }
}
