<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Annotation\Groups;

trait DateAddedTrait
{
    /**
     * @Groups({
     *     "event:read", "event:write", "campaign:read"
     * })
     */
    private \DateTime $dateAdded;

    public static function addDateAddedField(ClassMetadataBuilder $builder): void
    {
        $builder->addDateAdded();
    }

    public function getDateAdded(): \DateTime
    {
        return $this->dateAdded;
    }

    public function setDateAdded(\DateTime $dateAdded): void
    {
        $this->dateAdded = $dateAdded;
    }
}
