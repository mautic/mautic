<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\LeadBundle\Form\DataTransformer\FieldDateTimeTransformer;
use Mautic\LeadBundle\Form\DataTransformer\FieldTypeTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ListType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class ListType extends AbstractType
{

    private $translator;
    private $fieldChoices;
    private $timezoneChoices;
    private $countryChoices;
    private $regionChoices;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel          = $factory->getModel('lead.list');
        $this->fieldChoices = $listModel->getChoiceFields();

        $this->timezoneChoices = FormFieldHelper::getTimezonesChoices();
        $this->countryChoices  = FormFieldHelper::getCountryChoices();
        $this->regionChoices   = FormFieldHelper::getRegionChoices();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('lead.list', $options));

        $builder->add(
            'name',
            'text',
            array(
                'label'      => 'mautic.core.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );

        $builder->add(
            'alias',
            'text',
            array(
                'label'      => 'mautic.core.alias',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'length'  => 25,
                    'tooltip' => 'mautic.lead.list.help.alias'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'description',
            'textarea',
            array(
                'label'      => 'mautic.core.description',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control editor'),
                'required'   => false
            )
        );

        $builder->add(
            'isGlobal',
            'yesno_button_group',
            array(
                'label' => 'mautic.lead.list.form.isglobal'
            )
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add(
            'filters',
            'collection',
            array(
                'type'           => 'leadlist_filters',
                'options'        => array(
                    'label'     => false,
                    'timezones' => $this->timezoneChoices,
                    'countries' => $this->countryChoices,
                    'regions'   => $this->regionChoices,
                    'fields'    => $this->fieldChoices
                ),
                'error_bubbling' => false,
                'mapped'         => true,
                'allow_add'      => true,
                'allow_delete'   => true,
                'label'          => false
            )
        );

        $builder->add('buttons', 'form_buttons');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Mautic\LeadBundle\Entity\LeadList'
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
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "leadlist";
    }
}