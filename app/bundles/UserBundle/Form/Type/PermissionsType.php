<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class PermissionsType
 */
class PermissionsType extends AbstractType
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
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->security   = $factory->getSecurity();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $permissionClasses = $this->security->getPermissionClasses();

        $builder->add("permissions-panel-wrapper-start", 'panel_wrapper_start', array(
            'attr' => array(
                'id' => "permissions-panel"
            )
        ));

        //first pass to order headers
        $panels = array();
        foreach ($permissionClasses as $class) {
            if ($class->isEnabled()) {
                $bundle = $class->getName();
                $label  = $this->translator->trans("mautic.{$bundle}.permissions.header");

                $panels[$bundle] = $label;
            }
        }

        //order panels
        uasort($panels, "strnatcmp");

        //build forms
        foreach ($panels as $bundle => $label) {
            $class =& $permissionClasses[$bundle];
            //convert the permission bits from the db into readable names
            $data    = $class->convertBitsToPermissionNames($options['permissions']);
            //get the ratio of granted/total
            list($granted, $total) = $class->getPermissionRatio($data);
            $ratio = (!empty($total)) ?
                      ' <span class="permission-ratio">('
                        . '<span class="' . $bundle . '_granted">' . $granted . '</span>/'
                        . '<span class="' . $bundle . '_total">' . $total . '</span>'
                    . ')</span>'
                : "";

            $builder->add("{$bundle}-panel-start", 'panel_start', array(
                'label'      => $label . $ratio,
                'dataParent' => "#permissions-panel",
                'bodyId'     => "{$bundle}-panel"
            ));
            $class->buildForm($builder, $options, $data);

            $builder->add("{$bundle}-panel-end", 'panel_end');
        }

        $builder->add("permissions-panel-wrapper-end", 'panel_wrapper_end');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "permissions";
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'cascade_validation' => true,
            'permissions'        => array()
        ));
    }
}
