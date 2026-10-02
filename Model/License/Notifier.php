<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Core
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Model\License;

use Magento\Framework\FlagManager;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mageplaza\Core\Helper\Validate;

class Notifier
{
    const SNAPSHOT_FLAG = 'mageplaza_core_license_notice_snapshot';
    const LICENSE_URL = 'https://dashboard.mageplaza.com/license/';

    private $flags;
    private $locks;
    private $notifier;
    private $timezone;
    private $validate;
    private $backendUrl;

    public function __construct(
        FlagManager $flags,
        LockManagerInterface $locks,
        NotifierInterface $notifier,
        TimezoneInterface $timezone,
        Validate $validate,
        UrlInterface $backendUrl
    ) {
        $this->flags = $flags;
        $this->locks = $locks;
        $this->notifier = $notifier;
        $this->timezone = $timezone;
        $this->validate = $validate;
        $this->backendUrl = $backendUrl;
    }

    public function notify(Response $response, array $installed)
    {
        if (!$this->validate->getConfigGeneral('notice_enable')) {
            return;
        }
        if (!$this->locks->lock('mageplaza_core_license_notice', 5)) {
            return;
        }
        try {
            $snapshot = $this->flags->getFlagData(self::SNAPSHOT_FLAG);
            $snapshot = is_array($snapshot) ? $snapshot : [];
            if (!$response->hasModuleData()) {
                $this->notifyLegacy($response, $snapshot);
            } else {
                $this->notifyUpdates($response, $installed, $snapshot);
                $this->notifyExpiry($response, $installed, $snapshot);
                $this->notifyBanner($response, $snapshot);
            }
            $this->flags->saveFlag(self::SNAPSHOT_FLAG, $snapshot);
        } finally {
            $this->locks->unlock('mageplaza_core_license_notice');
        }
    }

    private function notifyLegacy(Response $response, array &$snapshot)
    {
        $data = $response->toArray();
        $message = isset($data['message']) && is_string($data['message']) ? trim($data['message']) : '';
        if (!$this->validate->isEnabledNotificationUpdate() || empty($data['is_update']) || $message === '') {
            return;
        }
        $hash = hash('sha256', $message);
        if (($snapshot['legacy'] ?? '') !== $hash) {
            $this->notifier->addNotice('Mageplaza Notice', $this->escape($message), self::LICENSE_URL);
            $snapshot['legacy'] = $hash;
        }
    }

    private function notifyUpdates(Response $response, array $installed, array &$snapshot)
    {
        if (!$this->validate->isEnabledNotificationUpdate()) {
            return;
        }
        $updates = [];
        $names = [];
        $lines = [];
        foreach ($installed as $package => $module) {
            $remote = $response->getModule($package);
            $current = Response::validVersion($module['version'] ?? null);
            $latest = $remote['latest_version'];
            if (!$remote['can_update'] || !$current || !$latest || version_compare($latest, $current, '<=')) {
                continue;
            }
            $updates[$package] = $latest;
            $label = $remote['label'] ?: (isset($module['label']) ? (string) $module['label'] : $package);
            $names[] = $label;
            $lines[] = $this->escape($label . ' ' . $current . ' → ' . $latest);
        }
        ksort($updates);
        if ($updates && ($snapshot['updates'] ?? []) !== $updates) {
            $title = count($updates) === 1
                ? $this->escape($names[0] . ' has a new version')
                : count($updates) . ' Mageplaza extensions have updates';
            if (count($updates) === 1) {
                $description = $lines[0];
            } else {
                $more = count($names) - 3;
                $list = $more > 0
                    ? implode(', ', array_slice($names, 0, 3)) . ' and ' . $more . ' more'
                    : implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names);
                $description = $this->escape($list . '. Open My Extensions to see the new versions.');
            }
            $this->notifier->addNotice($title, $description, $this->backendUrl->getUrl('mpcore/extensions/index'));
        }
        $snapshot['updates'] = $updates;
    }

    private function notifyExpiry(Response $response, array $installed, array &$snapshot)
    {
        $zone = new \DateTimeZone($this->timezone->getConfigTimezone());
        $today = new \DateTimeImmutable('today', $zone);
        $expirySnapshot = is_array($snapshot['expiry'] ?? null) ? $snapshot['expiry'] : [];
        $next = [];
        $url = $response->getAccountUrl('license_url') ?: self::LICENSE_URL;
        foreach ($installed as $package => $module) {
            $remote = $response->getModule($package);
            foreach (['support_expired_at' => 'Support', 'subscription_expired_at' => 'Subscription'] as $field => $label) {
                $date = $remote[$field];
                if (!$date) {
                    continue;
                }
                $expiry = new \DateTimeImmutable($date, $zone);
                $days = (int) $today->diff($expiry)->format('%r%a');
                if ($days > 30 || $days < -30) {
                    continue;
                }
                $tier = $days <= 0 ? 0 : ($days <= 7 ? 7 : 30);
                $old = $expirySnapshot[$package][$field] ?? null;
                $next[$package][$field] = ['date' => $date, 'tier' => $tier];
                if (is_array($old) && ($old['date'] ?? '') === $date && (int) ($old['tier'] ?? 999) <= $tier) {
                    continue;
                }
                $name = $remote['label'] ?: (isset($module['label']) ? (string) $module['label'] : $package);
                $title = $this->escape($name . ' ' . $label . ($tier === 0 ? ' expired' : ' expires soon'));
                $description = $this->escape($label . ' expires on ' . $date . ($tier === 0 ? ' (expired)' : ' (' . $days . ' days left)'));
                if ($tier <= 7) {
                    $this->notifier->addMajor($title, $description, $url);
                } else {
                    $this->notifier->addNotice($title, $description, $url);
                }
            }
        }
        $snapshot['expiry'] = $next;
    }

    private function notifyBanner(Response $response, array &$snapshot)
    {
        $categories = array_map('trim', explode(',', (string) $this->validate->getConfigGeneral('notice_type')));
        if (!in_array('marketing', $categories, true)) {
            return;
        }
        $banner = $response->getBanner();
        if (!$banner || empty($banner['notify']) || ($snapshot['banner'] ?? '') === $banner['id']) {
            return;
        }
        $title = mb_substr($this->escape($banner['notify']['title']), 0, 255, 'UTF-8');
        $description = $this->escape($banner['notify']['description']);
        $this->notifier->addNotice($title, $description, $banner['url']);
        $snapshot['banner'] = $banner['id'];
    }

    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
