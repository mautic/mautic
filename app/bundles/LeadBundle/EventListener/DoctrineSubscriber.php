<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Mautic\LeadBundle\EventListener;


use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Factory\MauticFactory;

class DoctrineSubscriber implements \Doctrine\Common\EventSubscriber
{
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            ToolEvents::postGenerateSchema
        );
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable(MAUTIC_TABLE_PREFIX . 'lead_fields')) {
            return;
        }

        $table = $schema->getTable( MAUTIC_TABLE_PREFIX . 'leads');

        try {
            //get a list of fields
            $fields = $this->factory->getModel('lead.field')->getRepository()->getFieldAliases();

            foreach ($fields as $f) {
                $table->addColumn($f['alias'], 'text', array('notnull' => false));
            }
        } catch (\Exception $e) {
            //table doesn't exist or something bad happened so oh well
            error_log($e->getMessage());
        }
    }
}