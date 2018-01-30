<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Api\Zoho;

use MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper;

class MapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $availableFields = [
        'Leads' => [
                'Company' => [
                        'type'     => 'string',
                        'label'    => 'Company',
                        'dv'       => 'Company',
                        'required' => true,
                    ],
                'FirstName' => [
                        'type'     => 'string',
                        'label'    => 'First Name',
                        'dv'       => 'First Name',
                        'required' => false,
                    ],
                'LastName' => [
                        'type'     => 'string',
                        'label'    => 'Last Name',
                        'dv'       => 'Last Name',
                        'required' => true,
                    ],
                'Email' => [
                        'type'     => 'string',
                        'label'    => 'Email',
                        'dv'       => 'Email',
                        'required' => false,
                    ],
            ],
    ];

    /**
     * @var array
     */
    protected $mappedFields = [
        'Company'   => 'company',
        'Email'     => 'email',
        'Country'   => 'country',
        'FirstName' => 'firstname',
        'LastName'  => 'lastname',
    ];

    /**
     * @var array
     */
    protected $contacts = [
        [
            'firstname'             => 'FirstName1',
            'lastname'              => 'LastName1',
            'email'                 => 'zoho1@email.com',
            'integration_entity'    => 'Leads',
            'integration_entity_id' => 'abc',
            'internal_entity'       => 'lead',
            'internal_entity_id'    => 1,
        ],
        [
            'firstname'             => 'FirstName2',
            'lastname'              => 'LastName2',
            'email'                 => 'zoho2@email.com',
            'integration_entity'    => 'Leads',
            'integration_entity_id' => 'def',
            'internal_entity'       => 'lead',
            'internal_entity_id'    => 2,
        ],
        [
            'firstname'             => 'FirstName3',
            'lastname'              => 'LastName3',
            'email'                 => 'zoho3@email.com',
            'integration_entity'    => 'Leads',
            'integration_entity_id' => 'ghi',
            'internal_entity'       => 'lead',
            'internal_entity_id'    => 3,
        ],
    ];

    /**
     * @testdox Test that xml is generated according to the mapping
     *
     * @covers  \MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper::map()
     * @covers  \MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper::getXml()
     */
    public function testXmlIsGeneratedBasedOnMapping()
    {
        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        foreach ($this->contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id']);
        }

        $xml = <<<'XML'
<Leads>
<row no="1">
<FL val="Email"><![CDATA[zoho1@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName1]]></FL>
<FL val="Last Name"><![CDATA[LastName1]]></FL>
</row>
<row no="2">
<FL val="Email"><![CDATA[zoho2@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName2]]></FL>
<FL val="Last Name"><![CDATA[LastName2]]></FL>
</row>
<row no="3">
<FL val="Email"><![CDATA[zoho3@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName3]]></FL>
<FL val="Last Name"><![CDATA[LastName3]]></FL>
</row>
</Leads>
XML;
        $this->assertEquals($xml, $mapper->getXml());
    }

    /**
     * @testdox Test that contacts do not inherit previous contact information
     *
     * @covers  \MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper::map()
     * @covers  \MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper::getXml()
     */
    public function testContactDoesNotInheritPrevioudContactData()
    {
        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        $contacts                 = $this->contacts;
        $contacts[1]['firstname'] = null;

        foreach ($contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id']);
        }

        $xml = <<<'XML'
<Leads>
<row no="1">
<FL val="Email"><![CDATA[zoho1@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName1]]></FL>
<FL val="Last Name"><![CDATA[LastName1]]></FL>
</row>
<row no="2">
<FL val="Email"><![CDATA[zoho2@email.com]]></FL>
<FL val="Last Name"><![CDATA[LastName2]]></FL>
</row>
<row no="3">
<FL val="Email"><![CDATA[zoho3@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName3]]></FL>
<FL val="Last Name"><![CDATA[LastName3]]></FL>
</row>
</Leads>
XML;

        $this->assertEquals($xml, $mapper->getXml());
    }

    /**
     * @testdox Test that xml is generated according to the mapping
     *
     * @covers  \MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper::map()
     * @covers  \MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper::getXml()
     */
    public function testXmlIsGeneratedBasedOnMappingWithId()
    {
        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        foreach ($this->contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id'], $contact['integration_entity_id']);
        }

        $xml = <<<'XML'
<Leads>
<row no="1">
<FL val="Id"><![CDATA[abc]]></FL>
<FL val="Email"><![CDATA[zoho1@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName1]]></FL>
<FL val="Last Name"><![CDATA[LastName1]]></FL>
</row>
<row no="2">
<FL val="Id"><![CDATA[def]]></FL>
<FL val="Email"><![CDATA[zoho2@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName2]]></FL>
<FL val="Last Name"><![CDATA[LastName2]]></FL>
</row>
<row no="3">
<FL val="Id"><![CDATA[ghi]]></FL>
<FL val="Email"><![CDATA[zoho3@email.com]]></FL>
<FL val="First Name"><![CDATA[FirstName3]]></FL>
<FL val="Last Name"><![CDATA[LastName3]]></FL>
</row>
</Leads>
XML;
        $this->assertEquals($xml, $mapper->getXml());
    }
}
