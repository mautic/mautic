<?php

namespace Utils\Rector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\SmsBundle\Model\SmsModel;

class ModelFactoryInConstructor
{
    /**
     * @var SmsModel|null
     */
    protected $model;

    public function __construct(ModelFactory $modelFactory)
    {
        $smsModel = $modelFactory->getModel('sms');
        \assert($smsModel instanceof SmsModel);

        $this->model = $smsModel;
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\GetModelToRequiredPropertyRector\Fixture;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\SmsBundle\Model\SmsModel;

class ModelFactoryInConstructor
{
    /**
     * @var SmsModel|null
     */
    protected $model;

    public function __construct(ModelFactory $modelFactory, \Mautic\SmsBundle\Model\SmsModel $smsModel)
    {
        $this->model = $smsModel;
    }
}

?>
