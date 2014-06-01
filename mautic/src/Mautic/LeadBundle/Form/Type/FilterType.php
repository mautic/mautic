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
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FilterType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class FilterType extends AbstractType
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
        $builder->add('glue', 'collection', array(
            'allow_add' => true,
            'allow_delete' => true
        ));

        $builder->add('operator', 'collection', array(
            'allow_add' => true,
            'allow_delete' => true
        ));

        $builder->add('filter', 'collection', array(
            'allow_add' => true,
            'allow_delete' => true
        ));

        $builder->add('display', 'collection', array(
            'allow_add' => true,
            'allow_delete' => true
        ));

        $builder->add('field', 'collection', array(
            'allow_add' => true,
            'allow_delete' => true
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "leadlist_filters";
    }
}