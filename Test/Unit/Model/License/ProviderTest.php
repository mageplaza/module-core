<?php
namespace Mageplaza\Core\Test\Unit\Model\License;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\Module\PackageInfoFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Model\License\Client;
use Mageplaza\Core\Model\License\InstalledModules;
use Mageplaza\Core\Model\License\Provider;
use Mageplaza\Core\Model\License\ResponseFactory;
use Mageplaza\Core\Model\License\Storage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProviderTest extends TestCase
{
    private function provider($storage, $client, $locks, $installed = null)
    {
        $installed = $installed ?: $this->createMock(InstalledModules::class);
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getEdition')->willReturn('Community');
        $metadata->method('getVersion')->willReturn('2.4.7');
        $package = $this->createMock(PackageInfo::class);
        $package->method('getVersion')->willReturn('1.6.0');
        $packages = $this->createMock(PackageInfoFactory::class);
        $packages->method('create')->willReturn($package);
        $provider = $this->getMockBuilder(Provider::class)->setConstructorArgs([
            $installed, $storage, $client, $this->createMock(ResponseFactory::class), $metadata,
            $this->createMock(StoreManagerInterface::class), $this->createMock(ResolverInterface::class),
            $packages, $locks
        ])->onlyMethods(['getDomain'])->getMock();
        $provider->method('getDomain')->willReturn('https://local.example.test/');
        return $provider;
    }

    /** @dataProvider cachedRefreshProvider */
    #[DataProvider('cachedRefreshProvider')]
    public function testCooldownAndConcurrentRefreshUseExistingData($lockAvailable)
    {
        $storage = $this->createMock(Storage::class);
        $storage->expects($lockAvailable ? $this->once() : $this->never())->method('canRefresh')->willReturn(false);
        $storage->expects($this->never())->method('saveData');
        $storage->expects($this->never())->method('saveError');
        $record = ['data' => ['is_update' => true], 'checked_at' => 123, 'attempted_at' => 123];
        $storage->method('getRecord')->willReturn($record);
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('fetch');
        $locks = $this->createMock(LockManagerInterface::class);
        $locks->method('lock')->willReturn($lockAvailable);
        $locks->expects($lockAvailable ? $this->once() : $this->never())->method('unlock');
        $provider = $this->provider($storage, $client, $locks);
        $this->assertFalse($provider->refresh());
        $this->assertSame($record, $provider->getRecord());
    }

    public static function cachedRefreshProvider()
    {
        return [[true], [false]];
    }

    /** @dataProvider fetchProvider */
    #[DataProvider('fetchProvider')]
    public function testExpiredCooldownFetchesAndKeepsRealFailuresVisible($response)
    {
        $storage = $this->createMock(Storage::class);
        $storage->method('canRefresh')->willReturn(true);
        $storage->expects($response === null ? $this->never() : $this->once())->method('saveData');
        $storage->expects($response === null ? $this->once() : $this->never())->method('saveError')->with('connection_error');
        $installed = $this->createMock(InstalledModules::class);
        $installed->method('getPayloadModules')->willReturn(['mageplaza/module-qa' => '1.0.0']);
        $installed->method('getPayloadKeys')->willReturn([]);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('fetch')->willReturn($response);
        $locks = $this->createMock(LockManagerInterface::class);
        $locks->method('lock')->willReturn(true);
        $locks->expects($this->once())->method('unlock');
        if ($response === null) {
            $this->expectException(LocalizedException::class);
        }
        $result = $this->provider($storage, $client, $locks, $installed)->refresh();
        if ($response !== null) {
            $this->assertTrue($result);
        }
    }

    public static function fetchProvider()
    {
        return [[['is_update' => false, 'modules' => []]], [null]];
    }
}
