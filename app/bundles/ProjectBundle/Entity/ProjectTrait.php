<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;

trait ProjectTrait
{
    /**
     * @var Collection<int, Project>
     */
    private Collection $projects;

    private function initializeProjects(): void
    {
        $this->projects = new ArrayCollection();
    }

    private static function addProjectsField(ClassMetadataBuilder $builder, string $tableName, string $columnName): void
    {
        $builder->createManyToMany('projects', Project::class)
            ->setJoinTable($tableName)
            ->addInverseJoinColumn('project_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn($columnName, 'id', false, false, 'CASCADE')
            ->setOrderBy(['name' => 'ASC'])
            ->setIndexBy('name')
            ->fetchLazy()
            ->cascadeMerge()
            ->cascadePersist()
            ->cascadeDetach()
            ->build();
    }

    private static function addProjectsInLoadApiMetadata(ApiMetadataDriver $metadata, string $groupPrefix): void
    {
        $metadata->setGroupPrefix($groupPrefix)->addProperties(['projects'])->build();
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val): void
    {
        if ('projects' === $prop) {
            if ($val instanceof Project) {
                $this->changes['projects']['added'][] = $val->getName();
            } else {
                $this->changes['projects']['removed'][] = $val;
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    public function addProject(Project $project): void
    {
        $this->isChanged('projects', $project);
        $this->projects[] = $project;
    }

    public function removeProject(Project $project): bool
    {
        $this->isChanged('projects', $project->getName());

        return $this->projects->removeElement($project);
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function setProjects(Collection $projects): self
    {
        $this->projects = $projects;

        return $this;
    }
}
