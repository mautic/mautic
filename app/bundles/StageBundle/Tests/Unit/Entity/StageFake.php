<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Unit\Entity;

use Mautic\StageBundle\Entity\Stage;

class StageFake extends Stage
{
    private ?int $id;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
        parent::__construct();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
