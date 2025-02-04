<?php

namespace Mautic\PageBundle\Tests\Form\Type;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Form\Type\PageListType;
use Mautic\PageBundle\Model\PageModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageListTypeTest extends TestCase
{
    private PageListType $page;

    private \PHPUnit\Framework\MockObject\MockObject $pageModelMock;

    public function setUp(): void
    {
        $corePermissionsHelper = $this->createMock(CorePermissions::class);
        $this->pageModelMock   = $this->createMock(PageModel::class);
        $this->page            = new PageListType($this->pageModelMock, $corePermissionsHelper);
    }

    public function testPageListTypeOptionsChoices(): void
    {
        $pageRepository = $this->createMock(PageRepository::class);
        $resolver       = new OptionsResolver();

        $this->pageModelMock
            ->method('getRepository')
            ->willReturn($pageRepository);

        $pageRepository->method('getPageList')
            ->willReturn([]);

        $this->page->configureOptions($resolver);

        $expectedOptions = [
            'placeholder' => false,
            'expanded'    => false,
            'multiple'    => true,
            'required'    => false,
            'top_level'   => 'variant',
            'ignore_ids'  => [],
            'choices'     => [],
        ];
        $this->assertEquals($expectedOptions, $resolver->resolve());
    }

    public function testGetParent(): void
    {
        $this->assertSame(ChoiceType::class, $this->page->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertSame('page_list', $this->page->getBlockPrefix());
    }
}
