<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Contacts extends \Codeception\Module
{
    public function FillInitialData()
    {
        $dbh = $this->getModule('Db')->dbh;

        $this->insertInDB('mautic_lead_lists', ['is_published'=> '1', 'created_by'=>'1',
        'created_by_user'                                 => 'Automated User', 'name'=>'Manually Added Segment', 'alias'=>'manually-added-segment',
        'filters'                                         => 'a:0:{}', 'is_global'=>'1', ]);

        $this->insertInDB('mautic_lead_lists', ['is_published'=> '1', 'created_by'=>'1',
            'created_by_user'                             => 'Automated User', 'name'=>'Import added Segments', 'alias'=>'import-added-segments',
            'filters'                                     => 'a:0:{}', 'is_global'=>'1', ]);

        $this->insertInDB('mautic_campaigns', ['is_published'=> '1', 'created_by'=>'1',
        'created_by_user'                                => 'Automated User', 'name'=>'Add points campaign', 'canvas_settings'=> 'a:2:{s:5:"nodes";a:2:{i:0;a:3:{s:2:"id";s:1:"1";s:9:"positionX";s:3:"199";s:9:"positionY";s:3:"155";}i:1;a:3:{s:2:"id";s:5:"lists";s:9:"positionX";s:3:"539";s:9:"positionY";s:2:"50";}}s:11:"connections";a:1:{i:0;a:3:{s:8:"sourceId";s:5:"lists";s:8:"targetId";s:1:"1";s:7:"anchors";a:2:{s:6:"source";s:10:"leadsource";s:6:"target";s:3:"top";}}}}', ]);

        $this->insertInDB('mautic_campaign_events', ['campaign_id'=> '1', 'name'=>'Adjust contact points',
        'type'                                                => 'lead.changepoints', 'event_type'=>'action', 'event_order'=>'0', 'properties'=>'a:15:{s:14:"canvasSettings";a:2:{s:8:"droppedX";s:3:"199";s:8:"droppedY";s:3:"155";}s:4:"name";s:0:"";s:11:"triggerMode";s:9:"immediate";s:11:"triggerDate";N;s:15:"triggerInterval";s:1:"1";s:19:"triggerIntervalUnit";s:1:"d";s:6:"anchor";s:10:"leadsource";s:10:"properties";a:1:{s:6:"points";s:2:"25";}s:4:"type";s:17:"lead.changepoints";s:9:"eventType";s:6:"action";s:15:"anchorEventType";s:6:"source";s:10:"campaignId";s:47:"mautic_01783931f236a33f9d31c8b52cb2d17a2b895bc6";s:6:"_token";s:43:"58EHjbbYf5uo6niRtPM2MmBcG9SrsqJj55xWm4ChavA";s:7:"buttons";a:1:{s:4:"save";s:0:"";}s:6:"points";d:25;}', 'trigger_interval'                                      => '1', 'trigger_interval_unit'=>'d', 'trigger_mode'=>'immediate', 'temp_id'=>'new2e8ce226a0d6b442bc88a79a5c987dbcac521ffc', ]);

        $this->insertInDB('mautic_campaign_leadlist_xref', ['campaign_id'=>'1', 'leadlist_id'=>'1']);
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
