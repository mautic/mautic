<?php

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\CoreBundle\Translation\Translator;
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
use Symfony\Component\Routing\RouterInterface;

class SubmissionModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var MockObject|TemplatingHelper
     */
    private $templatingHelperMock;

    /**
     * @var MockObject|FormModel
     */
    private $formModel;

    /**
     * @var MockObject|PageModel
     */
    private $pageModel;

    /**
     * @var MockObject|LeadModel
     */
    private $leadModel;

    /**
     * @var MockObject|CampaignModel
     */
    private $campaignModel;

    /**
     * @var MockObject|MembershipManager
     */
    private $membershipManager;

    /**
     * @var MockObject|LeadFieldModel
     */
    private $leadFieldModel;

    /**
     * @var MockObject|CompanyModel
     */
    private $companyModel;

    /**
     * @var MockObject|FormFieldHelper
     */
    private $fieldHelper;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|Translator
     */
    private $translator;

    /**
     * @var MockObject|DateHelper
     */
    private $dateHelper;

    /**
     * @var MockObject|UserHelper
     */
    private $userHelper;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|SubmissionRepository
     */
    private $submissioRepository;

    /**
     * @var MockObject|LeadRepository
     */
    private $leadRepository;

    /**
     * @var MockObject|Logger
     */
    private $mockLogger;

    /**
     * @var MockObject|UploadFieldValidator
     */
    private $uploadFieldValidatorMock;

    /**
     * @var MockObject|FormUploader
     */
    private $formUploaderMock;

    /**
     * @var MockObject|DeviceTrackingServiceInterface
     */
    private $deviceTrackingService;

    /**
     * @var MockObject|UploadedFile
     */
    private $file1Mock;

    /**
     * @var MockObject|RouterInterface
     */
    private $router;

    /**
     * @var MockObject|ContactTracker
     */
    private $contactTracker;

    /**
     * @var MockObject|ContactMerger
     */
    private $contactMerger;

    /**
     * @var SubmissionModel
     */
    private $submissionModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $this->templatingHelperMock     = $this->createMock(TemplatingHelper::class);
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
        $this->dateHelper               = $this->createMock(DateHelper::class);
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

        $this->submissionModel          = new SubmissionModel(
            $this->ipLookupHelper,
            $this->templatingHelperMock,
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
            $this->contactMerger
        );

        $this->submissionModel->setDispatcher($this->dispatcher);
        $this->submissionModel->setTranslator($this->translator);
        $this->submissionModel->setEntityManager($this->entityManager);
        $this->submissionModel->setUserHelper($this->userHelper);
        $this->submissionModel->setLogger($this->mockLogger);
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
                        ['MauticLeadBundle:Lead', $this->leadRepository],
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

        /** @var SubmissionEvent $submissionEvent */
        $submissionEvent = $this->submissionModel->saveSubmission($post, $server, $form, $request, true)['submission'];
        $this->assertInstanceOf(SubmissionEvent::class, $submissionEvent);
        $tokens = $submissionEvent->getTokens();
        $this->assertEquals($formData['email'], $tokens['{formfield=email}']);
        $this->assertEquals($formData['file'], $tokens['{formfield=file}']);
        $this->assertSame(['email' => 'test@email.com'], $submissionEvent->getContactFieldMatches());

        $this->assertFalse($this->submissionModel->saveSubmission($post, $server, $form, $request));
    }

    public function testNormalizeValues()
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
}
