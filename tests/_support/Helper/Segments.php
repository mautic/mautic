<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Segments extends \Codeception\Module
{
    public function FillInitialData()
    {
        $dbh = $this->getModule('Db')->dbh;

        $this->insertInDB('mautic_leads', ['owner_id'=> '1', 'date_identified'=>  '2018-02-08 06:47:17', 'date_added'=>  '2018-02-08 06:47:17',
            'is_published'                           => '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'points'=>  '0',    'internal'=>  'a:0:{}',    'social_cache'=>  'a:0:{}',    'preferred_profile_image'=>  'gravatar',    'firstname'=>  'John',    'lastname'=>  'Sparrow',    'email'=>  'equal@mailinator.com',    'address1'=>  null,    'city'=>  'Massachussetts', ]);
        $this->insertInDB('mautic_leads', ['owner_id'=> '1', 'date_identified'=>  '2018-02-08 06:47:17', 'date_added'=>  '2018-02-08 06:47:17',
            'is_published'                           => '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'points'=>  '0',    'internal'=>  'a:0:{}',    'social_cache'=>  'a:0:{}',    'preferred_profile_image'=>  'gravatar',    'firstname'=>  'David',    'lastname'=>  'Moore',    'email'=>  'dmoore@mailinator.com',    'address1'=>  '3rd Avenue',    'city'=>  'Florima', ]);
        $this->insertInDB('mautic_leads', ['owner_id'=> '1', 'date_identified'=>  '2018-02-08 06:47:17', 'date_added'=>  '2018-02-08 06:47:17',
            'is_published'                           => '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'points'=>  '0',    'internal'=>  'a:0:{}',    'social_cache'=>  'a:0:{}',    'preferred_profile_image'=>  'gravatar',    'firstname'=>  'Remy',    'lastname'=>  'Dima',    'email'=>  null,    'address1'=>  'main street',    'city'=>  null, ]);
        $this->insertInDB('mautic_leads', ['owner_id'=> '1', 'date_identified'=>  '2018-02-08 06:47:17', 'date_added'=>  '2018-02-08 06:47:17',
            'is_published'                           => '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'points'=>  '0',    'internal'=>  'a:0:{}',    'social_cache'=>  'a:0:{}',    'preferred_profile_image'=>  'gravatar',    'firstname'=>  null,    'lastname'=>  'Sputnik',    'email'=>  'sput@mailinator.com',    'address1'=>  null,    'city'=>  null, ]);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Equal Email Filter',    'alias'=>  'equal-email-filter',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:5:\"email\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:5:\"email\";s:6:\"filter\";s:20:\"equal@mailinator.com\";s:7:\"display\";N;s:8:\"operator\";s:1:\"=\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Equals Name Filter',    'alias'=>  'equals-name-filter',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:9:\"firstname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:4:\"John\";s:7:\"display\";N;s:8:\"operator\";s:1:\"=\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Name not Equals',    'alias'=>  'name-not-equals',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:9:\"firstname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:4:\"John\";s:7:\"display\";N;s:8:\"operator\";s:2:\"!=\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Empty First Name',    'alias'=>  'empty-first-name',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:9:\"firstname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";N;s:7:\"display\";N;s:8:\"operator\";s:5:\"empty\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Not Empty Email',    'alias'=>  'not-empty-email',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:5:\"email\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:5:\"email\";s:6:\"filter\";N;s:7:\"display\";N;s:8:\"operator\";s:6:\"!empty\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Like Address',    'alias'=>  'like-address',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"address1\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:6:\"avenue\";s:7:\"display\";N;s:8:\"operator\";s:4:\"like\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Not Like Address 1',    'alias'=>  'not-like-address-1',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"address1\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:4:\"main\";s:7:\"display\";N;s:8:\"operator\";s:5:\"!like\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Starts with City MA',    'alias'=>  'starts-with-city-ma',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:4:\"city\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:2:\"Ma\";s:7:\"display\";N;s:8:\"operator\";s:10:\"startsWith\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Ends with Last Name',    'alias'=>  'ends-with-last-name',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"lastname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:2:\"ma\";s:7:\"display\";N;s:8:\"operator\";s:8:\"endsWith\";}}',    'is_global'=>  '1']);
        $this->insertInDB('mautic_lead_lists', ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'name'=>  'Contains City',    'alias'=>  'contains-city',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:4:\"city\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:2:\"ma\";s:7:\"display\";N;s:8:\"operator\";s:8:\"contains\";}}',    'is_global'=>  '1']);
    }

    public function insertInDB($table, array $data)
    {
        $dbh = $this->getModule('Db')->dbh;

        $columns = [];
        $values  = [];
        foreach ($data as $column => $value) {
            $columns[] = "`{$column}`";
            $values[]  ="'{$value}'";
        }

        $query = sprintf('insert into %s (%s) values (%s);', $table, implode(', ', $columns), implode(', ', $values));
        $this->debugSection('Query', $query);
        $sth = $dbh->prepare($query);
        $sth->execute();
    }
}
