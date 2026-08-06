<?php
/**
 * Unit test for Mageplaza\Core\Plugin\AllowSanitizedSvg
 *
 * @category    Mageplaza
 * @package     Mageplaza_Core
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Test\Unit\Plugin;

use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Mageplaza\Core\Model\SvgUploadContext;
use Mageplaza\Core\Plugin\AllowSanitizedSvg;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class AllowSanitizedSvgTest
 * @package Mageplaza\Core\Test\Unit\Plugin
 */
class AllowSanitizedSvgTest extends TestCase
{
    /**
     * @var SvgUploadContext
     */
    private $context;

    /**
     * @var AllowSanitizedSvg
     */
    private $plugin;

    /**
     * @var MockObject|NotProtectedExtension
     */
    private $subjectMock;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->context = new SvgUploadContext();
        $this->plugin = new AllowSanitizedSvg($this->context);
        $this->subjectMock = $this->createMock(NotProtectedExtension::class);
    }

    /**
     * svg is let through only while Mageplaza is the one saving the upload.
     */
    public function testSvgIsAllowedInsideMageplazaUpload(): void
    {
        $this->context->enter();

        $this->assertTrue($this->plugin->afterIsValid($this->subjectMock, false, 'svg'));
    }

    /**
     * The case that keeps every other uploader in the installation protected.
     */
    public function testSvgStaysProtectedForEveryoneElse(): void
    {
        $this->assertFalse($this->plugin->afterIsValid($this->subjectMock, false, 'svg'));
    }

    /**
     * Extensions other than svg keep whatever Magento decided, even inside a Mageplaza
     * upload. svgz is gzipped, so the sanitizer cannot read it.
     *
     * @dataProvider otherExtensionDataProvider
     * @param string $extension
     */
    #[DataProvider('otherExtensionDataProvider')]
    public function testOtherExtensionsAreUntouched(string $extension): void
    {
        $this->context->enter();

        $this->assertFalse($this->plugin->afterIsValid($this->subjectMock, false, $extension));
    }

    /**
     * @return array
     */
    public static function otherExtensionDataProvider(): array
    {
        return [
            'svgz' => ['svgz'],
            'php' => ['php'],
            'phtml' => ['phtml'],
            'html' => ['html'],
            'xml' => ['xml'],
            'htaccess' => ['htaccess'],
        ];
    }

    /**
     * Case and padding must not be a way past the extension comparison.
     *
     * @dataProvider svgSpellingDataProvider
     * @param string $extension
     */
    #[DataProvider('svgSpellingDataProvider')]
    public function testSvgIsRecognisedRegardlessOfSpelling(string $extension): void
    {
        $this->context->enter();

        $this->assertTrue($this->plugin->afterIsValid($this->subjectMock, false, $extension));
    }

    /**
     * @return array
     */
    public static function svgSpellingDataProvider(): array
    {
        return [
            'lowercase' => ['svg'],
            'uppercase' => ['SVG'],
            'mixed case' => ['SvG'],
            'padded' => ['  svg  '],
        ];
    }

    /**
     * An extension Magento already allows must be returned untouched.
     */
    public function testAllowedExtensionIsReturnedUnchanged(): void
    {
        $this->context->enter();

        $this->assertTrue($this->plugin->afterIsValid($this->subjectMock, true, 'png'));
    }

    /**
     * Leaving the context must close the window again.
     */
    public function testLeavingTheContextRevokesTheAllowance(): void
    {
        $this->context->enter();
        $this->context->leave();

        $this->assertFalse($this->plugin->afterIsValid($this->subjectMock, false, 'svg'));
    }

    /**
     * A nested upload finishing must not close the window for the outer one.
     */
    public function testNestedUploadsDoNotRevokeEachOther(): void
    {
        $this->context->enter();
        $this->context->enter();
        $this->context->leave();

        $this->assertTrue($this->plugin->afterIsValid($this->subjectMock, false, 'svg'));
    }
}
