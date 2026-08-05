<?php
/**
 * Unit test for Mageplaza\Core\Helper\Media
 *
 * @category    Mageplaza
 * @package     Mageplaza_Core
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Test\Unit\Helper;

use Exception;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\Media;
use Mageplaza\Core\Model\SvgUploadContext;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Class MediaTest
 * @package Mageplaza\Core\Test\Unit\Helper
 */
class MediaTest extends TestCase
{
    /**
     * @var Media
     */
    private $media;

    /**
     * In-memory media directory: relative path => file content.
     *
     * @var array
     */
    private $files = [];

    /**
     * @var ReflectionMethod
     */
    private $sanitizeSvg;

    /**
     * @var MockObject|UploaderFactory
     */
    private $uploaderFactoryMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var SvgUploadContext
     */
    private $svgUploadContext;

    /**
     * Makes the in-memory writeFile() fail, standing in for a filesystem error.
     *
     * @var bool
     */
    private $writeShouldFail = false;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $directoryMock = $this->createMock(WriteInterface::class);
        $directoryMock->method('isFile')
            ->willReturnCallback(function ($path) {
                return isset($this->files[$path]);
            });
        $directoryMock->method('readFile')
            ->willReturnCallback(function ($path) {
                return $this->files[$path];
            });
        $directoryMock->method('writeFile')
            ->willReturnCallback(function ($path, $content) {
                if ($this->writeShouldFail) {
                    throw new Exception('Filesystem is read only');
                }
                $this->files[$path] = $content;

                return strlen($content);
            });
        $directoryMock->method('delete')
            ->willReturnCallback(function ($path) {
                unset($this->files[$path]);

                return true;
            });

        $filesystemMock = $this->createMock(Filesystem::class);
        $filesystemMock->method('getDirectoryWrite')->willReturn($directoryMock);

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getLogger')->willReturn($this->loggerMock);

        $this->uploaderFactoryMock = $this->createMock(UploaderFactory::class);
        $this->svgUploadContext = new SvgUploadContext();

        $this->media = new Media(
            $contextMock,
            $this->createMock(ObjectManagerInterface::class),
            $this->createMock(StoreManagerInterface::class),
            $filesystemMock,
            $this->uploaderFactoryMock,
            $this->createMock(AdapterFactory::class),
            $this->svgUploadContext
        );

