<?php

namespace Mageplaza\Core\Model;

class Feed extends \Magento\AdminNotification\Model\Feed
{
    const MAGEPLAZA_FEED_URL = 'www.mageplaza.com/notifications.xml';

    public function getFeedUrl()
    {
        $httpPath = $this->_backendConfig->isSetFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://';
        if ($this->_feedUrl === null) {
            $this->_feedUrl = $httpPath . self::MAGEPLAZA_FEED_URL;
        }
        return $this->_feedUrl;
    }

    public function getLastUpdate()
    {
        return $this->_cacheManager->load('mageplaza_notifications_lastcheck');
    }

    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), 'mageplaza_notifications_lastcheck');
        return $this;
    }

}
