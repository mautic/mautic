<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\AuditLog;

/**
 * Class AuditLogModel
 *
 * @package Mautic\CoreBundle\Model
 */
class AuditLogModel extends CommonModel
{

    /**
     * Writes an entry to the audit log
     *
     * @param array $args [bundle, object, objectId, action, details, ipAddress]
     */
    public function writeToLog(array $args)
    {
        $bundle     = (isset($args["bundle"])) ? $args["bundle"] : "";
        $object     = (isset($args["object"])) ? $args["object"] : "";
        $objectId   = (isset($args["objectId"])) ? $args["objectId"] : "";
        $action     = (isset($args["action"])) ? $args["action"] : "";
        $details    = (isset($args["details"])) ? $args["details"] : "";
        $ipAddress  = (isset($args["ipAddress"])) ? $args["ipAddress"] : "";

        $log = new AuditLog();
        $log->setBundle($bundle);
        $log->setObject($object);
        $log->setObjectId($objectId);
        $log->setAction($action);
        $log->setDetails($details);
        $log->setIpAddress($ipAddress);
        $token = $this->container->get('security.context')->getToken();
        $userId = (!empty($token)) ? $token->getUser()->getId() : 0;
        $log->setUserId($userId);

        $this->em->getRepository("MauticCoreBundle:AuditLog")->saveEntity($log);
    }
}