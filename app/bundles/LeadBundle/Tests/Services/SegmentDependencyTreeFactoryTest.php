<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Services;

use Mautic\CoreBundle\Helper\Tree\JsPlumbFormatter;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Services\SegmentDependencyTreeFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class SegmentDependencyTreeFactoryTest extends TestCase
{
    public function testInaccessibleChildrenAndDescendantsAreOmittedFromTheFormattedTree(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $route, array $parameters): string => '/s/segments/view/'.$parameters['objectId']
        );

        $root = $this->createSegment(1, 'Owned root', 10, [
            $this->leadListFilter([2, 3]),
        ]);
        $accessibleChild = $this->createSegment(2, 'Accessible child', 20, [
            $this->leadListFilter([4, 5]),
        ]);
        $inaccessibleChild = $this->createSegment(3, 'Inaccessible child', 30);
        $accessibleDescendant = $this->createSegment(4, 'Accessible descendant', 20);
        $inaccessibleDescendant = $this->createSegment(5, 'Inaccessible descendant', 30);

        $repository = $this->createMock(LeadListRepository::class);
        $repository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnCallback(
                static function (array $criteria) use ($accessibleChild, $inaccessibleChild, $accessibleDescendant, $inaccessibleDescendant): array {
                    return match ($criteria['id']) {
                        [2, 3] => [$accessibleChild, $inaccessibleChild],
                        [4, 5] => [$accessibleDescendant, $inaccessibleDescendant],
                        default => [],
                    };
                }
            );

        $permissions = $this->createMock(CorePermissions::class);
        $permissions->method('hasEntityAccess')->willReturnCallback(
            static fn (string $ownPermission, string $otherPermission, int $ownerId): bool => in_array($ownerId, [10, 20], true)
        );

        $tree = (new SegmentDependencyTreeFactory($router, $repository, $permissions))->buildTree($root);
        $formatted = (new JsPlumbFormatter())->format($tree);

        self::assertSame([
            [
                'nodes' => [
                    ['id' => '0-1', 'name' => 'Owned root', 'link' => '/s/segments/view/1'],
                ],
            ],
            [
                'nodes' => [
                    ['id' => '1-2', 'name' => 'Accessible child', 'link' => '/s/segments/view/2'],
                ],
            ],
            [
                'nodes' => [
                    ['id' => '2-4', 'name' => 'Accessible descendant', 'link' => '/s/segments/view/4'],
                ],
            ],
        ], $formatted['levels']);

        self::assertSame([
            ['source' => '0-1', 'target' => '1-2'],
            ['source' => '1-2', 'target' => '2-4'],
        ], $formatted['edges']);
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     */
    private function createSegment(int $id, string $name, int $ownerId, array $filters = []): LeadList
    {
        $segment = new LeadList();
        $segment->setId($id);
        $segment->setName($name);
        $segment->setFilters($filters);
        $segment->setCreatedBy($ownerId);

        return $segment;
    }

    /**
     * @param int[] $segmentIds
     *
     * @return array<string, mixed>
     */
    private function leadListFilter(array $segmentIds): array
    {
        return [
            'type'       => 'leadlist',
            'properties' => ['filter' => $segmentIds],
        ];
    }
}
