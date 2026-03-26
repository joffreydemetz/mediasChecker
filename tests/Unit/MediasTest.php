<?php

namespace JDZ\Medias\Tests\Unit;

use JDZ\Medias\Medias;
use JDZ\Medias\MediasList;
use PHPUnit\Framework\TestCase;

class MediasTest extends TestCase
{
    protected function tearDown(): void
    {
        Medias::$mediaRootFolder = 'media';
    }

    public function testConstructorCreatesMediasList(): void
    {
        $medias = new Medias('/some/path');
        $this->assertInstanceOf(MediasList::class, $medias->getMedialist());
    }

    public function testGetMedialistReturnsSameInstance(): void
    {
        $medias = new Medias('/some/path');
        $this->assertSame($medias->getMedialist(), $medias->getMedialist());
    }

    public function testMediaRootFolderDefaultValue(): void
    {
        $this->assertSame('media', Medias::$mediaRootFolder);
    }

    public function testMediaRootFolderIsConfigurable(): void
    {
        Medias::$mediaRootFolder = 'custom';
        $this->assertSame('custom', Medias::$mediaRootFolder);
    }

    public function testConstructorSetsPublicPath(): void
    {
        $medias = new Medias('/my/public');
        $reflection = new \ReflectionProperty(Medias::class, 'publicPath');
        $this->assertSame('/my/public', $reflection->getValue($medias));
    }
}
