<?php

namespace JDZ\Medias\Tests\Unit;

use JDZ\Medias\MediasFile;
use PHPUnit\Framework\TestCase;

class MediasFileTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $file = new MediasFile('media/', 'photo.jpg', 'media');
        $this->assertSame('media/', $file->folder);
        $this->assertSame('photo.jpg', $file->name);
        $this->assertSame('media', $file->type);
    }

    public function testConstructorDefaultType(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $this->assertSame('media', $file->type);
    }

    public function testConstructorCustomType(): void
    {
        $file = new MediasFile('fonts/', 'roboto.woff', 'fonts');
        $this->assertSame('fonts', $file->type);
    }

    public function testDefaultPropertyValues(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $this->assertFalse($file->ignore);
        $this->assertFalse($file->physical);
        $this->assertSame(0, $file->occurences);
        $this->assertSame([], $file->db);
        $this->assertSame([], $file->tmpl);
        $this->assertSame([], $file->css);
        $this->assertSame([], $file->js);
    }

    public function testIsPhysicalSetsTrue(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $file->isPhysical();
        $this->assertTrue($file->physical);
    }

    public function testIsPhysicalReturnsSelf(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $result = $file->isPhysical();
        $this->assertSame($file, $result);
    }

    public function testIsPhysicalIgnoresParameter(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        // Note: isPhysical() ignores its $physical parameter and always sets true
        $file->isPhysical(false);
        $this->assertTrue($file->physical);
    }

    public function testGetOccurencesEmpty(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $this->assertSame([], $file->getOccurences('/root/'));
    }

    public function testGetOccurencesWithDbEntries(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $file->db[] = (object)['path' => 'articles.image', 'type' => 'media.mediafile', 'asset' => false];

        $result = $file->getOccurences('/root/');
        $this->assertSame(['articles.image (media.mediafile)'], $result);
    }

    public function testGetOccurencesWithTmplEntries(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $file->tmpl[] = (object)['path' => '/root/templates/page.tmpl', 'type' => 'media.template-asset', 'asset' => true];

        $result = $file->getOccurences('/root/');
        $this->assertSame(['templates/page.tmpl (media.template-asset)'], $result);
    }

    public function testGetOccurencesWithCssEntries(): void
    {
        $file = new MediasFile('assets/images/', 'banner.svg', 'assets');
        $file->css[] = (object)['path' => '/root/css/main.css', 'type' => 'assets.css-image', 'asset' => false];

        $result = $file->getOccurences('/root/');
        $this->assertSame(['css/main.css (assets.css-image)'], $result);
    }

    public function testGetOccurencesWithMixedEntries(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $file->db[] = (object)['path' => 'articles.image', 'type' => 'media.mediafile', 'asset' => false];
        $file->tmpl[] = (object)['path' => '/root/templates/page.tmpl', 'type' => 'media.template-asset', 'asset' => true];
        $file->css[] = (object)['path' => '/root/css/main.css', 'type' => 'media.css-image', 'asset' => false];

        $result = $file->getOccurences('/root/');
        $this->assertCount(3, $result);
    }

    public function testGetOccurencesDeduplicates(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $file->db[] = (object)['path' => 'articles.image', 'type' => 'media.mediafile', 'asset' => false];
        $file->db[] = (object)['path' => 'articles.image', 'type' => 'media.mediafile', 'asset' => false];

        $result = $file->getOccurences('/root/');
        $this->assertCount(1, $result);
    }

    public function testGetOccurencesDoesNotIncludeJs(): void
    {
        $file = new MediasFile('assets/images/', 'banner.svg', 'assets');
        $file->js[] = (object)['path' => '/root/js/app.js', 'type' => 'assets.js-image', 'asset' => false];

        // Note: getOccurences() does not iterate $this->js
        $result = $file->getOccurences('/root/');
        $this->assertSame([], $result);
    }

    public function testRootPropertyIsUninitialized(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        // $root is declared as bool but has no default; accessing it throws
        $this->expectException(\Error::class);
        $_ = $file->root;
    }
}
