<?php
/** Mageplaza Core — server-side click relay; no keys or customer data leave this class. */
namespace Mageplaza\Core\Model\License;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Module\PackageInfoFactory;

class EventReporter
{
    const INSTALLATION_FLAG = 'mageplaza_core_installation_id';

    private $client;
    private $config;
    private $flags;
    private $locks;
    private $packageInfo;
    private $metadata;

    public function __construct(
        Client $client,
        ScopeConfigInterface $config,
        FlagManager $flags,
        LockManagerInterface $locks,
        PackageInfoFactory $packageInfo,
        ProductMetadataInterface $metadata
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->flags = $flags;
        $this->locks = $locks;
        $this->packageInfo = $packageInfo;
        $this->metadata = $metadata;
    }

    public function isEnabled()
    {
        return $this->config->isSetFlag('mageplaza/general/event_tracking_enabled');
    }

    public function send($eventName, $eventId, $package = null, $bannerId = null)
    {
        if (!$this->isEnabled() || !in_array($eventName,
            ['banner_click', 'upgrade_click', 'release_notes_click', 'request_feature_click'], true)
            || !$this->isUuid($eventId)) {
            return false;
        }
        if ($package !== null && (!is_string($package)
            || !preg_match('#^mageplaza/[a-z0-9][a-z0-9._-]*$#D', $package) || strlen($package) > 128)) {
            return false;
        }
        if ($bannerId !== null && (!is_string($bannerId) || trim($bannerId) === ''
            || strlen($bannerId) > 128 || preg_match('/[\x00-\x1f]/', $bannerId))) {
            return false;
        }
        if (($eventName === 'banner_click' && $bannerId === null)
            || (in_array($eventName, ['upgrade_click', 'release_notes_click'], true) && $package === null)) {
            return false;
        }
        $installationId = $this->installationId();
        if ($installationId === '') {
            return false;
        }
        $payload = [
            'schema_version' => 1,
            'event_id' => $eventId,
            'installation_id' => $installationId,
            'event_name' => $eventName,
            'occurred_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'source' => 'my_extensions',
            'core_version' => (string) $this->packageInfo->create()->getVersion('Mageplaza_Core'),
            'magento_version' => (string) $this->metadata->getVersion(),
            'magento_edition' => (string) $this->metadata->getEdition(),
        ];
        if ($package !== null) {
            $payload['package'] = $package;
        }
        if ($bannerId !== null) {
            $payload['banner_id'] = $bannerId;
        }
        return $this->client->sendEvent($payload);
    }

    private function installationId()
    {
        if (!$this->locks->lock('mageplaza_core_installation_id', 5)) {
            return '';
        }
        try {
            $value = $this->flags->getFlagData(self::INSTALLATION_FLAG);
            if ($this->isUuid($value)) {
                return $value;
            }
            $bytes = random_bytes(16);
            $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
            $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
            $hex = bin2hex($bytes);
            $value = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
                . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
            $this->flags->saveFlag(self::INSTALLATION_FLAG, $value);
            return $value;
        } finally {
            $this->locks->unlock('mageplaza_core_installation_id');
        }
    }

    private function isUuid($value)
    {
        return is_string($value) && (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/Di', $value);
    }
}
