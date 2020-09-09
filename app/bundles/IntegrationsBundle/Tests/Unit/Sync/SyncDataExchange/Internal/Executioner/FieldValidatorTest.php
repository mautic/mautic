<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\Executioner;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\FieldValidator;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldValidatorTest extends TestCase
{
    /**
     * @var LeadFieldRepository|MockObject
     */
    private $leadFieldRepository;

    /**
     * @var UserNotificationHelper|MockObject
     */
    private $userNotificationHelper;

    /**
     * @var FieldValidator
     */
    private $fieldValidator;

    protected function setup(): void
    {
        $this->leadFieldRepository    = $this->createMock(LeadFieldRepository::class);
        $this->userNotificationHelper = $this->createMock(UserNotificationHelper::class);
        $this->fieldValidator         = new FieldValidator($this->leadFieldRepository, $this->userNotificationHelper);
    }

    public function testValidateFields(): void
    {
        $this->leadFieldRepository->method('getFieldSchemaData')
            ->willReturn([
                'company' => [
                    'alias'             => 'company',
                    'label'             => 'Company',
                    'type'              => 'text',
                    'isUniqueIdentifer' => false,
                    'charLengthLimit'   => 5,
                ],
                'email' => [
                    'alias'             => 'email',
                    'label'             => 'Email',
                    'type'              => 'email',
                    'isUniqueIdentifer' => true,
                    'charLengthLimit'   => 64,
                ],
                'date' => [
                    'alias'             => 'date',
                    'label'             => 'Date',
                    'type'              => 'date',
                    'isUniqueIdentifer' => false,
                    'charLengthLimit'   => null,
                ],
                'time' => [
                    'alias'             => 'time',
                    'label'             => 'Time',
                    'type'              => 'time',
                    'isUniqueIdentifer' => false,
                    'charLengthLimit'   => null,
                ],
                'bool' => [
                    'alias'             => 'bool',
                    'label'             => 'Bool',
                    'type'              => 'boolean',
                    'isUniqueIdentifer' => false,
                    'charLengthLimit'   => null,
                ],
                'number' => [
                    'alias'             => 'number',
                    'label'             => 'Number',
                    'type'              => 'number',
                    'isUniqueIdentifer' => false,
                    'charLengthLimit'   => null,
                ],
            ]);

        $firstChangedObject = (new ObjectChangeDAO('integration', 'lead', '1', 'Lead', '00Q4H00000juXes'))
            ->addField(new FieldDAO('company', new NormalizedValueDAO('string', 'Some company', 'Some company')))
            ->addField(new FieldDAO('email', new NormalizedValueDAO('string', 'email@domain.tld', 'email@domain.tld')))
            ->addField(new FieldDAO('unknown', new NormalizedValueDAO('string', 'something', 'something')));
        $secondChangedObject = (new ObjectChangeDAO('integration', 'lead', '1', 'Lead', '00Q4H00000juXes'))
            ->addField(new FieldDAO('date', new NormalizedValueDAO('datetime', '2020-09-08 10:05:35', '2020-09-08 10:05:35')))
            ->addField(new FieldDAO('time', new NormalizedValueDAO('date', '2020-09-08', '2020-09-08')))
            ->addField(new FieldDAO('number', new NormalizedValueDAO('url', 'https://url', 'https://url')))
            ->addField(new FieldDAO('bool', new NormalizedValueDAO('boolean', 1, true)));
        $changedObjects = [
            $firstChangedObject,
            $secondChangedObject,
        ];

        $this->userNotificationHelper->expects($this->exactly(3))
            ->method('writeNotification')
            ->withConsecutive(
                $this->getNotificationAssertion("Field 'Company' with value 'Some company' exceeded maximum allowed length and was ignored during the sync", $firstChangedObject),
                $this->getNotificationAssertion("Field 'Time' of type 'time' did not match type 'date' and was ignored during the sync", $secondChangedObject),
                $this->getNotificationAssertion("Field 'Number' of type 'number' did not match type 'url' and was ignored during the sync", $secondChangedObject)
            );

        $this->fieldValidator->validateFields('lead', $changedObjects);

        Assert::assertNull($firstChangedObject->getField('company'));
        Assert::assertInstanceOf(FieldDAO::class, $firstChangedObject->getField('email'));
        Assert::assertInstanceOf(FieldDAO::class, $firstChangedObject->getField('unknown'));
        Assert::assertInstanceOf(FieldDAO::class, $secondChangedObject->getField('date'));
        Assert::assertNull($secondChangedObject->getField('time'));
        Assert::assertNull($secondChangedObject->getField('number'));
        Assert::assertInstanceOf(FieldDAO::class, $secondChangedObject->getField('bool'));
    }

    private function getNotificationAssertion(string $message, ObjectChangeDAO $changedObject): array
    {
        return [
            $this->identicalTo($message),
            $this->identicalTo($changedObject->getIntegration()),
            $this->identicalTo($changedObject->getMappedObjectId()),
            $this->identicalTo($changedObject->getObject()),
            $this->identicalTo(0),
            $this->identicalTo(sprintf('SF object %s', $changedObject->getMappedObjectId())),
        ];
    }
}
