<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\UserBundle\Form\Validator\Constraints\NotWeak;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class User extends FormEntity implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface, CacheInvalidateInterface
{
    public const CACHE_NAMESPACE = 'User';

    /**
     * @var ?int
     */
    protected $id;

    protected ?string $username = null;

    /**
     * @var string
     */
    protected $password;

    /**
     * Used for when updating the password.
     *
     * @var ?string
     */
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
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string|null
     */
    private $position;

    /**
     * @var Role
     */
    private $role;

    /**
     * @var string|null
     */
    private $timezone = '';

    /**
     * @var string|null
     */
    private $locale = '';

    /**
     * @var \DateTimeInterface
     */
    private $lastLogin;

    /**
     * @var \DateTimeInterface
     */
    private $lastActive;

    /**
     * Stores active role permissions.
     */
    private $activePermissions;

    /**
     * @var array
     */
    private $preferences = [];

    /**
     * @var string|null
     */
    private $signature;

    /**
     * @param bool $guest
     */
    public function __construct(
        private $guest = false,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('users')
            ->setCustomRepositoryClass(UserRepository::class);

        $builder->addId();

        $builder->createField('username', 'string')
            ->length(191)
            ->unique()
            ->build();

        $builder->createField('password', 'string')
            ->length(64)
            ->build();

        $builder->createField('firstName', 'string')
            ->columnName('first_name')
            ->length(191)
            ->build();

        $builder->createField('lastName', 'string')
            ->columnName('last_name')
            ->length(191)
            ->build();

        $builder->createField('email', 'string')
            ->length(191)
            ->unique()
            ->build();

        $builder->createField('position', 'string')
            ->length(191)
            ->nullable()
            ->build();

        $builder->createManyToOne('role', 'Role')
            ->inversedBy('users')
            ->cascadeMerge()
            ->addJoinColumn('role_id', 'id', false)
            ->build();

        $builder->createField('timezone', 'string')
            ->nullable()
            ->build();

        $builder->createField('locale', 'string')
            ->nullable()
            ->build();

        $builder->createField('lastLogin', 'datetime')
            ->columnName('last_login')
            ->nullable()
            ->build();

        $builder->createField('lastActive', 'datetime')
            ->columnName('last_active')
            ->nullable()
            ->build();

        $builder->createField('preferences', 'array')
            ->nullable()
            ->build();

        $builder->createField('signature', 'text')
            ->nullable()
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('username', new Assert\NotBlank(
            ['message' => 'mautic.user.user.username.notblank']
        ));

        $metadata->addConstraint(new UniqueEntity(
            [
                'fields'           => ['username'],
                'message'          => 'mautic.user.user.username.unique',
                'repositoryMethod' => 'checkUniqueUsernameEmail',
            ]
        ));

        $metadata->addPropertyConstraint('firstName', new Assert\NotBlank(
            ['message' => 'mautic.user.user.firstname.notblank']
        ));

        $metadata->addPropertyConstraint('lastName', new Assert\NotBlank(
            ['message' => 'mautic.user.user.lastname.notblank']
        ));

        $metadata->addPropertyConstraint('email', new Assert\NotBlank(
            ['message' => 'mautic.user.user.email.valid']
        ));

        $metadata->addPropertyConstraint('email', new Assert\Email(
            [
                'message' => 'mautic.user.user.email.valid',
                'groups'  => ['SecondPass'],
            ]
        ));

        $metadata->addConstraint(new UniqueEntity(
            [
                'fields'           => ['email'],
                'message'          => 'mautic.user.user.email.unique',
                'repositoryMethod' => 'checkUniqueUsernameEmail',
            ]
        ));

        $metadata->addPropertyConstraint('role', new Assert\NotBlank(
            ['message' => 'mautic.user.user.role.notblank']
        ));

        $metadata->addPropertyConstraint('plainPassword', new Assert\NotBlank(
            [
                'message' => 'mautic.user.user.password.notblank',
                'groups'  => ['CheckPasswordNotBlank'],
            ]
        ));

        $metadata->addPropertyConstraint('plainPassword', new Assert\Length(
            [
                'min'        => 6,
                'minMessage' => 'mautic.user.user.password.minlength',
                'groups'     => ['CheckPassword'],
            ]
        ));

        $metadata->addPropertyConstraint('plainPassword', new NotWeak(
            [
                'message'    => 'mautic.user.user.password.weak',
                'groups'     => ['CheckPassword'],
            ]
        ));

        $metadata->setGroupSequence(['User', 'SecondPass', 'CheckPassword']);
    }

    public static function determineValidationGroups(Form $form): array
    {
        $data   = $form->getData();
        $groups = ['User', 'SecondPass'];
        if ($data instanceof User) {
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

    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
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
     * Get plain password.
     *
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

    public function eraseCredentials(): void
    {
        $this->plainPassword   = null;
        $this->currentPassword = null;
    }

    /**
     * @return array<int, mixed>
     */
    public function __serialize(): array
    {
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

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->isChanged('username', $username);
        $this->username = $username;

        return $this;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set plain password.
     *
     * @return User
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Set current password.
     *
     * @return User
     */
    public function setCurrentPassword($currentPassword)
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

    /**
     * Set firstName.
     *
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->isChanged('firstName', $firstName);
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName.
     *
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->isChanged('lastName', $lastName);
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Get full name.
     *
     * @param bool $lastFirst
     */
    public function getName($lastFirst = false): string
    {
        return ($lastFirst) ? $this->lastName.', '.$this->firstName : $this->firstName.' '.$this->lastName;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->isChanged('email', $email);
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set role.
     *
     * @return User
     */
    public function setRole(Role $role = null)
    {
        $this->isChanged('role', $role);
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set active permissions.
     *
     * @return User
     */
    public function setActivePermissions(array $permissions)
    {
        $this->activePermissions = $permissions;

        return $this;
    }

    /**
     * Get active permissions.
     *
     * @return mixed
     */
    public function getActivePermissions()
    {
        return $this->activePermissions;
    }

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return User
     */
    public function setPosition($position)
    {
        $this->isChanged('position', $position);
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set timezone.
     *
     * @param string $timezone
     *
     * @return User
     */
    public function setTimezone($timezone)
    {
        $this->isChanged('timezone', $timezone);
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone.
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @return User
     */
    public function setLocale(?string $locale)
    {
        $this->isChanged('locale', $locale);
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
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
        } else {
            return false;
        }
    }

    /**
     * @return mixed
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
     * @return mixed
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
     * @return mixed
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @param mixed $preferences
     */
    public function setPreferences(array $preferences): void
    {
        $this->preferences = $preferences;
    }

    /**
     * Set signature.
     *
     * @param string $signature
     *
     * @return User
     */
    public function setSignature($signature)
    {
        $this->isChanged('signature', $signature);
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature.
     *
     * @return string
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

        $thisUser = $this->getId().$this->getUserIdentifier().$this->getPassword();
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
}
