<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
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
 * @copyright   Copyright (c) 2016-2018 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Model;

use Mageplaza\Core\Helper\AbstractData;

/**
 * Class Activate
 * @package Mageplaza\Core\Model
 */
class Activate extends \Magento\AdminNotification\Model\Feed
{
    /**
     * @inheritdoc
     */
    const MAGEPLAZA_ACTIVE_URL = 'http://store.mageplaza.com/license/index/activate';

    /**
     * @inheritdoc
     */
    public function getActiveUrl()
    {
        return self::MAGEPLAZA_ACTIVE_URL;
//        $httpPath = $this->_backendConfig->isSetFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://';
//        if ($this->_feedUrl === null) {
//            $this->_feedUrl = $httpPath . self::MAGEPLAZA_ACTIVE_URL;
//        }
//
//        return $this->_feedUrl;
    }

    /**
     * @param array $params
     * @return array
     */
    public function activate($params = [])
    {
        $result = ['success' => false];

        $curl = $this->curlFactory->create();
        $curl->write(\Zend_Http_Client::POST, $this->getActiveUrl(), '1.1', [], http_build_query($params));

        try {
            $resultCurl = $curl->read();
            if (!empty($resultCurl)) {
                $responseBody = \Zend_Http_Response::extractBody($resultCurl);
                $result       += AbstractData::jsonDecode($responseBody);
                if (isset($result['status']) && in_array($result['status'], [200, 201])) {
                    $result['success'] = true;
                }
            } else {
                $result['message'] = __('Cannot connect to server. Please try again later.');
            }
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }

        $curl->close();

        return $result;
    }
}
