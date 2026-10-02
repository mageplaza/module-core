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
                'has_update' => $remote['can_update'] && $remote['latest_version'] && Response::validVersion($module['version'])
                    && version_compare($remote['latest_version'], $module['version'], '>'),
                'renew_url' => $remote['renew_url'],
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
                if (!empty($license['manage_url'])) {
                    $row['license_links'][] = ['url' => $license['manage_url'],
                        'id' => count($licenses) > 1 ? ($license['license_id'] ?? null) : null];
                }
                $field = $remote['is_free'] ? 'support_expired_at' : 'subscription_expired_at';
                if (!empty($license[$field])) {
                    $date = $this->getExpiry($license[$field], $remote['is_free'] ? __('Support') : __('Subscription'));
                    $date['renew_url'] = $license['renew_url'] ?? null;
                    $date['license_id'] = count($licenses) > 1 ? ($license['license_id'] ?? null) : null;
                    $row['dates'][] = $date;
                }
            }
            foreach ($row['dates'] as $date) {
                if ($date['days'] <= 30) {
                    $row['expiring'] = true;
                }
            }
            $row['unlinked_paid'] = $remote['label'] !== null && !$remote['is_free'] && !$row['license_links'];
            $row['request_feature_url'] = $this->getRequestFeatureUrl($row);
            $rows[] = $row;
        }
        usort($rows, function ($first, $second) {
            if ($first['has_update'] != $second['has_update']) {
                return $first['has_update'] ? -1 : 1;
            }
            if ($first['expiring'] != $second['expiring']) {
                return $first['expiring'] ? -1 : 1;
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
        return $this->provider->get()->getBanner();
    }

    /** @return string */
    public function getLicenseUrl()
    {
        return $this->provider->get()->getAccountUrl('license_url');
    }

    /** @return string */
    public function getRequestFeatureUrl(array $row = [])
    {
        return $row
            ? $this->provider->get()->getModule($row['package'])['request_feature_url']
            : $this->provider->get()->getAccountUrl('request_feature_url');
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

    /** @return array */
    private function getExpiry($date, $label)
    {
        $timezone = new \DateTimeZone($this->_localeDate->getConfigTimezone());
        $today = new \DateTimeImmutable('today', $timezone);
        $expiry = new \DateTimeImmutable($date, $timezone);
        $days = max(0, (int) $today->diff($expiry)->format('%r%a'));
        return ['label' => $label, 'date' => $this->formatDate($expiry, \IntlDateFormatter::MEDIUM, false, $timezone->getName()),
            'days' => $days, 'state' => $days === 0 ? 'expired' : ($days <= 30 ? 'warning' : 'active')];
    }
}
