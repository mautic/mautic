<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

/**
 * AuditLogRepository
 */
class AuditLogRepository extends CommonRepository
{
	/**
     * Get array of objects which belongs to the object
     *
     * @param string $object
     * @param integer $id
     * @param intener $limit of items
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLogForObject($object = null, $id = null, $limit = 100)
    {
        $query = $this->createQueryBuilder('al')
            ->select('al.userName, al.userId, al.bundle, al.object, al.objectId, al.action, al.details, al.dateAdded, al.ipAddress');

        if ($object && $id) {
            $query->where('al.object = :object')
                ->andWhere('al.objectId = :id')
                ->setParameter('object', $object)
                ->setParameter('id', $id);
        }

        $query->orderBy('al.dateAdded', 'DESC')
            ->setMaxResults($limit);

        $logs = $query->getQuery()
            ->getArrayResult();

        // Format log details which are not strings
        foreach ($logs as &$log) {
            // Special case for Form Fields
            if (isset($log['details']['fields']) && is_array($log['details']['fields']) && $log['details']['fields']) {
                $fields = array();
                foreach ($log['details']['fields'] as $field) {
                    if (isset($field['label'][1]) && $field['label'][1]) {
                        $fields[] = $field['label'][1];
                    }
                }

                $log['details']['fields'][1] = implode(', ', $fields);

                if (!$log['details']['fields'][1]) {
                    unset($log['details']['fields']);
                }
            }

        	foreach ($log['details'] as &$detail) {
        		if (isset($detail[1])){
                    $this->variableToText($detail[1]);
                } else {
                    $detail[1] = '';
                }

                if (isset($detail[0])){
                    $this->variableToText($detail[0]);
                } else {
                    $detail[0] = '';
                }
        	}
        }

        return $logs;
    }

    /**
     * If variable is boolean/array, it returns human alternative.
     *
     * @param mixed $object
     *
     * @return mixed
     */
    public function variableToText(&$variable)
    {
        if ($variable === true) {
            $variable = 'mautic.core.form.yes';
        }
        if ($variable === false) {
            $variable = 'mautic.core.form.no';
        }
        if (is_array($variable)) {
            $tmp = array();
            foreach ($variable as $key => $var) {
                if (is_string($var)) {
                    $tmp[] = $key . ': ' . $var;
                }
                if (is_array($var) || is_bool($var)) {
                    $tmp[] = $this->variableToText($var);
                }
            }
            $variable = implode(', ', $tmp);
        }
    }
}
