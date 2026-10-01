<?php
namespace Mageplaza\Core\Test\Unit\Model\License;

use Mageplaza\Core\Model\License\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /** @dataProvider rejectedUrls */
    #[DataProvider('rejectedUrls')]
    public function testUntrustedUrlsAreRejected($url)
    {
        $this->assertNull(Response::filterUrl($url));
    }

    public static function rejectedUrls()
    {
        return [[null], [[]], ['http://www.mageplaza.com/'], ['https://evil.com/'],
            ['https://evil-mageplaza.com/'], ['https://mageplaza.com.evil.com/'],
            ['https://user:pass@mageplaza.com/'], ['javascript:alert(1)']];
    }

    public function testTrustedSubdomainIsAllowed()
    {
        $this->assertSame('https://cdn.mageplaza.com/banner.png', Response::filterUrl('https://cdn.mageplaza.com/banner.png'));
    }

    public function testLegacyAndMalformedModulesHaveEmptyOptionalFields()
    {
        foreach ([[], ['is_update' => true], ['modules' => 'invalid']] as $data) {
            $response = new Response($data);
            $this->assertFalse($response->hasModuleData());
            $this->assertNull($response->getModule('mageplaza/module-osc')['latest_version']);
            $this->assertNull($response->getBanner());
        }
    }

    public function testInvalidDatesAndVersionsAreNotExposed()
    {
        $response = new Response(['modules' => ['test' => [
            'latest_version' => '<script>', 'support_expired_at' => '2026-02-30',
            'subscription_expired_at' => ['2026-01-01'], 'upgrade' => ['url' => 'https://evil.com/']
        ]]]);
        $module = $response->getModule('test');
        $this->assertNull($module['latest_version']);
        $this->assertNull($module['support_expired_at']);
        $this->assertNull($module['subscription_expired_at']);
        $this->assertNull($module['upgrade_url']);
    }

    public function testBannerWithoutDateBoundsIsAllowed()
    {
        $response = new Response(['banner' => ['id' => 'campaign',
            'image' => 'https://cdn.mageplaza.com/image.png', 'url' => 'https://www.mageplaza.com/']]);
        $this->assertSame('campaign', $response->getBanner()['id']);
    }

    public function testExpiredAndUntrustedBannersAreHidden()
    {
        $banner = ['id' => 'campaign', 'image' => 'https://cdn.mageplaza.com/image.png',
            'url' => 'https://www.mageplaza.com/', 'to' => '2000-01-01'];
        $this->assertNull((new Response(['banner' => $banner]))->getBanner());
        unset($banner['to']);
        $banner['image'] = 'https://evil.com/image.png';
        $this->assertNull((new Response(['banner' => $banner]))->getBanner());
    }
}
