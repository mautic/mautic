<?php

namespace Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Fixture;

use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Model\PageModel;
use Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Source\AbstractParentWithConstructor;

final class ReuseExistingAutowireMethod extends AbstractParentWithConstructor
{
    private PageModel $pageModel;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireReuseExistingAutowireMethod(PageModel $pageModel): void
    {
        $this->pageModel = $pageModel;
    }

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
use Mautic\PageBundle\Model\PageModel;
use Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Source\AbstractParentWithConstructor;

final class ReuseExistingAutowireMethod extends AbstractParentWithConstructor
{
    private \Mautic\PageBundle\Entity\HitRepository $hitRepository;
    private PageModel $pageModel;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireReuseExistingAutowireMethod(PageModel $pageModel, \Mautic\PageBundle\Entity\HitRepository $hitRepository): void
    {
        $this->pageModel = $pageModel;
        $this->hitRepository = $hitRepository;
    }

    public function countHits(): int
    {
        return $this->hitRepository->count([]);
    }
}

?>
