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

class Response
{
    /** @var array */
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /** @return array */
    public function toArray()
    {
        return $this->data;
    }

    /** @return bool */
    public function isEmpty()
    {
        return !$this->data;
    }

    /** @return bool */
    public function hasModuleData()
    {
        return isset($this->data['modules']) && is_array($this->data['modules']);
    }

    /** @return array */
    public function getModule($package)
    {
        $module = $this->hasModuleData() && isset($this->data['modules'][$package])
            && is_array($this->data['modules'][$package]) ? $this->data['modules'][$package] : [];
        $upgrade = isset($module['upgrade']) && is_array($module['upgrade']) ? $module['upgrade'] : [];
        $licenses = [];
        foreach (isset($module['licenses']) && is_array($module['licenses']) ? array_slice($module['licenses'], 0, 1000) : [] as $license) {
            if (!is_array($license) || empty($license['license_id']) || !is_numeric($license['license_id'])) {
                continue;
            }
            $licenses[] = [
                'license_id' => (int) $license['license_id'],
                'download_url' => self::filterUrl($license['download_url'] ?? null),
                'can_update' => ($license['can_update'] ?? false) === true,
                'support_expired_at' => self::validDate($license['support_expired_at'] ?? null),
                'subscription_expired_at' => self::validDate($license['subscription_expired_at'] ?? null),
                'renew_url' => self::filterUrl($license['renew_url'] ?? null),
                'manage_url' => self::filterUrl($license['manage_url'] ?? null),
            ];
        }
        return [
            'licenses' => $licenses,
            'manage_url' => self::filterUrl($module['manage_url'] ?? null),
            'download_url' => self::filterUrl($module['download_url'] ?? null),
            'edition' => $this->text($module['edition'] ?? null),
            'label' => $this->text(isset($module['label']) ? $module['label'] : null),
            'is_free' => isset($module['is_free']) && $module['is_free'] === true,
            'can_update' => isset($module['can_update']) && $module['can_update'] === true,
            'latest_version' => self::validVersion(isset($module['latest_version']) ? $module['latest_version'] : null),
            'release_url' => self::filterUrl(isset($module['release_url']) ? $module['release_url'] : null),
            'support_expired_at' => self::validDate(isset($module['support_expired_at']) ? $module['support_expired_at'] : null),
            'subscription_expired_at' => self::validDate(isset($module['subscription_expired_at']) ? $module['subscription_expired_at'] : null),
            'request_feature_url' => self::filterUrl(isset($module['request_feature_url']) ? $module['request_feature_url'] : null),
            'renew_url' => self::filterUrl(isset($module['renew_url']) ? $module['renew_url'] : null),
            'upgrade_label' => $this->text(isset($upgrade['label']) ? $upgrade['label'] : null),
            'upgrade_url' => self::filterUrl(isset($upgrade['url']) ? $upgrade['url'] : null)
        ];
    }

    /** @return string|null */
    public function getAccountUrl($name)
    {
        return self::filterUrl(isset($this->data['account'][$name]) ? $this->data['account'][$name] : null);
    }

    /** @return array|null */
    public function getBanner()
    {
        $banner = isset($this->data['banner']) && is_array($this->data['banner']) ? $this->data['banner'] : [];
        $id = $this->text(isset($banner['id']) ? $banner['id'] : null);
        $image = self::filterUrl(isset($banner['image']) ? $banner['image'] : null);
        $url = self::filterUrl(isset($banner['url']) ? $banner['url'] : null);
        $text = $this->text(isset($banner['text']) ? $banner['text'] : null);
        $buttonText = $this->text(isset($banner['button_text']) ? $banner['button_text'] : null);
        if (!$id || !$url || (!$image && (!$text || !$buttonText))) {
            return null;
        }
        $today = gmdate('Y-m-d');
        foreach (['from', 'to'] as $boundary) {
            if (!empty($banner[$boundary])) {
                $date = self::validDate($banner[$boundary]);
                if (!$date || ($boundary === 'from' && $today < $date) || ($boundary === 'to' && $today > $date)) {
                    return null;
                }
            }
        }
        $bgColor = self::color(isset($banner['bg_color']) ? $banner['bg_color'] : null, '#fff4ec');
        $buttonColor = self::color(isset($banner['button_color']) ? $banner['button_color'] : null, '#eb5202');
        $result = ['id' => $id, 'image' => (string) $image, 'url' => $url,
            'text' => (string) $text, 'button_text' => (string) $buttonText,
            'bg_color' => $bgColor, 'button_color' => $buttonColor,
            'text_color' => self::contrastColor($bgColor), 'button_text_color' => self::contrastColor($buttonColor)];
        $notify = isset($banner['notify']) && is_array($banner['notify']) ? $banner['notify'] : [];
        $title = $this->text(isset($notify['title']) ? $notify['title'] : null);
        $description = $this->text(isset($notify['description']) ? $notify['description'] : null);
        if ($title && $description) {
            $result['notify'] = ['title' => $title, 'description' => $description];
        }
        return $result;
    }

    /** @return string */
    public static function color($value, $default)
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
    }

    /** @return string */
    public static function contrastColor($hex)
    {
        $luminance = 0;
        foreach ([0 => 0.2126, 2 => 0.7152, 4 => 0.0722] as $offset => $weight) {
            $channel = hexdec(substr($hex, 1 + $offset, 2)) / 255;
            $luminance += $weight * ($channel <= 0.03928 ? $channel / 12.92 : pow(($channel + 0.055) / 1.055, 2.4));
        }
        return $luminance > 0.5 ? '#303030' : '#ffffff';
    }

    /** @return array */
    public function getRecommendations()
    {
        $items = isset($this->data['recommendations']) && is_array($this->data['recommendations'])
            ? $this->data['recommendations'] : [];
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $package = isset($item['package']) ? $item['package'] : null;
            $label = $this->text(isset($item['label']) ? $item['label'] : null);
            $image = self::filterUrl(isset($item['image']) ? $item['image'] : null);
            $url = self::filterUrl(isset($item['url']) ? $item['url'] : null);
            if (!is_string($package) || !preg_match('#^mageplaza/[a-z0-9][a-z0-9._-]*$#D', $package)
                || !$label || !$image || !$url) {
                continue;
            }
            $result[] = ['package' => $package, 'label' => $label, 'image' => $image, 'url' => $url,
                'price' => $this->text(isset($item['price']) ? $item['price'] : null) ?: ''];
        }
        return $result;
    }

    /** @return string|null */
    public static function filterUrl($url)
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'https'
            || !preg_match('/(^|\.)mageplaza\.com$/i', $parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return null;
        }
        return $url;
    }

    /** @return string|null */
    public static function validVersion($value)
    {
        return is_string($value) && preg_match('/^\d+(?:\.\d+)*(?:[-+][a-zA-Z0-9.-]+)?$/D', $value) ? $value : null;
    }

    /** @return string|null */
    public static function validDate($value)
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    /** @return string|null */
    private function text($value)
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
