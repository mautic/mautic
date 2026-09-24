<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Annotation\Groups;

trait DateAddedTrait
{
    /**
     * @Groups({
     *     "event:read", "event:write", "campaign:read"
     * })
     */
    #[ORM\Column(name: 'date_added', type: Types::DATETIME_MUTABLE, options: ['default' => '1970-01-01 00:00:00'])]
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
