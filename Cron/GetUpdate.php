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

namespace Mageplaza\Core\Cron;

use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Core\Helper\Validate;
use Mageplaza\Core\Model\License\InstalledModules;
use Mageplaza\Core\Model\License\Notifier;
use Mageplaza\Core\Model\License\Provider;
use Psr\Log\LoggerInterface;

class GetUpdate
{
    const CHECK_VERSION_URL = 'https://dashboard.mageplaza.com/mageplaza/product/checkversion/?isAjax=true';
    const DASHBOARD_URL = 'https://dashboard.mageplaza.com/license/';

    private $provider;
    private $installed;
    private $notifier;
    private $logger;
    private $validate;

    public function __construct(
        Provider $provider,
        InstalledModules $installed,
        Notifier $notifier,
        LoggerInterface $logger,
        Validate $validate
    ) {
        $this->provider = $provider;
        $this->installed = $installed;
        $this->notifier = $notifier;
        $this->logger = $logger;
        $this->validate = $validate;
    }

    public function execute()
    {
        if (!$this->validate->getConfigGeneral('notice_enable')) {
            return $this;
        }
        $modules = $this->installed->getList();
        if (!$modules) {
            return $this;
        }
        try {
            try {
                if (!$this->provider->refresh()) {
                    return $this;
                }
            } catch (LocalizedException $exception) {
            }
            $record = $this->provider->getRecord();
            if (!empty($record['error']) || empty($record['checked_at'])) {
                return $this;
            }
            $this->notifier->notify($this->provider->get(), $modules);
        } catch (\Throwable $exception) {
            $this->logger->warning('Mageplaza extension daily check failed: ' . get_class($exception));
        }
        return $this;
    }
}
