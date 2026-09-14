<?php

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Model\Client as BaseClient;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use OAuth2\OAuth2;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'oauth2_clients')]
#[ORM\Index(columns: ['random_id'], name: 'client_id_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Client extends BaseClient
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'mautic.core.name.required')]
    protected $name;

    /**
     * @var ArrayCollection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinTable(name: 'oauth2_user_client_xref')]
    #[ORM\JoinColumn(name: 'client_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    protected $users;

    /**
     * @var ArrayCollection
     */
    protected $authCodes;

    #[ORM\Column(name: 'random_id', type: 'string', length: 191)]
    protected ?string $randomId = null;

    #[ORM\Column(type: 'string', length: 191)]
    protected ?string $secret = null;

    /**
     * @var array<string>
     */
    #[Assert\NotBlank(message: 'mautic.api.client.redirecturis.notblank')]
    #[ORM\Column(name: 'redirect_uris', type: 'array')]
    protected array $redirectUris = [];

    /**
     * @var array<string>
     */
    #[ORM\Column(name: 'allowed_grant_types', type: 'array')]
    protected array $allowedGrantTypes;

    #[ORM\ManyToOne(targetEntity: Role::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'role_id')]
    protected ?Role $role = null;

    public function __construct()
    {
        parent::__construct();

        $this->allowedGrantTypes = [
            OAuth2::GRANT_TYPE_AUTH_CODE,
            OAuth2::GRANT_TYPE_REFRESH_TOKEN,
        ];

        $this->users     = new ArrayCollection();
        $this->authCodes = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addIdColumns('name', false);
    }

    /**
     * @var array
     */
    protected $changes;

    protected function isChanged($prop, $val): void
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->{$getter}();
        if ($current != $val) {
            $this->changes[$prop] = [$current, $val];
        }
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setRedirectUris(array $redirectUris): void
    {
        $this->isChanged('redirectUris', $redirectUris);

        $this->redirectUris = $redirectUris;
    }

    public function addAuthCode(AuthCode $authCodes): static
    {
        $this->authCodes[] = $authCodes;

        return $this;
    }

    public function removeAuthCode(AuthCode $authCodes): void
    {
        $this->authCodes->removeElement($authCodes);
    }

    /**
     * @return ArrayCollection
     */
    public function getAuthCodes()
    {
        return $this->authCodes;
    }

    /**
     * Determines if a client attempting API access is already authorized by the user.
     *
     * @return bool
     */
    public function isAuthorizedClient(User $user)
    {
        return $this->users->contains($user);
    }

    public function addUser(User $users): static
    {
        $this->users[] = $users;

        return $this;
    }

    public function removeUser(User $users): void
    {
        $this->users->removeElement($users);
    }

    /**
     * @return ArrayCollection<int, User>
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add Authorization Grant Type.
     */
    public function addGrantType(string $grantType): self
    {
        $this->allowedGrantTypes[] = $grantType;

        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }
}
