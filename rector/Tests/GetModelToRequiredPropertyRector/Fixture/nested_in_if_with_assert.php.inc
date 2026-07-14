<?php

namespace MauticRector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\SmsBundle\Model\SmsModel;

class NestedInIfWithAssert extends CommonController
{
    public function deleteAction(int $objectId, bool $isPost): array
    {
        $flashes = [];

        if ($isPost) {
            $model = $this->getModel('sms');
            \assert($model instanceof SmsModel);
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = 'not found';
            }
        }

        return $flashes;
    }
}

?>
-----
<?php

namespace MauticRector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\SmsBundle\Model\SmsModel;

class NestedInIfWithAssert extends CommonController
{
    private \Mautic\SmsBundle\Model\SmsModel $smsModel;
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(\Mautic\SmsBundle\Model\SmsModel $smsModel): void
    {
        $this->smsModel = $smsModel;
    }
    public function deleteAction(int $objectId, bool $isPost): array
    {
        $flashes = [];

        if ($isPost) {
            $entity = $this->smsModel->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = 'not found';
            }
        }

        return $flashes;
    }
}

?>
