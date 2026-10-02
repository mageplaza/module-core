<?php
namespace Mageplaza\Core\Test\Unit\Model\License;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mageplaza\Core\Helper\Validate;
use Mageplaza\Core\Model\License\Notifier;
use Mageplaza\Core\Model\License\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotifierTest extends TestCase
{
    private $stored;
    private $notices;

    public function testSameLegacyMessageIsNotifiedOnce()
    {
        $notifier = $this->createNotifier();
        $notifier->notify($this->legacyResponse('Update available'), []);
        $notifier->notify($this->legacyResponse('Update available'), []);
        $this->assertSame(['Update available'], $this->notices);
    }

    public function testChangedLegacyMessageIsNotifiedAgain()
    {
        $notifier = $this->createNotifier();
        $notifier->notify($this->legacyResponse('First message'), []);
        $notifier->notify($this->legacyResponse('Second message'), []);
        $this->assertSame(['First message', 'Second message'], $this->notices);
    }

    public function testLegacySnapshotStoresSha256Hash()
    {
        $this->createNotifier()->notify($this->legacyResponse('Update available'), []);
        $this->assertSame(hash('sha256', 'Update available'), $this->stored['legacy']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->stored['legacy']);
    }

    private function createNotifier()
    {
        $this->stored = null;
        $this->notices = [];

        $flags = $this->createMock(FlagManager::class);
        $flags->method('getFlagData')->willReturnCallback(function () {
            return $this->stored;
        });
        $flags->method('saveFlag')->willReturnCallback(function ($code, $data) {
            $this->stored = $data;
            return true;
        });

        $locks = $this->createMock(LockManagerInterface::class);
        $locks->method('lock')->willReturn(true);

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->method('addNotice')->willReturnCallback(function ($title, $description) {
            $this->notices[] = $description;
        });

        $validate = $this->createMock(Validate::class);
        $validate->method('getConfigGeneral')->willReturn('1');
        $validate->method('isEnabledNotificationUpdate')->willReturn(true);

        return new Notifier(
            $flags,
            $locks,
            $notifier,
            $this->createMock(TimezoneInterface::class),
            $validate,
            $this->createMock(UrlInterface::class)
        );
    }

    private function legacyResponse($message)
    {
        return new Response(['is_update' => 1, 'message' => $message]);
    }
}
