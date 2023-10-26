<?php

namespace Mautic\DashboardBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * WidgetRepository.
 */
class WidgetRepository extends CommonRepository
{
    /**
     * Update widget ordering.
     *
     * @param array $ordering
     * @param int   $userId
     *
     * @return string
     */
    public function updateOrdering($ordering, $userId)
    {
        $widgets = $this->getEntities(
            [
                'filter' => [
                    'createdBy' => $userId,
                ],
            ]
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
        return [
            ['w.ordering', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'w';
    }
}
