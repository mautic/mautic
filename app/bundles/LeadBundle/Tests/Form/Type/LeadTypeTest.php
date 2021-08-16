<?php

namespace Mautic\LeadBundle\Tests\Form\Type;

use function Clue\StreamFilter\fun;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Form\Type\LeadType;
use Mautic\LeadBundle\Model\CompanyModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translatorMock;

    /**
     * @var CompanyModel|MockObject
     */
    private $companyModelMock;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManagerMock;

    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilderInterfaceMock;

    /**
     * @var Company
     */
    private $companyEntity;

    /**
     * @var CompanyLeadRepository|MockObject
     */
    private $companyRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translatorMock           = $this->createMock(TranslatorInterface::class);
        $this->companyModelMock         = $this->createMock(CompanyModel::class);
        $this->entityManagerMock        = $this->createMock(EntityManager::class);
        $this->formBuilderInterfaceMock = $this->createMock(FormBuilderInterface::class);
        $this->companyRepositoryMock    = $this->createMock(CompanyLeadRepository::class);
        $this->companyEntity            = new Company();
    }

    public function testLeadTypeLookup(): void
    {
        $this->companyRepositoryMock->expects($this->once())
            ->method('getCompaniesByLeadId')
            ->willReturn([]);

        $this->companyModelMock->expects($this->once())
            ->method('getCompanyLeadRepository')
            ->willReturn($this->companyRepositoryMock);

        $this->formBuilderInterfaceMock->expects($this->any())
            ->method('create')
            ->willReturn($this->createMock(FormConfigBuilderInterface::class));

        $leadType = new LeadType($this->translatorMock, $this->companyModelMock, $this->entityManagerMock);
        $options  = [
            'data'                        => $this->companyEntity,
            'isShortForm'                 => true,
            'ignore_required_constraints' => null,
            'fields'                      => [
                [
                    'isUniqueIdentifer' => false,
                    'isPublished'       => true,
                    'object'            => 'lead',
                    'type'              => 'lookup',
                    'label'             => 'label',
                    'properties'        => [
                        'list' => null,
                    ],
                    'alias'        => 'alias',
                    'group'        => 'group',
                    'isRequired'   => false,
                    'defaultValue' => null,
                ],
            ],
        ];
        $leadType->buildForm($this->formBuilderInterfaceMock, $options);
    }
}
