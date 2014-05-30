<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;
use Doctrine\ORM\EntityManager;
use Mautic\Corebundle\Form\DataTransformer\CleanTransformer;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class LeadType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class LeadType extends AbstractType
{

    private $container;
    private $em;

    /**
     * @param Container     $container
     * @param EntityManager $em
     */
    public function __construct(Container $container, EntityManager $em) {
        $this->container = $container;
        $this->em        = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $userTransformer  = new Transformers\UserToIdTransformer($this->em);
        $cleanTransformer = new CleanTransformer();

        $builder->add(
            $builder->create('owner_lookup', 'text', array(
                'label'      => 'mautic.lead.lead.field.owner',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.help.autocomplete',
                ),
                'mapped'     => false,
                'required'   => false
            ))->addViewTransformer($cleanTransformer)
        );

        $builder->add(
            $builder->create('owner', 'hidden', array(
                'required' => false,
            ))
                ->addViewTransformer($userTransformer)
        );

        //get a list of fields
        $fields      = $this->container->get('mautic.model.leadfield')->getEntities();
        $fieldValues = (!empty($options['data'])) ? $options['data']->getFields() : array('filter' => array('isVisible' => true));
        $values      = array();
        foreach ($fieldValues as $v) {
            $field = $v->getField();
            $values[$field->getId()] = $v->getValue();
        }
        foreach ($fields as $field) {
            $attr        = array('class' => 'form-control');
            $definitions = $field->getDefinitions();
            $type        = $field->getType();
            $required    = $field->isRequired();
            $constraints = array();
            if ($required) {
                $constraints[] = new \Symfony\Component\Validator\Constraints\NotBlank(
                    array('message' => 'mautic.lead.customfield.notblank')
                );
            }
            if ($type == 'select' || $type == 'boolean') {
                $choices = array();
                if ($type == 'select' && !empty($definitions['list'])) {
                    $list    = explode('|', $definitions['list']);
                    foreach ($list as $l) {
                        $l = trim($l);
                        $choices[$l] = $l;
                    }
                    $expanded = false;
                }
                if ($type == 'boolean' && !empty($definitions['yes']) && !empty($definitions['no'])) {
                    $expanded = true;
                    $choices  = array(1 => $definitions['yes'], 0 => $definitions['no']);
                    $attr     = array();
                }

                if (!empty($choices)) {
                    $builder->add("field_{$field->getAlias()}", 'choice', array(
                        'choices'     => $choices,
                        'required'    => $required,
                        'label'       => $field->getLabel(),
                        'label_attr'  => array('class' => 'control-label'),
                        'data'        => (isset($values[$field->getId()])) ? $values[$field->getId()] : $field->getDefaultValue(),
                        'attr'        => $attr,
                        'mapped'      => false,
                        'multiple'    => false,
                        'empty_value' => false,
                        'expanded'    => $expanded,
                        'constraints' => $constraints
                    ));
                }
            } else {
                if ($type == 'lookup') {
                    $type                = "text";
                    $attr['data-toggle'] = 'field-lookup';
                    $attr['data-target'] = $field->getAlias();

                    if (!empty($definitions['list'])) {
                        $attr['data-options'] = $definitions['list'];
                    }
                }
                $builder->add("field_{$field->getAlias()}", $type, array(
                    'required'    => $field->getIsRequired(),
                    'label'       => $field->getLabel(),
                    'label_attr'  => array('class' => 'control-label'),
                    'attr'        => $attr,
                    'data'        => (isset($values[$field->getId()])) ? $values[$field->getId()] : $field->getDefaultValue(),
                    'mapped'      => false,
                    'constraints' => $constraints
                ));
            }
        }

        $builder->add('save', 'submit', array(
            'label' => 'mautic.core.form.save',
            'attr'  => array(
                'class' => 'btn btn-primary',
                'icon'  => 'fa fa-check padding-sm-right'
            ),
        ));

        $builder->add('cancel', 'submit', array(
            'label' => 'mautic.core.form.cancel',
            'attr'  => array(
                'class'   => 'btn btn-danger',
                'icon'    => 'fa fa-times padding-sm-right'
            )
        ));

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
            'data_class' => 'Mautic\LeadBundle\Entity\Lead'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "lead";
    }
}