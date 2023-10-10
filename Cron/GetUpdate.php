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
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Cron;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Mageplaza\Core\Helper\Validate;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Notification\NotifierInterface as NotifierPool;
use Psr\Log\LoggerInterface;

/**
 * Class GetUpdate
 * @package Mageplaza\Core\Cron
 */
class GetUpdate
{
    const CHECK_VERSION_URL = 'https://dashboard.mageplaza.com/mageplaza/product/checkversion/?isAjax=true';
    const DASHBOARD_URL = 'https://dashboard.mageplaza.com/license/';

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var Validate
     */
    protected $helperValidate;

    /**
     * @var ComponentRegistrarInterface
     */
    protected $componentRegistrar;

    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * @var NotifierPool
     */
    protected $notifierPool;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * GetUpdate constructor.
     *
     * @param Validate $helperValidate
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     * @param CurlFactory $curlFactory
     * @param NotifierPool $notifierPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        Validate $helperValidate,
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory,
        CurlFactory $curlFactory,
        NotifierPool $notifierPool,
        LoggerInterface $logger
    ) {
        $this->helperValidate     = $helperValidate;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory        = $readFactory;
        $this->curlFactory        = $curlFactory;
        $this->notifierPool       = $notifierPool;
        $this->logger             = $logger;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        if (!$this->helperValidate->isEnabledNotificationUpdate()) {
            return $this;
        }

        $moduleList = $this->helperValidate->getModuleList();
        $edition = $this->helperValidate->getEdition();

        $modules = [];
        foreach ($moduleList as $moduleName) {
            if ($moduleName === 'Mageplaza_Core') {
                continue;
            }

            try {
                $path                   = $this->componentRegistrar->getPath(
                    ComponentRegistrar::MODULE,
                    $moduleName
                );
                $directoryRead          = $this->readFactory->create($path);
                $composerJsonData       = $directoryRead->readFile('composer.json');
                $data                   = json_decode($composerJsonData, true);
                $modules[$data['name']] = $data['version'];
            } catch (\Exception $exception) {
                continue;
            }
        }

        try {
            $curl = $this->curlFactory->create();
            $curl->post(self::CHECK_VERSION_URL, ['magento_edition' => $edition, 'modules' => $modules]);
            $response = $curl->getBody();

            if ($response) {
                $response = Validate::jsonDecode($response);
                if (isset($response['is_update']) && $response['is_update']) {
                    $this->notifierPool->addNotice('Mageplaza Notice', $response['message'], self::DASHBOARD_URL);
                }
            }

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
