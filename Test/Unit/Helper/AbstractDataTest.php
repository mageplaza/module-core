<?php
/**
 * Unit test for Mageplaza\Core\Helper\AbstractData
 *
 * @category    Mageplaza
 * @package     Mageplaza_Core
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AbstractDataTest
 * @package Mageplaza\Core\Test\Unit\Helper
 */
#[AllowMockObjectsWithoutExpectations]
class AbstractDataTest extends TestCase
{
    /**
     * @var AbstractData
     */
    private $abstractData;

    /**
     * @var MockObject|ObjectManagerInterface
     */
    private $objectManagerMock;

    /**
     * @var MockObject|StoreManagerInterface
     */
    private $storeManagerMock;

    /**
     * @var MockObject|Context
     */
    private $contextMock;

    /**
     * @var MockObject|ProductMetadataInterface
     */
    private $productMetadataMock;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->productMetadataMock = $this->createMock(ProductMetadataInterface::class);

        $this->abstractData = new AbstractData(
            $this->contextMock,
            $this->objectManagerMock,
            $this->storeManagerMock
        );
    }

    /**
     * Test is247Below() returns true when Magento version is 2.4.7 or below
     *
     * @dataProvider versionDataProvider
     * @param string $version
     * @param bool $expectedResult
     */
    #[DataProvider('versionDataProvider')]
    public function testIs247Below(string $version, bool $expectedResult): void
    {
        // Create a partial mock to override versionCompare method
        $abstractDataMock = $this->getMockBuilder(AbstractData::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->objectManagerMock,
                $this->storeManagerMock
            ])
            ->onlyMethods(['versionCompare'])
            ->getMock();

        $abstractDataMock->expects($this->once())
            ->method('versionCompare')
            ->with('2.4.8', '<')
            ->willReturn($expectedResult);

        $result = $abstractDataMock->is247Below();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for version testing
     * Tests versionCompare('2.4.8', '<') results
     *
     * @return array
     */
    public static function versionDataProvider(): array
    {
        return [
            'Version below 2.4.8 should return true' => ['2.4.5', true],
            'Version 2.4.7 should return true' => ['2.4.7', true],
            'Version 2.4.7-p1 should return true' => ['2.4.7-p1', true],
            'Version 2.4.7-p2 should return true' => ['2.4.7-p2', true],
            'Version 2.4.8 should return false' => ['2.4.8', false],
            'Version 2.4.8-p1 should return false' => ['2.4.8-p1', false],
            'Version 2.4.9 should return false' => ['2.4.9', false],
            'Version 2.4.9-p1 should return false (stripped to 2.4.9)' => ['2.4.9-p1', false]
        ];
    }


    /**
     * Test is247Below() method calls versionCompare correctly
     */
    public function testIs247BelowCallsVersionCompareCorrectly(): void
    {
        // Create a partial mock to verify versionCompare is called correctly
        $abstractDataMock = $this->getMockBuilder(AbstractData::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->objectManagerMock,
                $this->storeManagerMock
            ])
            ->onlyMethods(['versionCompare'])
            ->getMock();

        $abstractDataMock->expects($this->once())
            ->method('versionCompare')
            ->with('2.4.8', '<')
            ->willReturn(true);

        $result = $abstractDataMock->is247Below();
        $this->assertTrue($result);
    }

    /**
     * Test is247Below() returns false when version is above 2.4.7
     */
    public function testIs247BelowReturnsFalseWhenVersionAbove247(): void
    {
        $abstractDataMock = $this->getMockBuilder(AbstractData::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->objectManagerMock,
                $this->storeManagerMock
            ])
            ->onlyMethods(['versionCompare'])
            ->getMock();

        $abstractDataMock->expects($this->once())
            ->method('versionCompare')
            ->with('2.4.8', '<')
            ->willReturn(false);

        $result = $abstractDataMock->is247Below();
        $this->assertFalse($result);
    }

    /**
     * The colour value is interpolated into an inline <script>, so it must never be able
     * to close the tag or break out of the string literal.
     *
     * @dataProvider colorPickerXssDataProvider
     * @param string $value
     */
    #[DataProvider('colorPickerXssDataProvider')]
    public function testGetHtmlJqColorPickerEscapesValue(string $value): void
    {
        $html = AbstractData::getHtmlJqColorPicker('field_id', $value);

        // Only the wrapper opens and closes the script element.
        $this->assertSame(1, substr_count($html, '<script'));
        $this->assertSame(1, substr_count(strtolower($html), '</script>'));
        $this->assertStringNotContainsString($value, $html);
    }

    /**
     * @return array
     */
    public static function colorPickerXssDataProvider(): array
    {
        return [
            'closing script tag' => ['#fff</script><script>alert(1)</script>'],
            'breaking out of the string' => ['#fff";alert(1);var a="'],
            'single quote break out' => ["#fff';alert(1);var a='"],
            'newline injection' => ["#fff\";alert(1)\n//"],
            'html entities' => ['&lt;script&gt;alert(1)&lt;/script&gt;'],
            'backslash' => ['#fff\\"'],
        ];
    }

    /**
     * A plain colour must still reach the JavaScript unchanged.
     */
    public function testGetHtmlJqColorPickerKeepsValidColor(): void
    {
        $html = AbstractData::getHtmlJqColorPicker('field_id', '#ff0000');

        $this->assertStringContainsString('"#ff0000"', $html);
        $this->assertStringContainsString('$("#field_id")', $html);
    }

    /**
     * Invalid UTF-8 makes json_encode() return false; emitting nothing would produce a
     * JavaScript syntax error and kill the whole colour picker.
     */
    public function testGetHtmlJqColorPickerHandlesInvalidUtf8(): void
    {
        $html = AbstractData::getHtmlJqColorPicker('field_id', "\xB1\x31");

        $this->assertStringContainsString('el.css("backgroundColor", "")', $html);
        $this->assertStringContainsString('color: ""', $html);
    }

    /**
     * Null must behave like an empty colour rather than emitting a bare null.
     */
    public function testGetHtmlJqColorPickerHandlesNull(): void
    {
        $html = AbstractData::getHtmlJqColorPicker('field_id', null);

        $this->assertStringContainsString('color: ""', $html);
    }

    /**
     * @param string[] $codes theme codes from the rendered theme up to its root parent
     */
    private function buildThemeChain(array $codes): ?ThemeInterface
    {
        $parent = null;
        foreach (array_reverse($codes) as $code) {
            $theme = $this->createStub(ThemeInterface::class);
            $theme->method('getCode')->willReturn($code);
            $theme->method('getParentTheme')->willReturn($parent);
            $parent = $theme;
        }

        return $parent;
    }

    /**
     * @return array<string, array{0: string[], 1: bool}>
     */
    public static function hyvaThemeChainProvider(): array
    {
        return [
            'hyva default'                   => [['Hyva/default'], true],
            'child of hyva'                  => [['Acme/child', 'Hyva/default'], true],
            'grandchild of hyva'             => [['Acme/grandchild', 'Acme/child', 'Hyva/default'], true],
            'child of hyva csp'              => [['Acme/csp-child', 'Hyva/default-csp', 'Hyva/default'], true],
            'child of hyva reset'            => [['Acme/reset-child', 'Hyva/reset'], true],
            'luma'                           => [['Magento/luma', 'Magento/blank'], false],
            'hyva in name but luma chain'    => [['Acme/hyva-migration', 'Magento/luma'], false],
            'child of hyva-named luma theme' => [['Acme/hyva-child', 'Acme/hyva-migration', 'Magento/luma'], false],
            'empty theme code'               => [[''], false],
        ];
    }

    /**
     * @param string[] $codes
     */
    #[DataProvider('hyvaThemeChainProvider')]
    public function testCheckHyvaThemeWalksThemeChain(array $codes, bool $expected): void
    {
        $design = $this->createStub(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($this->buildThemeChain($codes));
        $this->objectManagerMock->method('get')->with(DesignInterface::class)->willReturn($design);

        $this->assertSame($expected, $this->abstractData->checkHyvaTheme());
    }

    public function testCheckHyvaThemeFallsBackToConfiguredThemeChain(): void
    {
        $provider = $this->createStub(ThemeProviderInterface::class);
        $provider->method('getThemeById')->with(7)->willReturn(
            $this->buildThemeChain(['Acme/grandchild', 'Acme/child', 'Hyva/default'])
        );
        $this->objectManagerMock->method('get')->willReturnCallback(
            static function (string $class) use ($provider) {
                if ($class === DesignInterface::class) {
                    throw new \RuntimeException('no design theme');
                }

                return $provider;
            }
        );
        $store = $this->createStub(Store::class);
        $store->method('getConfig')->with('design/theme/theme_id')->willReturn('7');
        $this->storeManagerMock->method('getStore')->willReturn($store);

        $this->assertTrue($this->abstractData->checkHyvaTheme());
    }

    public function testCheckHyvaThemeReturnsFalseWhenNoThemeCanBeResolved(): void
    {
        $this->objectManagerMock->method('get')->willThrowException(new \RuntimeException('no theme'));
        $this->storeManagerMock->method('getStore')->willThrowException(new \RuntimeException('no store'));

        $this->assertFalse($this->abstractData->checkHyvaTheme());
    }
}
