<?php

namespace Mautic\CoreBundle\Tests\Form\Validator\Constraints;

use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependency;
use Mautic\CoreBundle\Form\Validator\Constraints\CircularDependencyValidator;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
    public function configureValidator($expectedMessage = null)
    {
        $filters  = 'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:8:"leadlist";s:6:"object";s:4:"lead";s:4:"type";s:8:"leadlist";s:6:"filter";a:1:{i:0;i:2;}s:7:"display";N;s:8:"operator";s:2:"in";}}';
        $filters2 = 'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:8:"leadlist";s:6:"object";s:4:"lead";s:4:"type";s:8:"leadlist";s:6:"filter";a:1:{i:0;i:1;}s:7:"display";N;s:8:"operator";s:2:"in";}}';

        $mockListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntity'])
            ->getMock();

        $mockEntity = $this->getMockBuilder(LeadList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntity1 = clone $mockEntity;
        $mockEntity1->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $mockEntity2 = clone $mockEntity;
        $mockEntity2->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        // $mockListModel->expects($this->any())
        //     ->method('getEntity')
        //     ->willReturnCallback(function ($id) {
        //         $mockEntity = $this->getMockBuilder(LeadList::class)
        //             ->disableOriginalConstructor()
        //             ->setMethods(['getFilters'])
        //             ->getMock();
        //          $mockEntity->expects($this->once())
        //             ->method('getFilters')
        //             ->willReturn(unserialize($filters));
        //          return $mockEntity;
        // });

        // mock the violation builder
        $builder = $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addViolation'])
            ->getMock()
        ;

        // mock the validator context
        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock()
        ;

        if ($expectedMessage) {
            $builder->expects($this->once())
                ->method('addViolation')
            ;

            $context->expects($this->once())
                ->method('buildViolation')
                ->with($this->equalTo($expectedMessage))
                ->willReturn($this->returnValue($builder))
            ;
        } else {
            $context->expects($this->never())
                ->method('buildViolation')
            ;
        }

        $request = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentRequest', '_route_params'])
            ->getMock();

        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $requestStack
            ->expects($this->once())
            ->method('_route_params')
            ->willReturn([
                'objectId' => 1,
            ]);

        // initialize the validator with the mocked context
        $validator = new CircularDependencyValidator($mockListModel, $requestStack);
        $validator->initialize($context);

        // return the CircularDependencyValidator
        return $validator;
    }

    /**
     * Verify a constraint message is triggered when value is invalid.
     */
    public function testValidateOnInvalid()
    {
        $filters    = 'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:8:"leadlist";s:6:"object";s:4:"lead";s:4:"type";s:8:"leadlist";s:6:"filter";a:1:{i:0;i:2;}s:7:"display";N;s:8:"operator";s:2:"in";}}';
        $filters    = unserialize($filters);
        $constraint = new CircularDependency();
        $validator  = $this->configureValidator($constraint->message);

        $validator->validate($filters, $constraint);
    }
}
