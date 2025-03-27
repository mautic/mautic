<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

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

    private ?int $id = null;

    public function __construct(
        private Page $page,
        private ?string $html = null,
        private ?string $template = null,
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

        $builder->addId();
        $builder->addNullableField('html', Types::TEXT);
        $builder->addNullableField('template', Types::STRING);
        $builder->createField('publicPreview', Types::BOOLEAN)
            ->columnName('public_preview')
            ->nullable(false)
            ->option('default', 1)
            ->build();

        $builder->createOneToOne('page', Page::class)
            ->inversedBy('draft')
            ->addJoinColumn('page_id', 'id', false)
            ->build();
    }

    /**
     * Lifecycle callback to clean URLs in the content.
     */
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
        return (bool) $this->publicPreview;
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
