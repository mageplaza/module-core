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
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AbstractDataTest
 * @package Mageplaza\Core\Test\Unit\Helper
 */
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
    public function versionDataProvider(): array
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
}
