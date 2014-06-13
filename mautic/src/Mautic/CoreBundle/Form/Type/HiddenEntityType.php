<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class HiddenEntityType extends AbstractType
{

    /**
     * @var
     */
    private $em;

    public function __construct (MauticFactory $factory)
    {
        $this->em = $factory->getEntityManager();
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new $options['transformer']($this->em, $options['repository'], $options['identifier']);
        $builder->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array("repository")
        );

        $resolver->setDefaults(array(
            'transformer' => 'Mautic\CoreBundle\Form\DataTransformer\EntityToIdTransformer',
            'identifier'  => 'id'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'hidden_entity';
    }
}