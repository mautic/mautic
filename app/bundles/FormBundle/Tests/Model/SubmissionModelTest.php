<?php

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Validator\UploadFieldValidator;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Model\PageModel;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class SubmissionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|IpLookupHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $ipLookupHelper;

    /**
     * @var MockObject|Environment
     */
    private \PHPUnit\Framework\MockObject\MockObject $twigMock;

    /**
     * @var MockObject|FormModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $formModel;

    /**
     * @var MockObject|PageModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $pageModel;

    /**
     * @var MockObject|LeadModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadModel;

    /**
     * @var MockObject|CampaignModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $campaignModel;

    /**
     * @var MockObject|MembershipManager
     */
    private \PHPUnit\Framework\MockObject\MockObject $membershipManager;

    /**
     * @var MockObject|LeadFieldModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadFieldModel;

    /**
     * @var MockObject|CompanyModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $companyModel;

    /**
     * @var MockObject|FormFieldHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $dispatcher;

    /**
     * @var MockObject|Translator
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    private \Mautic\CoreBundle\Twig\Helper\DateHelper $dateHelper;

    /**
     * @var MockObject|UserHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $userHelper;

    /**
     * @var MockObject|EntityManager
     */
    private \PHPUnit\Framework\MockObject\MockObject $entityManager;

    /**
     * @var MockObject|SubmissionRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $submissioRepository;

    /**
     * @var MockObject|LeadRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadRepository;

    /**
     * @var MockObject|Logger
     */
    private \PHPUnit\Framework\MockObject\MockObject $mockLogger;

    /**
     * @var MockObject|UploadFieldValidator
     */
    private \PHPUnit\Framework\MockObject\MockObject $uploadFieldValidatorMock;

    /**
     * @var MockObject|FormUploader
     */
    private \PHPUnit\Framework\MockObject\MockObject $formUploaderMock;

    /**
     * @var MockObject|DeviceTrackingServiceInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $deviceTrackingService;

    /**
     * @var MockObject|UploadedFile
     */
    private \PHPUnit\Framework\MockObject\MockObject $file1Mock;

    /**
     * @var MockObject|RouterInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $router;

    /**
     * @var MockObject|ContactTracker
     */
    private \PHPUnit\Framework\MockObject\MockObject $contactTracker;

    /**
     * @var MockObject|ContactMerger
     */
    private \PHPUnit\Framework\MockObject\MockObject $contactMerger;

    private \Mautic\FormBundle\Model\SubmissionModel $submissionModel;

    /**
     * @var \ReflectionClass<SubmissionModel>
     */
    private \ReflectionClass $submissionModelReflection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $this->twigMock                 = $this->createMock(Environment::class);
        $this->formModel                = $this->createMock(FormModel::class);
        $this->pageModel                = $this->createMock(PageModel::class);
        $this->leadModel                = $this->createMock(LeadModel::class);
        $this->campaignModel            = $this->createMock(CampaignModel::class);
        $this->membershipManager        = $this->createMock(MembershipManager::class);
        $this->leadFieldModel           = $this->createMock(LeadFieldModel::class);
        $this->companyModel             = $this->createMock(CompanyModel::class);
        $this->fieldHelper              = $this->createMock(FormFieldHelper::class);
        $this->dispatcher               = $this->createMock(EventDispatcherInterface::class);
        $this->translator               = $this->createMock(Translator::class);
        $this->dateHelper               = new DateHelper(
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'H:i',
            $this->translator,
            $this->createMock(\Mautic\CoreBundle\Helper\CoreParametersHelper::class)
        );
        $this->userHelper               = $this->createMock(UserHelper::class);
        $this->entityManager            = $this->createMock(EntityManager::class);
        $this->submissioRepository      = $this->createMock(SubmissionRepository::class);
        $this->leadRepository           = $this->createMock(LeadRepository::class);
        $this->mockLogger               = $this->createMock(Logger::class);
        $this->uploadFieldValidatorMock = $this->createMock(UploadFieldValidator::class);
        $this->formUploaderMock         = $this->createMock(FormUploader::class);
        $this->deviceTrackingService    = $this->createMock(DeviceTrackingServiceInterface::class);
        $this->file1Mock                = $this->createMock(UploadedFile::class);
        $this->router                   = $this->createMock(RouterInterface::class);
        $this->contactTracker           = $this->createMock(ContactTracker::class);
        $this->contactMerger            = $this->createMock(ContactMerger::class);

        $this->fieldHelper->method('getFieldFilter')->willReturn('string');

        $this->submissionModel = new SubmissionModel(
            $this->ipLookupHelper,
            $this->twigMock,
            $this->formModel,
            $this->pageModel,
            $this->leadModel,
            $this->campaignModel,
            $this->membershipManager,
            $this->leadFieldModel,
            $this->companyModel,
            $this->fieldHelper,
            $this->uploadFieldValidatorMock,
            $this->formUploaderMock,
            $this->deviceTrackingService,
            new FieldValueTransformer($this->router),
            $this->dateHelper,
            $this->contactTracker,
            $this->contactMerger,
            $this->entityManager,
            $this->createMock(CorePermissions::class),
            $this->dispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $this->translator,
            $this->userHelper,
            $this->mockLogger,
            $this->createMock(CoreParametersHelper::class)
        );

        $this->submissionModelReflection = new \ReflectionClass($this->submissionModel);
    }

    public function testSaveSubmission(): void
    {
        $this->contactTracker->expects($this->any())
            ->method('getContact')
            ->willReturn(new Lead());

        $this->userHelper->expects($this->any())
            ->method('getUser')
            ->willReturn(new User());

        $mockLeadField['email'] = [
            'label'        => 'Email',
            'alias'        => 'email',
            'type'         => 'email',
            'group'        => 'core',
            'group_label'  => 'Core',
            'defaultValue' => '',
            'properties'   => [],
        ];

        $this->leadFieldModel->expects($this->any())
            ->method('getUniqueIdentifierFields')
            ->willReturn(['eyJpc1B1Ymxpc2hlZCI6dHJ1ZSwiaXNVbmlxdWVJZGVudGlmZXIiOnRydWUsIm9iamVjdCI6ImxlYWQifQ==' => ['email' => 'Email']]);

        $this->leadFieldModel->expects($this->any())
            ->method('getFieldListWithProperties')
            ->willReturn($mockLeadField);

        $this->companyModel->method('fetchCompanyFields')->willReturn([]);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [\Mautic\LeadBundle\Entity\Lead::class, $this->leadRepository],
                        [Submission::class, $this->submissioRepository],
                    ]
                )
            );

        $this->leadRepository->expects($this->any())
            ->method('getLeadsByUniqueFields')
            ->willReturn(null);

        $this->file1Mock->expects($this->any())
            ->method('getClientOriginalName')
            ->willReturn('test.jpg');

        $this->router->expects($this->any())
            ->method('generate')
            ->willReturn('test.jpg');

        $this->uploadFieldValidatorMock->expects($this->any())
            ->method('processFileValidation')
            ->willReturn($this->file1Mock);

        $this->ipLookupHelper->expects($this->any())
            ->method('getIpAddress')
            ->willReturn(new IpAddress());

        $request = new Request();
        $request->setMethod('POST');
        $formData = [
            'var_name_1' => 'value 1',
            'var_name_2' => 'value 2',
            'email'      => 'test@email.com',
            'file'       => 'test.jpg',
            'submit'     => '',
            'formId'     => 1,
            'return'     => '',
            'formName'   => 'testform',
            'formid'     => 1,
        ];
        $post      = $formData;
        $server    = $request->server->all();
        $form      = new Form();
        $fields    = $this->getTestFormFields();
        $formModel = new class() extends FormModel {
            public function __construct()
            {
            }
        };
        $formModel->setFields($form, $fields);

        $submissionEvent = $this->submissionModel->saveSubmission($post, $server, $form, $request, true)['submission'];
        $this->assertInstanceOf(SubmissionEvent::class, $submissionEvent);
        $tokens = $submissionEvent->getTokens();
        $this->assertEquals($formData['email'], $tokens['{formfield=email}']);
        $this->assertEquals($formData['file'], $tokens['{formfield=file}']);
        $this->assertSame(['email' => 'test@email.com'], $submissionEvent->getContactFieldMatches());

        $this->assertFalse($this->submissionModel->saveSubmission($post, $server, $form, $request));
    }

    public function testNormalizeValues(): void
    {
        $reflection = new \ReflectionClass(SubmissionModel::class);
        $method     = $reflection->getMethod('normalizeValue');
        $method->setAccessible(true);
        $fieldSession          = 'mautic_'.sha1(uniqid((string) mt_rand(), true));
        $fields[$fieldSession] = [
            'label'        => 'Email',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'email',
            'type'         => 'email',
            'mappedField'  => 'email',
            'mappedObject' => 'contact',
            'id'           => $fieldSession,
        ];

        $field = new Field();
        $this->assertEquals('', $method->invokeArgs($this->submissionModel, ['', $field]));
        $this->assertEquals(1, $method->invokeArgs($this->submissionModel, [1, $field]));
        $this->assertEquals('1, 2', $method->invokeArgs($this->submissionModel, [[1, 2], $field]));

        // field wiht list
        $field = new Field();
        $field->setProperties(
            [
                'list' => [
                    'list' => [
                        [
                            'label' => 'First',
                            'value' => 1,
                        ],
                        [
                            'label' => 'Second',
                            'value' => 2,
                        ],
                    ],
                ],
            ]
        );
        $this->assertEquals('', $method->invokeArgs($this->submissionModel, ['', $field]));
        $this->assertEquals('First', $method->invokeArgs($this->submissionModel, [1, $field]));
        $this->assertEquals('First, Second', $method->invokeArgs($this->submissionModel, [[1, 2], $field]));
    }

    /**
     * @return mixed[]
     */
    private function getTestFormFields(): array
    {
        $fieldSession          = 'mautic_'.sha1(uniqid((string) mt_rand(), true));
        $fields[$fieldSession] = [
            'label'        => 'Email',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'email',
            'type'         => 'email',
            'mappedField'  => 'email',
            'mappedObject' => 'contact',
            'id'           => $fieldSession,
        ];

        $fields['file'] = [
            'label'                   => 'File',
            'showLabel'               => 1,
            'saveResult'              => 1,
            'defaultValue'            => false,
            'alias'                   => 'file',
            'type'                    => 'file',
            'id'                      => 'file',
            'allowed_file_size'       => 1,
            'allowed_file_extensions' => ['jpg', 'gif'],
        ];

        return $fields;
    }

    private function setUpExport(): void
    {
        $this->formModel->expects($this->any())
            ->method('getCustomComponents')
            ->willReturn(['viewOnlyFields' => ['button', 'captcha', 'freetext']]);

        $this->submissioRepository->expects($this->any())
            ->method('getEntities')
            ->willReturn([]);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->submissioRepository);
    }

    public function testExportResultsCsv(): void
    {
        $this->setUpExport();
        $response = $this->submissionModel->exportResults('csv', new Form(), []);

        $this->assertSame($response::class, \Symfony\Component\HttpFoundation\StreamedResponse::class);
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    public function testExportResultsExcel(): void
    {
        $this->setUpExport();
        $response = $this->submissionModel->exportResults('xlsx', new Form(), []);

        $this->assertSame($response::class, \Symfony\Component\HttpFoundation\StreamedResponse::class);
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    private function mockTranslation(): void
    {
        $values = ['Submission ID', 'Contact ID', 'Date Submitted', 'IP address', 'Referrer', 'Form ID'];

        $this->translator->expects($this->any())
            ->method('trans')
            ->with($this->anything())
            ->will($this->returnCallback(fn ($text) => match ($text) {
                'mautic.form.report.submission.id'  => $values[0],
                'mautic.lead.report.contact_id'     => $values[1],
                'mautic.form.result.thead.date'     => $values[2],
                'mautic.core.ipaddress'             => $values[3],
                'mautic.form.result.thead.referrer' => $values[4],
                'mautic.form.report.form_id'        => $values[5],
                default                             => null,
            }));
    }

    /**
     * @throws \ReflectionException
     */
    public function getAccessibleReflectionMethod(string $name): \ReflectionMethod
    {
        $method = $this->submissionModelReflection->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testGetExportHeader(): void
    {
        $form   = new Form();
        $field  = new Field();
        $field2 = new Field();
        $field->setLabel('Email');
        $field2->setType('text');
        $field2->setLabel('Click');
        $field2->setType('button');
        $form->addField('email', $field);
        $form->addField('button', $field2);
        $viewOnlyFields = ['button', 'captcha', 'freetext'];

        $expectedHeader = ['Submission ID', 'Contact ID', 'Date Submitted', 'IP address', 'Referrer', 'Email'];
        $this->mockTranslation();

        try {
            $getExportHeaderRef = $this->getAccessibleReflectionMethod('getExportHeader');
            $header             = $getExportHeaderRef->invokeArgs($this->submissionModel, [$form, $viewOnlyFields]);
        } catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertCount(6, $header);
        $this->assertSame($expectedHeader, $header);
        $this->assertNotContains('Click', $header);
    }

    public function testGetExportHeaderForPage(): void
    {
        $expectedHeader = ['Submission ID', 'Contact ID', 'Form ID', 'Date Submitted', 'IP address', 'Referrer'];
        $this->mockTranslation();

        try {
            $getExportHeaderForPageRef = $this->getAccessibleReflectionMethod('getExportHeaderForPage');
            $header1                   = $getExportHeaderForPageRef->invokeArgs($this->submissionModel, []);
            $header2                   = $getExportHeaderForPageRef->invokeArgs($this->submissionModel, ['xlsx']);
        } catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertCount(6, $header1);
        $this->assertCount(5, $header2);
        $this->assertSame($expectedHeader, $header1);
        $this->assertNotContains('Form ID', $header2);
    }

    public function testPutCsvExportRow(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_csv_export_test_');
        $handle  = fopen($tmpFile, 'r+');
        $header  = ['Submission ID', 'Contact ID', 'Form ID'];

        try {
            $putCsvExportRowRef = $this->getAccessibleReflectionMethod('putCsvExportRow');
            $putCsvExportRowRef->invokeArgs($this->submissionModel, [$handle, $header]);
        } catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        fclose($handle);
        $result = array_map('str_getcsv', file($tmpFile));

        $this->assertCount(1, $result);
        $this->assertSame($header, $result[0]);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function testGetExportRow(): void
    {
        $viewOnlyFields = ['button'];
        $dateSubmitted  = '28-03-2023 12:00';
        $fixture        = [
            'id'            => 1,
            'leadId'        => 123,
            'dateSubmitted' => $dateSubmitted,
            'ipAddress'     => '127.0.0.1',
            'referer'       => 'https://test.com',
            'results'       => [
                [
                    'type'  => 'text',
                    'label' => 'Email',
                    'value' => 'a@b.c',
                ],
                [
                    'type'  => 'button',
                    'label' => 'Click',
                    'value' => true,
                ],
            ],
        ];

        try {
            $getExportRowRef = $this->getAccessibleReflectionMethod('getExportRow');
            $result          = $getExportRowRef->invokeArgs($this->submissionModel, [$fixture, $viewOnlyFields]);
        } catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertSame([1, 123, $this->dateHelper->toFull($dateSubmitted, 'UTC'), '127.0.0.1', 'https://test.com', 'a@b.c'], $result);
    }

    public function testGetExportRowForPage(): void
    {
        $email         = 'a@b.c';
        $formId        = 432;
        $dateSubmitted = '28-03-2023 12:00';
        $fixture       = [
            'id'            => 1,
            'leadId'        => 123,
            'dateSubmitted' => $dateSubmitted,
            'ipAddress'     => '127.0.0.1',
            'referer'       => 'https://test.com',
            'formId'        => $formId,
            'results'       => [
                [
                    'type'  => 'text',
                    'label' => 'Email',
                    'value' => $email,
                ],
            ],
        ];

        try {
            $getExportRowForPageRef = $this->getAccessibleReflectionMethod('getExportRowForPage');
            $row1                   = $getExportRowForPageRef->invokeArgs($this->submissionModel, [$fixture]);
            $row2                   = $getExportRowForPageRef->invokeArgs($this->submissionModel, [$fixture, 'xlsx']);
        } catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsArray($row1);
        $this->assertIsArray($row2);
        $this->assertCount(6, $row1);
        $this->assertCount(5, $row2);
        $this->assertSame([1, 123, $formId, $this->dateHelper->toFull($dateSubmitted, 'UTC'), '127.0.0.1', 'https://test.com'], $row1);
        $this->assertSame([1, 123, $this->dateHelper->toFull($dateSubmitted, 'UTC'), '127.0.0.1', 'https://test.com'], $row2);
        $this->assertNotContains($formId, $row2);
        $this->assertNotContains($email, $row1);
    }
}
