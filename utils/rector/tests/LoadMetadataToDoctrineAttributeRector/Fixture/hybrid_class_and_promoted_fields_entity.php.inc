<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PageDraft
{
    public const TABLE_NAME = 'pages_draft';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    public function __construct(
        #[ORM\OneToOne(targetEntity: Page::class, inversedBy: 'draft')]
        #[ORM\JoinColumn(name: 'page_id', nullable: false)]
        private Page $page,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $html = null,
        #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
        private ?string $template = null,
        #[ORM\Column(name: 'public_preview', type: Types::BOOLEAN, options: ['default' => 1])]
        private bool $publicPreview = true,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(PageDraftRepository::class)
            ->addLifecycleEvent('cleanUrlsInContent', Events::preUpdate)
            ->addLifecycleEvent('cleanUrlsInContent', Events::prePersist);
    }

    public function cleanUrlsInContent(): void
    {
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: PageDraftRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PageDraft
{
    public const TABLE_NAME = 'pages_draft';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    public function __construct(
        #[ORM\OneToOne(targetEntity: Page::class, inversedBy: 'draft')]
        #[ORM\JoinColumn(name: 'page_id', nullable: false)]
        private Page $page,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $html = null,
        #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
        private ?string $template = null,
        #[ORM\Column(name: 'public_preview', type: Types::BOOLEAN, options: ['default' => 1])]
        private bool $publicPreview = true,
    ) {
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function cleanUrlsInContent(): void
    {
    }
}
