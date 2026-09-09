<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

class MixedChainEntity
{
    protected $id;

    protected $email;

    private $customMjml;

    private ?string $draftCustomMjml = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('bundle_grapesjsbuilder')
            ->setCustomRepositoryClass(GrapesJsBuilderRepository::class)
            ->addNamedField('customMjml', Types::TEXT, 'custom_mjml', true)
            ->addNamedField('draftCustomMjml', Types::TEXT, 'draft_custom_mjml', true)
            ->addId();

        $builder->createManyToOne(
            'email',
            Email::class
        )->addJoinColumn('email_id', 'id', true, false, 'CASCADE')->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

#[ORM\Entity(repositoryClass: GrapesJsBuilderRepository::class)]
#[ORM\Table(name: 'bundle_grapesjsbuilder')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class MixedChainEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Email::class)]
    #[ORM\JoinColumn(name: 'email_id', onDelete: 'CASCADE')]
    protected $email;

    #[ORM\Column(name: 'custom_mjml', type: Types::TEXT, nullable: true)]
    private $customMjml;

    #[ORM\Column(name: 'draft_custom_mjml', type: Types::TEXT, nullable: true)]
    private ?string $draftCustomMjml = null;
}
