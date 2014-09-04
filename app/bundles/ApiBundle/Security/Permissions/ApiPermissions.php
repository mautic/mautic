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
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class ApiPermissions
 *
 * @package Mautic\UserBundle\Security\Permissions
 */
class ApiPermissions extends AbstractPermissions
{

    public function __construct($params)
    {

        parent::__construct($params);

        $this->permissions = array(
            'access' => array(
                'full'     => 1024
            )
        );
        $this->addStandardPermissions('clients', false);
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
        $builder->add('api:access', 'button_group', array(
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

        $this->addStandardFormFields('api', 'clients', $builder, $data, false);
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
        if (empty($this->params['api_enabled'])) {
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
        return !empty($this->params['api_enabled']);
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
            return array($name, "full");
        } else {
            return parent::getSynonym($name, $level);
        }
    }
}