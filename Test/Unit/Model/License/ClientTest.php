<?php
namespace Mageplaza\Core\Test\Unit\Model\License;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Mageplaza\Core\Cron\GetUpdate;
use Mageplaza\Core\Model\License\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientTest extends TestCase
{
    /** @dataProvider endpointProvider */
    public function testDefaultAndLocalEndpoints($override, $expected)
    {
        $curl = $this->createMock(Curl::class);
        $params = ['modules' => ['mageplaza/module-osc' => '4.0.0']];
        $curl->expects($this->once())->method('post')->with($expected, $params);
        $curl->method('getStatus')->willReturn(200);
        $curl->method('getBody')->willReturn('{"is_update":false,"modules":{}}');
        $factory = $this->createMock(CurlFactory::class);
        $factory->method('create')->willReturn($curl);
        $deployment = $this->createMock(DeploymentConfig::class);
        $deployment->method('get')->with('mageplaza_core/check_version_url', GetUpdate::CHECK_VERSION_URL)
            ->willReturn($override === null ? GetUpdate::CHECK_VERSION_URL : $override);
        $client = new Client($factory, $this->createMock(LoggerInterface::class), $deployment);
        $this->assertSame(['is_update' => false, 'modules' => []], $client->fetch($params));
    }

    public static function endpointProvider()
    {
        return [[null, GetUpdate::CHECK_VERSION_URL],
            ['https://dashboard.ddev.site/mageplaza/product/checkversion/', 'https://dashboard.ddev.site/mageplaza/product/checkversion/']];
    }

    public function testInvalidOverrideNeverCallsAnEndpoint()
    {
        $curl = $this->createMock(Curl::class);
        $curl->expects($this->never())->method('post');
        $factory = $this->createMock(CurlFactory::class);
        $factory->method('create')->willReturn($curl);
        $deployment = $this->createMock(DeploymentConfig::class);
        $deployment->method('get')->willReturn('http://dashboard.ddev.site/');
        $client = new Client($factory, $this->createMock(LoggerInterface::class), $deployment);
        $this->assertNull($client->fetch([]));
    }
}
