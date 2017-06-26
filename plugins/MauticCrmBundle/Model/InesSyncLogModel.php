<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 *
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Model;

use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MauticCrmBundle\Entity\InesSyncLog;

/**
 * Class InesSyncLogModel.
 */
class InesSyncLogModel extends FormModel
{
    public function __construct(EntityManager $em)
    {
        $this->setEntityManager($em);
        $this->_createTableIfNotExists();
    }

    /**
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new InesSyncLog();
        } else {
            $entity = parent::getEntity($id);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @var \MauticPlugin\MauticCrmBundle\Entity\InesSyncLog
     */
    public function saveEntity($entity, $unlock = true)
    {
        $now = new \DateTime();
        $entity->setDateLastUpdate($now);
        parent::saveEntity($entity, $unlock);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCrmBundle:InesSyncLog');
    }

    /**
     * Deletes records that match a list of criteria.
     *
     * @param array $filters
     */
    public function removeEntitiesBy($filters)
    {
        $items = $this->getRepository()->findBy($filters);

        if (!empty($items)) {
            foreach ($items as $item) {
                $this->em->remove($item);
            }
            $this->em->flush();
        }
    }

    /**
     * Returns X pending leads waiting for sync.
     *
     * @param $action string 'UPDATE' | 'DELETE'
     * @param $limit     int
     *
     * @return array
     */
    public function getPendingEntities($action, $limit)
    {
        $pendingItems = $this->getRepository()->findBy(
            [
                'status' => 'PENDING',
                'action' => $action,
            ],
            ['id' => 'ASC'],
            $limit
        );

        return $pendingItems;
    }

    /**
     * Checks whether the queue is empty or not.
     *
     * @param $action string 'UPDATE' | 'DELETE'
     *
     * @return bool
     */
    public function havePendingEntities($action)
    {
        return empty(
            $this->getPendingEntities($action, 1)
        );
    }

    /**
     * Returns queue history, by decreasing update date.
     *
     * @param  $limit int
     *
     * @return array
     */
    public function getAllEntities($limit)
    {
        $allItems = $this->getRepository()->findBy(
            [],
            ['dateLastUpdate' => 'DESC'],
            $limit
        );

        return $allItems;
    }

    private function _createTableIfNotExists()
    {
        $tableName = $this->em->getClassMetadata('MauticCrmBundle:InesSyncLog')->getTableName();

        $schemaManager = $this->em->getConnection()->getSchemaManager();

        if (!$schemaManager->tablesExist([$tableName]) === true) {
            $table = new Table($tableName);

            $table->addColumn('id', 'integer', [
                'Autoincrement' => true,
            ]);
            $table->addUniqueIndex(['id']);

            $table->addColumn('action', 'string');
            $table->addColumn('lead_id', 'integer');
            $table->addColumn('lead_email', 'string');
            $table->addColumn('lead_company', 'string');
            $table->addColumn('date_added', 'datetime');
            $table->addColumn('date_last_update', 'datetime');
            $table->addColumn('status', 'string');
            $table->addColumn('counter', 'integer');

            $schemaManager->createTable($table);
        }
    }
}
