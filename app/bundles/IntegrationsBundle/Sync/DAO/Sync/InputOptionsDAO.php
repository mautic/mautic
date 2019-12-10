<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use MauticPlugin\IntegrationsBundle\Exception\InvalidValueException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;

class InputOptionsDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var bool
     */
    private $firstTimeSync;

    /**
     * @var bool
     */
    private $disablePush;

    /**
     * @var bool
     */
    private $disablePull;

    /**
     * @var ObjectIdsDAO|null
     */
    private $mauticObjectIds;

    /**
     * @var ObjectIdsDAO|null
     */
    private $integrationObjectIds;

    /**
     * @var DateTimeInterface|null
     */
    private $startDateTime;

    /**
     * @var DateTimeInterface|null
     */
    private $endDateTime;

    /**
     * Example $input:
     * [
     *      'integration' => 'Magento', // required
     *      'first-time-sync' => true,
     *      'disable-push' => false,
     *      'disable-pull' => false,
     *      'mautic-object-id' => ['contact:12', 'contact:13'] or a ObjectIdsDAO object,
     *      'integration-object-id' => ['Lead:hfskjdhf', 'Lead:hfskjdhr'] or a ObjectIdsDAO object,
     *      'start-datetime' => '2019-09-12T12:01:20' or a DateTimeInterface object, Expecting UTC timezone
     *      'end-datetime' => '2019-09-12T12:01:20' or a DateTimeInterface object, Expecting UTC timezone
     * ].
     *
     * @param array $input
     *
     * @throws InvalidValueException
     */
    public function __construct(array $input)
    {
        if (empty($input['integration'])) {
            throw new InvalidValueException('An integration must be specified. None provided.');
        }
        $input                      = $this->fixNaming($input);
        $this->integration          = $input['integration'];
        $this->firstTimeSync        = (bool) ($input['first-time-sync'] ?? false);
        $this->disablePush          = (bool) ($input['disable-push'] ?? false);
        $this->disablePull          = (bool) ($input['disable-pull'] ?? false);
        $this->startDateTime        = $this->validateDateTime($input, 'start-datetime');
        $this->endDateTime          = $this->validateDateTime($input, 'end-datetime');
        $this->mauticObjectIds      = $this->validateObjectIds($input, 'mautic-object-id');
        $this->integrationObjectIds = $this->validateObjectIds($input, 'integration-object-id');
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @return bool
     */
    public function isFirstTimeSync(): bool
    {
        return $this->firstTimeSync;
    }

    /**
     * @return bool
     */
    public function pullIsEnabled(): bool
    {
        return !$this->disablePull;
    }

    /**
     * @return bool
     */
    public function pushIsEnabled(): bool
    {
        return !$this->disablePush;
    }

    /**
     * @return ObjectIdsDAO|null
     */
    public function getMauticObjectIds(): ?ObjectIdsDAO
    {
        return $this->mauticObjectIds;
    }

    /**
     * @return ObjectIdsDAO|null
     */
    public function getIntegrationObjectIds(): ?ObjectIdsDAO
    {
        return $this->integrationObjectIds;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStartDateTime(): ?\DateTimeInterface
    {
        return $this->startDateTime;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getEndDateTime(): ?\DateTimeInterface
    {
        return $this->endDateTime;
    }

    /**
     * @param array  $input
     * @param string $optionName
     *
     * @return DateTimeInterface|null
     *
     * @throws InvalidValueException
     */
    private function validateDateTime(array $input, string $optionName): ?DateTimeInterface
    {
        if (empty($input[$optionName])) {
            return null;
        }

        if ($input[$optionName] instanceof DateTimeInterface) {
            return $input[$optionName];
        } else {
            try {
                return is_string($input[$optionName]) ? new DateTimeImmutable($input[$optionName], new DateTimeZone('UTC')) : null;
            } catch (\Throwable $e) {
                throw new InvalidValueException("'$input[$optionName]' is not valid. Use 'Y-m-d H:i:s' format like '2018-12-24 20:30:00' or something like '-10 minutes'");
            }
        }
    }

    /**
     * @param array  $input
     * @param string $optionName
     *
     * @return ObjectIdsDAO|null
     *
     * @throws InvalidValueException
     */
    private function validateObjectIds(array $input, string $optionName): ?ObjectIdsDAO
    {
        if (empty($input[$optionName])) {
            return null;
        }

        if ($input[$optionName] instanceof ObjectIdsDAO) {
            return $input[$optionName];
        } elseif (is_array($input[$optionName])) {
            return ObjectIdsDAO::createFromCliOptions($input[$optionName]);
        } else {
            throw new InvalidValueException("{$optionName} option has an unexpected type. Use an array or ObjectIdsDAO object.");
        }
    }

    /**
     * This method exists only because Mautic leads were renamed to contacts. Users will be able
     * to use the "contact" keywoard and developers "lead" as the integration bundle use "lead" everywhere.
     *
     * @param array $input
     *
     * @return array
     */
    private function fixNaming(array $input): array
    {
        if (empty($input['mautic-object-id'])) {
            return $input;
        }

        if (!is_array($input['mautic-object-id'])) {
            return $input;
        }

        foreach ($input['mautic-object-id'] as $key => $mauticObjectId) {
            $input['mautic-object-id'][$key] = preg_replace(
                '/^contact:/',
                Contact::NAME.':',
                $mauticObjectId
            );
        }

        return $input;
    }
}
