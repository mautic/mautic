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

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;

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

    private array $options;

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
        $this->options              = $this->validateOptions($input);
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    public function isFirstTimeSync(): bool
    {
        return $this->firstTimeSync;
    }

    public function pullIsEnabled(): bool
    {
        return !$this->disablePull;
    }

    public function pushIsEnabled(): bool
    {
        return !$this->disablePush;
    }

    public function getMauticObjectIds(): ?ObjectIdsDAO
    {
        return $this->mauticObjectIds;
    }

    public function getIntegrationObjectIds(): ?ObjectIdsDAO
    {
        return $this->integrationObjectIds;
    }

    public function getStartDateTime(): ?\DateTimeInterface
    {
        return $this->startDateTime;
    }

    public function getEndDateTime(): ?\DateTimeInterface
    {
        return $this->endDateTime;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
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

    private function validateOptions(array $input): array
    {
        if (is_array($input['options'] ?? null)) {
            return $input['options'];
        }

        $options = [];

        if (is_array($input['option'] ?? null)) {
            foreach ($input['option'] as $option) {
                $parsedOption = explode(':', $option);
                if (2 === count($parsedOption)) {
                    $options[$parsedOption[0]] = $parsedOption[1];
                }
            }
        }

        return $options;
    }
}
