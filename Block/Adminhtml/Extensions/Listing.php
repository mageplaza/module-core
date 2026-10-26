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

namespace Mageplaza\Core\Block\Adminhtml\Extensions;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Mageplaza\Core\Model\License\InstalledModules;
use Mageplaza\Core\Model\License\Provider;
use Mageplaza\Core\Model\License\Response;

class Listing extends Template
{
    /** @var InstalledModules */
    private $installed;
    /** @var Provider */
    private $provider;
    /** @var array|null */
    private $rows;

    public function __construct(Context $context, InstalledModules $installed, Provider $provider, array $data = [])
    {
        $this->installed = $installed;
        $this->provider = $provider;
        parent::__construct($context, $data);
    }

    /** @return int */
    public function getRefreshRetryAfter()
    {
        return $this->provider->getRefreshRetryAfter();
    }

    /** @return array */
    public function getRows()
    {
        if ($this->rows !== null) {
            return $this->rows;
        }
        $response = $this->provider->get();
        $rows = [];
        foreach ($this->installed->getList() as $package => $module) {
            $remote = $response->getModule($package);
            $row = [
                'package' => $package,
                'label' => $remote['label'] ?: $module['label'],
                'version' => $module['version'],
                'latest_version' => $remote['latest_version'],
                'release_url' => $remote['release_url'],
                'has_update' => $remote['latest_version'] && Response::validVersion($module['version'])
                    && version_compare($remote['latest_version'], $module['version'], '>'),
                'is_free' => $remote['is_free'],
                'edition' => $remote['edition'],
                'download_url' => $remote['download_url'] ?: ($remote['is_free'] ? $this->getLicenseUrl() : null),
                'renew_actions' => [],
                'can_download' => $remote['is_free'] || $remote['can_update'],
                'upgrade_url' => $remote['upgrade_url'],
                'upgrade_label' => $remote['upgrade_label'] ?: __('Upgrade edition'),
                'config_url' => $module['section']
                    ? $this->getUrl('adminhtml/system_config/edit', ['section' => $module['section']]) : null,
                'expiring' => false,
                'dates' => [],
                'license_links' => []
            ];
            $licenses = $remote['licenses'] ?: [$remote];
            foreach ($licenses as $license) {
                $field = $remote['is_free'] ? 'support_expired_at' : 'subscription_expired_at';
                $downloadUrl = $license['download_url'] ?? null;
                if (!$downloadUrl && !empty($license['manage_url'])) {
                    $downloadUrl = str_replace('open_license=', 'open_download=', $license['manage_url']);
                }
                if ($downloadUrl && ($remote['is_free'] || (!empty($license[$field])
                    && $this->getExpiry($license[$field], '')['days'] > 0))) {
                    $row['download_url'] = $downloadUrl;
                }
                if (!empty($license['manage_url'])) {
                    $row['license_links'][] = ['url' => $license['manage_url'],
                        'id' => count($licenses) > 1 ? ($license['license_id'] ?? null) : null];
                }
                if (!empty($license[$field])) {
                    $date = $this->getExpiry($license[$field], $remote['is_free'] ? __('Support until') : __('Support & Update until'));
                    $date['renew_url'] = $license['renew_url'] ?? null;
                    $date['license_id'] = count($licenses) > 1 ? ($license['license_id'] ?? null) : null;
                    $row['dates'][] = $date;
                    if ($date['days'] <= ($remote['is_free'] ? 0 : 7) && $date['renew_url']) {
                        $row['renew_actions'][] = $date;
                    }
                }
            }
            foreach ($row['dates'] as $date) {
                if ($date['days'] <= 30) {
                    $row['expiring'] = true;
                }
            }
            $row['unlinked_paid'] = $remote['label'] !== null && !$remote['is_free'] && !$row['license_links'];
            $row['request_feature_url'] = $this->getRequestFeatureUrl($row);
            $row['group'] = !$row['is_free'] && $row['expiring'] ? 0 : ($row['has_update'] ? 1 : 2);
            $row['can_download'] = $row['has_update'] && $row['can_download'] && $row['download_url'];
            if (!$row['is_free'] && $row['dates'] && !array_filter($row['dates'], function ($date) {
                return $date['days'] > 0;
            })) {
                $row['can_download'] = false;
            }
            $rows[] = $row;
        }
        usort($rows, function ($first, $second) {
            if ($first['group'] !== $second['group']) {
                return $first['group'] <=> $second['group'];
            }
            return strcasecmp($first['label'], $second['label']);
        });
        $this->rows = $rows;
        return $this->rows;
    }

