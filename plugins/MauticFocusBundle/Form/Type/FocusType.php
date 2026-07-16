<?php

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use DeviceDetector\Parser\Device\AbstractDeviceParser as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\PublishDownDateType;
use Mautic\CoreBundle\Form\Type\PublishUpDateType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\Form\Type\DwcEntryFiltersType;
use Mautic\EmailBundle\Form\Type\EmailUtmTagsType;
use Mautic\FormBundle\Form\Type\FormListType;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\RelativeDate;
use Mautic\ProjectBundle\Form\Type\ProjectType;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<Focus>
 */
class FocusType extends AbstractType
{
    /**
     * @var mixed[]
     */
    private array $fieldChoices;

    /**
     * @var mixed[]
     */
    private readonly array $countryChoices;

    /**
     * @var mixed[]
     */
    private readonly array $regionChoices;

    /**
     * @var mixed[]
     */
    private readonly array $timezoneChoices;

    /**
     * @var mixed[]
     */
    private readonly array $localeChoices;

    /**
     * @var mixed[]
     */
    private readonly array $deviceTypesChoices;

    /**
     * @var mixed[]
     */
    private readonly array $deviceBrandsChoices;

    /**
     * @var mixed[]
     */
    private readonly array $deviceOsChoices;

    /**
     * @var array<string, string>
     */
    private array $tagChoices = [];

    public function __construct(
        private readonly CorePermissions $security,
        ListModel $listModel,
        LeadModel $leadModel,
        private readonly TranslatorInterface $translator,
        private readonly RelativeDate $relativeDate,
    ) {
        $this->fieldChoices    = $listModel->getChoiceFields();
        $this->timezoneChoices = FormFieldHelper::getTimezonesChoices();
        $this->countryChoices  = FormFieldHelper::getCountryChoices();
        $this->regionChoices   = FormFieldHelper::getRegionChoices();
        $this->localeChoices   = FormFieldHelper::getLocaleChoices();

        $this->filterFieldChoices($leadModel);

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
        $builder->addEventSubscriber(new CleanFormSubscriber(['website' => 'url', 'html' => 'html', 'editor' => 'html', 'description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('focus', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
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
                'required' => false,
            ]
        );

        $builder->add(
            'html_mode',
            ButtonGroupType::class,
            [
                'label'      => 'mautic.focus.form.html_mode',
                'label_attr' => ['class' => 'control-label'],
                'data'       => !empty($options['data']->getHtmlMode()) ? $options['data']->getHtmlMode() : 'basic',
                'attr'       => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.focusUpdatePreview()',
                    'tooltip'  => 'mautic.focums.html_mode.tooltip',
                ],
                'choices' => [
                    'mautic.focus.form.basic'  => 'basic',
                    'mautic.focus.form.editor' => 'editor',
                    'mautic.focus.form.html'   => 'html',
                ],
            ]
        );

        $builder->add(
            'editor',
            TextareaType::class,
            [
                'label'      => 'mautic.focus.form.editor',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control editor editor-basic',
                    'data-show-on' => '{"focus_html_mode_1":"checked"}',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'html',
            TextareaType::class,
            [
                'label'      => 'mautic.focus.form.html',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'rows'         => 12,
                    'data-show-on' => '{"focus_html_mode_2":"checked"}',
                    'onchange'     => 'Mautic.focusUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'website',
            UrlType::class,
            [
                'label'      => 'mautic.focus.form.website',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.focus.form.website.tooltip',
                ],
                'required' => false,
            ]
        );

        // add category
        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'plugin:focus',
            ]
        );

        $builder->add('projects', ProjectType::class);

        if (!empty($options['data']) && $options['data']->getId()) {
            $readonly = !$this->security->isGranted('focus:items:publish');
            $data     = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('focus:items:publish')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = false;
        }

        $builder->add(
            'isPublished',
            YesNoButtonGroupType::class,
            [
                'data' => $data,
                'attr' => [
                    'readonly' => $readonly,
                ],
            ]
        );

        $builder->add('publishUp', PublishUpDateType::class);
        $builder->add('publishDown', PublishDownDateType::class);
        $builder->add('properties', PropertiesType::class, ['data' => $options['data']->getProperties()]);

        $filterModalTransformer = new FieldFilterTransformer($this->translator, $this->relativeDate);
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

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                // delete default prototype values
                $data = $event->getData();
                unset($data['filters']['__name__']);
                $event->setData($data);
            }
        );

        // Will be managed by JS
        $builder->add('type', HiddenType::class);
        $builder->add('style', HiddenType::class);

        $builder->add(
            'form',
            FormListType::class,
            [
                'label'       => 'mautic.focus.form.choose_form',
                'multiple'    => false,
                'placeholder' => '',
                'attr'        => [
                    'onchange' => 'Mautic.focusUpdatePreview()',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $customButtons = [
            [
                'name'  => 'builder',
                'label' => 'mautic.core.builder',
                'attr'  => [
                    'class'   => 'btn btn-tertiary btn-dnd btn-nospin',
                    'icon'    => 'ri-layout-line',
                    'onclick' => 'Mautic.launchFocusBuilder();',
                ],
            ],
        ];

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text'        => false,
                    'pre_extra_buttons' => $customButtons,
                ]
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
                FormButtonsType::class,
                [
                    'pre_extra_buttons' => $customButtons,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Focus::class,
            ]
        );
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

    private function filterFieldChoices(LeadModel $leadModel): void
    {
        unset($this->fieldChoices['company']);

        $customFields = $leadModel->getRepository()->getCustomFieldList('lead');

        $this->fieldChoices['lead'] = array_filter(
            $this->fieldChoices['lead'],
            fn ($key): bool => in_array(
                $key,
                array_merge(
                    array_keys($customFields[0]),
                    ['date_added', 'date_modified', 'device_brand', 'device_model', 'device_os', 'device_type', 'tags', 'leadlist']
                ),
                true
            ),
            ARRAY_FILTER_USE_KEY
        );
    }
}
