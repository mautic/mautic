<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
    private $dateHelper;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->em         = $factory->getEntityManager();
        $this->dateHelper = $factory->getDate();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('text' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('lead.note', $options));

        $builder->add('text', 'textarea', array(
            'label'      => 'mautic.lead.note.form.text',
            'label_attr' => array('class' => 'control-label sr-only'),
            'attr'       => array('class' => 'mousetrap form-control editor', 'rows' => 10, 'autofocus' => 'autofocus')
        ));

        $builder->add('type', 'choice', array(
            'label'      => 'mautic.lead.note.form.type',
            'choices'    => array(
                'general' => 'mautic.lead.note.type.general',
                'email'   => 'mautic.lead.note.type.email',
                'call'    => 'mautic.lead.note.type.call',
                'meeting' => 'mautic.lead.note.type.meeting',
            ),
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $dt   = $options['data']->getDatetime();
        $data = ($dt == null) ? $this->dateHelper->getDateTime() : $dt;

        $builder->add('dateTime', 'datetime', array(
            'label'      => 'mautic.core.date.added',
            'label_attr' => array('class' => 'control-label'),
            'widget'     => 'single_text',
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
                'preaddon'    => 'fa fa-calendar'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'data'       => $data
        ));

        $builder->add('buttons', 'form_buttons', array(
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\LeadBundle\Entity\LeadNote'
        ));
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return "leadnote";
    }
}