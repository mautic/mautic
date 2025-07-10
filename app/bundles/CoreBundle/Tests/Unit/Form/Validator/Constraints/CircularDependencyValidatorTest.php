<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\Validator\Constraints;

use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependency;
use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContext;

class CircularDependencyValidatorTest extends \PHPUnit\Framework\TestCase
{
    private MockObject&ListModel $mockListModel;

    private MockObject&ExecutionContext $context;

    private MockObject&RequestStack $requestStack;

    private Request $request;

    private CircularDependencyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockListModel = $this->createMock(ListModel::class);
        $this->context       = $this->createMock(ExecutionContext::class);
        $this->requestStack  = $this->createMock(RequestStack::class);
        $this->request       = new Request();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->validator = new CircularDependencyValidator($this->mockListModel, $this->requestStack);
        $this->validator->initialize($this->context);
    }

    /**
     * Checks that the validator won't break if the segment ID is not present in the request.
     */
    public function testIfSegmentIdIsNotInTheRequest(): void
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->mockListModel->expects($this->never())
            ->method('getEntity');

        $this->validator->validate([], new CircularDependency([]));
    }

    /**
     * Configure a CircularDependencyValidator.
     *
     * @param string $expectedMessage  the expected message on a validation violation, if any
     * @param int    $currentSegmentId
     *
     * @return Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator
     */
    private function configureValidator($expectedMessage, $currentSegmentId)
    {
        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'leadlist',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [2], // Keeping filter in the root to test also for BC segments.
                'display'  => null,
                'operator' => 'in',
            ],
        ];

        $filters2 = [
            [
                'glue'       => 'and',
                'field'      => 'leadlist',
                'object'     => 'lead',
                'type'       => 'leadlist',
                'properties' => ['filter' => [1]],
                'display'    => null,
                'operator'   => 'in',
            ],
        ];

        $filters3 = [
            [
                'glue'       => 'and',
                'field'      => 'first_name',
                'object'     => 'lead',
                'type'       => 'text',
                'properties' => ['filter' => 'John'],
                'display'    => null,
                'operator'   => '=',
            ],
        ];

        $mockEntity1 = $this->createMock(LeadList::class);
        $mockEntity1->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $mockEntity1->expects($this->any())
            ->method('getFilters')
            ->willReturn($filters);

        $mockEntity2 = $this->createMock(LeadList::class);
        $mockEntity2->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $mockEntity2->expects($this->any())
            ->method('getFilters')
            ->willReturn($filters2);

        $mockEntity3 = $this->createMock(LeadList::class);
        $mockEntity3->expects($this->any())
            ->method('getId')
            ->willReturn(3);
        $mockEntity3->expects($this->any())
            ->method('getFilters')
            ->willReturn($filters3);

        $entities = [
            1 => $mockEntity1,
            2 => $mockEntity2,
            3 => $mockEntity3,
        ];

        $this->mockListModel->expects($this->any())
            ->method('getEntity')
            ->willReturnCallback(fn ($id) => $entities[$id]);

        if (!empty($expectedMessage)) {
            $this->context->expects($this->once())
                ->method('addViolation')
                ->with($this->equalTo($expectedMessage));
        } else {
            $this->context->expects($this->never())
                ->method('addViolation');
        }

        $this->request->request->add(['_route_params' => ['objectId' => $currentSegmentId]]);

        return $this->validator;
    }

    /**
     * Verify a constraint message.
     *
     * @dataProvider validateDataProvider
     */
    public function testValidateOnInvalid($message, $currentSegmentId, $filters): void
    {
        $this->configureValidator($message, $currentSegmentId)
            ->validate($filters, new CircularDependency(['message' => 'mautic.core.segment.circular_dependency_exists']));
    }

    public static function validateDataProvider()
    {
        $constraint = new CircularDependency(['message' => 'mautic.core.segment.circular_dependency_exists']);

        return [
            // Segment 1 is dependent on Segment 2 which is dependent on segment 1 - circular
            [
                $constraint->message,
                2, // current segment id
                [
                    [
                        'glue'     => 'and',
                        'field'    => 'leadlist',
                        'object'   => 'lead',
                        'type'     => 'leadlist',
                        'filter'   => [1], // Keeping filter in the root to test also for BC segments.
                        'display'  => null,
                        'operator' => 'in',
                    ],
                ],
            ],
            // Segment 2 is dependent on Segment 1 which is dependent on segment 2 - circular
            [
                $constraint->message,
                1, // current segment id
                [
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [2]],
                        'display'    => null,
                        'operator'   => 'in',
                    ],
                ],
            ],
            // Test when there are no validation errors
            // The segment in the filter (3) is NOT dependent on any
            [
                null,
                1, // current segment id
                [
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [3]],
                        'display'    => null,
                        'operator'   => 'in',
                    ],
                ],
            ],
            // Test when no lead list filters
            [
                null,
                1, // current segment id
                [
                    [
                        'glue'     => 'and',
                        'field'    => 'first_name',
                        'object'   => 'lead',
                        'type'     => 'text',
                        'filter'   => 'Doe', // Keeping filter in the root to test also for BC segments.
                        'display'  => null,
                        'operator' => '=',
                    ],
                ],
            ],
            // Test multiple lead list filters. Fails because 2 is dependent on 1
            [
                $constraint->message,
                2, // current segment id
                [
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [1]],
                        'display'    => null,
                        'operator'   => 'in',
                    ],
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [3]],
                        'display'    => null,
                        'operator'   => 'in',
                    ],
                ],
            ],
            // @TODO: MUST ADD TEST CASES ONCE WE FIX DEEP CIRCULAR (1 depends on 2 which depends on 3 which depends on 1) TO AN ARBITRARY DEPTH
        ];
    }
}
