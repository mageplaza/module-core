<?php
namespace Mageplaza\Core\Test\Unit\Model\License;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Serialize\Serializer\Json;
use Mageplaza\Core\Model\License\Storage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StorageTest extends TestCase
{
    public function testCacheMissKeepsDurableDataAndFailureKeepsSuccessfulTimestamp()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('load')->willReturn(false);
        $flags = $this->createMock(FlagManager::class);
        $flags->method('getFlagData')->willReturn(['data' => ['modules' => []], 'checked_at' => 123]);
        $flags->expects($this->once())->method('saveFlag')->with(Storage::FLAG_DATA, $this->callback(function ($data) {
            return $data['checked_at'] === 123 && $data['data'] === ['modules' => []]
                && $data['error'] === 'connection_error' && isset($data['attempted_at']);
        }));
        $storage = new Storage($cache, $flags, new Json());
        $this->assertSame(['modules' => []], $storage->loadData());
        $storage->saveError('connection_error');
    }

    public function testRecentAttemptPreventsRefreshEvenWhenCacheIsStale()
    {
        $cache = $this->createMock(CacheInterface::class);
        $flags = $this->createMock(FlagManager::class);
        $flags->method('getFlagData')->willReturn(['attempted_at' => time()]);
        $storage = new Storage($cache, $flags, new Json());
        $this->assertFalse($storage->canRefresh());
    }
}