        $this->sanitizeSvg = new ReflectionMethod($this->media, 'sanitizeSvg');
    }

    /**
     * Run a payload through sanitizeSvg() and return the rewritten file.
     *
     * @param string $svg
     *
     * @return string
     * @throws Exception
     */
    private function sanitize(string $svg): string
    {
        $this->files['test.svg'] = $svg;
        $this->sanitizeSvg->invoke($this->media, 'test.svg');

        return $this->files['test.svg'];
    }

    /**
     * Attack payloads must not survive sanitization.
     *
     * @dataProvider maliciousSvgDataProvider
     * @param string $svg
     * @param string $mustNotContain
     * @throws Exception
     */
    #[DataProvider('maliciousSvgDataProvider')]
    public function testMaliciousMarkupIsStripped(string $svg, string $mustNotContain): void
    {
        $this->assertStringNotContainsStringIgnoringCase(
            $mustNotContain,
            $this->sanitize($svg),
            'Dangerous markup survived sanitization'
        );
    }

    /**
     * @return array
     */
    public static function maliciousSvgDataProvider(): array
    {
        $ns = 'xmlns="http://www.w3.org/2000/svg"';
        $xl = 'xmlns:xlink="http://www.w3.org/1999/xlink"';

        return [
            'script element' => ["<svg $ns><script>alert(1)</script><rect/></svg>", '<script'],
            'script uppercase' => ["<svg $ns><SCRIPT>alert(1)</SCRIPT><rect/></svg>", 'script'],
            'script in CDATA' => ["<svg $ns><script><![CDATA[alert(1)]]></script></svg>", 'script'],
            'script nested in defs' => ["<svg $ns><defs><script>alert(1)</script></defs></svg>", 'script'],
            'script nested in switch' => ["<svg $ns><switch><script>alert(1)</script></switch></svg>", 'script'],
            'onload attribute' => ["<svg $ns onload=\"alert(1)\"><rect/></svg>", 'onload'],
            'onload uppercase' => ["<svg $ns ONLOAD=\"alert(1)\"><rect/></svg>", 'onload'],
            'onclick attribute' => ["<svg $ns><rect onclick=\"alert(1)\"/></svg>", 'onclick'],
            'foreignObject' => ["<svg $ns><foreignObject><b/></foreignObject></svg>", 'foreignObject'],
            'handler element' => ["<svg $ns><handler>alert(1)</handler></svg>", 'handler'],
            'href javascript' => ["<svg $ns><a href=\"javascript:alert(1)\"/></svg>", 'javascript'],
            // The regex-based sanitizer this replaced was bypassed by exactly these three.
            'href entity decimal' => ["<svg $ns><a href=\"&#106;avascript:alert(1)\"/></svg>", 'avascript'],
            'href entity hex' => ["<svg $ns><a href=\"&#x6a;avascript:alert(1)\"/></svg>", 'avascript'],
            'href entity padded' => ["<svg $ns><a href=\"&#0000106;avascript:alert(1)\"/></svg>", 'avascript'],
            'href tab in scheme' => ["<svg $ns><a href=\"java&#9;script:alert(1)\"/></svg>", 'script'],
            'href uppercase scheme' => ["<svg $ns><a href=\"JaVaScRiPt:alert(1)\"/></svg>", 'javascript'],
            'href leading spaces' => ["<svg $ns><a href=\"   javascript:alert(1)\"/></svg>", 'javascript'],
            'xlink:href javascript' => ["<svg $ns $xl><a xlink:href=\"javascript:alert(1)\"/></svg>", 'javascript'],
            'SMIL set' => ["<svg $ns><set attributeName=\"onload\" to=\"alert(1)\"/></svg>", 'set'],
            'SMIL animate' => ["<svg $ns><a><animate attributeName=\"href\"/></a></svg>", 'animate'],
            'SMIL animateTransform' => ["<svg $ns><animateTransform to=\"alert(1)\"/></svg>", 'animate'],
            'use with data URI' => ["<svg $ns><use href=\"data:image/svg+xml;base64,eA==\"/></svg>", 'data:'],
            'use with external file' => ["<svg $ns><use href=\"https://evil.tld/x.svg#a\"/></svg>", 'evil.tld'],
            'image with svg data URI' => ["<svg $ns><image href=\"data:image/svg+xml;base64,eA==\"/></svg>", 'data:'],
            'style expression' => ["<svg $ns><rect style=\"width:expression(alert(1))\"/></svg>", 'expression'],
            'style url javascript' => ["<svg $ns><rect style=\"fill:url(javascript:alert(1))\"/></svg>", 'javascript'],
            'style -moz-binding' => ["<svg $ns><rect style=\"-moz-binding:url(http://e.tld/x)\"/></svg>", 'binding'],
            'style @import' => ["<svg $ns><rect style=\"@import url(http://e.tld/x.css)\"/></svg>", 'import'],
            'style element' => ["<svg $ns><style>*{x:url(javascript:alert(1))}</style></svg>", 'javascript'],
            'comment mXSS' => ["<svg $ns><!--<script>alert(1)</script>--><rect/></svg>", 'script'],
            'nested svg with script' => ["<svg $ns><svg><script>alert(1)</script></svg></svg>", 'script'],
        ];
    }

    /**
     * Payloads that cannot be made safe must delete the file and abort the upload.
     *
     * @dataProvider rejectedSvgDataProvider
     * @param string $svg
     */
    #[DataProvider('rejectedSvgDataProvider')]
    public function testUnsafeSvgIsRejected(string $svg): void
    {
        $this->expectException(Exception::class);

        try {
            $this->sanitize($svg);
        } finally {
            $this->assertArrayNotHasKey('test.svg', $this->files, 'Rejected file was left on disk');
        }
    }

    /**
     * @return array
     */
    public static function rejectedSvgDataProvider(): array
    {
        $ns = 'xmlns="http://www.w3.org/2000/svg"';

        return [
            'XXE file read' => [
                '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
                . "<svg $ns><text>&xxe;</text></svg>"
            ],
            'billion laughs' => [
                '<!DOCTYPE lolz [<!ENTITY lol "lol">'
                . '<!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">]>'
                . "<svg $ns><text>&lol2;</text></svg>"
            ],
            'XXE parameter entity' => [
                '<!DOCTYPE svg [<!ENTITY % p SYSTEM "http://evil.tld/e.dtd">%p;]>'
                . "<svg $ns><rect/></svg>"
            ],
            'not well formed' => ["<svg $ns><rect></svg>"],
            'root is not svg' => ['<html xmlns="http://www.w3.org/1999/xhtml"><body/></html>'],
        ];
    }

    /**
     * Legitimate artwork must come out intact, otherwise the sanitizer is unusable.
     *
     * @dataProvider legitimateSvgDataProvider
     * @param string $svg
     * @param string $mustContain
     * @throws Exception
     */
    #[DataProvider('legitimateSvgDataProvider')]
    public function testLegitimateMarkupIsPreserved(string $svg, string $mustContain): void
    {
        $this->assertStringContainsString($mustContain, $this->sanitize($svg));
    }

    /**
     * @return array
     */
    public static function legitimateSvgDataProvider(): array
    {
        $ns = 'xmlns="http://www.w3.org/2000/svg"';

        return [
            'shape with presentation attributes' => [
                "<svg $ns><rect width=\"10\" height=\"10\" fill=\"red\"/></svg>",
                'fill="red"'
            ],
            'path data' => ["<svg $ns><path d=\"M0 0 L10 10\"/></svg>", 'd="M0 0 L10 10"'],
            'gradient definition' => [
                "<svg $ns><defs><linearGradient id=\"g\"><stop stop-color=\"#fff\"/>"
                . '</linearGradient></defs></svg>',
                'linearGradient'
            ],
            // Regression: an earlier revision rejected every url(), breaking gradients.
            'style referencing a local gradient' => [
                "<svg $ns><rect style=\"fill:url(#g)\"/></svg>",
                'url(#g)'
            ],
            'use with local fragment' => ["<svg $ns><use href=\"#icon\"/></svg>", 'href="#icon"'],
            'image with relative path' => [
                "<svg $ns><image href=\"logo.png\"/></svg>",
                'href="logo.png"'
            ],
            'image with png data URI' => [
                "<svg $ns><image href=\"data:image/png;base64,iVBORw0KGgo=\"/></svg>",
                'data:image/png;base64'
            ],
            'transform and viewBox' => [
                "<svg $ns viewBox=\"0 0 10 10\"><g transform=\"scale(2)\"><rect/></g></svg>",
                'transform="scale(2)"'
            ],
        ];
    }

    /**
     * A missing file is a no-op, not a fatal error.
     *
     * @throws Exception
     */
    public function testMissingFileIsIgnored(): void
    {
        $this->sanitizeSvg->invoke($this->media, 'does-not-exist.svg');

        $this->assertSame([], $this->files);
    }

    /**
     * Make uploaderFactory->create() return an uploader that saves $savedFile.
     *
     * @param array|null $savedFile
     *
     * @return void
     */
    private function stubUploader(?array $savedFile): void
    {
        $uploaderMock = $this->createMock(Uploader::class);
        $uploaderMock->method('save')->willReturn($savedFile);
        $this->uploaderFactoryMock->method('create')->willReturn($uploaderMock);
    }

    /**
     * Make uploaderFactory->create() fail the way Magento's uploader does.
     *
     * @param \Throwable $error
     *
     * @return void
     */
    private function stubUploaderFailure(\Throwable $error): void
    {
        $this->uploaderFactoryMock->method('create')->willThrowException($error);
    }

    /**
     * Raster uploads must keep working untouched by the SVG handling.
     *
     * @dataProvider rasterUploadDataProvider
     * @param string $uploadedFile
     * @param string $expected
     */
    #[DataProvider('rasterUploadDataProvider')]
    public function testRasterUploadIsStored(string $uploadedFile, string $expected): void
    {
        $this->stubUploader(['file' => $uploadedFile]);
        $this->loggerMock->expects($this->never())->method('critical');

        $data = ['image' => ['value' => 'old.png']];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertSame($expected, $data['image']);
    }

    /**
     * @return array
     */
    public static function rasterUploadDataProvider(): array
    {
        return [
            'png' => ['/p/i/pic.png', 'p/i/pic.png'],
            'jpg' => ['/p/i/pic.jpg', 'p/i/pic.jpg'],
            'jpeg' => ['/p/i/pic.jpeg', 'p/i/pic.jpeg'],
            'gif' => ['/p/i/pic.gif', 'p/i/pic.gif'],
            'windows separators' => ['\\p\\i\\pic.png', 'p/i/pic.png'],
            // Must not be mistaken for an SVG by a loose extension check.
            'svg only in the middle of the name' => ['/l/o/logo.svg.png', 'l/o/logo.svg.png'],
        ];
    }

    /**
     * A raster upload must never be routed through the SVG sanitizer, which would
     * reject it as malformed XML.
     */
    public function testRasterUploadIsNotSanitized(): void
    {
        $this->files['mageplaza/blog/post/p/i/pic.png'] = 'not xml at all';
        $this->stubUploader(['file' => '/p/i/pic.png']);

        $data = [];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertSame('not xml at all', $this->files['mageplaza/blog/post/p/i/pic.png']);
        $this->assertSame('p/i/pic.png', $data['image']);
    }

    /**
     * An uploaded SVG must be rewritten in place before the path is stored.
     */
    public function testSvgUploadIsSanitized(): void
    {
        $path = 'mageplaza/blog/post/i/c/icon.svg';
        $this->files[$path] = '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<script>alert(1)</script><rect width="10"/></svg>';
        $this->stubUploader(['file' => '/i/c/icon.svg']);

        $data = [];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertStringNotContainsString('script', $this->files[$path]);
        $this->assertStringContainsString('rect', $this->files[$path]);
        $this->assertSame('i/c/icon.svg', $data['image']);
    }

    /**
     * Uppercase extensions reach the sanitizer too.
     */
    public function testUppercaseSvgExtensionIsSanitized(): void
    {
        $path = 'mageplaza/blog/post/i/c/icon.SVG';
        $this->files[$path] = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>';
        $this->stubUploader(['file' => '/i/c/icon.SVG']);

        $data = [];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertStringNotContainsString('onload', $this->files[$path]);
    }

    /**
     * A rejected SVG must be deleted, keep the previous image and be logged.
     */
    public function testRejectedSvgFallsBackAndIsLogged(): void
    {
        $path = 'mageplaza/blog/post/i/c/icon.svg';
        $this->files[$path] = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            . '<svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>';
        $this->stubUploader(['file' => '/i/c/icon.svg']);
        $this->loggerMock->expects($this->once())->method('critical');

        $data = ['image' => ['value' => 'old.png']];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertArrayNotHasKey($path, $this->files, 'Rejected SVG was left in the media directory');
        $this->assertSame('old.png', $data['image']);
    }

    /**
     * Saving a form without picking a new image is the common case and must stay silent.
     * Logging it would fill the log on every save across every module using this helper.
     */
    public function testNoFileSubmittedIsNotLogged(): void
    {
        $this->stubUploaderFailure(new \DomainException('$_FILES array is empty'));
        $this->loggerMock->expects($this->never())->method('critical');

        $data = ['image' => ['value' => 'old.png']];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertSame('old.png', $data['image']);
    }

    /**
     * A real upload failure must still be logged.
     */
    public function testUploadFailureIsLogged(): void
    {
        $this->stubUploaderFailure(new Exception('Disk full'));
        $this->loggerMock->expects($this->once())->method('critical')->with('Disk full');

        $data = ['image' => ['value' => 'old.png']];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertSame('old.png', $data['image']);
    }

    /**
     * With no previous value to fall back to, the field is emptied rather than left as an array.
     */
    public function testFailedUploadWithoutPreviousValueEmptiesField(): void
    {
        $this->stubUploaderFailure(new \DomainException('$_FILES array is empty'));

        $data = ['image' => []];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertSame('', $data['image']);
    }

    /**
     * The delete checkbox must remove the old file and clear the field.
     */
    public function testDeleteRemovesOldImage(): void
    {
        $this->files['mageplaza/blog/post/old.png'] = 'binary';

        $data = ['image' => ['delete' => 1]];
        $this->media->uploadImage($data, 'image', 'blog/post', 'old.png');

        $this->assertArrayNotHasKey('mageplaza/blog/post/old.png', $this->files);
        $this->assertSame('', $data['image']);
    }

    /**
     * svg must be offered to the uploader; Magento's own protected extension list is
     * lifted for this call by the plugin.
     */
    public function testSvgIsOfferedToTheUploader(): void
    {
        $offered = null;
        $uploaderMock = $this->createMock(Uploader::class);
        $uploaderMock->method('setAllowedExtensions')
            ->willReturnCallback(function ($extensions) use (&$offered, $uploaderMock) {
                $offered = $extensions;

                return $uploaderMock;
            });
        $uploaderMock->method('save')->willReturn(['file' => '/p/i/pic.png']);
        $this->uploaderFactoryMock->method('create')->willReturn($uploaderMock);

        $data = [];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertContains('svg', $offered);
        $this->assertContains('png', $offered);
    }

    /**
     * An unexpected failure while rewriting must still leave nothing behind: the upload
     * directory is public and the dispersion path is derived from the file name.
     */
    public function testUnexpectedSanitiseFailureStillDeletesTheFile(): void
    {
        $this->writeShouldFail = true;
        $this->files['test.svg'] = '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';

        $this->expectException(Exception::class);

        try {
            $this->sanitizeSvg->invoke($this->media, 'test.svg');
        } finally {
            $this->assertArrayNotHasKey('test.svg', $this->files, 'Unrewritten SVG survived');
        }
    }

    /**
     * The relaxation of Magento's protected extension rule must not outlive the save call,
     * otherwise an unrelated uploader later in the same request would inherit it.
     */
    public function testSvgUploadContextIsLeftInactiveAfterSave(): void
    {
        $this->stubUploader(['file' => '/p/i/pic.png']);
        $this->assertFalse($this->svgUploadContext->isActive(), 'Context active before upload');

        $data = [];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertFalse($this->svgUploadContext->isActive());
    }

    /**
     * The same holds when the upload throws.
     */
    public function testSvgUploadContextIsLeftInactiveAfterFailure(): void
    {
        $this->stubUploaderFailure(new Exception('Disk full'));

        $data = [];
        $this->media->uploadImage($data, 'image', 'blog/post');

        $this->assertFalse($this->svgUploadContext->isActive());
    }

    /**
     * Replacing an image must delete the one it replaces.
     */
    public function testUploadReplacesOldImage(): void
    {
        $this->files['mageplaza/blog/post/old.png'] = 'binary';
        $this->stubUploader(['file' => '/n/e/new.png']);

        $data = [];
        $this->media->uploadImage($data, 'image', 'blog/post', 'old.png');

        $this->assertArrayNotHasKey('mageplaza/blog/post/old.png', $this->files);
        $this->assertSame('n/e/new.png', $data['image']);
    }
}
