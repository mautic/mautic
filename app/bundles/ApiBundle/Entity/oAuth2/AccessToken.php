<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Model\AccessToken as BaseAccessToken;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
#[ORM\Table(name: 'oauth2_accesstokens')]
#[ORM\Index(columns: ['token'], name: 'oauth2_access_token_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class AccessToken extends BaseAccessToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected $id;
    #[ORM\Column(type: 'string', length: 191, unique: true)]
    protected string $token;
    #[ORM\Column(name: 'expires_at', type: 'bigint', nullable: true)]
    protected ?int $expiresAt = null;
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    protected ?string $scope = null;
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createManyToOne('client', 'Client')
            ->addJoinColumn('client_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('user', User::class)
            ->addJoinColumn('user_id', 'id', true, false, 'CASCADE')
            ->build();
    }
}
