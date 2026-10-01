<?php
/** Mageplaza Core — local reads and explicit manual refresh for My Extensions. */
namespace Mageplaza\Core\Model\License;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Module\PackageInfoFactory;
use Magento\Store\Model\StoreManagerInterface;

class Provider
{
    /** @var InstalledModules */
    private $installed;
    /** @var Storage */
    private $storage;
    /** @var Client */
    private $client;
    /** @var ResponseFactory */
    private $responseFactory;
    /** @var ProductMetadataInterface */
    private $metadata;
    /** @var StoreManagerInterface */
    private $stores;
    /** @var ResolverInterface */
    private $locale;
    /** @var PackageInfoFactory */
    private $packageInfoFactory;
    /** @var LockManagerInterface */
    private $locks;

    public function __construct(
        InstalledModules $installed,
        Storage $storage,
        Client $client,
        ResponseFactory $responseFactory,
        ProductMetadataInterface $metadata,
        StoreManagerInterface $stores,
        ResolverInterface $locale,
        PackageInfoFactory $packageInfoFactory,
        LockManagerInterface $locks
    ) {
        $this->installed = $installed;
        $this->storage = $storage;
        $this->client = $client;
        $this->responseFactory = $responseFactory;
        $this->metadata = $metadata;
        $this->stores = $stores;
        $this->locale = $locale;
        $this->packageInfoFactory = $packageInfoFactory;
        $this->locks = $locks;
    }

    /** Local reads never call the remote API. @return Response */
    public function get()
    {
        return $this->responseFactory->create(['data' => $this->storage->loadData()]);
    }

    /** @return array */
    public function getRecord()
    {
        return $this->storage->getRecord();
    }

    /** @return int */
    public function getRefreshRetryAfter()
    {
        return $this->storage->getRefreshRetryAfter();
    }

    /** @return string */
    public function getDomain()
    {
        $store = $this->stores->getDefaultStoreView() ?: $this->stores->getStore();
        return $store->getBaseUrl();
    }

    /** @return string */
    public function getMagentoVersion()
    {
        return $this->metadata->getVersion();
    }

    /** @return bool True when fetched; false when the caller should use local data. */
    public function refresh($force = false)
    {
        $lockName = 'mageplaza_core_license_refresh';
        if (!$this->locks->lock($lockName, 0)) {
            return false;
        }
        try {
            if (!$force && !$this->storage->canRefresh()) {
                return false;
            }
            $modules = $this->installed->getPayloadModules();
            if (!$modules) {
                throw new LocalizedException(__('No Mageplaza extensions are available to check.'));
            }
            $data = $this->client->fetch([
                'modules' => $modules,
                'keys' => $this->installed->getPayloadKeys(),
                'domain' => $this->getDomain(),
                'magento_edition' => $this->metadata->getEdition(),
                'magento_version' => $this->metadata->getVersion(),
                'php_version' => PHP_VERSION,
                'core_version' => $this->packageInfoFactory->create()->getVersion('Mageplaza_Core'),
                'locale' => $this->locale->getLocale()
            ]);
            if ($data === null) {
                $this->storage->saveError('connection_error');
                throw new LocalizedException(__('Unable to check for updates. Please try again later.'));
            }
            $this->storage->saveData($data);
            return true;
        } finally {
            $this->locks->unlock($lockName);
        }
    }
}
