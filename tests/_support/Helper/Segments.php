<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use OpenCloud\Common\Constants\Datetime;

class Segments extends \Codeception\Module
{
    public function FillInitialData()
    {
        $dbh = $this->getModule('Db')->dbh;

        //Contacts

        $commonMauticDataLeadArray = ['owner_id'=> '1', 'date_identified'=>  '2018-02-08 06:47:17', 'date_added'=>  '2018-02-08 06:47:17',
            'is_published'                      => '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'points'=>  '0',    'internal'=>  'a:0:{}',    'social_cache'=>  'a:0:{}',    'preferred_profile_image'=>  'gravatar', ];

        $allLeads=[
            ['firstname'=>'John',    'lastname'=>'Sparrow',    'email'=>'equal@mailinator.com',    'City'=>'Massachussetts',    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'David',    'lastname'=>'Moore',    'email'=>'dmoore@mailinator.com',    'City'=>'Florima',    'Country'=>null,    'address1'=>'3rd Avenue',    'attribution'=>0],
            ['firstname'=> 'Remy',    'lastname'=>'Dima',    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>'main street',    'attribution'=>0],
            ['firstname'=> null,    'lastname'=>'Sputnik',    'email'=>'sput@mailinator.com',    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Bruce',    'lastname'=>'Wayne',    'email'=>'imbatman@mailinator.com',    'City'=>null,    'Country'=>'Ireland',    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Not Batman',    'lastname'=>'Wayne',    'email'=>'notbatman@mailinator.com',    'City'=>null,    'Country'=>'Mexico',    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'YAB',    'lastname'=>'Wayne',    'email'=>null,    'City'=>null,    'Country'=>'Miramar',    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Maybe ',    'lastname'=>'Wayne',    'email'=>'maybebatman@mailinator.com',    'City'=>null,    'Country'=>'Miramar',    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Bruce',    'lastname'=>'Banner',    'email'=>'smash@mailinator.com',    'City'=>null,    'Country'=>null,    'address1'=>'Markets diagonal',    'attribution'=>0],
            ['firstname'=> 'Mark',    'lastname'=>'Thompson',    'email'=>'mt@mailinator.com',    'City'=>'Miami',    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Jon',    'lastname'=>'Minute',    'email'=>'jm@mailinator.com',    'City'=>null,    'Country'=>null,    'address1'=>'Markets information',    'attribution'=>0],
            ['firstname'=> 'Peter',    'lastname'=>'Parker',    'email'=>null,    'City'=>'Orlando',    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Ramy',    'lastname'=>'Magic',    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Superman',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Kal',    'lastname'=>'El',    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Scott',    'lastname'=>'Summers',    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Marcos',    'lastname'=>'Summers',    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Jean',    'lastname'=>'Gray',    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Angolan',    'lastname'=>'Citizen',    'email'=>null,    'City'=>null,    'Country'=>'Angola',    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Albania',    'lastname'=>'Citizen',    'email'=>null,    'City'=>null,    'Country'=>'Albania',    'address1'=>null,    'attribution'=>0],
            ['firstname'=> 'Greater than 100',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>'101'],
            ['firstname'=> 'Greater or equal 90',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>'90'],
            ['firstname'=> 'Less than 50',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>'45'],
            ['firstname'=> 'LessEqual40',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>'40'],
            ['firstname'=> '100 exactly',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>'100'],
            ['firstname'=> '50 exactly',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>'50'],
            ['firstname'=> 'Date Feb 25 3:01pm',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0,    'attribution_date'=>'2018-02-25 15:01'],
            ['firstname'=> 'Date Feb 25 2:59pm',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0,    'attribution_date'=>'2018-02-25 14:59'],
            ['firstname'=> 'Date Feb 25 3pm',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0,    'attribution_date'=>'2018-02-25 15:00'],
            ['firstname'=> 'Date Tomorrow',    'lastname'=>null,    'email'=>null,    'City'=>null,    'Country'=>null,    'address1'=>null,    'attribution'=>0,    'attribution_date'=>date('Y-m-d g:i', strtotime('+1 day 5 minutes'))],
        ];

        foreach ($allLeads as $lead) {
            $this->insertInDB('mautic_leads', array_merge($commonMauticDataLeadArray, $lead));
        }

        //Segments
        $commonMauticSegmentData = ['is_published'=>  '1',    'created_by'=>  '1',    'created_by_user'=>  'Automated User',    'checked_out_by'=>  '1',    'checked_out_by_user'=>  'Automated User',    'is_global'=>  '1'];

        $allSegments = [
            //Simple Segments
            ['name'=>  'Equal Email Filter',    'alias'=>  'equal-email-filter',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:5:\"email\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:5:\"email\";s:6:\"filter\";s:20:\"equal@mailinator.com\";s:7:\"display\";N;s:8:\"operator\";s:1:\"=\";}}'],
            ['name'=> 'Equals Name Filter',    'alias'=>  'equals-name-filter',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:9:\"firstname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:4:\"John\";s:7:\"display\";N;s:8:\"operator\";s:1:\"=\";}}'],
            ['name'=> 'Name not Equals',    'alias'=>  'name-not-equals',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:9:\"firstname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:4:\"John\";s:7:\"display\";N;s:8:\"operator\";s:2:\"!=\";}}'],
            ['name'=> 'Empty First Name',    'alias'=>  'empty-first-name',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:9:\"firstname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";N;s:7:\"display\";N;s:8:\"operator\";s:5:\"empty\";}}'],
            ['name'=> 'Not Empty Email',    'alias'=>  'not-empty-email',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:5:\"email\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:5:\"email\";s:6:\"filter\";N;s:7:\"display\";N;s:8:\"operator\";s:6:\"!empty\";}}'],
            ['name'=> 'Like Address',    'alias'=>  'like-address',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"address1\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:6:\"avenue\";s:7:\"display\";N;s:8:\"operator\";s:4:\"like\";}}'],
            ['name'=> 'Not Like Address 1',    'alias'=>  'not-like-address-1',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"address1\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:4:\"main\";s:7:\"display\";N;s:8:\"operator\";s:5:\"!like\";}}'],
            ['name'=> 'Starts with City MA',    'alias'=>  'starts-with-city-ma',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:4:\"city\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:2:\"Ma\";s:7:\"display\";N;s:8:\"operator\";s:10:\"startsWith\";}}'],
            ['name'=> 'Ends with Last Name',    'alias'=>  'ends-with-last-name',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"lastname\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:2:\"ma\";s:7:\"display\";N;s:8:\"operator\";s:8:\"endsWith\";}}'],
            ['name'=> 'Contains City',    'alias'=>  'contains-city',    'filters'=>  'a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:4:\"city\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:4:\"text\";s:6:\"filter\";s:2:\"ma\";s:7:\"display\";N;s:8:\"operator\";s:8:\"contains\";}}'],
            ['name'=> 'Including Country', 'alias'=>  'including-country',    'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:7:"country";s:6:"object";s:4:"lead";s:4:"type";s:7:"country";s:6:"filter";a:2:{i:0;s:7:"Albania";i:1;s:6:"Angola";}s:7:"display";N;s:8:"operator";s:2:"in";}}'],
            ['name'=> 'Excluding Country', 'alias'=>  'excluding-country',    'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:7:"country";s:6:"object";s:4:"lead";s:4:"type";s:7:"country";s:6:"filter";a:1:{i:0;s:6:"Angola";}s:7:"display";N;s:8:"operator";s:3:"!in";}}'],
            ['name'=> 'Greater100', 'alias'=>  'greater100',    'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:11:"attribution";s:6:"object";s:4:"lead";s:4:"type";s:6:"number";s:6:"filter";s:3:"100";s:7:"display";N;s:8:"operator";s:2:"gt";}}'],
            ['name'=> 'GreaterEqual90', 'alias'=>  'greaterequal90',    'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:11:"attribution";s:6:"object";s:4:"lead";s:4:"type";s:6:"number";s:6:"filter";s:2:"90";s:7:"display";N;s:8:"operator";s:3:"gte";}}'],
            ['name'=> 'Less50', 'alias'=>  'less50',    'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:11:"attribution";s:6:"object";s:4:"lead";s:4:"type";s:6:"number";s:6:"filter";s:2:"50";s:7:"display";N;s:8:"operator";s:2:"lt";}}'],
            ['name'=> 'LessEqual40', 'alias'=>  'lessequal40',    'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:11:"attribution";s:6:"object";s:4:"lead";s:4:"type";s:6:"number";s:6:"filter";s:2:"40";s:7:"display";N;s:8:"operator";s:3:"lte";}}'],
            ['name'=> 'Date Greater Than', 'alias'=>  'date-greater-than',   'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:16:"attribution_date";s:6:"object";s:4:"lead";s:4:"type";s:8:"datetime";s:6:"filter";s:16:"2018-02-25 15:00";s:7:"display";N;s:8:"operator";s:2:"gt";}}'],
            ['name'=> 'Date Greater Equal', 'alias'=>  'date-less-equal',   'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:16:"attribution_date";s:6:"object";s:4:"lead";s:4:"type";s:8:"datetime";s:6:"filter";s:16:"2018-02-25 15:00";s:7:"display";N;s:8:"operator";s:3:"gte";}}'],
            ['name'=> 'Date Less Than', 'alias'=>  'date-less-than',   'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:16:"attribution_date";s:6:"object";s:4:"lead";s:4:"type";s:8:"datetime";s:6:"filter";s:16:"2018-02-25 15:00";s:7:"display";N;s:8:"operator";s:2:"lt";}}'],
            ['name'=> 'Date Less Equal', 'alias'=>  'date-less-equal',   'filters'=>  'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:16:"attribution_date";s:6:"object";s:4:"lead";s:4:"type";s:8:"datetime";s:6:"filter";s:16:"2018-02-25 15:00";s:7:"display";N;s:8:"operator";s:3:"lte";}}'],
            ['name'=> 'Date Greater Than Tomorrow', 'alias'=>  'date-greater-than-tomorrow',   'filters'=>'a:1:{i:0;a:7:{s:4:"glue";s:3:"and";s:5:"field";s:16:"attribution_date";s:6:"object";s:4:"lead";s:4:"type";s:8:"datetime";s:6:"filter";s:8:"tomorrow";s:7:"display";N;s:8:"operator";s:2:"gt";}}'],
        ];

        foreach ($allSegments as $segment) {
            $this->insertInDB('mautic_lead_lists', array_merge($commonMauticSegmentData, $segment));
        }

        return $allLeads;
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
