<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Form\Type;

use DeviceDetector\Parser\Device\DeviceParserAbstract as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Form\DataTransformer\EmojiToShortTransformer;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DynamicContentType.
 */
class DynamicContentType extends AbstractType
{
    private $em;

    private $fieldChoices;
    private $countryChoices;
    private $regionChoices;
    private $timezoneChoices;
    private $stageChoices = [];
    private $localeChoices;

    private $translator;
    private $listChoices  = [];
    private $emailChoices = [];
    private $deviceTypesChoices;
    private $deviceBrandsChoices;
    private $deviceOsChoices;
    private $tagChoices        = [];
    private $categoriesChoices = [];

    /**
     * DynamicContentType constructor.
     *
     * @param EntityManager       $entityManager
     * @param ListModel           $listModel
     * @param StageModel          $stageModel
     * @param TranslatorInterface $translator
     * @param LeadModel           $leadModel
     * @param CategoryModel       $categoryModel
     * @param EmailModel          $emailModel
     * @param CorePermissions     $security
     * @param UserHelper          $userHelper
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(EntityManager $entityManager, ListModel $listModel, StageModel $stageModel, TranslatorInterface $translator, LeadModel $leadModel, CategoryModel $categoryModel, EmailModel $emailModel, CorePermissions $security, UserHelper $userHelper)
    {
        $this->em = $entityManager;

        $this->fieldChoices = $listModel->getChoiceFields();

        $this->countryChoices  = FormFieldHelper::getCountryChoices();
        $this->regionChoices   = FormFieldHelper::getRegionChoices();
        $this->timezoneChoices = FormFieldHelper::getTimezonesChoices();
        $this->localeChoices   = FormFieldHelper::getLocaleChoices();

        $stages = $stageModel->getRepository()->getSimpleList();
        foreach ($stages as $stage) {
            $this->stageChoices[$stage['value']] = $stage['label'];
        }

        $this->translator = $translator;

        // emails
        $viewOther   = $security->isGranted('email:emails:viewother');
        $currentUser = $userHelper->getUser();
        $emailRepo   = $emailModel->getRepository();

        $emailRepo->setCurrentUser($currentUser);

        $emails = $emailRepo->getEmailList('', 0, 0, $viewOther, true);

        foreach ($emails as $email) {
            $this->emailChoices[$email['language']][$email['id']] = $email['name'];
        }
        ksort($this->emailChoices);

        $tags = $leadModel->getTagList();
        foreach ($tags as $tag) {
            $this->tagChoices[$tag['value']] = $tag['label'];
        }

        $categories = $categoryModel->getLookupResults('global');
        foreach ($categories as $category) {
            $this->categoriesChoices[$category['id']] = $category['title'];
        }

        // Segments
        /** @var array $lists */
        $lists = $listModel->getUserLists();
        foreach ($lists as $list) {
            $this->listChoices[$list['id']] = $list['name'];
        }

        $this->deviceTypesChoices  = array_combine(DeviceParser::getAvailableDeviceTypeNames(), DeviceParser::getAvailableDeviceTypeNames());
        $this->deviceBrandsChoices = DeviceParser::$deviceBrands;
        $this->deviceOsChoices     = array_combine(
            array_keys(OperatingSystem::getAvailableOperatingSystemFamilies()),
            array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())
        );
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('dynamicContent.dynamicContent', $options));

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.dynamicContent.form.internal.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $emojiTransformer = new EmojiToShortTransformer();
        $builder->add(
            $builder->create(
                'description',
                'textarea',
                [
                    'label'      => 'mautic.dynamicContent.description',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            )->addModelTransformer($emojiTransformer)
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add(
            'isCampaignBased',
            'yesno_button_group',
            [
                'label' => 'mautic.dwc.form.is_campaign_based',
                'data'  => (bool) $options['data']->isCampaignBased(),
                'attr'  => [
                    'tooltip'  => 'mautic.dwc.form.is_campaign_based.tooltip',
                    'onchange' => 'mQuery("#dwcFiltersTab").toggleClass("hide")',
                ],
            ]
        );

        $builder->add(
            'language',
            'locale',
            [
                'label'      => 'mautic.core.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'publishUp',
            'datetime',
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        $builder->add(
            'publishDown',
            'datetime',
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        $builder->add(
            'content',
            'textarea',
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

        $transformer = new IdToEntityModelTransformer($this->em, 'MauticDynamicContentBundle:DynamicContent');
        $builder->add(
            $builder->create(
                'translationParent',
                'dwc_list',
                [
                    'label'      => 'mautic.core.form.translation_parent',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.form.translation_parent.help',
                    ],
                    'required'    => false,
                    'multiple'    => false,
                    'empty_value' => 'mautic.core.form.translation_parent.empty',
                    'top_level'   => 'translation',
                    'ignore_ids'  => [(int) $options['data']->getId()],
                ]
            )->addModelTransformer($transformer)
        );

        $builder->add(
            'category',
            'category',
            ['bundle' => 'dynamicContent']
        );

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                'form_buttons',
                ['apply_text' => false]
            );

            $builder->add(
                'updateSelect',
                'hidden',
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                'form_buttons'
            );
        }

        $filterModalTransformer = new FieldFilterTransformer($this->translator);
        $builder->add(
            $builder->create(
                'filters',
                'collection',
                [
                    'type'    => DwcEntryFiltersType::class,
                    'options' => [
                        'countries'      => $this->countryChoices,
                        'regions'        => $this->regionChoices,
                        'timezones'      => $this->timezoneChoices,
                        'stages'         => $this->stageChoices,
                        'locales'        => $this->localeChoices,
                        'fields'         => $this->fieldChoices,
                        'emails'         => $this->emailChoices,
                        'lists'          => $this->listChoices,
                        'deviceTypes'    => $this->deviceTypesChoices,
                        'deviceBrands'   => $this->deviceBrandsChoices,
                        'deviceOs'       => $this->deviceOsChoices,
                        'tags'           => $this->tagChoices,
                        'globalcategory' => $this->categoriesChoices,
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
            function (FormEvent $event) {
                // delete default prototype values
                $data = $event->getData();
                unset($data['filters']['__name__']);
                $event->setData($data);
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'     => DynamicContent::class,
            'label'          => false,
            'error_bubbling' => false,
        ]);

        $resolver->setDefined(['update_select']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dwc';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields']         = $this->fieldChoices;
        $view->vars['countries']      = $this->countryChoices;
        $view->vars['regions']        = $this->regionChoices;
        $view->vars['timezones']      = $this->timezoneChoices;
        $view->vars['emails']         = $this->emailChoices;
        $view->vars['lists']          = $this->listChoices;
        $view->vars['deviceTypes']    = $this->deviceTypesChoices;
        $view->vars['deviceBrands']   = $this->deviceBrandsChoices;
        $view->vars['deviceOs']       = $this->deviceOsChoices;
        $view->vars['tags']           = $this->tagChoices;
        $view->vars['stage']          = $this->stageChoices;
        $view->vars['locales']        = $this->localeChoices;
        $view->vars['globalcategory'] = $this->categoriesChoices;
    }
}
