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
use Mautic\UserBundle\Entity\Role;

/**
 * Class RoleToIdTransformer
 */
class RoleToIdTransformer implements DataTransformerInterface
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
     * Transforms an object (role) to a string (id).
     *
     * @param  Role|null $role
     *
     * @return string
     */
    public function transform($role)
    {
        if ($role === null) {
            return '';
        }

        return $role->getId();
    }

    /**
     * Transforms a string (id) to an object (role).
     *
     * @param  string $id
     *
     * @return Role|null
     *
     * @throws TransformationFailedException if object (role) is not found.
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return null;
        }

        $user = $this->em
            ->getRepository('MauticUserBundle:Role')
            ->findOneBy(array('id' => $id));

        if ($user === null) {
            throw new TransformationFailedException(sprintf(
                'A role with id "%s" does not exist!',
                $id
            ));
        }

        return $user;
    }
}
