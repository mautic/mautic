<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\CommonPermissions;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;

/**
 * Class ApiPermissions
 *
 * @package Mautic\UserBundle\Security\Permissions
 */
class ApiPermissions extends CommonPermissions
{

    /**
     * {@inheritdoc}
     *
     * @param Container     $container
     * @param EntityManager $em
     */
    public function __construct(Container $container, EntityManager $em)
    {
        parent::__construct($container, $em);
            $this->permissions = array(
                'access' => array(
                    'full'     => 1024
                ),
                'clients' => array(
                    'view'          => 1,
                    'editother'     => 4,
                    'create'        => 8,
                    'deleteother'   => 32,
                    'full'          => 1024
                )
            );
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'api';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @param array                $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $builder->add('api:access', 'choice', array(
            'choices'  => array(
                'full'     => 'mautic.api.permissions.granted',
            ),
            'label'    => 'mautic.api.permissions.apiaccess',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'api\')'
            ),
            'data'     => (!empty($data['access']) ? $data['access'] : array())
        ));

        $builder->add('api:clients', 'choice', array(
            'choices'    => array(
                'view'        => 'mautic.core.permissions.view',
                'editother'   => 'mautic.core.permissions.edit',
                'create'      => 'mautic.core.permissions.create',
                'deleteother' => 'mautic.core.permissions.delete',
                'full'        => 'mautic.core.permissions.full'
            ),
            'label'      => 'mautic.api.permissions.clients',
            'label_attr' => array('class' => 'control-label'),
            'expanded'   => true,
            'multiple'   => true,
            'attr'       => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'api\')'
            ),
            'data'      => (!empty($data['clients']) ? $data['clients'] : array())
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @param $name
     * @param $perm
     */
    public function getValue($name, $perm)
    {
        //ensure api is enabled system wide
        if (!$this->container->getParameter('mautic.api_enabled')) {
            return 0;
        } else {
            return parent::getValue($name, $perm);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|mixed
     */
    public function isEnabled() {
        return $this->container->getParameter('mautic.api_enabled', 0);
    }

    /**
     * {@inheritdoc}
     *
     * @param $name
     * @param $level
     * @return array
     */
    protected function getSynonym($name, $level) {
        if ($name == "access" && $level == "granted") {
            $level = "full";
        } elseif ($name == "clients") {
            switch ($level) {
                case "edit":
                    $level = "editother";
                    break;
                case "delete":
                    $level = "deleteother";
                    break;
            }
        }

        return array($name, $level);
    }


    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return string
     */
    public function getPermissionRatio(array $data)
    {
        $totalAvailable = $totalGranted = 0;

        foreach ($this->permissions as $level => $perms) {
            $perms = array_keys($perms);

            if ($level == 'access') {
                $totalAvailable++;
                if (!empty($data[$level]) && in_array('full', $data[$level])) {
                    $totalGranted++;
                }
            } else {
                $totalAvailable += count($perms);

                if (in_array('full', $perms)) {
                    //remove full from total count
                    $totalAvailable--;
                    if (!empty($data[$level]) && in_array('full', $data[$level])) {
                        //user has full access so sum perms minus full
                        $totalGranted += count($perms) - 1;
                        //move on to the next level
                        continue;
                    }
                }

                if (isset($data[$level]))
                    $totalGranted += count($data[$level]);

            }
        }
        return array($totalGranted, $totalAvailable);
    }
}