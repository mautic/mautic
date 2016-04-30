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
 * WidgetRepository
 */
class WidgetRepository extends CommonRepository
{
    /**
     * Update widget ordering
     *
     * @param  array   $ordering
     * @param  integer $userId
     *
     * @return string
     */
    public function updateOrdering($ordering, $userId)
    {
        $widgets = $this->getEntities(
            array(
                'filter' => array(
                    'createdBy' => $userId
                )
            )
        );

        foreach ($widgets as &$widget) {
            if (isset($ordering[$widget->getId()])) {
                $widget->setOrdering((int) $ordering[$widget->getId()]);
            }
        }

        $this->saveEntities($widgets);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder()
    {
        return array(
            array('w.ordering', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'w';
    }
}
