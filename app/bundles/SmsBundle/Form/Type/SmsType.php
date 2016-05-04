<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SmsType
 *
 * @package Mautic\SmsBundle\Form\Type
 */
class SmsType extends AbstractType
{
    private $translator;
    private $em;
    private $request;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator   = $factory->getTranslator();
        $this->em           = $factory->getEntityManager();
        $this->request      = $factory->getRequest();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html', 'customHtml' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('sms.sms', $options));

        $builder->add(
            'name',
            'text',
            array(
                'label'      => 'mautic.sms.form.internal.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );

        $builder->add(
            'description',
            'textarea',
            array(
                'label'      => 'mautic.sms.form.internal.description',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => false
            )
        );

        $builder->add(
            'message',
            'textarea',
            array(
                'label'      => 'mautic.sms.form.message',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control',
                    'rows'  => 6
                )
            )
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add(
            'publishUp',
            'datetime',
            array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            )
        );

        $builder->add(
            'publishDown',
            'datetime',
            array(
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime'
                ),
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false
            )
        );

        //add category
        // $builder->add(
        //     'category',
        //     'category',
        //     array(
        //         'bundle' => 'email'
        //     )
        // );

        //add lead lists
        $transformer = new IdToEntityModelTransformer($this->em, 'MauticLeadBundle:LeadList', 'id', true);
        $builder->add(
            $builder->create(
                'lists',
                'leadlist_choices',
                array(
                    'label'      => 'mautic.sms.form.list',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'multiple'   => true,
                    'expanded'   => false,
                    'required'   => true
                )
            )
                ->addModelTransformer($transformer)
        );

        $builder->add(
            'language',
            'locale',
            array(
                'label'      => 'mautic.core.language',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control'
                ),
                'required'   => false,
            )
        );

        $builder->add('buttons', 'form_buttons');
        $builder->add('smsType', 'hidden');

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                'form_buttons',
                array(
                    'apply_text' => false
                )
            );
            $builder->add(
                'updateSelect',
                'hidden',
                array(
                    'data'   => $options['update_select'],
                    'mapped' => false
                )
            );
        } else {
            $builder->add(
                'buttons',
                'form_buttons'
            );
        }

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
                'data_class' => 'Mautic\SmsBundle\Entity\Sms'
            )
        );

        $resolver->setOptional(array('update_select'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "sms";
    }
}