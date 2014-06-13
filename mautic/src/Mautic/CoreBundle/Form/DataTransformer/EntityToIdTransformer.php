<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class EntityToIdTransformer
 *
 * @package Mautic\CoreBundle\Form\DataTransformer
 */
class EntityToIdTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var
     */
    private $repository;

    /**
     * @var string
     */
    private $id;

    /**
     * @param EntityManager $em
     * @param string        $repo
     * @param string        $identifier
     */
    public function __construct(EntityManager $em, $repo = '', $identifier = 'id')
    {
        $this->em         = $em;
        $this->repository = $repo;
        $this->id         = $identifier;
    }

    /**
     * Transforms an object to a string (id).
     *
     * @param  Object|null $entity
     * @return string
     */
    public function transform($entity)
    {
        $func = 'get' . ucfirst($this->id);
        if (is_null($entity) || !is_object($entity) || !method_exists($entity, $func)) {
            return '';
        }

        return $entity->$func();
    }

    /**
     * Transforms a string (id) to an object.
     *
     * @param  string $id
     *
     * @return Entity|null
     *
     * @throws TransformationFailedException if object is not found.
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return null;
        }

        $entity = $this->em
            ->getRepository($this->repository)
            ->findOneBy(array($this->id => $id))
        ;

        if ($entity === null) {
            throw new TransformationFailedException(sprintf(
                'An entity with a/an ' . $this->id . ' of "%s" does not exist!',
                $id
            ));
        }

        return $entity;
    }

    /**
     * Set the repository to use
     *
     * @param $repo
     */
    public function setRepository($repo)
    {
        $this->repository = $repo;
    }

    /**
     * Set the identifier to use
     *
     * @param $id
     */
    public function setIdentifier($id)
    {
        $this->id = $id;
    }
}
