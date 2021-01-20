<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CompanyMergeEvent.
 */
class CompanyMergeEvent extends Event
{
    private $victor;

    private $loser;

    public function __construct(Company $victor, Company $loser)
    {
        $this->victor = $victor;
        $this->loser  = $loser;
    }

    /**
     * Returns the victor (loser merges into the victor).
     *
     * @return Company
     */
    public function getVictor()
    {
        return $this->victor;
    }

    /**
     * Returns the loser (loser merges into the victor).
     */
    public function getLoser()
    {
        return $this->loser;
    }
}
