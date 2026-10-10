<?php

namespace JDZ\Medias\Tests\Unit;

use JDZ\Medias\ExtraData;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ExtraDataTest extends TestCase
{
    public function testConstructWithEmptyArray(): void
    {
        $ed = new ExtraData();
        $this->assertSame([], $ed->all());
    }

    public function testConstructWithData(): void
    {
        $ed = new ExtraData(['a' => 1, 'b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2], $ed->all());
    }

    public function testSetSingleValue(): void
    {
        $ed = new ExtraData();
        $ed->set('key', 'val');
        $this->assertSame('val', $ed->get('key'));
    }

    public function testSetsMultipleValues(): void
    {
        $ed = new ExtraData();
        $ed->sets(['a' => 1, 'b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2], $ed->all());
    }

    public function testSetOverwritesExistingKey(): void
    {
        $ed = new ExtraData(['key' => 'old']);
        $ed->set('key', 'new');
        $this->assertSame('new', $ed->get('key'));
    }

    public function testGetNonExistentKeyReturnsNull(): void
    {
        $ed = new ExtraData();
        $this->assertNull($ed->get('missing'));
    }

    public function testGetNonExistentKeyReturnsDefault(): void
    {
        $ed = new ExtraData();
        $this->assertSame('fallback', $ed->get('missing', 'fallback'));
    }

    public function testGetDefaultNotUsedWhenKeyExists(): void
    {
        $ed = new ExtraData(['key' => 'real']);
        $this->assertSame('real', $ed->get('key', 'fallback'));
    }

    public function testFluentChaining(): void
    {
        $ed = new ExtraData();
        $result = $ed->set('a', 1)->set('b', 2)->sets(['c' => 3]);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $ed->all());
    }

    #[DataProvider('valueTypesProvider')]
    public function testSetWithVariousValueTypes(mixed $value): void
    {
        $ed = new ExtraData();
        $ed->set('key', $value);
        $this->assertSame($value, $ed->get('key'));
    }

    public static function valueTypesProvider(): array
    {
        return [
            'integer' => [42],
            'string' => ['hello'],
            'boolean true' => [true],
            'boolean false' => [false],
            'null' => [null],
            'array' => [[1, 2, 3]],
            'empty string' => [''],
            'zero' => [0],
        ];
    }
}
