<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Response;

final class ProjectSortingTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test projects with specific dates and names for sorting
        $this->createProjectWithDates('Alpha Project', '2023-01-15 10:00:00', '2023-06-20 15:30:00');
        $this->createProjectWithDates('Beta Project', '2023-03-10 14:20:00', '2023-05-15 09:45:00');
        $this->createProjectWithDates('Gamma Project', '2023-02-20 08:15:00', '2023-07-30 16:00:00');
        $this->createProjectWithDates('Delta Project', '2023-04-05 11:30:00', '2023-04-10 12:00:00');

        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @dataProvider sortingProvider
     *
     * @param array<int, string> $expectedOrder
     */
    public function testProjectListSorting(string $orderBy, array $expectedOrder): void
    {
        // When a user clicks the table header for the first time they get a DESC sort
        $this->client->request('GET', '/s/projects', ['tmpl' => 'list', 'name' => 'projects', 'orderby' => $orderBy]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(
            $expectedOrder,
            $this->getOrderedProjectsFromResponse($this->client->getResponse()->getContent())
        );

        // When they click again they get an ASC sort
        $this->client->request('GET', '/s/projects', ['tmpl' => 'list', 'name' => 'projects', 'orderby' => $orderBy]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(
            array_reverse($expectedOrder),
            $this->getOrderedProjectsFromResponse($this->client->getResponse()->getContent())
        );
    }

    /**
     * @return iterable<string, array{orderBy: string, expectedOrder: array<int, string>}>
     */
    public static function sortingProvider(): iterable
    {
        yield 'sort by name' => [
            'orderBy'       => 'p.name',
            'expectedOrder' => [
                'Gamma Project',
                'Delta Project',
                'Beta Project',
                'Alpha Project',
            ],
        ];

        yield 'sort by dateAdded' => [
            'orderBy'       => 'p.dateAdded',
            'expectedOrder' => [
                'Delta Project',    // 2023-04-05
                'Beta Project',     // 2023-03-10
                'Gamma Project',    // 2023-02-20
                'Alpha Project',    // 2023-01-15
            ],
        ];

        yield 'sort by dateModified' => [
            'orderBy'       => 'p.dateModified',
            'expectedOrder' => [
                'Gamma Project',    // 2023-07-30
                'Alpha Project',    // 2023-06-20
                'Beta Project',     // 2023-05-15
                'Delta Project',    // 2023-04-10
            ],
        ];
    }

    /**
     * Create a project with specific dateAdded and dateModified values.
     */
    private function createProjectWithDates(string $name, string $dateAdded, string $dateModified): void
    {
        $project = new Project();
        $project->setName($name);
        $project->setDateAdded(new \DateTime($dateAdded));
        $project->setDateModified(new \DateTime($dateModified));

        $this->em->persist($project);
    }

    /**
     * Extract project names from HTML response in the order they appear.
     *
     * @return array<int, string>
     */
    private function getOrderedProjectsFromResponse(string $content): array
    {
        $testProjectNames = ['Alpha Project', 'Beta Project', 'Gamma Project', 'Delta Project'];
        $foundProjects    = [];

        // Find each project name in the HTML and record its position
        foreach ($testProjectNames as $projectName) {
            $position = strpos($content, $projectName);
            if (false !== $position) {
                $foundProjects[$position] = $projectName;
            }
        }

        // Sort by position to maintain the order they appear in the response
        ksort($foundProjects);

        return array_values($foundProjects);
    }
}
