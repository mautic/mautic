<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FilterType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class DynamicContentFilterEntryType extends AbstractType
{
    private $translator;
    private $fieldChoices = [];
    private $timezoneChoices = [];
    private $countryChoices = [];
    private $regionChoices = [];
    private $listChoices = [];
    private $emailChoices = [];
    private $tagChoices = [];
    private $stageChoices = [];
    private $localeChoices = [];

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel          = $factory->getModel('lead.list');
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

        // Emails
        /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
        $emailModel = $factory->getModel('email');
        $viewOther  = $factory->getSecurity()->isGranted('email:emails:viewother');
        $emails     = $emailModel->getRepository()->getEmailList('', 0, 0, $viewOther, true);
        foreach ($emails as $email) {
            $this->emailChoices[$email['language']][$email['id']] = $email['name'];
        }
        ksort($this->emailChoices);

        // Tags
        $leadModel = $factory->getModel('lead');
        $tags      = $leadModel->getTagList();
        foreach ($tags as $tag) {
            $this->tagChoices[$tag['value']] = $tag['label'];
        }

        $stages = $factory->getModel('stage')->getRepository()->getSimpleList();
        foreach ($stages as $stage) {
            $this->stageChoices[$stage['value']] = $stage['label'];
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'content',
            'textarea',
            [
                'label' => 'mautic.core.dynamicContent.alt_content',
                'attr'  => [
                    'class' => 'form-control'
                ]
            ]
        );

        $builder->add(
            $builder->create(
                'filters',
                'collection',
                [
                    'type' => 'dynamic_content_filter_entry_filters',
                    'options' => [
                        'label' => false,
                        'attr'  => [
                            'class' => 'form-control'
                        ],
                        'countries' => $this->countryChoices,
                        'timezones' => $this->timezoneChoices,
                        'emails' => $this->emailChoices,
                        'fields' => $this->fieldChoices,
                        'lists' => $this->listChoices,
                        'regions' => $this->regionChoices,
                        'stage' => $this->stageChoices,
                        'tags' => $this->tagChoices,
                    ],
                    'error_bubbling' => false,
                    'mapped'         => true,
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'label'          => false
                ]
            )
        );

    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields']    = $this->fieldChoices;
        $view->vars['countries'] = $this->countryChoices;
        $view->vars['regions']   = $this->regionChoices;
        $view->vars['timezones'] = $this->timezoneChoices;
        $view->vars['lists']     = $this->listChoices;
        $view->vars['emails']    = $this->emailChoices;
        $view->vars['tags']      = $this->tagChoices;
        $view->vars['stage']     = $this->stageChoices;
        $view->vars['locales']   = $this->localeChoices;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            [
                'content',
                'filters'
            ]
        );

        $resolver->setDefaults(
            array(
                'label'          => false,
                'error_bubbling' => false
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "dynamic_content_filter_entry";
    }
}