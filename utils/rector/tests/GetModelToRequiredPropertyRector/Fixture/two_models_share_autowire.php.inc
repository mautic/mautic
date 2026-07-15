<?php

namespace Utils\Rector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\SmsBundle\Model\SmsModel;

class TwoModelsShareAutowire extends CommonController
{
    public function indexAction(): array
    {
        /** @var SmsModel $smsModel */
        $smsModel = $this->getModel('sms');

        return $smsModel->getEntities([]);
    }

    public function emailAction(): array
    {
        /** @var EmailModel $emailModel */
        $emailModel = $this->getModel('email');

        return $emailModel->getEntities([]);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\SmsBundle\Model\SmsModel;

class TwoModelsShareAutowire extends CommonController
{
    private \Mautic\EmailBundle\Model\EmailModel $emailModel;
    private \Mautic\SmsBundle\Model\SmsModel $smsModel;
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(\Mautic\SmsBundle\Model\SmsModel $smsModel, \Mautic\EmailBundle\Model\EmailModel $emailModel): void
    {
        $this->smsModel = $smsModel;
        $this->emailModel = $emailModel;
    }
    public function indexAction(): array
    {
        return $this->smsModel->getEntities([]);
    }

    public function emailAction(): array
    {
        return $this->emailModel->getEntities([]);
    }
}

?>
