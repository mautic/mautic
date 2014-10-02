<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Entity;

use Bazinga\OAuthServerBundle\Model\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class User
 *
 * @package Mautic\UserBundle\Entity
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="Mautic\UserBundle\Entity\UserRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class User extends FormEntity implements UserInterface, AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails", "userList"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails", "userList"})
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $password;

    /**
     * Used for when updating the password
     */
    private $plainPassword;

    /**
     * Used for updating account
     * @var
     */
    private $currentPassword;

    /**
     * @ORM\Column(name="first_name",type="string", length=50)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails", "userList"})
     */
    private $firstName;

    /**
     * @ORM\Column(name="last_name", type="string", length=50)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails", "userList"})
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=60, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $position;

    /**
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="users")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $role;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $timezone = 'UTC';

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $locale   = 'en_US';

    /**
     * @ORM\Column(type="datetime", name="last_login", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $lastLogin;

    /**
     * @ORM\Column(type="datetime", name="last_active", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $lastActive;

    /**
     * @ORM\Column(type="string", name="online_status", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"userDetails"})
     */
    private $onlineStatus;

    /**
     * Stores active role permissions
     * @var
     */
    private $activePermissions;

    protected function isChanged($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'role') {
            if ($current && !$val) {
                $this->changes['role'] = array($current->getName() . ' ('. $current->getId().')', $val);
            } elseif (!$this->role && $val) {
                $this->changes['role'] = array($current, $val->getName() . ' ('. $val->getId().')');
            } elseif ($current && $val && $current->getId() != $val->getId()) {
                $this->changes['role'] = array($current->getName() . '('. $current->getId().')',
                    $val->getName() . '('. $val->getId().')');
            }
        } elseif ($current != $val) {
            $this->changes[$prop] = array($current, $val);
        }
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('username', new Assert\NotBlank(
            array('message' => 'mautic.user.user.username.notblank')
        ));

        $metadata->addConstraint(new UniqueEntity(
            array(
                'fields'           => array('username'),
                'message'          => 'mautic.user.user.username.unique',
                'repositoryMethod' => 'checkUniqueUsernameEmail'
            )
        ));

        $metadata->addPropertyConstraint('firstName', new Assert\NotBlank(
            array('message' => 'mautic.user.user.firstname.notblank')
        ));

        $metadata->addPropertyConstraint('lastName',  new Assert\NotBlank(
            array('message' => 'mautic.user.user.lastname.notblank')
        ));

        $metadata->addPropertyConstraint('email',  new Assert\NotBlank(
            array('message' => 'mautic.user.user.email.valid')
        ));

        $metadata->addPropertyConstraint('email',     new Assert\Email(
            array(
                'message' => 'mautic.user.user.email.valid',
                'groups'  => array('SecondPass')
            )
        ));

        $metadata->addConstraint(new UniqueEntity(
            array(
                'fields'           => array('email'),
                'message'          => 'mautic.user.user.email.unique',
                'repositoryMethod' => 'checkUniqueUsernameEmail'
            )
        ));

        $metadata->addPropertyConstraint('role',  new Assert\NotBlank(
            array('message' => 'mautic.user.user.role.notblank')
        ));

        $metadata->addPropertyConstraint('plainPassword',  new Assert\NotBlank(
            array(
                'message' => 'mautic.user.user.password.notblank',
                'groups'  => array('CheckPassword')
            )
        ));

        $metadata->addPropertyConstraint('plainPassword',  new Assert\Length(
            array(
                'min'        => 6,
                'minMessage' => 'mautic.user.user.password.minlength',
                'groups'     => array('CheckPassword')
            )
        ));

        $metadata->addPropertyConstraint('currentPassword',  new SecurityAssert\UserPassword(
            array(
                'message' => 'mautic.user.account.password.userpassword',
                'groups'  => array('Profile')
            )
        ));

        $metadata->setGroupSequence(array('User', 'SecondPass', 'CheckPassword'));
    }

    /**
     * @param Form $form
     * @return array
     */
    static public function determineValidationGroups(Form $form) {
        $data = $form->getData();
        $groups = array('User', 'SecondPass');

        //check if creating a new user or editing an existing user and the password has been updated
        if (!$data->getId() || ($data->getId() && $data->getPlainPassword())) {
            $groups[] = 'CheckPassword';
        }

        //require current password if on profile page
        if ($form->has('currentPassword')) {
            $groups[] = 'Profile';
        }

        return $groups;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return null|string
     */
    public function getSalt()
    {
        //bcrypt generates its own salt
        return null;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Get plain password
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Get current password (that a user has typed into a form)
     *
     * @return string
     */
    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    /**
     * Determines user role for symfony authentication
     *
     * @return array
     */
    public function getRoles()
    {
        $roles = array(
            "ROLE_API",
            (($this->isAdmin()) ? "ROLE_ADMIN" : "ROLE_USER")
        );
        return $roles;
    }

    public function eraseCredentials()
    {

    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->isPublished()
        ));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            $published
        ) = unserialize($serialized);
        $this->setIsPublished($published);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->isChanged('username', $username);
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set plain password
     *
     * @param $plainPassword
     * @return $this
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Set current password
     *
     * @param $currentPassword
     * @return $this
     */
    public function setCurrentPassword($currentPassword)
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }


    /**
     * Set firstName
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->isChanged('firstName', $firstName);
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->isChanged('lastName', $lastName);
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Get full name
     *
     * @param bool $lastFirst
     * @return string
     */
    public function getName($lastFirst = false)
    {
        $fullName = ($lastFirst) ? $this->lastName . ", " . $this->firstName : $this->firstName . " " . $this->lastName;
        return $fullName;

    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->isChanged('email', $email);
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isPublished();
    }

    /**
     * Set role
     *
     * @param \Mautic\UserBundle\Entity\Role $role
     * @return User
     */
    public function setRole(\Mautic\UserBundle\Entity\Role $role = null)
    {
        $this->isChanged('role', $role);
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Mautic\UserBundle\Entity\Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set active permissions
     *
     * @param array $permissions
     */
    public function setActivePermissions(array $permissions) {
        $this->activePermissions = $permissions;
        return $this;
    }

    /**
     * Get active permissions
     *
     * @return mixed
     */
    public function getActivePermissions() {
        return $this->activePermissions;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return User
     */
    public function setPosition($position)
    {
        $this->isChanged('position', $position);
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     * @return User
     */
    public function setTimezone($timezone)
    {
        $this->isChanged('timezone', $timezone);
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return User
     */
    public function setLocale($locale)
    {
        $this->isChanged('locale', $locale);
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Determines if user is admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        if ($this->role !== null) {
            return $this->role->isAdmin();
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getLastLogin ()
    {
        return $this->lastLogin;
    }

    /**
     * @param mixed $lastLogin
     */
    public function setLastLogin ($lastLogin = null)
    {
        if (empty($lastLogin)) {
            $lastLogin = new \DateTime();
        }
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return mixed
     */
    public function getLastActive ()
    {
        return $this->lastActive;
    }

    /**
     * @param mixed $lastActive
     */
    public function setLastActive ($lastActive = null)
    {
        if (empty($lastActive)) {
            $lastActive = new \DateTime();
        }
        $this->lastActive = $lastActive;
    }

    /**
     * @return mixed
     */
    public function getOnlineStatus ()
    {
        return $this->onlineStatus;
    }

    /**
     * @param mixed $status
     */
    public function setOnlineStatus ($status)
    {
        $this->onlineStatus = $status;
    }
}
