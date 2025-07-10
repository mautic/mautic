<?php

namespace Mautic\ConfigBundle\Service;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Compare normalized for data and log changes.
 */
class ConfigChangeLogger
{
    /**
     * Keys to remove from log.
     *
     * @var string[]
     */
    private array $filterKeys = [
        'transifex_password',
        'mailer_is_owner',
    ];

    /**
     * @var mixed[]|null
     */
    private ?array $originalNormData = null;

    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    /**
     * @return ConfigChangeLogger
     */
    public function setOriginalNormData(array $originalNormData)
    {
        $this->originalNormData = $originalNormData;

        return $this;
    }

    /**
     * Log changes if something was changed.
     * Diff is based on form normalized data before and after post.
     *
     * @see Form::getNormData()
     */
    public function log(array $postNormData): void
    {
        if (null === $this->originalNormData) {
            throw new \RuntimeException('Set original normalized data at first');
        }

        $originalData = $this->normalizeData($this->originalNormData);
        $postData     = $this->filterData($this->normalizeData($postNormData));

        $diff = [];
        foreach ($postData as $key => $value) {
            if (array_key_exists($key, $originalData) && $originalData[$key] != $value) {
                if ($value instanceof UploadedFile) {
                    $value = $value->getFilename();
                }

                $diff[$key] = $value;
            }
        }

        if (empty($diff)) {
            return;
        }

        $log = [
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
     */
    private function normalizeData(array $data): array
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

    /**
     * Filter unused keys from post data.
     */
    private function filterData(array $data): array
    {
        $keys = $this->filterKeys;

        return array_filter($data, fn ($key): bool => !in_array($key, $keys),
            ARRAY_FILTER_USE_KEY);
    }
}
