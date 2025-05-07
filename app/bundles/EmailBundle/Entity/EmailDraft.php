<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class EmailDraft
{
    private int $id;

    public function __construct(private Email $email, private ?string $html, private ?string $template, private ?bool $publicPreview = true)
    {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('emails_draft')
            ->setCustomRepositoryClass(EmailDraftRepository::class)
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

        $builder->createOneToOne('email', Email::class)
            ->inversedBy('draft')
            ->addJoinColumn('email_id', 'id', false)
            ->build();
    }

    /**
     * Lifecycle callback to clean URLs in the content.
     */
    public function cleanUrlsInContent(): void
    {
        $this->decodeAmpersands($this->html);
    }

    /**
     * Check all links in content and decode &amp;
     * This even works with double encoded ampersands.
     */
    private function decodeAmpersands(string &$content): void
    {
        if (preg_match_all('/((https?|ftps?):\/\/)([a-zA-Z0-9-\.{}]*[a-zA-Z0-9=}]*)(\??)([^\s\"\]]+)?/i', $content, $matches)) {
            foreach ($matches[0] as $url) {
                $newUrl = $url;

                while (str_contains($newUrl, '&amp;')) {
                    $newUrl = str_replace('&amp;', '&', $newUrl);
                }

                $content = str_replace($url, $newUrl, $content);
            }
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function setHtml(string $html): void
    {
        $this->html = $html;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function isPublicPreview(): bool
    {
        return $this->publicPreview;
    }

    public function getPublishStatus(): bool
    {
        return $this->publicPreview;
    }

    public function setPublicPreview(bool $publicPreview): void
    {
        $this->publicPreview = $publicPreview;
    }
}
