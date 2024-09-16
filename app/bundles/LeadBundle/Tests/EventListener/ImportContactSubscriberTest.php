<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Event\ImportValidateEvent;
use Mautic\LeadBundle\EventListener\ImportContactSubscriber;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

final class ImportContactSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testHandleValidateTags(): void
    {
        $tag = new Tag();
        $tag->setTag('tagLabel');

        $formMock = $this->createMock(Form::class);
        $formMock->method('getData')
            ->willReturn(
                [
                    'name' => 'Bud',
                    'tags' => new ArrayCollection([$tag]),
                ]
            );

        $event      = new ImportValidateEvent('contacts', $formMock);
        $subscriber = new ImportContactSubscriber(
            new class() extends FieldList {
                public function __construct()
                {
                }

                public function getFieldList(bool $byGroup = true, bool $alphabetical = true, array $filters = ['isPublished' => true, 'object' => 'lead']): array
                {
                    return [];
                }
            },
            $this->getCorePermissionsFake(),
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );

        $subscriber->onValidateImport($event);

        Assert::assertSame(['tagLabel'], $event->getTags());
        Assert::assertSame(['name' => 'Bud'], $event->getMatchedFields());
    }

    /**
     * @see https://github.com/mautic/mautic/issues/11080
     */
    public function testHandleFieldWithIntValues(): void
    {
        $formMock = $this->createMock(Form::class);
        $formMock->method('getData')
            ->willReturn(
                [
                    'name'           => 'Bud',
                    'skip_if_exists' => 1,
                ]
            );

        $event      = new ImportValidateEvent('contacts', $formMock);
        $subscriber = new ImportContactSubscriber(
            new class() extends FieldList {
                public function __construct()
                {
                }

                public function getFieldList(bool $byGroup = true, bool $alphabetical = true, array $filters = ['isPublished' => true, 'object' => 'lead']): array
                {
                    return [];
                }
            },
            $this->getCorePermissionsFake(),
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );

        $subscriber->onValidateImport($event);

        Assert::assertSame(['name' => 'Bud', 'skip_if_exists' => 1], $event->getMatchedFields());
    }

    public function testOnImportInitForUknownObject(): void
    {
        $subscriber = new ImportContactSubscriber(
            $this->getFieldListFake(),
            $this->getCorePermissionsFake(),
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );
        $event = new ImportInitEvent('unicorn');
        $subscriber->onImportInit($event);
        Assert::assertFalse($event->objectSupported);
    }

    public function testOnImportInitForContactsObjectWithoutPermissions(): void
    {
        $subscriber = new ImportContactSubscriber(
            $this->getFieldListFake(),
            new class() extends CorePermissions {
                public function __construct()
                {
                }

                /**
                 * @param string $requestedPermission
                 */
                public function isGranted($requestedPermission, $mode = 'MATCH_ALL', $userEntity = null, $allowUnknown = false): bool
                {
                    Assert::assertSame('lead:imports:create', $requestedPermission);

                    return false;
                }
            },
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );
        $event = new ImportInitEvent('contacts');
        $this->expectException(AccessDeniedException::class);
        $subscriber->onImportInit($event);
    }

    public function testOnImportInitForContactsObjectWithPermissions(): void
    {
        $subscriber = new ImportContactSubscriber(
            $this->getFieldListFake(),
            new class() extends CorePermissions {
                public function __construct()
                {
                }

                /**
                 * @param string $requestedPermission
                 */
                public function isGranted($requestedPermission, $mode = 'MATCH_ALL', $userEntity = null, $allowUnknown = false): bool
                {
                    Assert::assertSame('lead:imports:create', $requestedPermission);

                    return true;
                }
            },
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );
        $event = new ImportInitEvent('contacts');
        $subscriber->onImportInit($event);
        Assert::assertTrue($event->objectSupported);
        Assert::assertSame('lead', $event->objectSingular);
        Assert::assertSame('mautic.lead.leads', $event->objectName);
        Assert::assertSame('#mautic_contact_index', $event->activeLink);
        Assert::assertSame('mautic_contact_index', $event->indexRoute);
    }

    public function testOnFieldMappingForUnknownObject(): void
    {
        $subscriber = new ImportContactSubscriber(
            $this->getFieldListFake(),
            $this->getCorePermissionsFake(),
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );
        $event = new ImportMappingEvent('unicorn');
        $subscriber->onFieldMapping($event);
        Assert::assertFalse($event->objectSupported);
    }

    public function testOnFieldMapping(): void
    {
        $subscriber = new ImportContactSubscriber(
            new class() extends FieldList {
                public function __construct()
                {
                }

                /**
                 * @param array<bool|string> $filters
                 *
                 * @return string[]
                 */
                public function getFieldList(bool $byGroup = true, bool $alphabetical = true, array $filters = ['isPublished' => true, 'object' => 'lead']): array
                {
                    return ['some fields'];
                }
            },
            $this->getCorePermissionsFake(),
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );
        $event = new ImportMappingEvent('contacts');
        $subscriber->onFieldMapping($event);
        Assert::assertTrue($event->objectSupported);
        Assert::assertSame(
            [
                'mautic.lead.contact' => [
                    'id' => 'mautic.lead.import.label.id',
                    'some fields',
                ],
                'mautic.lead.company' => [
                    'some fields',
                ],
                'mautic.lead.special_fields' => [
                    'dateAdded'      => 'mautic.lead.import.label.dateAdded',
                    'createdByUser'  => 'mautic.lead.import.label.createdByUser',
                    'dateModified'   => 'mautic.lead.import.label.dateModified',
                    'modifiedByUser' => 'mautic.lead.import.label.modifiedByUser',
                    'lastActive'     => 'mautic.lead.import.label.lastActive',
                    'dateIdentified' => 'mautic.lead.import.label.dateIdentified',
                    'ip'             => 'mautic.lead.import.label.ip',
                    'stage'          => 'mautic.lead.import.label.stage',
                    'doNotEmail'     => 'mautic.lead.import.label.doNotEmail',
                    'ownerusername'  => 'mautic.lead.import.label.ownerusername',
                ],
            ],
            $event->fields
        );
    }

    public function testOnImportProcessForUnknownObject(): void
    {
        $subscriber = new ImportContactSubscriber(
            $this->getFieldListFake(),
            $this->getCorePermissionsFake(),
            $this->getLeadModelFake(),
            $this->getTranslatorFake()
        );
        $import = new Import();
        $import->setObject('unicorn');
        $event = new ImportProcessEvent($import, new LeadEventLog(), []);
        $subscriber->onImportProcess($event);
        $this->expectException(\UnexpectedValueException::class);
        $event->wasMerged();
    }

    public function testOnImportProcessForKnownObject(): void
    {
        $subscriber = new ImportContactSubscriber(
            $this->getFieldListFake(),
            $this->getCorePermissionsFake(),
            new class() extends LeadModel {
                public function __construct()
                {
                }

                /**
                 * @param array<string> $fields
                 * @param array<string> $data
                 */
                public function import($fields, $data, $owner = null, $list = null, $tags = null, $persist = true, LeadEventLog $eventLog = null, $importId = null, $skipIfExists = false): bool
                {
                    return true;
                }
            },
            $this->getTranslatorFake()
        );
        $import = new Import();
        $import->setObject('lead');
        $event = new ImportProcessEvent($import, new LeadEventLog(), []);
        $subscriber->onImportProcess($event);
        Assert::assertTrue($event->wasMerged());
    }

    private function getFieldListFake(): FieldList
    {
        return new class() extends FieldList {
            public function __construct()
            {
            }
        };
    }

    private function getCorePermissionsFake(): CorePermissions
    {
        return new class() extends CorePermissions {
            public function __construct()
            {
            }
        };
    }

    private function getLeadModelFake(): LeadModel
    {
        return new class() extends LeadModel {
            public function __construct()
            {
            }
        };
    }

    private function getTranslatorFake(): TranslatorInterface
    {
        return new class() extends Translator {
            public function __construct()
            {
            }
        };
    }
}
