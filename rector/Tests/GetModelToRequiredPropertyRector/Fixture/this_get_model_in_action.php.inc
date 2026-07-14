<?php

namespace MauticRector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\SmsBundle\Model\SmsModel;

class ThisGetModelInAction extends CommonController
{
    public function indexAction(): array
    {
        /** @var SmsModel $model */
        $model = $this->getModel('sms');

        return $model->getEntities([]);
    }
}

?>
-----
<?php

namespace MauticRector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\SmsBundle\Model\SmsModel;

class ThisGetModelInAction extends CommonController
{
    private \Mautic\SmsBundle\Model\SmsModel $smsModel;
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(\Mautic\SmsBundle\Model\SmsModel $smsModel): void
    {
        $this->smsModel = $smsModel;
    }
    public function indexAction(): array
    {
        return $this->smsModel->getEntities([]);
    }
}

?>
