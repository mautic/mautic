<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ListType.
 */
class ListType extends AbstractType
{
    private $translator;
    private $fieldChoices    = [];
    private $timezoneChoices = [];
    private $countryChoices  = [];
    private $regionChoices   = [];
    private $listChoices     = [];
    private $emailChoices    = [];
    private $tagChoices      = [];
    private $stageChoices    = [];
    private $localeChoices   = [];

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
                        'label'     => false,
                        'timezones' => $this->timezoneChoices,
                        'countries' => $this->countryChoices,
                        'regions'   => $this->regionChoices,
                        'fields'    => $this->fieldChoices,
                        'lists'     => $this->listChoices,
                        'emails'    => $this->emailChoices,
                        'tags'      => $this->tagChoices,
                        'stage'     => $this->stageChoices,
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
     * @return string
     */
    public function getName()
    {
        return 'leadlist';
    }
}
