<?php

namespace Utils\Rector\Tests\RouteRequirementParamTypeRector\Fixture;

use Symfony\Component\Routing\Attribute\Route;

final class DigitRequirement
{
    #[Route(
        path: '/s/dwc/{page}',
        name: 'mautic_dynamicContent_index',
        requirements: ['page' => '\d+'],
        defaults: ['page' => 0],
    )]
    public function indexAction($page = 1, $objectId = null)
    {
    }

    #[Route(path: '/s/dwc/view/{objectId}', name: 'view', requirements: ['objectId' => '[0-9]+'])]
    public function viewAction($objectId = null)
    {
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RouteRequirementParamTypeRector\Fixture;

use Symfony\Component\Routing\Attribute\Route;

final class DigitRequirement
{
    #[Route(
        path: '/s/dwc/{page}',
        name: 'mautic_dynamicContent_index',
        requirements: ['page' => '\d+'],
        defaults: ['page' => 0],
    )]
    public function indexAction(int $page = 1, $objectId = null)
    {
    }

    #[Route(path: '/s/dwc/view/{objectId}', name: 'view', requirements: ['objectId' => '[0-9]+'])]
    public function viewAction(?int $objectId = null)
    {
    }
}

?>
