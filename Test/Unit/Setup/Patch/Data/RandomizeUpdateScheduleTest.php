<?php
namespace Mageplaza\Core\Test\Unit\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mageplaza\Core\Setup\Patch\Data\RandomizeUpdateSchedule;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RandomizeUpdateScheduleTest extends TestCase
{
    public function testEmptyConfigWritesRandomDailyExpression()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())->method('getValue')
            ->with(RandomizeUpdateSchedule::XML_PATH)->willReturn(null);
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects($this->once())->method('save')->with(
            RandomizeUpdateSchedule::XML_PATH,
            $this->callback(function ($expr) {
                if (!preg_match('/^(\d+) (\d+) \* \* \*$/', $expr, $m)) {
                    return false;
                }
                return $m[1] >= 0 && $m[1] <= 59 && $m[2] >= 0 && $m[2] <= 23;
            })
        );
        $patch = new RandomizeUpdateSchedule($scopeConfig, $writer);
        $this->assertSame($patch, $patch->apply());
    }

    /** @dataProvider existingValueProvider */
    #[DataProvider('existingValueProvider')]
    public function testExistingValueIsNotOverwritten($existing)
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with(RandomizeUpdateSchedule::XML_PATH)->willReturn($existing);
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects($this->never())->method('save');
        $patch = new RandomizeUpdateSchedule($scopeConfig, $writer);
        $this->assertSame($patch, $patch->apply());
    }

    public static function existingValueProvider()
    {
        return [['15 3 * * *'], ['0 0 * * *']];
    }

    public function testEmptyStringConfigIsTreatedAsMissing()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('');
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects($this->once())->method('save')->with(RandomizeUpdateSchedule::XML_PATH, $this->callback('is_string'));
        (new RandomizeUpdateSchedule($scopeConfig, $writer))->apply();
    }

    public function testPatchHasNoDependenciesOrAliases()
    {
        $patch = new RandomizeUpdateSchedule(
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(WriterInterface::class)
        );
        $this->assertInstanceOf(DataPatchInterface::class, $patch);
        $this->assertSame([], RandomizeUpdateSchedule::getDependencies());
        $this->assertSame([], $patch->getAliases());
    }
}
