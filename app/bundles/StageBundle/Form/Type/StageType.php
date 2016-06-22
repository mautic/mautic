<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class StageType
 */
class StageType extends AbstractType
{
    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    private $security;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->security = $factory->getSecurity();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('stage', $options));

        $builder->add('description', 'textarea', array(
            'label' => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr' => array('class' => 'form-control editor'),
            'required' => false
        ));
        $builder->add('name', 'text', array(
            'label' => 'mautic.core.name',
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('weight', 'number', array(
            'label' => 'mautic.stage.action.weight',
            'label_attr' => array('class' => 'control-label'),
            'attr' =>
                array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.stage.action.weight.help'
                ),
            'precision' => 0,
            'required' => false
        ));


        if (!empty($options['data']) && $options['data'] instanceof Stage) {
            $readonly = !$this->security->hasEntityAccess(
                'stage:stages:publishown',
                'stage:stages:publishother',
                $options['data']->getCreatedBy()
            );

            $data = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('stage:stages:publishown')) {
            $readonly = true;
            $data = false;
        } else {
            $readonly = false;
            $data = true;
        }

        $builder->add('isPublished', 'yesno_button_group', array(
            'read_only' => $readonly,
            'data' => $data
        ));

        $builder->add('publishUp', 'datetime', array(
            'widget' => 'single_text',
            'label' => 'mautic.core.form.publishup',
            'label_attr' => array('class' => 'control-label'),
            'attr' => array(
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format' => 'yyyy-MM-dd HH:mm',
            'required' => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget' => 'single_text',
            'label' => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr' => array(
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format' => 'yyyy-MM-dd HH:mm',
            'required' => false
        ));

        //add category
        $builder->add('category', 'category', array(
            'bundle' => 'stage'
        ));

        $builder->add('buttons', 'form_buttons');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\StageBundle\Entity\Stage',
        ));

        $resolver->setRequired(array('stageActions'));

        $resolver->setOptional(array('actionType'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "stage";
    }
}
