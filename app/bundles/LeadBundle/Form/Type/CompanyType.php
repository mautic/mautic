<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Form\Type\EntityFieldsBuildFormTrait;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CompanyType
 */
class CompanyType extends AbstractType
{
    use EntityFieldsBuildFormTrait;
    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    private $security;

    private $em;

    /**
     * @param EntityManager $entityManager
     *
     */
    public function __construct(EntityManager $entityManager, CorePermissions $security)
    {
        $this->em = $entityManager;
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->getFormFields($builder, $options, 'company');

        $transformer = new IdToEntityModelTransformer(
            $this->em,
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                'user_list',
                [
                    'label' => 'mautic.lead.lead.field.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr' => [
                        'class' => 'form-control'
                    ],
                    'required' => false,
                    'multiple' => false
                ]
            )
                ->addModelTransformer($transformer)
        );

        if (!empty($options['data']) && $options['data'] instanceof Company) {
            $readonly = !$this->security->hasEntityAccess(
                'lead:leads:editother',
                'lead:leads:editother',
                $options['data']->getCreatedBy()
            );

            $data = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('lead:leads:editother')) {
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

        if (empty($options['update_select'])) {
            $builder->add(
                'buttons',
                'form_buttons',
                [
                    'apply_text' => false
                ]
            );
            $builder->add(
                'updateSelect',
                'hidden',
                [
                    'data' => $options['update_select'],
                    'mapped' => false
                ]
            );
        } else {
            $builder->add(
                'buttons',
                'form_buttons'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'  => 'Mautic\LeadBundle\Entity\Company',
                'isShortForm' => false
            ]
        );

        $resolver->setRequired(['fields', 'update_select']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "company";
    }
}
