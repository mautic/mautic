<?php
/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;

class DoNotContactParts
{
    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $field
     */
    public function __construct($field)
    {
        $parts         = explode('_', $field);
        $this->type    = $parts[1];
        $this->channel = count($parts) === 3 ? $parts[2] : 'email';
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return int
     */
    public function getParameterType()
    {
        return $this->type === 'bounced' ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED;
    }
}
