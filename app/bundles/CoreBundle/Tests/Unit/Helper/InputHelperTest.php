<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\InputHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class InputHelperTest extends TestCase
{
    /**
     * @testdox The html returns correct values
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::html
     */
    public function testHtmlFilter(): void
    {
        $outlookXML = '<!--[if gte mso 9]><xml>
 <o:OfficeDocumentSettings>
  <o:AllowPNG/>
  <o:PixelsPerInch>96</o:PixelsPerInch>
 </o:OfficeDocumentSettings>
</xml><![endif]-->';
        $html5Doctype            = '<!DOCTYPE html>';
        $html5DoctypeWithContent = '<!DOCTYPE html>
        <html>
        </html>';
        $html5DoctypeWithUnicodeContent = '<!DOCTYPE html>
        <html>
        <body>
            <a href="https://m3.mautibox.com/3.x/media/images/testá.png">test with unicode</a>
        </body>
        </html>';
        $xhtml1Doctype = '<!DOCTYPE html PUBLIC
  "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        $cdata   = '<![CDATA[content]]>';
        $script  = '<script>for (let i = 0; i < 10; i += 1) {console.log(i);}</script>';
        $unicode = '<a href="https://m3.mautibox.com/3.x/media/images/testá.png">test with unicode</a>';

        $samples = [
            $outlookXML                     => $outlookXML,
            $html5Doctype                   => $html5Doctype,
            $html5DoctypeWithContent        => $html5DoctypeWithContent,
            $html5DoctypeWithUnicodeContent => $html5DoctypeWithUnicodeContent,
            $xhtml1Doctype                  => $xhtml1Doctype,
            $cdata                          => $cdata,
            $script                         => $script,
            $unicode                        => $unicode,
            '<applet>content</applet>'      => 'content',
            '<p>👍</p>'                      => '<p>👍</p>',
        ];

        foreach ($samples as $sample => $expected) {
            $actual = InputHelper::html($sample);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @testdox The email returns value without double period
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::email
     */
    public function testEmailFilterRemovesDoublePeriods(): void
    {
        $clean = InputHelper::email('john..doe@email.com');

        $this->assertEquals('john..doe@email.com', $clean);
    }

    /**
     * @testdox The email returns value without surrounding white spaces
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::email
     */
    public function testEmailFilterRemovesWhitespace(): void
    {
        $clean = InputHelper::email('    john.doe@email.com  ');

        $this->assertEquals('john.doe@email.com', $clean);
    }

    /**
     * @testdox The array is cleaned
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::cleanArray
     */
    public function testCleanArrayWithEmptyValue(): void
    {
        $this->assertEquals([], InputHelper::cleanArray(null));
    }

    /**
     * @testdox The string is converted to an array
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::cleanArray
     */
    public function testCleanArrayWithStringValue(): void
    {
        $this->assertEquals(['kuk'], InputHelper::cleanArray('kuk'));
    }

    /**
     * @testdox Javascript is encoded
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::cleanArray
     */
    public function testCleanArrayWithJS(): void
    {
        $this->assertEquals(
            ['&#60;script&#62;console.log(&#34;log me&#34;);&#60;/script&#62;'],
            InputHelper::cleanArray(['<script>console.log("log me");</script>'])
        );
    }

    /**
     * @testdox Test that filename handles some UTF8 chars
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::filename
     */
    public function testFilename(): void
    {
        $this->assertSame(
            '29nidji__dsfjhro85t784_fff.r.txt',
            InputHelper::filename('29NIDJi  dsfjh(#*RO85T784šěí_áčýžěé+ěšéřářf/ff/./r.txt')
        );
    }

    /**
     * @testdox Test that filename handles some UTF8 chars
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::filename
     */
    public function testFilenameWithChangingDir(): void
    {
        $this->assertSame(
            '29nidji__dsfjhro85t784_fff..r',
            InputHelper::filename('../29NIDJi  dsfjh(#*RO85T784šěí_áčýžěé+ěšéřářf/ff/../r')
        );
    }

    /**
     * @testdox Test filename with extension
     *
     * @covers \Mautic\CoreBundle\Helper\InputHelper::filename
     */
    public function testFilenameWithExtension(): void
    {
        $this->assertSame(
            '29nidji__dsfjhro85t784.txt',
            InputHelper::filename('29NIDJi  dsfjh(#*RO85T784šěíáčýžěé+ěšéřář', 'txt')
        );
    }

    public function testTransliterate(): void
    {
        $tests = [
            'custom test' => 'custom test',
            'čusťom test' => 'custom test',
            null          => '',
        ];
        foreach ($tests as $input=>$expected) {
            $this->assertEquals(InputHelper::transliterate($input), $expected);
        }
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUrlSanitization(string $inputUrl, string $outputUrl, bool $ignoreFragment = false): void
    {
        $cleanedUrl = InputHelper::url($inputUrl, false, null, null, [], $ignoreFragment);

        Assert::assertEquals($cleanedUrl, $outputUrl);
    }

    public static function urlProvider(): iterable
    {
        // valid URL is reconstructed as expected
        yield ['https://www.mautic.org/somewhere/something?foo=bar#abc123', 'https://www.mautic.org/somewhere/something?foo=bar#abc123'];

        // non URL is simply cleaned
        yield ['<img src="hello.png" />', '&#60;imgsrc=&#34;hello.png&#34;/&#62;'];

        // disallowed protocol changed to default
        yield ['foo://www.mautic.org', 'http://www.mautic.org'];

        // user and password are included
        yield ['http://user:password@www.mautic.org', 'http://user:password@www.mautic.org'];

        // user and password have tags stripped
        // PHP 7.3.26 changed behavior for this type of URL but in either case, the <img> tag is sanitized
        $sanitizedUrl = (\version_compare(PHP_VERSION, '7.3.26', '>=')) ?
            'http://&#60;img&#62;:&#60;img&#62;@www.mautic.org' :
            'http://:@www.mautic.org';
        yield ['http://<img>:<img>@www.mautic.org', $sanitizedUrl];

        // host is cleaned (should have the whole url go through ::clean() because it's not recognized as a valid host
        yield ['http://<img/src="doesnotexist.jpg">', 'http://&#60;img/src=&#34;doesnotexist.jpg&#34;&#62;'];

        // port is included
        yield ['http://www.mautic.org:8080/path', 'http://www.mautic.org:8080/path'];

        // path has tags stripped
        yield ['http://www.mautic.org/abc<img/src="doesnotexist.jpg">123', 'http://www.mautic.org/abc123'];

        // query keys are urlencoded
        yield ['http://www.mautic.org?<foo>=bar', 'http://www.mautic.org?%3Cfoo%3E=bar'];

        // query is urlencoded appropriately
        yield ['http://www.mautic.org?%3Cfoo%3E=<bar>', 'http://www.mautic.org?%3Cfoo%3E=%3Cbar%3E'];

        // fragment is included and cleaned
        yield ['http://www.mautic.org#<img/src="doesnotexist.jpg">', 'http://www.mautic.org#'];
        yield ['http://www.mautic.org#%3Cimg%2Fsrc%3D%22doesnotexist.jpg%22%3E', 'http://www.mautic.org#%3Cimg%2Fsrc%3D%22doesnotexist.jpg%22%3E'];
        yield ['http://www.mautic.org#abc<img/src="doesnotexist.jpg">123', 'http://www.mautic.org#abc123'];

        // fragment is not included
        yield ['http://www.mautic.org#abc123', 'http://www.mautic.org', true];
    }

    /**
     * @dataProvider filenameProvider
     */
    public function testFilenameSanitization(string $inputFilename, string $outputFilename): void
    {
        $cleanedUrl = InputHelper::transliterateFilename($inputFilename);

        Assert::assertEquals($cleanedUrl, $outputFilename);
    }

    /**
     * @return iterable<array<string>>
     */
    public static function filenameProvider(): iterable
    {
        yield [
            'dirname',
            'dirname',
        ];

        yield [
            'file.png',
            'file.png',
        ];

        yield [
            'dirname with space',
            'dirname-with-space',
        ];

        yield [
            'filename with space.png',
            'filename-with-space.png',
        ];

        yield [
            'directory with čšťĺé',
            'directory-with-cstle',
        ];

        yield [
            'filename with čšťĺé.png',
            'filename-with-cstle.png',
        ];
    }

    /**
     * @dataProvider minifyHTMLProvider
     */
    public function testMinifyHTML(string $html, string $expected): void
    {
        $this->assertEquals($expected, InputHelper::minifyHTML($html));
    }

    /**
     * @return array<array<string>>
     */
    public static function minifyHTMLProvider(): array
    {
        return [
            // Test with a simple HTML string with no whitespace
            ['<p>Hello World</p>', '<p>Hello World</p>'],
            // Test with an HTML string with multiple spaces between tags
            ['<p>    Hello World    </p>', '<p>Hello World</p>'],
            // Test with an HTML string with multiple newlines between tags
            ["<p>\n\nHello World\n\n</p>", '<p>Hello World</p>'],
            // Test with an HTML string with inline CSS
            ['<p style="color: red;">Hello World</p>', '<p style="color:red;">Hello World</p>'],
            // Test with an empty HTML string
            ['', ''],
            // Test with an HTML string with multiple attributes
            ['<p class="big" id="title">Hello World</p>', '<p class="big" id="title">Hello World</p>'],
            // Test with an HTML string with multiple same tag
            ['<p>Hello World</p><p>Hello World</p>', '<p>Hello World</p><p>Hello World</p>'],
            // Test with an HTML string with multiple same tag but with different attributes
            ['<p class="big">Hello World</p><p class="small">Hello World</p>', '<p class="big">Hello World</p><p class="small">Hello World</p>'],
            [file_get_contents(__DIR__.'/resource/email/email-no-minify.html'), file_get_contents(__DIR__.'/resource/email/email-minify.html')],
        ];
    }

    /**
     * @dataProvider underscoreProvider
     */
    public function testUndersore(mixed $provided, mixed $expected): void
    {
        $this->assertSame($expected, InputHelper::_($provided));
    }

    /**
     * @return mixed[]
     */
    public static function underscoreProvider(): array
    {
        return [
            ['hello', 'hello'],
            [null, null],
            [false, ''],
            [true, '1'],
            [0, '0'],
            [10, '10'],
            [[null], [null]],
            [[0], ['0']],
            [[false], ['']],
            [[true], ['1']],
            [[null, 'hello'], [null, 'hello']],
            [[null, 3], [null, '3']],
            [[[null]], [[null]]],
        ];
    }
}
