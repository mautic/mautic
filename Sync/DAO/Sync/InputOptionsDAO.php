<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync;

use MauticPlugin\IntegrationsBundle\Exception\InvalidValueException;
use Symfony\Component\Console\Input\InputInterface;
use DateTimeInterface;

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
     * @var string
     */
    private $env;

    /**
     * @var DateTimeInterface|null
     */
    private $startDateTime;

    /**
     * @var DateTimeInterface|null
     */
    private $endDateTime;

    /**
     * @param InputInterface $input
     * 
     * @throws InvalidValueException
     */
    public function __construct(InputInterface $input)
    {
        $this->integration   = $input->getArgument('integration');
        $this->firstTimeSync = (bool) $input->getOption('first-time-sync');
        $this->env           = $input->getOption('env');
        $startDateTimeString = $input->getOption('start-datetime');
        $endDateTimeString   = $input->getOption('end-datetime');

        try {
            $this->startDateTime = ($startDateTimeString) ? new DateTimeImmutable($startDateTimeString) : null;
        } catch (\Exception $e) {
            throw new InvalidValueException("'$startDateTimeString' is not valid. Use 'Y-m-d H:i:s' format like '2018-12-24 20:30:00' or something like '-10 minutes'");
        }

        try {
            $this->endDateTime = ($endDateTimeString) ? new DateTimeImmutable($endDateTimeString) : null;
        } catch (\Exception $e) {
            throw new InvalidValueException("'$endDateTimeString' is not valid. Use 'Y-m-d H:i:s' format like '2018-12-24 20:30:00' or something like '-10 minutes'");
        }
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
     * @return string
     */
    public function getEnv(): string
    {
        return $this->env;
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
}
