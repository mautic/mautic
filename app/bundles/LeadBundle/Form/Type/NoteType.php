<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
// use Mautic\LeadBundle\Helper\FormFieldHelper;

/**
 * Class NoteType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class NoteType extends AbstractType
{

    private $translator;
    private $em;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->em         = $factory->getEntityManager();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('lead.note', $options));

        $builder->add('text', 'text', array(
            'label'      => 'mautic.lead.note.form.text',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control', 'length' => 50)
        ));

        // $builder->add('group', 'choice', array(
        //     'choices'       => array(
        //         'core'         => 'mautic.lead.note.group.core',
        //         'social'       => 'mautic.lead.field.group.social',
        //         'personal'     => 'mautic.lead.field.group.personal',
        //         'professional' => 'mautic.lead.field.group.professional'
        //     ),
        //     'attr'        => array(
        //         'class'    => 'form-control',
        //         'tooltip'  => 'mautic.lead.field.form.group.help'
        //     ),
        //     'expanded'      => false,
        //     'multiple'      => false,
        //     'label'         => 'mautic.lead.field.form.group',
        //     'empty_value'   => false,
        //     'required'      => false
        // ));

        // $disabled = (!empty($options['data'])) ? $options['data']->isFixed() : false;
        // $new      = (!empty($options['data']) && $options['data']->getAlias()) ? false : true;
        // $default  = ($new) ? 'text' : $options['data']->getType();
        // $fieldHelper = new FormFieldHelper();
        // $fieldHelper->setTranslator($this->translator);
        // $builder->add('type', 'choice', array(
        //     'choices'     => $fieldHelper->getChoiceList(),
        //     'expanded'    => false,
        //     'multiple'    => false,
        //     'label'       => 'mautic.lead.field.form.type',
        //     'empty_value' => false,
        //     'disabled'    => ($disabled || !$new),
        //     'attr'        => array(
        //         'class'    => 'form-control',
        //         'onchange' => 'Mautic.updateLeadFieldProperties(this.value);'
        //     ),
        //     'data'        => $default
        // ));

        // $builder->add('properties', 'collection', array(
        //     'required'        => false,
        //     'allow_add'       => true,
        //     'error_bubbling'  => false
        // ));

        // $builder->add('defaultValue', 'text', array(
        //     'label'      => 'mautic.lead.field.form.defaultvalue',
        //     'label_attr' => array('class' => 'control-label'),
        //     'attr'       => array('class' => 'form-control'),
        //     'required'   => false
        // ));

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
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\LeadBundle\Entity\LeadNote'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "leadnote";
    }
}