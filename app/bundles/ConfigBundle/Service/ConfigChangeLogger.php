<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Service;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

/**
 * Compare normalized for data and log changes.
 */
class ConfigChangeLogger
{
    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var array
     */
    private $originalNormData;

    /**
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel  = $auditLogModel;
    }

    /**
     * @param array $originalNormData
     *
     * @return ConfigChangeLogger
     */
    public function setOriginalNormData(array $originalNormData)
    {
        $this->originalNormData = $originalNormData;

        return $this;
    }

    /**
     * @param array $postNormData
     */
    public function log(array $postNormData)
    {
        if ($this->originalNormData === null) {
            throw new \RuntimeException('Set original normalized data at first');
        }

        $originalData = $this->normalizeData($this->originalNormData);
        $postData     = $this->normalizeData($postNormData);

        $diff = [];
        foreach ($postData as $key => $value) {
            if ($originalData[$key] != $value) {
                $diff[$key] = $value;
            }
        }

        if (empty($diff)) {
            return;
        }

        $log     = [
            'bundle'    => 'config',
            'object'    => 'config',
            'objectId'  => 0,
            'action'    => 'update',
            'details'   => $diff,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];

        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Some form data (AssetBundle) has 'parameters' inside array too.
     * Normalize all.
     *
     * @param array $data
     *
     * @return array
     */
    private function normalizeData(array $data)
    {
        $key = 'parameters';

        $normData = [];
        foreach ($data as $values) {
            if (array_key_exists($key, $values)) {
                $normData = array_merge($normData, $values[$key]);
            } else {
                $normData = array_merge($normData, $values);
            }
        }

        return $normData;
    }
}
