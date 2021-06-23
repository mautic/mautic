<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Validator\UploadFieldValidator;
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
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

class FormTestAbstract extends TestCase
{
    protected static $mockId   = 123;
    protected static $mockName = 'Mock test name';
    protected $mockTrackingId;
    protected $formRepository;
    protected $leadFieldModel;

    protected function setUp(): void
    {
        $this->mockTrackingId = hash('sha1', uniqid(mt_rand()));
    }

    /**
     * @return FormModel
     */
    protected function getFormModel()
    {
        $requestStack         = $this->createMock(RequestStack::class);
        $templatingHelperMock = $this->createMock(TemplatingHelper::class);
        $themeHelper          = $this->createMock(ThemeHelperInterface::class);
        $formActionModel      = $this->createMock(ActionModel::class);
        $formFieldModel       = $this->createMock(FieldModel::class);
        $fieldHelper          = $this->createMock(FormFieldHelper::class);
        $dispatcher           = $this->createMock(EventDispatcher::class);
        $translator           = $this->createMock(Translator::class);
        $entityManager        = $this->createMock(EntityManager::class);
        $formUploaderMock     = $this->createMock(FormUploader::class);
        $contactTracker       = $this->createMock(ContactTracker::class);
        $this->leadFieldModel = $this->createMock(LeadFieldModel::class);
        $this->formRepository = $this->createMock(FormRepository::class);
        $columnSchemaHelper   = $this->createMock(ColumnSchemaHelper::class);
        $tableSchemaHelper    = $this->createMock(TableSchemaHelper::class);

        $contactTracker->expects($this
            ->any())
            ->method('getContact')
            ->willReturn($this
                ->returnValue(['id' => self::$mockId, 'name' => self::$mockName])
            );

        $templatingHelperMock->expects($this
            ->any())
            ->method('getTemplating')
            ->willReturn($this->createMock(EngineInterface::class));

        $entityManager->expects($this
            ->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticFormBundle:Form', $this->formRepository],
                    ]
                )
            );

        $formModel = new FormModel(
            $requestStack,
            $templatingHelperMock,
            $themeHelper,
            $formActionModel,
            $formFieldModel,
            $fieldHelper,
            $this->leadFieldModel,
            $formUploaderMock,
            $contactTracker,
            $columnSchemaHelper,
            $tableSchemaHelper
        );

        $formModel->setDispatcher($dispatcher);
        $formModel->setTranslator($translator);
        $formModel->setEntityManager($entityManager);

        return $formModel;
    }

    /**
     * @return SubmissionModel
     */
    protected function getSubmissionModel()
    {
        $ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $templatingHelperMock     = $this->createMock(TemplatingHelper::class);
        $formModel                = $this->createMock(FormModel::class);
        $pageModel                = $this->createMock(PageModel::class);
        $leadModel                = $this->createMock(LeadModel::class);
        $campaignModel            = $this->createMock(CampaignModel::class);
        $membershipManager        = $this->createMock(MembershipManager::class);
        $leadFieldModel           = $this->createMock(LeadFieldModel::class);
        $companyModel             = $this->createMock(CompanyModel::class);
        $fieldHelper              = $this->createMock(FormFieldHelper::class);
        $dispatcher               = $this->createMock(EventDispatcher::class);
        $translator               = $this->createMock(Translator::class);
        $dateHelper               = $this->createMock(DateHelper::class);
        $contactTracker           = $this->createMock(ContactTracker::class);
        $userHelper               = $this->createMock(UserHelper::class);
        $entityManager            = $this->createMock(EntityManager::class);
        $formRepository           = $this->createMock(FormRepository::class);
        $leadRepository           = $this->createMock(LeadRepository::class);
        $mockLogger               = $this->createMock(Logger::class);
        $uploadFieldValidatorMock = $this->createMock(UploadFieldValidator::class);
        $formUploaderMock         = $this->createMock(FormUploader::class);
        $deviceTrackingService    = $this->createMock(DeviceTrackingServiceInterface::class);
        $file1Mock                = $this->createMock(UploadedFile::class);
        $lead                     = new Lead();
        $lead->setId(123);

        $leadFieldModel->expects($this->any())
            ->method('getUniqueIdentifierFields')
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
                        ['MauticLeadBundle:Lead', $leadRepository],
                        ['MauticFormBundle:Submission', $formRepository],
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

        $submissionModel = new SubmissionModel(
            $ipLookupHelper,
            $templatingHelperMock,
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
            new FieldValueTransformer($this->createMock(RouterInterface::class)),
            $dateHelper,
            $contactTracker
        );

        $submissionModel->setDispatcher($dispatcher);
        $submissionModel->setTranslator($translator);
        $submissionModel->setEntityManager($entityManager);
        $submissionModel->setUserHelper($userHelper);
        $submissionModel->setLogger($mockLogger);

        return $submissionModel;
    }

    public function getTestFormFields()
    {
        $fieldSession          = 'mautic_'.sha1(uniqid(mt_rand(), true));
        $fields[$fieldSession] =
            [
                'label'        => 'Email',
                'showLabel'    => 1,
                'saveResult'   => 1,
                'defaultValue' => false,
                'alias'        => 'email',
                'type'         => 'email',
                'leadField'    => 'email',
                'id'           => $fieldSession,
            ];

        $fields['file'] =
            [
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
