<?php
/** Mageplaza Core — cached response with a durable Magento flag fallback. */
namespace Mageplaza\Core\Model\License;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Serialize\Serializer\Json;

class Storage
{
    const CACHE_KEY = 'mageplaza_core_license_data';
    const FLAG_DATA = 'mageplaza_core_license';
    const REFRESH_INTERVAL = 60;
    /** @var CacheInterface */
    private $cache;
    /** @var FlagManager */
    private $flags;
    /** @var Json */
    private $serializer;
    /** @var array|null */
    private $record;

    public function __construct(CacheInterface $cache, FlagManager $flags, Json $serializer)
    {
        $this->cache = $cache;
        $this->flags = $flags;
        $this->serializer = $serializer;
    }

    /** @return array */
    public function getRecord()
    {
        if ($this->record !== null) {
            return $this->record;
        }
        $cached = $this->cache->load(self::CACHE_KEY);
        try {
            $record = $cached ? $this->serializer->unserialize($cached) : null;
        } catch (\InvalidArgumentException $exception) {
            $record = null;
        }
        if (!is_array($record)) {
            $record = $this->flags->getFlagData(self::FLAG_DATA);
        }
        $this->record = is_array($record) ? $record : [];
        return $this->record;
    }

    /** @return array */
    public function loadData()
    {
        $record = $this->getRecord();
        return isset($record['data']) && is_array($record['data']) ? $record['data'] : [];
    }

    /** @return bool */
    public function canRefresh()
    {
        return $this->getRefreshRetryAfter() === 0;
    }

    /** Seconds until another API attempt is allowed; survives cache flushes. @return int */
    public function getRefreshRetryAfter()
    {
        $record = $this->flags->getFlagData(self::FLAG_DATA);
        $attemptedAt = isset($record['attempted_at']) ? (int) $record['attempted_at']
            : (isset($record['checked_at']) ? (int) $record['checked_at'] : 0);
        return min(self::REFRESH_INTERVAL, max(0, self::REFRESH_INTERVAL - (time() - $attemptedAt)));
    }

    /** @return void */
    public function saveData(array $data)
    {
        $this->save(['data' => $data, 'checked_at' => time(), 'attempted_at' => time(), 'error' => null]);
    }

    /** @return void */
    public function saveError($code)
    {
        $record = $this->getRecord();
        $record['attempted_at'] = time();
        $record['error'] = $code;
        $this->save($record);
    }

    /** @return void */
    private function save(array $record)
    {
        $this->flags->saveFlag(self::FLAG_DATA, $record);
        $this->record = $record;
        $this->cache->save($this->serializer->serialize($record), self::CACHE_KEY, ['MAGEPLAZA_CORE_LICENSE'], 43200);
    }
}
