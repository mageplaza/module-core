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

namespace Mageplaza\Core\Model;

use Exception;
use Laminas\Http\Request;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Mageplaza\Core\Helper\AbstractData;

/**
 * Class Activate
 * @package Mageplaza\Core\Model
 */
class Activate extends DataObject
{
    /**
     * Localhost maybe not active via https
     *
     */
    const MAGEPLAZA_ACTIVE_URL = 'https://dashboard.mageplaza.com/license/index/activate/?isAjax=true';

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * Activate constructor.
     *
     * @param CurlFactory $curlFactory
     * @param array $data
     */
    public function __construct(
        CurlFactory $curlFactory,
        array $data = []
    ) {
        $this->curlFactory = $curlFactory;

        parent::__construct($data);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function activate($params = [])
    {
        $result = ['success' => false];

        $curl = $this->curlFactory->create();
        $curl->write(
            Request::METHOD_POST,
            self::MAGEPLAZA_ACTIVE_URL,
            '1.1',
            [],
            http_build_query($params)
        );

        try {
            $resultCurl = $curl->read();
            if (empty($resultCurl)) {
                $result['message'] = __('Cannot connect to server. Please try again later.');
            } else {
                $responseBody = $this->extractBody($resultCurl);
                $result       += AbstractData::jsonDecode($responseBody);
                if (isset($result['status']) && in_array($result['status'], [200, 201])) {
                    $result['success'] = true;
                }
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        $curl->close();

        return $result;
    }

    /**
     * Extract the body from a response string
     *
     * @param string $response_str
     *
     * @return string
     */
    public function extractBody(string $response_str): string
    {
        $parts = preg_split('|(?:\r\n){2}|m', $response_str, 2);
        if (isset($parts[1])) {
            return $parts[1];
        }

        return '';
    }
}
