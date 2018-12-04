<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161123225456 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Fix a bug in dynamic content and segments if there are boolean fields

        // Check if there are even boolean fields to worry about
        $qb = $this->connection->createQueryBuilder();
        $qb->select('lf.alias, lf.properties')
           ->from($this->prefix.'lead_fields', 'lf')
           ->where(
               $qb->expr()->eq('lf.type', $qb->expr()->literal('boolean'))
           );
        $booleanFields = $qb->execute()->fetchAll();

        if (count($booleanFields)) {
            $fields = [];
            foreach ($booleanFields as $key => $field) {
                $fields[$field['alias']] = unserialize($field['properties']);
            }

            $this->fixEmails($qb, $fields);
            $this->fixSegments($qb, $fields);
        }
    }

    /**
     * @param $qb
     * @param $fields
     */
    protected function fixEmails(QueryBuilder $qb, $fields)
    {
        // Get a list of emails that do not have the default dynamic content
        $emails = $qb->resetQueryParts()
                     ->select('e.id, e.dynamic_content')
                     ->from($this->prefix.'emails', 'e')
                     ->where(
                         $qb->expr()->andX(
                             $qb->expr()->neq(
                                 'e.dynamic_content',
                                 $qb->expr()->literal(
                                     'a:1:{i:0;a:3:{s:9:"tokenName";N;s:7:"content";N;s:7:"filters";a:1:{i:0;a:2:{s:7:"content";N;s:7:"filters";a:1:{i:0;a:7:{s:4:"glue";N;s:5:"field";N;s:6:"object";N;s:4:"type";N;s:8:"operator";N;s:7:"display";N;s:6:"filter";N;}}}}}}'
                                 )
                             ),
                             $qb->expr()->neq(
                                 'e.dynamic_content',
                                 $qb->expr()->literal(
                                     'a:1:{i:0;a:3:{s:9:"tokenName";N;s:7:"content";N;s:7:"filters";a:1:{i:0;a:2:{s:7:"content";N;s:7:"filters";a:0:{}}}}}'
                                 )
                             )
                         )
                     )
                     ->execute()
                     ->fetchAll();

        // Start a transaction
        if (count($emails)) {
            foreach ($emails as $email) {
                $update         = false;
                $dynamicContent = unserialize($email['dynamic_content']);
                foreach ($dynamicContent as &$dc) {
                    foreach ($dc['filters'] as &$filter) {
                        foreach ($filter['filters'] as &$checkMe) {
                            $this->fixField($checkMe, $update, $fields);
                        }
                    }
                }

                if ($update) {
                    $this->fixRow($qb, 'emails', 'dynamic_content', $email['id'], $dynamicContent);
                }
            }
        }
    }

    /**
     * @param $qb
     * @param $fields
     */
    protected function fixSegments(QueryBuilder $qb, $fields)
    {
        // Now fix segment filters
        $segments = $qb->resetQueryParts()
                       ->select('s.id, s.filters')
                       ->from($this->prefix.'lead_lists', 's')
                       ->where(
                           $qb->expr()->neq(
                               's.filters',
                               $qb->expr()->literal('a:0:{}')
                           )
                       )
                        ->execute()
                        ->fetchAll();

        if (count($segments)) {
            foreach ($segments as $segment) {
                $update  = false;
                $filters = unserialize($segment['filters']);
                foreach ($filters as &$filter) {
                    $this->fixField($filter, $update, $fields);
                }

                if ($update) {
                    $this->fixRow($qb, 'lead_lists', 'filters', $segment['id'], $filters);
                }
            }
        }
    }

    /**
     * @param $qb
     * @param $table
     * @param $column
     * @param $value
     * @param $id
     */
    protected function fixRow(QueryBuilder $qb, $table, $column, $id, $value)
    {
        $qb->resetQueryParts()
           ->update($this->prefix.$table)
           ->set($column, $qb->expr()->literal(serialize($value)))
           ->where(
               $qb->expr()->eq('id', $id)
           )
           ->execute();
    }

    /**
     * @param $checkMe
     * @param $update
     * @param $fields
     */
    protected function fixField(&$checkMe, &$update, $fields)
    {
        if ($checkMe['field'] && array_key_exists($checkMe['field'], $fields)) {
            // Boolean field found so check to see if the label was used
            if ($fields[$checkMe['field']]['no'] === $checkMe['filter']) {
                $update            = true;
                $checkMe['filter'] = 0;
            } elseif ($fields[$checkMe['field']]['yes'] === $checkMe['filter']) {
                $update            = true;
                $checkMe['filter'] = 1;
            }
        }
    }
}
