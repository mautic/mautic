<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Model\RefreshToken as BaseRefreshToken;
use Mautic\UserBundle\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'oauth2_refreshtokens')]
#[ORM\Index(columns: ['token'], name: 'oauth2_refresh_token_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class RefreshToken extends BaseRefreshToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected $id;
    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', nullable: false, onDelete: 'CASCADE')]
    protected \FOS\OAuthServerBundle\Model\ClientInterface $client;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    protected ?\Symfony\Component\Security\Core\User\UserInterface $user = null;
    #[ORM\Column(type: 'string', length: 191, unique: true)]
    protected string $token;
    #[ORM\Column(name: 'expires_at', type: 'bigint', nullable: true)]
    protected ?int $expiresAt = null;
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    protected ?string $scope = null;
}
