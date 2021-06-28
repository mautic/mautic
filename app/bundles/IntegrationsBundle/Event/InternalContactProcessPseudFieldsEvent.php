<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

class InternalContactProcessPseudFieldsEvent extends Event
{
    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $integration;

    public function __construct(Lead $contact, array $fields, string $integration)
    {
        $this->contact     = $contact;
        $this->fields      = $fields;
        $this->integration = $integration;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }
}
