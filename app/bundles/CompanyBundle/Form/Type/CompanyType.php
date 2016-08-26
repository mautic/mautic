<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CompanyBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CompanyBundle\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;

/**
 * Class CompanyType
 */
class CompanyType extends AbstractType
{
    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    private $security;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->security = $factory->getSecurity();
        $this->factory    = $factory;

    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('companyNumber', 'text', array(
            'label' => 'mautic.company.company.number',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('companySource', 'text', array(
            'label' => 'mautic.company.company.source',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('address1', 'text', array(
            'label' => 'mautic.company.address1',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('address2', 'text', array(
            'label' => 'mautic.company.address2',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('annualRevenue', 'text', array(
            'label' => 'mautic.company.annual.revenue',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('city', 'text', array(
            'label' => 'mautic.company.city',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('country', 'choice',
            array(
                'choices'     => FormFieldHelper::getCountryChoices(),
                'required'    => 'false',
                'label'       => 'mautic.company.country',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'            => 'form-control'
                ),
                'mapped'      => false,
                'required' => false,
                'multiple'    => false,
                'expanded'    => false
            ));
        $builder->add('email', 'email', array(
            'label' => 'mautic.company.email',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('fax', 'text', array(
            'label' => 'mautic.company.fax',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));

        $builder->add('name', 'text', array(
            'label' => 'mautic.company.name',
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('numberOfEmployees', 'text', array(
            'label' => 'mautic.company.numberOfEmployees',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));

        $transformer = new IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                'user_list',
                [
                    'label'      => 'mautic.lead.lead.field.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control'
                    ],
                    'required'   => false,
                    'multiple'   => false
                ]
            )
                ->addModelTransformer($transformer)
        );
        $builder->add('phone', 'text', array(
            'label' => 'mautic.company.phone',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('score', 'number', array(
            'label' => 'mautic.company.score',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('state', 'text', array(
            'label' => 'mautic.company.state',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('zipcode', 'text', array(
            'label' => 'mautic.company.zipcode',
            'required' => false,
            'label_attr' => array(
                'class' => 'control-label'
            ), 'attr' => array(
                'class' => 'form-control'
            )));
        $builder->add('website', 'text', array(
        'label' => 'mautic.company.website',
        'required' => false,
        'label_attr' => array(
            'class' => 'control-label'
        ), 'attr' => array(
            'class' => 'form-control'
        )));
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('company', $options));
        $builder->add('description', 'textarea', array(
            'label' => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr' => array('class' => 'form-control editor'),
            'required' => false
        ));

        $builder->add('score', 'number', array(
            'label' => 'mautic.company.score',
            'required' => false,
            'label_attr' => array('class' => 'control-label'),
            'attr' =>
                array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.stage.action.weight.help'
                ),
            'precision' => 0,
            'required' => false
        ));

        if (!empty($options['data']) && $options['data'] instanceof Company) {
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


        $builder->add('buttons', 'form_buttons');
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "company";
    }
}
