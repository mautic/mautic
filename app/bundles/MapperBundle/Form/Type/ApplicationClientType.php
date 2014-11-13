<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\MapperBundle\MapperEvents;
use Mautic\MapperBundle\Event\MapperFormEvent;

/**
 * Class ApplicationClientType
 * @package Mautic\MapperBundle\Form\Type
 */
class ApplicationClientType extends AbstractType
{
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FormExitSubscriber('mapper.ApplicationClient', $options));

        $builder->add('title', 'text', array(
            'label'      => 'mautic.mapper.form.title',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $event = new MapperFormEvent($this->factory->getSecurity());
        $this->factory->getDispatcher()->dispatch(MapperEvents::FORM_ON_BUILD, $event);
        $extraFields = $event->getFields();
        foreach($extraFields as $extraField) {
            $builder->add($extraField['child'], $extraField['type'], $extraField['params']);
        }

        $builder->add('application', 'hidden', array(
            'data' => $options['application']
        ));

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
            'data_class' => 'Mautic\MapperBundle\Entity\ApplicationClient'
        ));

        $resolver->setRequired(array('application'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "applicationclient";
    }
}