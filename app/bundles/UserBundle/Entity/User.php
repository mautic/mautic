<?php

namespace Mautic\UserBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\UserBundle\ApiPlatform\UserProcessor;
use Mautic\UserBundle\Form\Validator\Constraints\NotWeak;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'User',
    operations: [
        new GetCollection(uriTemplate: '/users', security: "is_granted('user:users:viewown')"),
        new Post(uriTemplate: '/users', security: "is_granted('user:users:create')", processor: UserProcessor::class),
        new Get(uriTemplate: '/users/{id}', security: "is_granted('user:users:viewown', object)"),
        new Put(uriTemplate: '/users/{id}', security: "is_granted('user:users:editown', object)", processor: UserProcessor::class),
        new Patch(uriTemplate: '/users/{id}', security: "is_granted('user:users:editother', object)", processor: UserProcessor::class),
        new Delete(uriTemplate: '/users/{id}', security: "is_granted('user:users:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['user:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['user:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[Assert\GroupSequence(['User', 'SecondPass', 'CheckPassword'])]
#[UniqueEntity(fields: ['username'], message: 'mautic.user.user.username.unique', repositoryMethod: 'checkUniqueUsernameEmail')]
#[UniqueEntity(fields: ['email'], message: 'mautic.user.user.email.unique', repositoryMethod: 'checkUniqueUsernameEmail', groups: ['User', 'SecondPass'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class User extends FormEntity implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface, CacheInvalidateInterface
{
    public const CACHE_NAMESPACE = 'User';

    /**
     * @var ?int
     */
    #[Groups(['user:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'mautic.user.user.username.notblank')]
    #[ORM\Column(type: 'string', length: 191, unique: true)]
    protected ?string $username = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 64)]
    protected $password;

    /**
     * Used for when updating the password.
     *
     * @var ?string
     */
    #[Groups(['user:write'])]
    #[Assert\NotBlank(message: 'mautic.user.user.password.notblank', groups: ['CheckPasswordNotBlank'])]
    #[Assert\Length(min: 6, minMessage: 'mautic.user.user.password.minlength', groups: ['CheckPassword'])]
    #[NotWeak(message: 'mautic.user.user.password.weak', groups: ['CheckPassword'])]
    private $plainPassword;

    /**
     * Used for updating account.
     *
     * @var ?string
     */
    private $currentPassword;

    /**
     * @var string
     */
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'mautic.user.user.firstname.notblank')]
    #[ORM\Column(name: 'first_name', type: 'string', length: 191)]
    private $firstName;

    /**
     * @var string
     */
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'mautic.user.user.lastname.notblank')]
    #[ORM\Column(name: 'last_name', type: 'string', length: 191)]
    private $lastName;

    /**
     * @var string
     */
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'mautic.user.user.email.valid')]
    #[Assert\Email(message: 'mautic.user.user.email.valid', groups: ['SecondPass'])]
    #[ORM\Column(type: 'string', length: 191, unique: true)]
    private $email;

    /**
     * @var string|null
     */
    #[Groups(['user:read', 'user:write'])]
    #[Assert\Length(max: 191, maxMessage: 'mautic.user.user.position.toolong')]
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $position;

    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'mautic.user.user.role.notblank')]
    #[ORM\ManyToOne(targetEntity: Role::class, cascade: ['merge'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'role_id', nullable: false)]
    private ?\Mautic\UserBundle\Entity\Role $role = null;

    /**
     * @var string|null
     */
    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $timezone = '';

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private ?string $locale = '';

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['user:read'])]
    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private $lastLogin;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['user:read'])]
    #[ORM\Column(name: 'last_active', type: 'datetime', nullable: true)]
    private $lastActive;

    /**
     * Stores active role permissions.
     */
    private ?array $activePermissions = null;

    /**
     * @var mixed[]
     */
    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(type: 'array', nullable: true)]
    private array $preferences = [];

    /**
     * @var string|null
     */
    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $signature;

    /**
     * @param bool $guest
     */
    public function __construct(
        private $guest = false,
    ) {
    }

    public static function determineValidationGroups(Form $form): array
    {
        $data   = $form->getData();
        $groups = ['User', 'SecondPass'];
        if ($data instanceof self) {
            $isNewUser        = !$data->getId();
            $hasPlainPassword = !empty($data->getPlainPassword());

            if ($isNewUser) {
                $groups[] = $hasPlainPassword ? 'CheckPassword' : 'CheckPasswordNotBlank';
            } elseif ($hasPlainPassword) {
                $groups[] = 'CheckPassword';
            }
        }

        return $groups;
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('user')
            ->addListProperties(
                [
                    'id',
                    'username',
                    'firstName',
                    'lastName',
                ]
            )
            ->addProperties(
                [
                    'email',
                    'position',
                    'role',
                    'timezone',
                    'locale',
                    'lastLogin',
                    'lastActive',
                    'signature',
                ]
            )
            ->build();
    }

    protected function isChanged($prop, $val): void
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->{$getter}();
        if ('role' == $prop) {
            if ($current && !$val) {
                $this->changes['role'] = [$current->getName().' ('.$current->getId().')', $val];
            } elseif (!$this->role && $val) {
                $this->changes['role'] = [$current, $val->getName().' ('.$val->getId().')'];
            } elseif ($current && $val && $current->getId() != $val->getId()) {
                $this->changes['role'] = [
                    $current->getName().'('.$current->getId().')',
                    $val->getName().'('.$val->getId().')',
                ];
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username ?? '';
    }

    public function getSalt(): ?string
    {
        // bcrypt generates its own salt
        return null;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return ?string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Get current password (that a user has typed into a form).
     *
     * @return ?string
     */
    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = [];

        if ($this->username) {
            $roles = [
                ($this->isAdmin()) ? 'ROLE_ADMIN' : 'ROLE_USER',
            ];

            if (defined('MAUTIC_API_REQUEST') && MAUTIC_API_REQUEST) {
                $roles[] = 'ROLE_API';
            }
        }

        return $roles;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    /**
     * @return array<int, mixed>
     */
    public function __serialize(): array
    {
        $this->plainPassword   = null;
        $this->currentPassword = null;

        return [
            $this->id,
            $this->username,
            $this->password,
            $this->isPublished(),
        ];
    }

    /**
     * @param array<int, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        [
            $this->id,
            $this->username,
            $this->password,
            $published,
        ] = $data;
        $this->setIsPublished($published);
    }

    /**
     * @return ?int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setUsername(?string $username): static
    {
        $this->isChanged('username', $username);
        $this->username = $username;

        return $this;
    }

    /**
     * @param string $password
     */
    public function setPassword($password): static
    {
        $this->password = $password;

        return $this;
    }

    public function setPlainPassword($plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function setCurrentPassword($currentPassword): static
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName): static
    {
        $this->isChanged('firstName', $firstName);
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName): static
    {
        $this->isChanged('lastName', $lastName);
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Get full name.
     */
    public function getName(bool $lastFirst = false): string
    {
        return ($lastFirst) ? $this->lastName.', '.$this->firstName : $this->firstName.' '.$this->lastName;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): static
    {
        $this->isChanged('email', $email);
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function setRole(?Role $role = null): static
    {
        $this->isChanged('role', $role);
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?\Mautic\UserBundle\Entity\Role
    {
        return $this->role;
    }

    public function setActivePermissions(array $permissions): static
    {
        $this->activePermissions = $permissions;

        return $this;
    }

    public function getActivePermissions(): ?array
    {
        return $this->activePermissions;
    }

    /**
     * @param string $position
     */
    public function setPosition($position): static
    {
        $this->isChanged('position', $position);
        $this->position = $position;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone): static
    {
        $this->isChanged('timezone', $timezone);
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setLocale(?string $locale): static
    {
        $this->isChanged('locale', $locale);
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Determines if user is admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        if (null !== $this->role) {
            return $this->role->isAdmin();
        }

        return false;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param mixed $lastLogin
     */
    public function setLastLogin($lastLogin = null): void
    {
        if (empty($lastLogin)) {
            $lastLogin = new \DateTime();
        }
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastActive()
    {
        return $this->lastActive;
    }

    /**
     * @param mixed $lastActive
     */
    public function setLastActive($lastActive = null): void
    {
        if (empty($lastActive)) {
            $lastActive = new \DateTime();
        }
        $this->lastActive = $lastActive;
    }

    /**
     * @return mixed[]
     */
    public function getPreferences(): array
    {
        return $this->preferences;
    }

    /**
     * @param mixed[] $preferences
     */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = $preferences;
    }

    /**
     * @param string $signature
     */
    public function setSignature($signature): static
    {
        $this->isChanged('signature', $signature);
        $this->signature = $signature;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Needed for SAML to work correctly.
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        $thisUser = $this->id.$this->getUserIdentifier().$this->password;
        $thatUser = $user->getId().$user->getUserIdentifier().$user->getPassword();

        return $thisUser === $thatUser;
    }

    /**
     * @return bool
     */
    public function isGuest()
    {
        return $this->guest;
    }

    public function getCacheNamespacesToDelete(): array
    {
        return [self::CACHE_NAMESPACE];
    }

    public static function createFromInvite(UserInvite $invite): self
    {
        $user = new self();
        $user->setEmail($invite->getEmail());
        $user->setRole($invite->getRole());

        return $user;
    }
}
