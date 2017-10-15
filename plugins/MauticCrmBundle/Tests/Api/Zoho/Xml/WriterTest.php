<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Api\Zoho\Xml;

use MauticPlugin\MauticCrmBundle\Api\Zoho\Xml\Writer;

class WriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that a single row is generated correctly with numerical index
     *
     * @covers  \Writer::row()
     * @covers  \Writer::add()
     * @covers  \Writer::write()
     */
    public function testXmlWithSingleRow()
    {
        $xml = new Writer('Leads');
        $xml->row('1')
            ->add('field', 'value')
            ->add('field2', 'value2')
            ->add('field3', 'value3');

        $expected = <<<'XML'
<Leads>
<row no="1">
<FL val="field"><![CDATA[value]]></FL>
<FL val="field2"><![CDATA[value2]]></FL>
<FL val="field3"><![CDATA[value3]]></FL>
</row>
</Leads>
XML;

        $this->assertEquals($expected, $xml->write());
    }

    /**
     * @testdox Test that multiple rows are generated correctly with specified indexes
     *
     * @covers  \Writer::row()
     * @covers  \Writer::add()
     * @covers  \Writer::write()
     */
    public function testXmlWithMultipleRows()
    {
        $xml = new Writer('Leads');
        $xml->row('abc')
            ->add('field', 'value')
            ->add('field2', 'value2')
            ->add('field3', 'value3');
        $xml->row('def')
            ->add('field', 'value')
            ->add('field2', 'value2')
            ->add('field3', 'value3');
        $xml->row('ghi')
            ->add('field', 'value')
            ->add('field2', 'value2')
            ->add('field3', 'value3');

        $expected = <<<'XML'
<Leads>
<row no="abc">
<FL val="field"><![CDATA[value]]></FL>
<FL val="field2"><![CDATA[value2]]></FL>
<FL val="field3"><![CDATA[value3]]></FL>
</row>
<row no="def">
<FL val="field"><![CDATA[value]]></FL>
<FL val="field2"><![CDATA[value2]]></FL>
<FL val="field3"><![CDATA[value3]]></FL>
</row>
<row no="ghi">
<FL val="field"><![CDATA[value]]></FL>
<FL val="field2"><![CDATA[value2]]></FL>
<FL val="field3"><![CDATA[value3]]></FL>
</row>
</Leads>
XML;

        $this->assertEquals($expected, $xml->write());
    }

    /**
     * @testdox Test that empty row is not included
     *
     * @covers  \Writer::row()
     * @covers  \Writer::add()
     * @covers  \Writer::write()
     */
    public function testXmlWithEmptyRow()
    {
        $xml = new Writer('Leads');
        $xml->row('abc')
            ->add('field', 'value')
            ->add('field2', 'value2')
            ->add('field3', 'value3');
        $xml->row('def');
        $xml->row('ghi')
            ->add('field', 'value')
            ->add('field2', 'value2')
            ->add('field3', 'value3');

        $expected = <<<'XML'
<Leads>
<row no="abc">
<FL val="field"><![CDATA[value]]></FL>
<FL val="field2"><![CDATA[value2]]></FL>
<FL val="field3"><![CDATA[value3]]></FL>
</row>
<row no="ghi">
<FL val="field"><![CDATA[value]]></FL>
<FL val="field2"><![CDATA[value2]]></FL>
<FL val="field3"><![CDATA[value3]]></FL>
</row>
</Leads>
XML;

        $this->assertEquals($expected, $xml->write());
    }
}
