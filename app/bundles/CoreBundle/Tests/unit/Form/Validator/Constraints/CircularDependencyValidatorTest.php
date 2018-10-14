<?php

namespace Mautic\CoreBundle\Tests\Form\Validator\Constraints;

use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependency;
use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * Exercises CircularDependencyValidator.
 */
class CircularDependencyValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Configure a CircularDependencyValidator.
     *
     * @param string $expectedMessage the expected message on a validation violation, if any
     *
     * @return Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator
     */
    public function configureValidator($expectedMessage, $currentSegmentId)
    {
        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'leadlist',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [2],
                'display'  => null,
                'operator' => 'in',
            ],
        ];

        $filters2 = [
            [
                'glue'     => 'and',
                'field'    => 'leadlist',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [1],
                'display'  => null,
                'operator' => 'in',
            ],
        ];

        $filters3 = [
            [
                'glue'     => 'and',
                'field'    => 'first_name',
                'object'   => 'lead',
                'type'     => 'text',
                'filter'   => 'John',
                'display'  => null,
                'operator' => '=',
            ],
        ];

        $mockListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntity'])
            ->getMock();

        $mockEntity = $this->getMockBuilder(LeadList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getFilters'])
            ->getMock();

        $mockEntity1 = clone $mockEntity;
        $mockEntity1->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $mockEntity1->expects($this->any())
            ->method('getFilters')
            ->willReturn($filters);

        $mockEntity2 = clone $mockEntity;
        $mockEntity2->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $mockEntity2->expects($this->any())
            ->method('getFilters')
            ->willReturn($filters2);

        $mockEntity3 = clone $mockEntity;
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

        $mockListModel->expects($this->any())
            ->method('getEntity')
            ->willReturnCallback(function ($id) use ($entities) {
                return $entities[$id];
            });

        // mock the validator context
        $context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['addViolation'])
            ->getMock();

        if (!empty($expectedMessage)) {
            $context->expects($this->once())
                ->method('addViolation')
                ->with($this->equalTo($expectedMessage));
        } else {
            $context->expects($this->never())
                ->method('addViolation');
        }

        $request = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request
            ->expects($this->once())
            ->method('get')
            ->with('_route_params')
            ->willReturn([
                'objectId' => $currentSegmentId,
            ]);

        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentRequest', 'get'])
            ->getMock();

        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        // initialize the validator with the mocked context
        $validator = new CircularDependencyValidator($mockListModel, $requestStack);
        $validator->initialize($context);

        // return the CircularDependencyValidator
        return $validator;
    }

    /**
     * Verify a constraint message.
     *
     * @dataProvider validateDataProvider
     */
    public function testValidateOnInvalid($message, $currentSegmentId, $filters)
    {
        $this->configureValidator($message, $currentSegmentId)
            ->validate($filters, new CircularDependency(['message' => 'mautic.core.segment.circular_dependency_exists']));
    }

    public function validateDataProvider()
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
                        'filter'   => [1],
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
                        'glue'     => 'and',
                        'field'    => 'leadlist',
                        'object'   => 'lead',
                        'type'     => 'leadlist',
                        'filter'   => [2],
                        'display'  => null,
                        'operator' => 'in',
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
                        'glue'     => 'and',
                        'field'    => 'leadlist',
                        'object'   => 'lead',
                        'type'     => 'leadlist',
                        'filter'   => [3],
                        'display'  => null,
                        'operator' => 'in',
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
                        'filter'   => 'Doe',
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
                        'glue'     => 'and',
                        'field'    => 'leadlist',
                        'object'   => 'lead',
                        'type'     => 'leadlist',
                        'filter'   => [1],
                        'display'  => null,
                        'operator' => 'in',
                    ],
                    [
                        'glue'     => 'and',
                        'field'    => 'leadlist',
                        'object'   => 'lead',
                        'type'     => 'leadlist',
                        'filter'   => [3],
                        'display'  => null,
                        'operator' => 'in',
                    ],
                ],
            ],
            // @TODO: MUST ADD TEST CASES ONCE WE FIX DEEP CIRCULAR (1 depends on 2 which depends on 3 which depends on 1) TO AN ARBITRARY DEPTH
        ];
    }
}
