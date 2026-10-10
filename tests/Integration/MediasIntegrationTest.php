<?php

namespace JDZ\Medias\Tests\Integration;

use JDZ\Medias\Medias;
use PHPUnit\Framework\TestCase;

class MediasIntegrationTest extends TestCase
{
    private string $publicPath;
    private Medias $medias;

    protected function setUp(): void
    {
        // Trailing slash required: getMediaFolders concatenates basePath + path directly
        $this->publicPath = str_replace('\\', '/', realpath(__DIR__ . '/../Fixtures/public')) . '/';
        Medias::$mediaRootFolder = 'media';
        $this->medias = new Medias($this->publicPath);
    }

    protected function tearDown(): void
    {
        Medias::$mediaRootFolder = 'media';
    }

    public function testLoadMediaFoldersReturnsArray(): void
    {
        $folders = $this->medias->loadMediaFolders();
        $this->assertIsArray($folders);
        $this->assertNotEmpty($folders);
    }

    public function testLoadMediaFoldersIncludesFonts(): void
    {
        $folders = $this->medias->loadMediaFolders();
        // fonts/ is hardcoded in loadMediaFolders, always present
        $this->assertArrayHasKey('fonts/', $folders);
        $this->assertSame('fonts', $folders['fonts/']->type);
    }

    public function testLoadMediaFoldersIncludesAssets(): void
    {
        $folders = $this->medias->loadMediaFolders();
        // assets/images/ is hardcoded in loadMediaFolders, always present
        $this->assertArrayHasKey('assets/images/', $folders);
        $this->assertSame('assets', $folders['assets/images/']->type);
    }

    public function testLoadMediaFoldersDiscoversFolders(): void
    {
        $folders = $this->medias->loadMediaFolders();
        // Should discover assets/images/icons/ subfolder
        $this->assertArrayHasKey('assets/images/icons/', $folders);
    }

    public function testLoadMediaFoldersReturnsCorrectTypes(): void
    {
        $folders = $this->medias->loadMediaFolders();
        $types = array_unique(array_map(fn($f) => $f->type, $folders));
        // Should contain at least 'fonts' and 'assets' types
        $this->assertContains('fonts', $types);
        $this->assertContains('assets', $types);
    }

    public function testLoadMediaFoldersIncludesMediaRoot(): void
    {
        $folders = $this->medias->loadMediaFolders();
        $this->assertArrayHasKey('media/', $folders);
        $this->assertArrayNotHasKey('/', $folders);
    }

    public function testLoadMediaFoldersUsesCustomMediaRoot(): void
    {
        Medias::$mediaRootFolder = 'users';
        $folders = $this->medias->loadMediaFolders();
        $this->assertArrayHasKey('users/', $folders);
        $this->assertArrayNotHasKey('media/', $folders);
    }

    public function testLoadMediafilesReturnsFileObjects(): void
    {
        $folders = $this->medias->loadMediaFolders();
        $files = $this->medias->loadMediafiles($folders);

        $this->assertIsArray($files);
        $this->assertNotEmpty($files);

        $first = reset($files);
        $this->assertObjectHasProperty('folder', $first);
        $this->assertObjectHasProperty('name', $first);
        $this->assertObjectHasProperty('type', $first);
    }

    public function testLoadMediafilesContainsFontFiles(): void
    {
        $folders = $this->medias->loadMediaFolders();
        $files = $this->medias->loadMediafiles($folders);

        $names = array_map(fn($f) => $f->name, $files);
        $this->assertContains('roboto.woff', $names);
    }

    public function testLoadMediafilesContainsAssetFiles(): void
    {
        $folders = $this->medias->loadMediaFolders();
        $files = $this->medias->loadMediafiles($folders);

        $names = array_map(fn($f) => $f->name, $files);
        $this->assertContains('banner.svg', $names);
    }

    public function testFullWorkflow(): void
    {
        $medias = new Medias($this->publicPath);
        $folders = $medias->loadMediaFolders();
        $files = $medias->loadMediafiles($folders);

        // Load files into the media list
        $medias->getMedialist()->loadFiles(array_values($files));

        $list = $medias->getMedialist();
        $this->assertGreaterThan(0, count($list->all()));

        // All loaded files should be marked as physical
        foreach ($list->all() as $item) {
            $this->assertTrue($item->physical);
        }
    }
}
