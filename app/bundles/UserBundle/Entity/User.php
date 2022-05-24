<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class User extends FormEntity implements UserInterface, \Serializable, EquatableInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * Used for when updating the password.
     *
     * @var string
     */
    private $plainPassword;

    /**
     * Used for updating account.
     *
     * @var string
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
     * @var string
     */
    private $position;

    /**
     * @var Role|null
     */
    private $role;

    /**
     * @var string
     */
    private $timezone = '';

    /**
     * @var string
     */
    private $locale = '';

    /**
     * @var \DateTime
     */
    private $lastLogin;

    /**
     * @var \DateTime
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
     * @var string
     */
    private $signature;

    /**
     * @var bool
     */
    private $guest = false;

    /**
     * @param bool $isGuest
     */
    public function __construct($isGuest = false)
    {
        $this->guest = $isGuest;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
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
                'groups'  => ['CheckPassword'],
            ]
        ));

        $metadata->addPropertyConstraint('plainPassword', new Assert\Length(
            [
                'min'        => 6,
                'minMessage' => 'mautic.user.user.password.minlength',
                'groups'     => ['CheckPassword'],
            ]
        ));

        $metadata->setGroupSequence(['User', 'SecondPass', 'CheckPassword']);
    }

    /**
     * @return array
     */
    public static function determineValidationGroups(Form $form)
    {
        $data   = $form->getData();
        $groups = ['User', 'SecondPass'];

        //check if creating a new user or editing an existing user and the password has been updated
        if ($data instanceof User && (!$data->getId() || ($data->getId() && $data->getPlainPassword()))) {
            $groups[] = 'CheckPassword';
        }

        return $groups;
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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

    /**
     * {@inheritdoc}
     */
    protected function isChanged($prop, $val)
    {
        if ('role' === $prop) {
            /** @var Role|null $newRole */
            $newRole     = $val;
            $currentRole = $this->getRole();

            if ($currentRole && $newRole) {
                if ((int) $currentRole->getId() === (int) $newRole->getId()) {
                    unset($this->changes['role']);
                } else {
                    $this->changes['role'] = [$currentRole->getNameAndId(), $newRole->getNameAndId()];
                }
            } elseif ($currentRole) {
                $this->changes['role'] = [$currentRole->getNameAndId(), null];
            } elseif ($newRole) {
                $this->changes['role'] = [null, $newRole->getNameAndId()];
            }

            return;
        }

        parent::isChanged($prop, $val);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        //bcrypt generates its own salt
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Get current password (that a user has typed into a form).
     *
     * @return string
     */
    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        $roles = [];

        if ($this->username) {
            $roles = [
                (($this->isAdmin()) ? 'ROLE_ADMIN' : 'ROLE_USER'),
            ];

            if (defined('MAUTIC_API_REQUEST') && MAUTIC_API_REQUEST) {
                $roles[] = 'ROLE_API';
            }
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            $this->isPublished(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            $published
            ) = unserialize($serialized);
        $this->setIsPublished($published);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
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
     * @param $plainPassword
     *
     * @return User
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @param $currentPassword
     *
     * @return User
     */
    public function setCurrentPassword($currentPassword)
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

    /**
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
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
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
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param bool $lastFirst
     *
     * @return string
     */
    public function getName($lastFirst = false)
    {
        return ($lastFirst) ? $this->lastName.', '.$this->firstName : $this->firstName.' '.$this->lastName;
    }

    /**
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
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return User
     */
    public function setRole(Role $role = null)
    {
        $this->isChanged('role', $role);
        $this->role = $role;

        return $this;
    }

    /**
     * @return Role|null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return User
     */
    public function setActivePermissions(array $permissions)
    {
        $this->activePermissions = $permissions;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getActivePermissions()
    {
        return $this->activePermissions;
    }

    /**
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
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
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
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $locale
     *
     * @return User
     */
    public function setLocale($locale)
    {
        $this->isChanged('locale', $locale);
        $this->locale = $locale;

        return $this;
    }

    /**
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
    public function setLastLogin($lastLogin = null)
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
    public function setLastActive($lastActive = null)
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
    public function setPreferences(array $preferences)
    {
        $this->preferences = $preferences;
    }

    /**
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
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param UserInterface $user
     *
     * Needed for SAML to work correctly
     */
    public function isEqualTo(UserInterface $user)
    {
        $thisUser = $this->getId().$this->getUsername().$this->getPassword();
        $thatUser = $user->getId().$user->getUsername().$user->getPassword();

        return $thisUser === $thatUser;
    }

    /**
     * @return bool
     */
    public function isGuest()
    {
        return $this->guest;
    }
}
