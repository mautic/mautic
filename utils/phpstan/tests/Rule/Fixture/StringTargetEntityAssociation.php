<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\ORM\Mapping as ORM;

class StringTargetEntityAssociation
{
    #[ORM\ManyToOne(targetEntity: 'Tweet', inversedBy: 'stats')]
    private $tweet;

    #[ORM\ManyToOne(targetEntity: Tweet::class)]
    private $tweetOk;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: 'Child')]
    private $children;

    #[ORM\Column(type: 'string')]
    private $name;
}
