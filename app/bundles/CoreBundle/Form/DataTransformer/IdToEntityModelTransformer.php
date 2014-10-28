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
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class IdToEntityModelTransformer
 *
 * @package Mautic\CoreBundle\Form\DataTransformer
 */
class IdToEntityModelTransformer implements DataTransformerInterface
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
     * @var bool
     */
    private $isArray;

    /**
     * @param EntityManager $em
     * @param string        $repo
     * @param string        $identifier
     */
    public function __construct(EntityManager $em, $repo = '', $identifier = 'id', $isArray = false)
    {
        $this->em         = $em;
        $this->repository = $repo;
        $this->id         = $identifier;
        $this->isArray    = $isArray;
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

        if (!$this->isArray) {
            if (is_null($entity) || !is_object($entity) || !method_exists($entity, $func)) {
                return '';
            }

            return $entity->$func();
        } else {
            if (is_null($entity) && !is_array($entity) && !$entity instanceof PersistentCollection) {
                return array();
            }

            $return = array();
            foreach ($entity as $e) {
                $return[] = $e->$func();
            }

            return $return;
        }
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
        if (!$this->isArray) {
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
        } else {
            if (empty($id) || !is_array($id)) {
                return array();
            }

            $repo   = $this->em->getRepository($this->repository);
            $prefix = $repo->getTableAlias();

            $entities = $repo->getEntities(array(
                'filter' => array(
                    'force' => array(
                        array(
                            'column' => $prefix . '.' . $this->id,
                            'expr'   => 'in',
                            'value'  => $id
                        )
                    )
                )
            ));

            if (!count($entities)) {
                throw new TransformationFailedException(sprintf(
                    'Entities with a/an ' . $this->id . ' of "%s" does not exist!',
                    $id
                ));
            }

            return $entities;
        }
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
