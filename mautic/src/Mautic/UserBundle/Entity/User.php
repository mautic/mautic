<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * Class User
 *
 * @package Mautic\UserBundle\Entity
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="Mautic\UserBundle\Entity\UserRepository")
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(name="date_added", type="datetime")
     */
    protected $dateAdded;

    public function __construct()
    {
        $this->isActive = true;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('username', new Assert\NotBlank(
            array('message' => 'mautic.user.username.notblank')
        ));

        $metadata->addConstraint(new UniqueEntity(
            array(
                'fields'  => array('username'),
                'message' => 'mautic.user.username.unique',
                'repositoryMethod' => 'findByUsernameOrMatchEmail'
            )
        ));

        $metadata->addPropertyConstraint('firstName', new Assert\NotBlank(
            array('message' => 'mautic.user.firstname.notblank')
        ));

        $metadata->addPropertyConstraint('lastName',  new Assert\NotBlank(
            array('message' => 'mautic.user.lastname.notblank')
        ));

        $metadata->addPropertyConstraint('email',  new Assert\NotBlank(
            array('message' => 'mautic.user.email.valid')
        ));

        $metadata->addPropertyConstraint('email',     new Assert\Email(
            array(
                'message' => 'mautic.user.email.valid',
                'groups'  => array('SecondPass')
            )

        ));

        $metadata->addConstraint(new UniqueEntity(
            array(
                'fields'  => 'email',
                'message' => 'mautic.user.email.unique'
            )
        ));

        $metadata->addPropertyConstraint('password',  new Assert\NotBlank(
            array(
                'message' => 'mautic.user.password.notblank',
                'groups'  => array('CheckPassword')
            )
        ));

        $metadata->addPropertyConstraint('password',  new Assert\Length(
            array(
                'min'        => 6,
                'minMessage' => 'mautic.user.password.minlength',
                'groups'     => array('CheckPassword')
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
        if (!$data->getId() || ($data->getId() && $data->getPassword())) {
            //creating a new user or editing an existing user and the password has been updated
            return array('User', 'SecondPass', 'CheckPassword');
        } else {
            //editing an existing user and the password is empty
            return array('User', 'SecondPass');
        }
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
     * @return array|\Symfony\Component\Security\Core\Role\Role[]
     */
    public function getRoles()
    {
        return array();
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
            $this->isActive
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
            $this->isActive
        ) = unserialize($serialized);
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
     * Set firstName
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
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
    public function getFullName($lastFirst = false)
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return User
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
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
        return $this->isActive;
    }
}
