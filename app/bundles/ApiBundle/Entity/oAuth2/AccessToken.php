<?php

namespace Mautic\ApiBundle\Entity\oAuth2;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Model\AccessToken as BaseAccessToken;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AccessToken extends BaseAccessToken
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('oauth2_accesstokens')
            ->addIndex(['token'], 'oauth2_access_token_search');

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createManyToOne('client', 'Client')
            ->addJoinColumn('client_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('user', \Mautic\UserBundle\Entity\User::class)
            ->addJoinColumn('user_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('token', 'string')
            ->unique()
            ->build();

        $builder->createField('expiresAt', 'bigint')
            ->columnName('expires_at')
            ->nullable()
            ->build();

        $builder->createField('scope', 'string')
            ->nullable()
            ->build();
    }
}
