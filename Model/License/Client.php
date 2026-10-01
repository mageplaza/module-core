<?php
/** Mageplaza Core — bounded checkversion request; never log license credentials. */
namespace Mageplaza\Core\Model\License;

use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\App\DeploymentConfig;
use Mageplaza\Core\Cron\GetUpdate;
use Psr\Log\LoggerInterface;

class Client
{
    /** @var CurlFactory */
    private $curlFactory;
    /** @var LoggerInterface */
    private $logger;

    /** @var DeploymentConfig */
    private $deploymentConfig;

    public function __construct(CurlFactory $curlFactory, LoggerInterface $logger, DeploymentConfig $deploymentConfig)
    {
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
        $this->deploymentConfig = $deploymentConfig;
    }

    /** Send only a server-built, credential-free event. @return bool */
    public function sendEvent(array $payload)
    {
        try {
            $checkUrl = $this->deploymentConfig->get('mageplaza_core/check_version_url', GetUpdate::CHECK_VERSION_URL);
            $parts = is_string($checkUrl) ? parse_url($checkUrl) : false;
            if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
                || isset($parts['user']) || isset($parts['pass'])) {
                $this->logger->warning('Mageplaza click report failed: invalid_endpoint');
                return false;
            }
            $url = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '')
                . '/mageplaza/product/event/';
            $curl = $this->curlFactory->create();
            $curl->setTimeout(2);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 2);
            $curl->addHeader('Content-Type', 'application/json');
            $curl->post($url, json_encode($payload));
            if ($curl->getStatus() !== 200) {
                $this->logger->warning('Mageplaza click report failed: HTTP ' . (int) $curl->getStatus());
                return false;
            }
            return true;
        } catch (\Throwable $exception) {
            $this->logger->warning('Mageplaza click report failed: connection_error');
            return false;
        }
    }

    /** @return array|null */
    public function fetch(array $params)
    {
        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout(10);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 5);
            // Deployment-only override keeps local QA isolated from the production endpoint.
            $endpoint = $this->deploymentConfig->get('mageplaza_core/check_version_url', GetUpdate::CHECK_VERSION_URL);
            if (!is_string($endpoint) || !filter_var($endpoint, FILTER_VALIDATE_URL)
                || parse_url($endpoint, PHP_URL_SCHEME) !== 'https') {
                $this->logger->warning('Mageplaza extension check failed: invalid_endpoint');
                return null;
            }
            $curl->post($endpoint, $params);
            if ($curl->getStatus() !== 200) {
                $this->logger->warning('Mageplaza extension check failed: HTTP ' . (int) $curl->getStatus());
                return null;
            }
            $data = json_decode($curl->getBody(), true);
            if (is_array($data) && $data && (isset($data['is_update']) || isset($data['modules']))) {
                return $data;
            }
            $this->logger->warning('Mageplaza extension check failed: invalid_response');
        } catch (\Exception $exception) {
            $this->logger->warning('Mageplaza extension check failed: connection_error');
        }
        return null;
    }
}
