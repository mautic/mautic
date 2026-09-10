<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageDraftRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PageDraft
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'pages_draft';

    /**
     * @var string
     */
    public const REGEX_DECODE_AMPERSAND = '/((https?|ftps?):\/\/)([a-zA-Z0-9-\.{}]*[a-zA-Z0-9=}]*)(\??)([^\s\"\]]+)?/i';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    public function __construct(
        #[ORM\OneToOne(inversedBy: 'draft', targetEntity: Page::class)]
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

    /**
     * Lifecycle callback to clean URLs in the content.
     */
    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function cleanUrlsInContent(): void
    {
        $this->html = $this->decodeAmpersands((string) $this->html);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
    }

    public function setHtml(?string $html): void
    {
        $this->html = $html;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    public function isPublicPreview(): bool
    {
        return $this->publicPreview;
    }

    public function setPublicPreview(bool $publicPreview): void
    {
        $this->publicPreview = $publicPreview;
    }

    /**
     * Check all links in content and decode &amp;
     * This even works with double encoded ampersands.
     */
    private function decodeAmpersands(string $content): string
    {
        if (!preg_match_all(self::REGEX_DECODE_AMPERSAND, $content, $matches)) {
            return $content;
        }

        foreach ($matches[0] as $url) {
            $newUrl = $url;
            while (str_contains($newUrl, '&amp;')) {
                $newUrl = str_replace('&amp;', '&', $newUrl);
            }
            $content = str_replace($url, $newUrl, $content);
        }

        return $content;
    }
}
