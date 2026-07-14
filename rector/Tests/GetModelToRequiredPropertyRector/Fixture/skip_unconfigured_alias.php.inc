<?php

namespace MauticRector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;

class SkipUnconfiguredAlias extends CommonController
{
    public function indexAction(): array
    {
        $model = $this->getModel('campaign');

        return $model->getEntities([]);
    }
}

?>
