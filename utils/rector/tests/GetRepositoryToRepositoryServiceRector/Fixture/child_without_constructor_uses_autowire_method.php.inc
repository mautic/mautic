<?php

namespace Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Fixture;

use Mautic\PageBundle\Entity\Hit;
use Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Source\AbstractParentWithConstructor;

final class ChildWithoutConstructorUsesAutowireMethod extends AbstractParentWithConstructor
{
    public function countHits(): int
    {
        return $this->entityManager->getRepository(Hit::class)->count([]);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Fixture;

use Mautic\PageBundle\Entity\Hit;
use Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Source\AbstractParentWithConstructor;

final class ChildWithoutConstructorUsesAutowireMethod extends AbstractParentWithConstructor
{
    private \Mautic\PageBundle\Entity\HitRepository $hitRepository;
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireChildWithoutConstructorUsesAutowireMethod(\Mautic\PageBundle\Entity\HitRepository $hitRepository): void
    {
        $this->hitRepository = $hitRepository;
    }
    public function countHits(): int
    {
        return $this->hitRepository->count([]);
    }
}

?>
