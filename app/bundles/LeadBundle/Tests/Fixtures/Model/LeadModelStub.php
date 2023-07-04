<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Fixtures\Model;

use Mautic\CoreBundle\Helper\UserHelper;

class LeadModelStub extends \Mautic\LeadBundle\Model\LeadModel
{
    public function setUserHelper(UserHelper $userHelper): void
    {
        $this->userHelper = $userHelper;
    }
}
