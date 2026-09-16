<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Model\AuthCode as BaseAuthCode;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'oauth2_authcodes')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class AuthCode extends BaseAuthCode
{
    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', nullable: false, onDelete: 'CASCADE')]
    protected \FOS\OAuthServerBundle\Model\ClientInterface $client;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    protected ?\Symfony\Component\Security\Core\User\UserInterface $user = null;
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createField('token', 'string')
            ->unique()
            ->build();

        $builder->createField('expiresAt', 'bigint')
            ->columnName('expires_at')
            ->nullable()
            ->build();

        $builder->createField('scope', 'string')
            ->nullable()
            ->build();

        $builder->createField('redirectUri', 'text')
            ->columnName('redirect_uri')
            ->build();
    }
}
