<?php
namespace Mageplaza\Core\Test\Unit\Block\Adminhtml\Extensions;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mageplaza\Core\Block\Adminhtml\Extensions\Listing;
use Mageplaza\Core\Model\License\InstalledModules;
use Mageplaza\Core\Model\License\Provider;
use Mageplaza\Core\Model\License\Response;
use PHPUnit\Framework\TestCase;

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
        foreach (['installed' => $modules, 'provider' => $provider, '_localeDate' => $timezone] as $key => $value) {
            $property = new \ReflectionProperty(Listing::class, $key);
            $property->setAccessible(true);
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
        $this->assertSame(['z', 'b', 'a'], array_column($rows, 'package'));
        $this->assertSame('Remote label', $rows[0]['label']);
        $this->assertCount(1, $rows[1]['dates']);
        $this->assertSame(0, $rows[1]['dates'][0]['days']);
        $this->assertSame('expired', $rows[1]['dates'][0]['state']);
    }

    public function testPastExpiryDoesNotHaveNegativeDays()
    {
        $row = $this->createListing(['osc' => $this->module()], [
            'modules' => ['osc' => ['subscription_expired_at' => '2000-01-01']]
        ])->getRows()[0];
        $this->assertSame(0, $row['dates'][0]['days']);
    }

    public function testServerUrlsMustBeMageplazaHttps()
    {
        $block = $this->createListing(['osc' => $this->module()], [
            'modules' => ['osc' => ['request_feature_url' => 'https://www.mageplaza.com/support/?extension=One%20Step%20Checkout']],
            'account' => ['connect_url' => 'https://dashboard.mageplaza.com/license/?connect_domain=store.example.com'],
        ]);
        $this->assertSame('https://www.mageplaza.com/support/?extension=One%20Step%20Checkout', $block->getRows()[0]['request_feature_url']);
        $this->assertSame('https://dashboard.mageplaza.com/license/?connect_domain=store.example.com', $block->getConnectUrl());

        $block = $this->createListing(['osc' => $this->module()], [
            'modules' => ['osc' => ['request_feature_url' => 'https://evil.example.com/support/']],
            'account' => ['connect_url' => 'http://dashboard.mageplaza.com/license/'],
        ]);
        $this->assertNull($block->getRows()[0]['request_feature_url']);
        $this->assertNull($block->getConnectUrl());
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
}
