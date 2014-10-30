<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class MapperPermissions
 */
class MapperPermissions extends AbstractPermissions
{

    /**
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->permissions = array(
            'config' => array(
                'full' => 1024
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mapper';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $builder->add('mapper:config', 'button_group', array(
            'choices'  => array(
                'full' => 'mautic.mapper.permissions.full'
            ),
            'label'    => 'mautic.mapper.permissions.config',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'mapper\')'
            ),
            'data'     => (!empty($data['config']) ? $data['config'] : array())
        ));
    }
}
