<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Fixtures\Model;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Model\LeadModel;

class LeadModelStub extends LeadModel
{
    public function setUserHelper(UserHelper $userHelper): void
    {
        $this->userHelper = $userHelper;
    }
}
