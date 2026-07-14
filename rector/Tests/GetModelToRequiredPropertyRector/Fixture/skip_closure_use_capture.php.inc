<?php

namespace MauticRector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;

class SkipClosureUseCapture extends CommonController
{
    public function indexAction(): \Closure
    {
        $model = $this->getModel('sms');

        return function () use ($model) {
            return $model->getEntities([]);
        };
    }
}

?>
