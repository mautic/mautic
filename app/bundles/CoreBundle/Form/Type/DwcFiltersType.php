<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use DeviceDetector\Parser\Device\DeviceParserAbstract as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Mautic\CategoryBundle\Model\CategoryModel;
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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DynamicContentFilterType.
 */
class DwcFiltersType extends AbstractType
{
    private $fieldChoices    = [];
    private $countryChoices  = [];
    private $regionChoices   = [];
    private $timezoneChoices = [];
    private $stageChoices    = [];
    private $localeChoices   = [];

    private $translator;
    private $listChoices         = [];
    private $emailChoices        = [];
    private $deviceTypesChoices  = [];
    private $deviceBrandsChoices = [];
    private $deviceOsChoices     = [];
    private $tagChoices          = [];
    private $categoriesChoices   = [];

    /**
     * DynamicContentFilterEntryType constructor.
     *
     * @param ListModel           $listModel
     * @param StageModel          $stageModel
     * @param TranslatorInterface $translator
     * @param LeadModel           $leadModel
     * @param CategoryModel       $categoryModel
     * @param EmailModel          $emailModel
     * @param CorePermissions     $security
     * @param UserHelper          $userHelper
     */
    public function __construct(ListModel $listModel, StageModel $stageModel, TranslatorInterface $translator, LeadModel $leadModel, CategoryModel $categoryModel, EmailModel $emailModel, CorePermissions $security, UserHelper $userHelper)
    {
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
        $lists = $listModel->getUserLists();
        foreach ($lists as $list) {
            $this->listChoices[$list['id']] = $list['name'];
        }

        $this->deviceTypesChoices  = array_combine((DeviceParser::getAvailableDeviceTypeNames()), (DeviceParser::getAvailableDeviceTypeNames()));
        $this->deviceBrandsChoices = DeviceParser::$deviceBrands;
        $this->deviceOsChoices     = array_combine((array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())), array_keys(OperatingSystem::getAvailableOperatingSystemFamilies()));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $filterModalTransformer = new FieldFilterTransformer($this->translator);
        $builder->add(
            $builder->create(
                'filters',
                'collection',
                [
                    'type'    => 'dynamic_web_content_entry_filters',
                    'options' => [
                        'label' => false,
                        'attr'  => [
                            'class' => 'form-control',
                        ],
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
                    'label'          => false,
                ]
            )->addModelTransformer($filterModalTransformer)
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

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dynamic_web_content_filters';
    }
}