    /** @return int */
    public function getUnlinkedPaidCount()
    {
        return count(array_filter(array_column($this->getRows(), 'unlinked_paid')));
    }

    /** @return string|null */
    public function getConnectUrl()
    {
        return $this->provider->get()->getAccountUrl('connect_url');
    }

    /** @return array|null */
    public function getBanner()
    {
        return $this->isPromotionEnabled() ? $this->provider->get()->getBanner() : null;
    }

    /** @return array */
    public function getRecommendations()
    {
        return $this->isPromotionEnabled() ? $this->provider->get()->getRecommendations() : [];
    }

    /** @return string */
    public function getLicenseUrl()
    {
        return $this->provider->get()->getAccountUrl('license_url') ?: 'https://dashboard.mageplaza.com/license/';
    }

    /** @return string */
    public function getRequestFeatureUrl(array $row = [])
    {
        $params = [
            'intent' => 'feature_request',
            'domain' => (string) $this->_scopeConfig->getValue('web/secure/base_url'),
            'magento_version' => $this->provider->getMagentoVersion(),
            'extension' => $row ? $row['label'] : 'general',
        ];
        if ($row) {
            $params['version'] = $row['version'];
        }
        return 'https://www.mageplaza.com/support/?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /** @return string */
    public function getTrackUrl()
    {
        return $this->getUrl('mpcore/extensions/track');
    }

    /** @return string */
    public function getRefreshUrl()
    {
        return $this->getUrl('mpcore/extensions/refresh');
    }

    /** @return \Magento\Framework\Phrase */
    public function getLastCheckedLabel()
    {
        $record = $this->provider->getRecord();
        if (empty($record['checked_at'])) {
            return __('Last checked: never');
        }
        return __('Last checked: %1', $this->formatDate((new \DateTimeImmutable())->setTimestamp((int) $record['checked_at']), \IntlDateFormatter::MEDIUM, true));
    }

    /** @return string|null */
    public function getNotificationSettingsUrl()
    {
        if (!$this->_authorization->isAllowed('Magento_Config::config')
            || !$this->_authorization->isAllowed('Mageplaza_Core::configuration')) {
            return null;
        }
        return $this->getUrl('adminhtml/system_config/edit', ['section' => 'mageplaza']);
    }

    /** @return bool */
    public function hasError()
    {
        $record = $this->provider->getRecord();
        return !empty($record['error']);
    }

    /** @return bool */
    public function hasChecked()
    {
        $record = $this->provider->getRecord();
        return !empty($record['checked_at']);
    }

    /** @return bool */
    private function isPromotionEnabled()
    {
        if (!$this->_scopeConfig->getValue('mageplaza/general/notice_enable')) {
            return false;
        }
        $types = array_map('trim', explode(',', (string) $this->_scopeConfig->getValue('mageplaza/general/notice_type')));
        return in_array('marketing', $types, true);
    }

    /** @return array */
    private function getExpiry($date, $label)
    {
        $timezone = new \DateTimeZone($this->_localeDate->getConfigTimezone());
        $today = new \DateTimeImmutable('today', $timezone);
        $expiry = new \DateTimeImmutable($date, $timezone);
        $days = max(0, (int) $today->diff($expiry)->format('%r%a'));
        return ['label' => $label, 'date' => $this->formatDate($expiry, \IntlDateFormatter::MEDIUM, false, $timezone->getName()),
            'days' => $days, 'state' => $days === 0 ? 'expired' : ($days <= 7 ? 'critical' : ($days <= 30 ? 'warning' : 'active'))];
    }
}
