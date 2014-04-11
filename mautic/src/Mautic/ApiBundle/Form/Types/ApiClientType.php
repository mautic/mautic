<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\ORM\EntityRepository;

/**
 * Class ApiClientType
 *
 * @package Mautic\UserBundle\Form\Type
 */
class ApiClientType extends AbstractType
{

    private $bundles;
    private $container;
    private $securityContext;

    /**
     * @param Container        $container
     * @param array            $bundles
     */
    public function __construct(Container $container, SecurityContext $securityContext, array $bundles) {
        $this->container       = $container;
        $this->securityContext = $securityContext;
        $this->bundles         = array_keys($bundles);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', 'text', array(
                'label'      => 'mautic.user.user.form.username',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            )
        );

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
            'data_class' => 'Mautic\ApiBundle\Entity\ApiKey'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "api";
    }
}