<?php

namespace MauticRector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;

class SkipReassignedVariable extends CommonController
{
    public function indexAction(): array
    {
        $model = $this->getModel('sms');
        $model = null;

        return [$model];
    }
}

?>
