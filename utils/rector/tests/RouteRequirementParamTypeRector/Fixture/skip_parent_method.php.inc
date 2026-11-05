<?php

namespace Utils\Rector\Tests\RouteRequirementParamTypeRector\Fixture;

use Symfony\Component\Routing\Attribute\Route;

abstract class ParentController
{
    public function viewAction($id)
    {
    }
}

final class SkipParentMethod extends ParentController
{
    #[Route(path: '/{id}', name: 'view', requirements: ['id' => '\d+'])]
    public function viewAction($id)
    {
    }
}
