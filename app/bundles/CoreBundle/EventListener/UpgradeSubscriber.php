<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\UpgradeEvent;
use Mautic\CoreBundle\Helper\UTF8Helper;
use Mautic\CoreBundle\IpLookup\AbstractLookup;

/**
 * Class UpgradeSubscriber
 */
class UpgradeSubscriber extends CommonSubscriber
{
    /**
     * @var AbstractLookup
     */
    private $ipLookupService;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CoreEvents::POST_UPGRADE => array('postUpgrade', 0)
        );
    }

    /**
     * @param UpgradeEvent $event
     *
     * @return void
     */
    public function postUpgrade(UpgradeEvent $event)
    {
        $version = $this->factory->getKernel()->getVersion();

        // In the upgrade to 1.3, we included a bugfix for corrupt ip data
        if ($event->isSuccessful() && version_compare($version, '1.3', '<=')) {
            $this->updateIpDetails();
        }
    }

    /**
     * Update the ip_details column to fix the bug in GH #1375
     *
     * @return void
     */
    private function updateIpDetails()
    {
        $connection = $this->factory->getEntityManager()->getConnection();
        $query = $connection->createQueryBuilder()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX . 'ip_addresses', 'ip');

        $results = (array) $query->execute()->fetchAll();

        foreach ($results as $result) {
            $ipDetails = $this->getIpDetails($result['ip_address']);
            $query2 = $connection->createQueryBuilder();

            $query2->update(MAUTIC_TABLE_PREFIX . 'ip_addresses', 'ip')
                ->set('ip.ip_details', $query2->expr()->literal($ipDetails))
                ->where('ip.id = ' . (int) $result['id']);

            $query2->execute();
        }
    }

    /**
     * @param $ip
     *
     * @return string
     */
    private function getIpDetails($ip)
    {
        $ipDetails = $this->getIpLookupService()->setIpAddress($ip)->getDetails();

        return $this->convertToArrayType($ipDetails);
    }

    /**
     * @return AbstractLookup
     */
    private function getIpLookupService()
    {
        if ($this->ipLookupService instanceof AbstractLookup) {
            return $this->ipLookupService;
        }

        $serviceName = $this->factory->getParameter('ip_lookup_service');

        /** @var \Mautic\CoreBundle\Factory\IpLookupFactory $ipServiceFactory */
        $ipServiceFactory = $this->factory->getKernel()->getContainer()->get('mautic.ip_lookup.factory');

        return $ipServiceFactory->getService($serviceName);
    }

    /**
     * Workaround to use the Mautic\CoreBundle\Doctrine\Type\ArrayType::convertToDatabaseValue
     * without having to locate the Doctrine type manager.
     *
     * @param $value
     * @return string
     */
    private function convertToArrayType($value)
    {
        if (!is_array($value)) {
            return (null === $value) ? 'N;' : 'a:0:{}';
        }

        // MySQL will crap out on corrupt UTF8 leading to broken serialized strings
        array_walk(
            $value,
            function (&$entry) {
                $entry = UTF8Helper::toUTF8($entry);
            }
        );

        $serialized = serialize($value);

        return $serialized;
    }
}
