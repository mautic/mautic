<?php

namespace Mautic\FormBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Collector\MappedObjectCollectorInterface;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Validator\UploadFieldValidator;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Model\PageModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class FormTestAbstract extends TestCase
{
    protected static $mockId   = 123;

    protected static $mockName = 'Mock test name';

    protected $mockTrackingId;

    protected $formRepository;

    protected $leadFieldModel;

    /**
     * @var MockObject|LeadModel
     */
    protected $leadModel;

    /**
     * @var MockObject|FormFieldHelper
     */
    protected $fieldHelper;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    protected function setUp(): void
    {
        $this->mockTrackingId = hash('sha1', uniqid((string) mt_rand()));
    }

    /**
     * @return FormModel
     */
    protected function getFormModel()
    {
        $requestStack          = $this->createMock(RequestStack::class);
        $twigMock              = $this->createMock(Environment::class);
        $themeHelper           = $this->createMock(ThemeHelperInterface::class);
        $formActionModel       = $this->createMock(ActionModel::class);
        $formFieldModel        = $this->createMock(FieldModel::class);
        $this->leadModel       = $this->createMock(LeadModel::class);
        $this->fieldHelper     = $this->createMock(FormFieldHelper::class);
        $primaryCompanyHelper  = $this->createMock(PrimaryCompanyHelper::class);
        $dispatcher            = $this->createMock(EventDispatcher::class);
        $translator            = $this->createMock(Translator::class);
        $entityManager         = $this->createMock(EntityManager::class);
        $formUploaderMock      = $this->createMock(FormUploader::class);
        $contactTracker        = $this->createMock(ContactTracker::class);
        $this->leadFieldModel  = $this->createMock(LeadFieldModel::class);
        $this->formRepository  = $this->createMock(FormRepository::class);
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $tableSchemaHelper     = $this->createMock(TableSchemaHelper::class);
        $mappedObjectCollector = $this->createMock(MappedObjectCollectorInterface::class);

        $contactTracker->expects($this
            ->any())
            ->method('getContact')
            ->willReturn($this
                ->returnValue(['id' => self::$mockId, 'name' => self::$mockName])
            );

        $entityManager->expects($this
            ->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [\Mautic\FormBundle\Entity\Form::class, $this->formRepository],
                    ]
                )
            );

        $formModel = new FormModel(
            $requestStack,
            $twigMock,
            $themeHelper,
            $formActionModel,
            $formFieldModel,
            $this->fieldHelper,
            $primaryCompanyHelper,
            $this->leadFieldModel,
            $formUploaderMock,
            $contactTracker,
            $columnSchemaHelper,
            $tableSchemaHelper,
            $mappedObjectCollector,
            $entityManager,
            $this->createMock(CorePermissions::class),
            $dispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $translator,
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class),
        );

        return $formModel;
    }

    /**
     * @return SubmissionModel
     */
    protected function getSubmissionModel()
    {
        $ipLookupHelper             = $this->createMock(IpLookupHelper::class);
        $twigMock                   = $this->createMock(Environment::class);
        $formModel                  = $this->createMock(FormModel::class);
        $pageModel                  = $this->createMock(PageModel::class);
        $leadModel                  = $this->createMock(LeadModel::class);
        $campaignModel              = $this->createMock(CampaignModel::class);
        $membershipManager          = $this->createMock(MembershipManager::class);
        $leadFieldModel             = $this->createMock(LeadFieldModel::class);
        $companyModel               = $this->createMock(CompanyModel::class);
        $fieldHelper                = $this->createMock(FormFieldHelper::class);
        $dispatcher                 = $this->createMock(EventDispatcher::class);
        $translator                 = $this->createMock(Translator::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $dateHelper                 = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $translator,
            $this->coreParametersHelper
        );
        $contactTracker           = $this->createMock(ContactTracker::class);
        $userHelper               = $this->createMock(UserHelper::class);
        $entityManager            = $this->createMock(EntityManager::class);
        $formRepository           = $this->createMock(SubmissionRepository::class);
        $leadRepository           = $this->createMock(LeadRepository::class);
        $mockLogger               = $this->createMock(LoggerInterface::class);
        $uploadFieldValidatorMock = $this->createMock(UploadFieldValidator::class);
        $formUploaderMock         = $this->createMock(FormUploader::class);
        $deviceTrackingService    = $this->createMock(DeviceTrackingServiceInterface::class);
        $file1Mock                = $this->createMock(UploadedFile::class);
        $router                   = $this->createMock(RouterInterface::class);
        $contactMerger            = $this->createMock(ContactMerger::class);
        $router->method('generate')->willReturn('absolute/path/somefile.jpg');
        $fieldsWithUniqueIdentifier = $this->createMock(FieldsWithUniqueIdentifier::class);

        $lead                     = new Lead();
        $lead->setId(123);

        $fieldsWithUniqueIdentifier->expects($this->any())
            ->method('getFieldsWithUniqueIdentifier')
            ->willReturn(['eyJpc1B1Ymxpc2hlZCI6dHJ1ZSwiaXNVbmlxdWVJZGVudGlmZXIiOnRydWUsIm9iamVjdCI6ImxlYWQifQ==' => ['email' => 'Email']]);

        $contactTracker->expects($this
            ->any())
            ->method('getContact')
            ->willReturn($lead);

        $userHelper->expects($this->any())
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

        $leadFieldModel->expects($this->any())
            ->method('getFieldListWithProperties')
            ->willReturn($mockLeadField);

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [Lead::class, $leadRepository],
                        [Submission::class, $formRepository],
                    ]
                )
            );

        $leadRepository->expects($this->any())
            ->method('getLeadsByUniqueFields')
            ->willReturn(null);

        $file1Mock->expects($this->any())
            ->method('getClientOriginalName')
            ->willReturn('test.jpg');

        $uploadFieldValidatorMock->expects($this->any())
            ->method('processFileValidation')
            ->willReturn($file1Mock);

        $ipLookupHelper->expects($this->any())
            ->method('getIpAddress')
            ->willReturn(new IpAddress());

        $companyModel->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([]);
        $submissionModel = new SubmissionModel(
            $ipLookupHelper,
            $twigMock,
            $formModel,
            $pageModel,
            $leadModel,
            $campaignModel,
            $membershipManager,
            $leadFieldModel,
            $companyModel,
            $fieldHelper,
            $uploadFieldValidatorMock,
            $formUploaderMock,
            $deviceTrackingService,
            new FieldValueTransformer($router),
            $dateHelper,
            $contactTracker,
            $contactMerger,
            $fieldsWithUniqueIdentifier,
            $entityManager,
            $this->createMock(CorePermissions::class),
            $dispatcher,
            $router,
            $translator,
            $userHelper,
            $mockLogger,
            $this->coreParametersHelper,
        );

        return $submissionModel;
    }

    /**
     * @return array<int|string, array<string, bool|int|list<string>|string>>
     */
    public function getTestFormFields(): array
    {
        $fieldSession          = 'mautic_'.sha1(uniqid((string) mt_rand(), true));
        $fieldSession2         = 'mautic_'.sha1(uniqid((string) mt_rand(), true));
        $fields[$fieldSession] = [
            'label'        => 'Email',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'email',
            'type'         => 'email',
            'leadField'    => 'email',
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

        $fields['123'] = [
            'label'        => 'Parent Field',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'parent',
            'type'         => 'select',
            'id'           => '123',
        ];

        $fields['456'] = [
            'label'        => 'Child',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'child',
            'type'         => 'text',
            'id'           => '456',
            'parent'       => '123',
        ];

        $fields[$fieldSession2] = [
            'label'        => 'New Child',
            'showLabel'    => 1,
            'saveResult'   => 1,
            'defaultValue' => false,
            'alias'        => 'new_child',
            'type'         => 'text',
            'id'           => $fieldSession2,
            'parent'       => '123',
        ];

        return $fields;
    }
}
