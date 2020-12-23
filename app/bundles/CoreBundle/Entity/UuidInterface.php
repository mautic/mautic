<?php

namespace Mautic\CoreBundle\Entity;

interface UuidInterface
{
    public function getUuid();

    public function setUuid();

    public function addUuidMetadata();
}
