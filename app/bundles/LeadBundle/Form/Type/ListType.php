<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use DeviceDetector\Parser\Device\DeviceParserAbstract as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
/**
 * Class ListType.
 */
class ListType extends AbstractType
{
    private $translator;
    private $fieldChoices         = [];
    private $timezoneChoices      = [];
    private $countryChoices       = [];
    private $regionChoices        = [];
    private $listChoices          = [];
    private $emailChoices         = [];
    private $deviceTypesChoices   = [];
    private $deviceBrandsChoices  = [];
    private $deviceOsChoices      = [];
    private $tagChoices           = [];
    private $stageChoices         = [];
    private $localeChoices        = [];
    private $categoriesChoices    = [];

    /**
     * ListType constructor.
     *
     * @param TranslatorInterface $translator
     * @param ListModel           $listModel
     * @param EmailModel          $emailModel
     * @param CorePermissions     $security
     * @param LeadModel           $leadModel
     * @param StageModel          $stageModel
     * @param CategoryModel       $categoryModel
     * @param UserHelper          $userHelper
     */
    public function __construct(TranslatorInterface $translator, ListModel $listModel, EmailModel $emailModel, CorePermissions $security, LeadModel $leadModel, StageModel $stageModel, CategoryModel $categoryModel, UserHelper $userHelper)
    {
        $this->translator = $translator;

        $this->fieldChoices = $listModel->getChoiceFields();

        // Locales
        $this->timezoneChoices = FormFieldHelper::getTimezonesChoices();
        $this->countryChoices  = FormFieldHelper::getCountryChoices();
        $this->regionChoices   = FormFieldHelper::getRegionChoices();
        $this->localeChoices   = FormFieldHelper::getLocaleChoices();

        // Segments
        $lists = $listModel->getUserLists();
        foreach ($lists as $list) {
            $this->listChoices[$list['id']] = $list['name'];
        }

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

        $stages = $stageModel->getRepository()->getSimpleList();
        foreach ($stages as $stage) {
            $this->stageChoices[$stage['value']] = $stage['label'];
        }

        $categories = $categoryModel->getLookupResults('global');

        foreach ($categories as $category) {
            $this->categoriesChoices[$category['id']] = $category['title'];
        }
        $this->deviceTypesChoices = array_combine((DeviceParser::getAvailableDeviceTypeNames()), (DeviceParser::getAvailableDeviceTypeNames()));
        $this->deviceBrandsChoices = DeviceParser::$deviceBrands;
        $this->deviceOsChoices = array_combine((array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())), array_keys(OperatingSystem::getAvailableOperatingSystemFamilies()));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('lead.list', $options));

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'alias',
            'text',
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'length'  => 25,
                    'tooltip' => 'mautic.lead.list.help.alias',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'description',
            'textarea',
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'isGlobal',
            'yesno_button_group',
            [
                'label' => 'mautic.lead.list.form.isglobal',
            ]
        );

        $builder->add('isPublished', 'yesno_button_group');

        $filterModalTransformer = new FieldFilterTransformer($this->translator);
        $builder->add(
            $builder->create(
                'filters',
                'collection',
                [
                    'type'    => 'leadlist_filter',
                    'options' => [
                        'label'          => false,
                        'timezones'      => $this->timezoneChoices,
                        'countries'      => $this->countryChoices,
                        'regions'        => $this->regionChoices,
                        'fields'         => $this->fieldChoices,
                        'lists'          => $this->listChoices,
                        'emails'         => $this->emailChoices,
                        'deviceTypes'    => $this->deviceTypesChoices,
                        'deviceBrands'   => $this->deviceBrandsChoices,
                        'deviceOs'       => $this->deviceOsChoices,
                        'tags'           => $this->tagChoices,
                        'stage'          => $this->stageChoices,
                        'locales'        => $this->localeChoices,
                        'globalcategory' => $this->categoriesChoices,
                    ],
                    'error_bubbling' => false,
                    'mapped'         => true,
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'label'          => false,
                ]
            )->addModelTransformer($filterModalTransformer)
        );

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Mautic\LeadBundle\Entity\LeadList',
            ]
        );
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
        $view->vars['lists']          = $this->listChoices;
        $view->vars['emails']         = $this->emailChoices;
        $view->vars['deviceTypes']    = $this->deviceTypesChoices;
        $view->vars['deviceBrands']   = $this->deviceBrandsChoices;
        $view->vars['deviceOs']       = $this->deviceOsChoices;
        $view->vars['tags']           = $this->tagChoices;
        $view->vars['stage']          = $this->stageChoices;
        $view->vars['locales']        = $this->localeChoices;
        $view->vars['globalcategory'] = $this->categoriesChoices;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leadlist';
    }
}
