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
 * Class ApplicationObjectMapperType
 * @package Mautic\MapperBundle\Form\Type
 */
class ApplicationObjectMapperType extends AbstractType
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
        $builder->addEventSubscriber(new FormExitSubscriber('mapper.ApplicationObjectMapper', $options));

        $event = new MapperFormEvent($this->factory->getSecurity());
        $this->factory->getDispatcher()->dispatch(MapperEvents::OBJECT_FORM_ON_BUILD, $event);
        $extraFields = $event->getFields();
        foreach($extraFields as $extraField) {
            $builder->add($extraField['child'], $extraField['type'], $extraField['params']);
        }

        $builder->add('objectName', 'hidden');

        $builder->add('applicationClientId', 'hidden_entity', array(
            'repository' => 'MauticMapperBundle:ApplicationClient'
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
            'data_class' => 'Mautic\MapperBundle\Entity\ApplicationObjectMapper'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "applicationobjectmapper";
    }
}