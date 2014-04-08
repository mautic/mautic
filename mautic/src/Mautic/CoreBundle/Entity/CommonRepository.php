<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Class CommonRepository
 *
 * @package Mautic\CoreBundle\Entity
 */
class CommonRepository extends EntityRepository
{

    /**
     * Save an entity through the repository
     *
     * @param $entity
     * @return int
     */
    public function saveEntity($entity)
    {
        try {
            $this->_em->persist($entity);
            $this->_em->flush();
            return 1;
        } catch (\Doctrine\ORM\ORMException $e) {
            //@TODO add error message
            return 0;
        }
    }

    /**
     * Delete an entity through the repository
     *
     * @param $entity
     * @return int
     */
    public function deleteEntity($entity)
    {
        try {
            //delete entity
            $this->_em->remove($entity);
            $this->_em->flush();
            return 1;
        } catch (\Exception $e) {
            //@TODO add error message
            return 0;
        }
    }
}
