<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ZohoBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class VtigerPermissions
 * @package Mautic\VtigerBundle\Security\Permissions
 */
class ZohoPermissions extends AbstractPermissions
{

    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('zoho');
        $this->addStandardPermissions('mapper');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName ()
    {
        return 'zoho';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('zoho', 'mapper', $builder, $data);
    }
}
