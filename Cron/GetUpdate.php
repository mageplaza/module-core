<?php
/** Daily My Extensions refresh and deduplicated inbox notifications. */
namespace Mageplaza\Core\Cron;

use Magento\Framework\Exception\LocalizedException;
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

    public function __construct(
        Provider $provider,
        InstalledModules $installed,
        Notifier $notifier,
        LoggerInterface $logger
    ) {
        $this->provider = $provider;
        $this->installed = $installed;
        $this->notifier = $notifier;
        $this->logger = $logger;
    }

    public function execute()
    {
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
                // A failed fetch leaves the previous snapshot; its error flag prevents notices.
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
