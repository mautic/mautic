<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\InputHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(InputHelper::class)]
final class InputHelperTest extends TestCase
{
    #[TestDox('The html returns correct values')]
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
            $outlookXML                             => $outlookXML,
            $html5Doctype                           => $html5Doctype,
            $html5DoctypeWithContent                => $html5DoctypeWithContent,
            $html5DoctypeWithUnicodeContent         => $html5DoctypeWithUnicodeContent,
            $xhtml1Doctype                          => $xhtml1Doctype,
            $cdata                                  => $cdata,
            $script                                 => $script,
            $unicode                                => $unicode,
            '<applet>content</applet>'              => 'content',
            '<p>👍</p>'                             => '<p>👍</p>',
        ];

        foreach ($samples as $sample => $expected) {
            $actual = InputHelper::html($sample);
            $this->assertEquals($expected, $actual);
        }
    }

    #[TestDox('The email returns value without double period')]
    public function testEmailFilterRemovesDoublePeriods(): void
    {
        $clean = InputHelper::email('john..doe@email.com');

        $this->assertSame('john..doe@email.com', $clean);
    }

    #[TestDox('The email returns value without surrounding white spaces')]
    public function testEmailFilterRemovesWhitespace(): void
    {
        $clean = InputHelper::email('    john.doe@email.com  ');

        $this->assertSame('john.doe@email.com', $clean);
    }

    #[TestDox('The array is cleaned')]
    public function testCleanArrayWithEmptyValue(): void
    {
        $this->assertSame([], InputHelper::cleanArray(null));
    }

    #[TestDox('The string is converted to an array')]
    public function testCleanArrayWithStringValue(): void
    {
        $this->assertSame(['kuk'], InputHelper::cleanArray('kuk'));
    }

    #[TestDox('Javascript is encoded')]
    public function testCleanArrayWithJS(): void
    {
        $this->assertSame(
            ['&#60;script&#62;console.log(&#34;log me&#34;);&#60;/script&#62;'],
            InputHelper::cleanArray(['<script>console.log("log me");</script>'])
        );
    }

    #[TestDox('Test that filename handles some UTF8 chars')]
    public function testFilename(): void
    {
        $this->assertSame(
            '29nidji__dsfjhro85t784_fff.r.txt',
            InputHelper::filename('29NIDJi  dsfjh(#*RO85T784šěí_áčýžěé+ěšéřářf/ff/./r.txt')
        );
    }

    #[TestDox('Test that filename handles some UTF8 chars')]
    public function testFilenameWithChangingDir(): void
    {
        $this->assertSame(
            '29nidji__dsfjhro85t784_fff..r',
            InputHelper::filename('../29NIDJi  dsfjh(#*RO85T784šěí_áčýžěé+ěšéřářf/ff/../r')
        );
    }

    #[TestDox('Test filename with extension')]
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
            ''            => '',
        ];
        foreach ($tests as $input=>$expected) {
            $this->assertEquals(InputHelper::transliterate($input), $expected);
        }
    }

    #[DataProvider('urlProvider')]
    public function testUrlSanitization(string $inputUrl, string $outputUrl, string $message, bool $ignoreFragment = false): void
    {
        $cleanedUrl = InputHelper::url($inputUrl, false, null, null, [], $ignoreFragment);

        $this->assertEquals($cleanedUrl, $outputUrl);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string, 3?: bool}>
     */
    public static function urlProvider(): iterable
    {
        yield [
            'https://www.mautic.org/somewhere/something?foo=bar#abc123',
            'https://www.mautic.org/somewhere/something?foo=bar#abc123',
            'A valid URL is reconstructed as expected.',
        ];

        yield [
            '<img src="hello.png" />',
            '&#60;imgsrc=&#34;hello.png&#34;/&#62;',
            'A non-URL is simply cleaned.',
        ];

        yield [
            'foo://www.mautic.org',
            'http://www.mautic.org',
            'A disallowed protocol is changed to the default (http).',
        ];

        // user and password are included
        yield [
            'http://user:password@www.mautic.org',
            'http://user:password@www.mautic.org',
            'User and password are included in the URL.',
        ];

        // PHP 7.3.26 changed behavior for this type of URL but in either case, the <img> tag is sanitized
        $sanitizedUrl = (\version_compare(PHP_VERSION, '7.3.26', '>=')) ?
            'http://&#60;img&#62;:&#60;img&#62;@www.mautic.org' :
            'http://:@www.mautic.org';
        yield [
            'http://<img>:<img>@www.mautic.org',
            $sanitizedUrl,
            'User and password have tags stripped.',
        ];

        yield [
            'http://<img/src="doesnotexist.jpg">',
            'http://&#60;img/src=&#34;doesnotexist.jpg&#34;&#62;',
            'Host is cleaned and tags are stripped.',
        ];

        yield [
            'http://www.mautic.org:8080/path',
            'http://www.mautic.org:8080/path',
            'Port is included in the URL.',
        ];

        yield [
            'http://www.mautic.org/abc<img/src="doesnotexist.jpg">123',
            'http://www.mautic.org/abc123',
            'Path has tags stripped.',
        ];

        yield [
            'http://www.mautic.org?<foo>=bar',
            'http://www.mautic.org?%3Cfoo%3E=bar',
            'Query keys are urlencoded.',
        ];

        yield [
            'http://www.mautic.org?%3Cfoo%3E=<bar>',
            'http://www.mautic.org?%3Cfoo%3E=%3Cbar%3E',
            'Query values are urlencoded.',
        ];

        yield [
            'http://www.mautic.org#<img/src="doesnotexist.jpg">',
            'http://www.mautic.org#',
            'Fragment is cleaned and tags are stripped.',
        ];

        yield [
            'http://www.mautic.org#%3Cimg%2Fsrc%3D%22doesnotexist.jpg%22%3E',
            'http://www.mautic.org#%3Cimg%2Fsrc%3D%22doesnotexist.jpg%22%3E',
            'Fragment is cleaned and tags are stripped.',
        ];

        yield [
            'http://www.mautic.org#abc<img/src="doesnotexist.jpg">123',
            'http://www.mautic.org#abc123',
            'Fragment is cleaned and tags are stripped.',
        ];

        yield [
            'http://www.mautic.org#abc123',
            'http://www.mautic.org',
            'Fragment is removed when ignoreFragment is true.',
            true,
        ];

        yield [
            'http://example.com/?q=this%20has%20spaces',
            'http://example.com/?q=this%20has%20spaces',
            '%20 Spaces are not encoded to +.',
        ];

        yield [
            'http://example.com/?q=this+has+spaces',
            'http://example.com/?q=this%20has%20spaces',
            '+ spaces are encoded to %20',
        ];

        yield [
            'http://example.com/?q=this+has+spaces&foo=~bar',
            'http://example.com/?q=this%20has%20spaces&foo=~bar',
            'The tilde character should not be encoded',
        ];
    }

    #[DataProvider('filenameProvider')]
    public function testFilenameSanitization(string $inputFilename, string $outputFilename): void
    {
        $cleanedUrl = InputHelper::transliterateFilename($inputFilename);

        $this->assertSame($cleanedUrl, $outputFilename);
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

    #[DataProvider('minifyHTMLProvider')]
    public function testMinifyHTML(string $html, string $expected): void
    {
        $this->assertSame($expected, InputHelper::minifyHTML($html));
    }

    /**
     * @return \Iterator<(int|string), array<string>>
     */
    public static function minifyHTMLProvider(): \Iterator
    {
        // Test with a simple HTML string with no whitespace
        yield ['<p>Hello World</p>', '<p>Hello World</p>'];
        // Test with an HTML string with multiple spaces between tags
        yield ['<p>    Hello World    </p>', '<p>Hello World</p>'];
        // Test with an HTML string with multiple newlines between tags
        yield ["<p>\n\nHello World\n\n</p>", '<p>Hello World</p>'];
        // Test with an HTML string with inline CSS
        yield ['<p style="color: red;">Hello World</p>', '<p style="color:red;">Hello World</p>'];
        // Test with an empty HTML string
        yield ['', ''];
        // Test with an HTML string with multiple attributes
        yield ['<p class="big" id="title">Hello World</p>', '<p class="big" id="title">Hello World</p>'];
        // Test with an HTML string with multiple same tag
        yield ['<p>Hello World</p><p>Hello World</p>', '<p>Hello World</p><p>Hello World</p>'];
        // Test with an HTML string with multiple same tag but with different attributes
        yield ['<p class="big">Hello World</p><p class="small">Hello World</p>', '<p class="big">Hello World</p><p class="small">Hello World</p>'];
        yield [file_get_contents(__DIR__.'/resource/email/email-no-minify.html'), file_get_contents(__DIR__.'/resource/email/email-minify.html')];
    }

    #[DataProvider('underscoreProvider')]
    public function testUndersore(mixed $provided, mixed $expected): void
    {
        $this->assertSame($expected, InputHelper::_($provided));
    }

    /**
     * @return \Iterator<(int|string), mixed>
     */
    public static function underscoreProvider(): \Iterator
    {
        yield ['hello', 'hello'];
        yield [null, null];
        yield [false, ''];
        yield [true, '1'];
        yield [0, '0'];
        yield [10, '10'];
        yield [[null], [null]];
        yield [[0], ['0']];
        yield [[false], ['']];
        yield [[true], ['1']];
        yield [[null, 'hello'], [null, 'hello']];
        yield [[null, 3], [null, '3']];
        yield [[[null]], [[null]]];
    }

    #[TestDox('Test that clean filter converts special characters to HTML entities')]
    public function testCleanConvertsSpecialCharacters(): void
    {
        $valueWithApostrophe = "administrator's";
        $cleanResult         = InputHelper::clean($valueWithApostrophe);
        $rawResult           = InputHelper::raw($valueWithApostrophe);

        $this->assertNotSame($valueWithApostrophe, $cleanResult);
        $this->assertStringContainsString('&#', (string) $cleanResult);

        $this->assertEquals($valueWithApostrophe, $rawResult);
    }

    #[TestDox('Test that raw filter preserves special characters')]
    public function testRawPreservesSpecialCharacters(): void
    {
        $testValues = [
            "administrator's",
            'manager&supervisor',
            '"quoted value"',
            '<tag>content</tag>',
        ];

        foreach ($testValues as $originalValue) {
            $rawResult = InputHelper::raw($originalValue);
            $this->assertEquals($originalValue, $rawResult, "Raw filter should preserve: {$originalValue}");
        }
    }
}
