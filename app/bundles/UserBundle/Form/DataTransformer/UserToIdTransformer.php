<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Mautic\UserBundle\Entity\User;

/**
 * Class UserToIdTransformer
 */
class UserToIdTransformer implements DataTransformerInterface
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Transforms an object (user) to a string (id).
     *
     * @param  User|null $user
     *
     * @return string
     */
    public function transform($user)
    {
        if ($user === null) {
            return '';
        }

        return $user->getId();
    }

    /**
     * Transforms a string (id) to an object (user).
     *
     * @param  string $id
     *
     * @return User|null
     *
     * @throws TransformationFailedException if object (user) is not found.
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return null;
        }

        $user = $this->em
            ->getRepository('MauticUserBundle:User')
            ->findOneBy(array('id' => $id));

        if ($user === null) {
            throw new TransformationFailedException(sprintf(
                'A user with id "%s" does not exist!',
                $id
            ));
        }

        return $user;
    }
}
