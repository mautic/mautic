<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * ModuleRepository
 */
class ModuleRepository extends CommonRepository
{
    /**
     * Update ordering
     *
     * @param  array   $ordering
     * @param  integer $userId
     *
     * @return string
     */
    public function updateOrdering($ordering, $userId)
    {
        $modules = $this->getEntities(
            array(
                'filter' => array(
                    'createdBy' => $userId
                )
            )
        );

        foreach ($modules as &$module) {
            if (isset($ordering[$module->getId()])) {
                $module->setOrdering((int) $ordering[$module->getId()]);
            }
        }

        $this->_em->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder()
    {
        return array(
            array('m.ordering', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'm';
    }
}
