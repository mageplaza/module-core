<?php
namespace Mageplaza\Core\Test\Unit\Block\Adminhtml\Extensions;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mageplaza\Core\Block\Adminhtml\Extensions\Listing;
use Mageplaza\Core\Model\License\InstalledModules;
use Mageplaza\Core\Model\License\Provider;
use Mageplaza\Core\Model\License\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ListingTest extends TestCase
{
    private function createListing(array $installed, array $remote = [])
    {
        $modules = $this->createMock(InstalledModules::class);
        $modules->method('getList')->willReturn($installed);
        $provider = $this->createMock(Provider::class);
        $provider->method('get')->willReturn(new Response($remote));
        $provider->method('getRecord')->willReturn([]);
        $provider->method('getDomain')->willReturn('https://store.example.com/');
        $provider->method('getMagentoVersion')->willReturn('2.4.7-p6');
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('getConfigTimezone')->willReturn('UTC');
        $block = $this->getMockBuilder(Listing::class)->disableOriginalConstructor()
            ->onlyMethods(['getUrl', 'formatDate'])->getMock();
        $block->method('getUrl')->willReturn('https://store.example.com/admin/config');
        $block->method('formatDate')->willReturnCallback(function ($date) { return $date->format('Y-m-d'); });
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with('web/secure/base_url')->willReturn('https://store.example.com/');
        foreach (['installed' => $modules, 'provider' => $provider, '_localeDate' => $timezone, '_scopeConfig' => $scopeConfig] as $key => $value) {
            $property = new \ReflectionProperty(is_a(Listing::class, \Magento\Framework\View\Element\AbstractBlock::class, true) && $key[0] === '_' ? \Magento\Framework\View\Element\AbstractBlock::class : Listing::class, $key);
            if (PHP_VERSION_ID < 80100) {
                $property->setAccessible(true);
            }
            $property->setValue($block, $value);
        }
        return $block;
    }

    private function module($label = 'One Step Checkout')
    {
        return ['label' => $label, 'version' => '4.6.0', 'key' => 'SECRET',
            'section' => 'osc', 'release_url' => 'https://www.mageplaza.com/releases/one-step-checkout/'];
    }

    public function testMissingRemoteDataKeepsCurrentVersion()
    {
        $row = $this->createListing(['osc' => $this->module()])->getRows()[0];
        $this->assertSame('4.6.0', $row['version']);
        $this->assertNull($row['latest_version']);
        $this->assertFalse((bool) $row['has_update']);
        $this->assertNull($row['release_url']);
        $this->assertStringNotContainsString('SECRET', json_encode($row));
        $this->assertNull($row['upgrade_url']);
        $this->assertFalse($row['unlinked_paid']);
    }

    public function testOnlyNewerVersionsShowAnUpdate()
    {
        foreach (['4.5.0' => false, '4.6.0' => false, '4.7.0' => true, 'broken' => false] as $version => $expected) {
            $row = $this->createListing(['osc' => $this->module()], ['modules' => ['osc' => ['latest_version' => $version, 'can_update' => true]]])->getRows()[0];
            $this->assertSame($expected, (bool) $row['has_update']);
        }
    }

    public function testSortAndSameDayExpiryWithMergedDates()
    {
        $today = gmdate('Y-m-d');
        $block = $this->createListing([
            'a' => $this->module('Alpha'), 'b' => $this->module('Beta'), 'z' => $this->module('Zeta')
        ], ['modules' => [
            'z' => ['latest_version' => '4.7.0', 'label' => 'Remote label', 'can_update' => true],
            'b' => ['support_expired_at' => $today, 'subscription_expired_at' => $today]
        ]]);
        $rows = $block->getRows();
        $this->assertSame(['b', 'z', 'a'], array_column($rows, 'package'));
        $this->assertSame('Remote label', $rows[1]['label']);
        $this->assertCount(1, $rows[0]['dates']);
        $this->assertSame(0, $rows[0]['dates'][0]['days']);
        $this->assertSame('expired', $rows[0]['dates'][0]['state']);
    }

    public function testPastExpiryDoesNotHaveNegativeDays()
    {
        $row = $this->createListing(['osc' => $this->module()], [
            'modules' => ['osc' => ['subscription_expired_at' => '2000-01-01']]
        ])->getRows()[0];
        $this->assertSame(0, $row['dates'][0]['days']);
    }

    public function testRequestFeatureUrlMatchesSpec()
    {
        $block = $this->createListing(['osc' => $this->module('Better Coupon')]);
        $this->assertSame(
            'https://www.mageplaza.com/support/?intent=feature_request&domain=https%3A%2F%2Fstore.example.com%2F&magento_version=2.4.7-p6&extension=Better%20Coupon&version=4.6.0',
            $block->getRows()[0]['request_feature_url']
        );
        $this->assertSame(
            'https://www.mageplaza.com/support/?intent=feature_request&domain=https%3A%2F%2Fstore.example.com%2F&magento_version=2.4.7-p6&extension=general',
            $block->getRequestFeatureUrl()
        );
    }

    public function testServerConnectUrlMustBeMageplazaHttps()
    {
        $block = $this->createListing(['osc' => $this->module()], [
            'account' => ['connect_url' => 'https://dashboard.mageplaza.com/license/?connect_domain=store.example.com'],
        ]);
        $this->assertSame('https://dashboard.mageplaza.com/license/?connect_domain=store.example.com', $block->getConnectUrl());
        $block = $this->createListing(['osc' => $this->module()], [
            'account' => ['connect_url' => 'http://dashboard.mageplaza.com/license/'],
        ]);
        $this->assertNull($block->getConnectUrl());
    }

    public function testLicenseUrlKeepsValidApiUrlAndRejectsUnsafeUrl()
    {
        $block = $this->createListing([], ['account' => ['license_url' => 'https://dashboard.mageplaza.com/license/?custom=1']]);
        $this->assertSame('https://dashboard.mageplaza.com/license/?custom=1', $block->getLicenseUrl());
        $block = $this->createListing([], ['account' => ['license_url' => 'https://evil.example.com/']]);
        $this->assertSame('https://dashboard.mageplaza.com/license/', $block->getLicenseUrl());
    }

    public function testOnlyPaidModulesWithoutLicenseCountAsUnlinked()
    {
        $block = $this->createListing([
            'paid' => $this->module('Paid'), 'free' => $this->module('Free'),
            'linked' => $this->module('Linked'), 'unknown' => $this->module('Unknown')
        ], ['modules' => [
            'paid' => ['label' => 'Paid'],
            'free' => ['label' => 'Free', 'is_free' => true],
            'linked' => ['label' => 'Linked', 'licenses' => [
                ['license_id' => 7, 'manage_url' => 'https://dashboard.mageplaza.com/license/?open_license=7']
            ]],
        ]]);
        $this->assertSame(1, $block->getUnlinkedPaidCount());
    }
    public function testRedesignExpiryBoundariesAndDownloadEntitlement()
    {
        foreach ([0, 1, 7, 8, 30, 31] as $days) {
            foreach ([false, true] as $free) {
                $date = gmdate('Y-m-d', strtotime('+' . $days . ' days'));
                $row = $this->createListing(['osc' => $this->module()], ['modules' => ['osc' => [
                    'is_free' => $free, 'latest_version' => '4.7.0', 'can_update' => $days > 0,
                    'download_url' => 'https://dashboard.mageplaza.com/license/?open_download=7',
                    'renew_url' => 'https://dashboard.mageplaza.com/license/?open_renew=7',
                    $free ? 'support_expired_at' : 'subscription_expired_at' => $date,
                ]]])->getRows()[0];
                $this->assertTrue((bool) $row['has_update']);
                $this->assertSame($free || $days > 0, (bool) $row['can_download']);
                $this->assertCount($days <= ($free ? 0 : 7) ? 1 : 0, $row['renew_actions']);
                $this->assertSame(!$free && $days <= 30 ? 0 : 1, $row['group']);
                $this->assertSame($days === 0 ? 'expired' : ($days <= 7 ? 'critical' : ($days <= 30 ? 'warning' : 'active')), $row['dates'][0]['state']);
            }
        }
    }

    public function testExpiredPaidLatestVersionStillNeedsRenewal()
    {
        $row = $this->createListing(['osc' => $this->module()], ['modules' => ['osc' => [
            'latest_version' => '4.6.0', 'subscription_expired_at' => '2000-01-01',
            'renew_url' => 'https://dashboard.mageplaza.com/license/?open_renew=7',
        ]]])->getRows()[0];
        $this->assertFalse((bool) $row['has_update']);
        $this->assertFalse((bool) $row['can_download']);
        $this->assertSame(0, $row['group']);
        $this->assertCount(1, $row['renew_actions']);
    }

    public function testMultipleLicensesDownloadUsesEntitledLicense()
    {
        $row = $this->createListing(['osc' => $this->module()], ['modules' => ['osc' => [
            'latest_version' => '4.7.0', 'can_update' => true, 'licenses' => [
                ['license_id' => 1, 'subscription_expired_at' => '2000-01-01', 'can_update' => false,
                    'download_url' => 'https://dashboard.mageplaza.com/license/?open_download=1',
                    'renew_url' => 'https://dashboard.mageplaza.com/license/?open_renew=1'],
                ['license_id' => 2, 'subscription_expired_at' => '2099-01-01', 'can_update' => true,
                    'download_url' => 'https://dashboard.mageplaza.com/license/?open_download=2'],
            ],
        ]]])->getRows()[0];
        $this->assertTrue((bool) $row['can_download']);
        $this->assertSame('https://dashboard.mageplaza.com/license/?open_download=2', $row['download_url']);
        $this->assertSame(1, $row['renew_actions'][0]['license_id']);
    }

}
