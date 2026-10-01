<?php
/** Mageplaza Core — validate all remote data before exposing it to the admin. */
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
                'support_expired_at' => self::validDate($license['support_expired_at'] ?? null),
                'subscription_expired_at' => self::validDate($license['subscription_expired_at'] ?? null),
                'renew_url' => self::filterUrl($license['renew_url'] ?? null),
                'manage_url' => self::filterUrl($license['manage_url'] ?? null),
            ];
        }
        return [
            'licenses' => $licenses,
            'manage_url' => self::filterUrl($module['manage_url'] ?? null),
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
        if (!$id || !$image || !$url) {
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
        $result = ['id' => $id, 'image' => $image, 'url' => $url,
            'alt' => $this->text(isset($banner['alt']) ? $banner['alt'] : null) ?: ''];
        $notify = isset($banner['notify']) && is_array($banner['notify']) ? $banner['notify'] : [];
        $title = $this->text(isset($notify['title']) ? $notify['title'] : null);
        $description = $this->text(isset($notify['description']) ? $notify['description'] : null);
        if ($title && $description) {
            $result['notify'] = ['title' => $title, 'description' => $description];
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
            || (!preg_match('/(^|\.)mageplaza\.com$/i', $parts['host'])
                && !(getenv('IS_DDEV_PROJECT') === 'true' && $parts['host'] === 'dashboard.ddev.site'))
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
