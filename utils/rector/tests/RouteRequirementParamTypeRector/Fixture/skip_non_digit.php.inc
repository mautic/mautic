<?php

namespace Utils\Rector\Tests\RouteRequirementParamTypeRector\Fixture;

use Symfony\Component\Routing\Attribute\Route;

final class SkipNonDigit
{
    #[Route(path: '/{objectAction}', name: 'action', requirements: ['objectAction' => '[a-zA-Z0-9_-]+'])]
    public function executeAction($objectAction)
    {
    }

    #[Route(path: '/{page}', name: 'string_default', requirements: ['page' => '\d+'], defaults: ['page' => ''])]
    public function stringRouteDefaultAction($page = 1)
    {
    }

    #[Route(path: '/{page}', name: 'string_param_default', requirements: ['page' => '\d+'])]
    public function stringParamDefaultAction($page = '1')
    {
    }

    #[Route(path: '/{page}', name: 'typed', requirements: ['page' => '\d+'])]
    public function typedAction(string $page)
    {
    }

    #[Route(path: '/a/{id}', name: 'first', requirements: ['id' => '\d+'])]
    #[Route(path: '/b/{id}', name: 'second', requirements: ['id' => '[a-z]+'])]
    public function mixedAction($id)
    {
    }
}
