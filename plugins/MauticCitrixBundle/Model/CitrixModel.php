<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Model;

use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Model\FormModel;
use Doctrine\DBAL\Schema\Table;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEvent;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;

/**
 * Class CitrixModel
 */
class CitrixModel extends FormModel
{

    private $container;

    public function __construct()
    {
        $this->container = CitrixHelper::getContainer();
        $this->setEntityManager($this->container->get('doctrine')->getManager());
        $this->_createTableIfNotExists();
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticCitrixBundle\Entity\CitrixEventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCitrixBundle:CitrixEvent');
    }

    /**
     *
     * @param string $product
     * @param string $email
     * @param string $eventName
     * @param string $eventType
     * @param \DateTime $eventDate
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function addEvent($product, $email, $eventName, $eventType, \DateTime $eventDate = null)
    {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            CitrixHelper::log('addEvent: incorrect data');
            return;
        }
        $citrixEvent = new CitrixEvent();
        $citrixEvent->setProduct($product);
        $citrixEvent->setEmail($email);
        $citrixEvent->setEventName($eventName);
        $citrixEvent->setEventType($eventType);

        if (null !== $eventDate) {
            $citrixEvent->setEventDate($eventDate);
        }

        $this->em->persist($citrixEvent);
        $this->em->flush();

        $this->_triggerCampaignEvents($product, $email);
    }

    /**
     * @param string $product
     * @param string $email
     * @return array
     */
    public function getEventsByLeadEmail($product, $email)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return []; // is not a valid citrix product
        }

        return $this->getRepository()->findByEmail($product, $email);
    }

    /**
     * @param string $product
     * @param string $eventName
     * @param string $eventType
     * @return array
     */
    public function getEmailsByEvent($product, $eventName, $eventType)
    {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            return []; // is not a valid citrix product
        }
        $citrixEvents = $this->getRepository()->findBy(
            array(
                'product' => $product,
                'eventName' => $eventName,
                'eventType' => $eventType,
            )
        );

        $emails = [];
        if (0 !== count($citrixEvents)) {
            $emails = array_map(
                function (\MauticPlugin\MauticCitrixBundle\Entity\CitrixEvent $citrixEvent) {
                    return $citrixEvent->getEmail();
                },
                $citrixEvents
            );
        }

        return $emails;
    }

    /**
     * @param string $product
     * @return array
     */
    public function getDistinctEventNames($product)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return []; // is not a valid citrix product
        }
        $dql = sprintf(
            "SELECT DISTINCT(c.eventName) FROM MauticCitrixBundle:CitrixEvent c WHERE c.product='%s'",
            $product
        );
        $query = $this->em->createQuery($dql);
        $items = $query->getResult();

        return array_map(
            function ($item) {
                return array_pop($item);
            },
            $items
        );
    }

    /**
     * @param string $product
     * @param string $email
     * @param string $eventType
     * @param array $eventNames
     * @return int
     */
    public function countEventsBy($product, $email, $eventType, array $eventNames = [])
    {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            return 0; // is not a valid citrix product
        }
        $dql = sprintf(
            "SELECT COUNT(c.id) as cant FROM MauticCitrixBundle:CitrixEvent c WHERE c.product='%s' and c.email='%s' AND c.eventType='%s' ",
            $product,
            $email,
            $eventType
        );

        if (0 !== count($eventNames)) {
            $dql .= sprintf(
                'AND c.eventName IN (%s)',
                implode(
                    ',',
                    array_map(
                        function ($name) {
                            return "'".$name."'";
                        },
                        $eventNames
                    )
                )
            );
        }

        $query = $this->em->createQuery($dql);

        return (int)$query->getResult()[0]['cant'];
    }

    /**
     * @param string $product
     * @param string $eventName
     * @param string $eventType
     * @param array $emailsToAdd
     * @param array $emailsToRemove
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function batchAddAndRemove(
        $product,
        $eventName,
        $eventType,
        array $emailsToAdd = [],
        array $emailsToRemove = []
    ) {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            return;
        }
        // Add events
        if (0 !== count($emailsToAdd)) {
            foreach ($emailsToAdd as $email) {
                $citrixEvent = new CitrixEvent;
                $citrixEvent->setEmail($email);
                $citrixEvent->setEventName($eventName);
                $citrixEvent->setEventType($eventType);
                $this->em->persist($citrixEvent);
            }
        }

        // Delete events
        if (0 !== count($emailsToRemove)) {
            $citrixEvents = $this->getRepository()->findBy(
                array(
                    'eventName' => $eventName,
                    'eventType' => $eventType,
                    'email' => $emailsToRemove,
                )
            );
            foreach ($citrixEvents as $citrixEvent) {
                $this->em->remove($citrixEvent);
            }
        }

        if (0 !== count($emailsToAdd) || 0 !== count($emailsToRemove)) {
            $this->em->flush();
        }

        if (0 !== count($emailsToAdd)) {
            foreach ($emailsToAdd as $email) {
                $this->_triggerCampaignEvents($product, $email);
            }
        }
    }

    /**
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function _createTableIfNotExists()
    {
        $tableName = 'plugin_citrix_events';
        $sm = $this->em->getConnection()->getSchemaManager();
        if (!$sm->tablesExist([$tableName])) {
            $table = new Table($tableName);
            $col = $table->addColumn('id', 'integer');
            $col->setAutoincrement(true);
            $table->addColumn('product', 'string', ['length' => 20]);
            $table->addColumn('email', 'string', ['length' => 255]);
            $table->addColumn('event_name', 'string', ['length' => 255]);
            $table->addColumn('event_type', 'string', ['length' => 50]);
            $table->addColumn('event_date', 'datetime');
            $table->setPrimaryKey(['id']);
            $table->addIndex(['product', 'email'], 'citrix_event_email');
            $table->addIndex(['product', 'event_name', 'event_type'], 'citrix_event_name');
            $table->addIndex(['product', 'event_type', 'event_date'], 'citrix_event_type');
            $table->addIndex(['product', 'email', 'event_type'], 'citrix_product');
            $table->addIndex(['product', 'email', 'event_type', 'event_name'], 'citrix_product_name');
            $table->addIndex(['event_date'], 'citrix_event_date');
            $sm->createTable($table);

        }
    }

    /**
     * @param string $product
     * @param string $email
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function _triggerCampaignEvents($product, $email)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return; // is not a valid citrix product
        }
        /** @var LeadModel $leadModel */
        $leadModel = $this->container->get('mautic.model.factory')->getModel('lead');
        $lead = $leadModel->getRepository()->getLeadByEmail($email);
        if (array_key_exists('id', $lead)) {
            $leadId = (int)$lead['id'];
            $entity = $leadModel->getEntity($leadId);
            $leadModel->setCurrentLead($entity);
            /** @var EventModel $eventModel */
            $eventModel = $this->container->get('mautic.model.factory')->getModel('campaign.event');
            $eventModel->triggerEvent('citrix.event.'.$product);
        }
    }
}
