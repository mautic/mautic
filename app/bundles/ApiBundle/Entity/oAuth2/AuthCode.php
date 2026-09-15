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
    #[ORM\Column(name: 'redirect_uri', type: 'text')]
    protected string $redirectUri;
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createManyToOne('client', 'Client')
            ->addJoinColumn('client_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('user', User::class)
            ->addJoinColumn('user_id', 'id', false, false, 'CASCADE')
            ->build();
    }
}
