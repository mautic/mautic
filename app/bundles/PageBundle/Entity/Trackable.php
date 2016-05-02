<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Trackable
 *
 * @package Mautic\PageBundle\Entity
 */
class Trackable
{
    /**
     * @var Redirect
     */
    private $redirect;

    /**
     * @var string
     */
    private $source;

    /**
     * @var int
     */
    private $sourceId;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('redirect_entity_xref');

        $builder->createManyToOne('redirect', 'Mautic\PageBundle\Entity\Redirect')
            ->addJoinColumn('redirect_id', 'id', true, false, 'DELETE')
            ->build();

        $builder->addNamedField('sourceId', 'integer', 'source_id');

        $builder->addField('source', 'string');
    }

    /**
     * @return Redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @param Redirect $redirect
     *
     * @return RedirectXref
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     *
     * @return RedirectXref
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     *
     * @return RedirectXref
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }
}
