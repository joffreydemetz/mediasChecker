<?php

namespace JDZ\Medias\Tests\Unit;

use JDZ\Medias\ExtraData;
use JDZ\Medias\MediasFolder;
use PHPUnit\Framework\TestCase;

class MediasFolderTest extends TestCase
{
    public function testConstructorSetsPathAndType(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $this->assertSame('media/', $folder->path);
        $this->assertSame('media', $folder->type);
    }

    public function testConstructorWithEmptyExtraData(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $this->assertSame([], $folder->extraData->all());
    }

    public function testConstructorWithExtraData(): void
    {
        $folder = new MediasFolder('media/', 'media', ['width' => 1200, 'height' => 800]);
        $this->assertSame(1200, $folder->extraData->get('width'));
        $this->assertSame(800, $folder->extraData->get('height'));
    }

    public function testExtraDataIsExtraDataInstance(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $this->assertInstanceOf(ExtraData::class, $folder->extraData);
    }

    public function testAllWithNoExtraData(): void
    {
        $folder = new MediasFolder('fonts/', 'fonts');
        $result = $folder->all();
        $this->assertSame(['path' => 'fonts/', 'type' => 'fonts'], $result);
    }

    public function testAllWithExtraDataIncludesExtras(): void
    {
        $folder = new MediasFolder('media/', 'media', ['width' => 1200]);
        $result = $folder->all();
        $this->assertSame([
            'path' => 'media/',
            'type' => 'media',
            'width' => 1200,
        ], $result);
    }

    public function testSetDirectProperty(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $folder->set('path', 'new/');
        $this->assertSame('new/', $folder->path);
    }

    public function testSetExtraDataProperty(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $folder->set('custom', 'value');
        $this->assertSame('value', $folder->extraData->get('custom'));
    }

    public function testSetReturnsFalse(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $result = $folder->set('path', 'new/');
        // Note: set() returns false instead of $this (unlike sets())
        $this->assertFalse($result);
    }

    public function testSetsMultipleProperties(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $folder->sets(['path' => 'assets/', 'type' => 'assets', 'custom' => 1]);
        $this->assertSame('assets/', $folder->path);
        $this->assertSame('assets', $folder->type);
        $this->assertSame(1, $folder->extraData->get('custom'));
    }

    public function testSetsReturnsSelf(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $result = $folder->sets(['path' => 'new/']);
        $this->assertSame($folder, $result);
    }

    public function testSetDistinguishesDirectFromExtra(): void
    {
        $folder = new MediasFolder('media/', 'media');
        $folder->set('path', 'changed/');
        $folder->set('unknown', 'extra');

        $this->assertSame('changed/', $folder->path);
        $this->assertSame('extra', $folder->extraData->get('unknown'));
        $this->assertNull($folder->extraData->get('path'));
    }
}
