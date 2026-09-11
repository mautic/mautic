<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: EmailDraftRepository::class)]
#[ORM\Table(name: 'emails_draft')]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class EmailDraft
{
    /**
     * @api cannot be readonly as modified by external source
     */
    private int $id;

    public function __construct(
        #[ORM\OneToOne(inversedBy: 'draft', targetEntity: Email::class)]
        #[ORM\JoinColumn(name: 'email_id', nullable: false)]
        private Email $email,
        private ?string $html,
        private ?string $template,
        #[ORM\Column(name: 'public_preview', type: Types::BOOLEAN, options: ['default' => 1])]
        private ?bool $publicPreview = true,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();
        $builder->addNullableField('html', Types::TEXT);
        $builder->addNullableField('template', Types::STRING);
    }

    /**
     * Lifecycle callback to clean URLs in the content.
     */
    #[ORM\PreUpdate]
    #[ORM\PrePersist]
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
