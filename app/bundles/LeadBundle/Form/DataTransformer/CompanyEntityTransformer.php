<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class TagEntityModelTransformer
 */
class CompanyEntityTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
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
     * @param bool          $isArray
     */
    public function __construct(EntityManager $em, $repo = '', $identifier = 'id', $isArray = false)
    {
        $this->em         = $em;
        $this->repository = $repo;
        $this->id         = $identifier;
        $this->isArray    = $isArray;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($entity)
    {
        if (!$this->isArray) {
            if (is_null($entity) || !is_object($entity)) {
                return '';
            }

            return $entity->getId();
        }

        if (is_null($entity) && !is_array($entity) && !$entity instanceof PersistentCollection) {
            return array();
        }

        $return = array();
        foreach ($entity as $e) {
            $return[] = $e->getId();
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TransformationFailedException if object is not found.
     */
    public function transform($id)
    {
        $repo   = $this->em->getRepository($this->repository);

        $entities = $repo->getCompanies();

        if (!count($entities)) {
            throw new TransformationFailedException(sprintf(
                'Companies for "%s" does not exist!',
                $id
            ));
        }

        return $entities;
    }

    /**
     * Set the repository to use
     *
     * @param string $repo
     *
     * @return void
     */
    public function setRepository($repo)
    {
        $this->repository = $repo;
    }

    /**
     * Set the identifier to use
     *
     * @param string $id
     *
     * @return void
     */
    public function setIdentifier($id)
    {
        $this->id = $id;
    }
}
