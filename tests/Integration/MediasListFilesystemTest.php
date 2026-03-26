<?php

namespace JDZ\Medias\Tests\Integration;

use JDZ\Medias\Medias;
use JDZ\Medias\MediasFile;
use JDZ\Medias\MediasList;
use PHPUnit\Framework\TestCase;

class MediasListFilesystemTest extends TestCase
{
    private string $fixturesPath;
    private string $publicPath;
    private MediasList $list;

    protected function setUp(): void
    {
        $this->fixturesPath = str_replace('\\', '/', realpath(__DIR__ . '/../Fixtures')) . '/';
        $this->publicPath = $this->fixturesPath . 'public/';
        $this->list = new MediasList();
        Medias::$mediaRootFolder = 'media';
    }

    protected function tearDown(): void
    {
        Medias::$mediaRootFolder = 'media';
    }

    // ── files() ──────────────────────────────────────────────────

    public function testFilesFindsFilesInDirectory(): void
    {
        $files = $this->list->files($this->publicPath . 'media');
        $this->assertContains('photo.jpg', $files);
        $this->assertContains('logo.png', $files);
        $this->assertContains('document.pdf', $files);
    }

    public function testFilesWithExtensionFilter(): void
    {
        $files = $this->list->files($this->publicPath . 'media', ['jpg', 'png']);
        $this->assertContains('photo.jpg', $files);
        $this->assertContains('logo.png', $files);
        $this->assertNotContains('document.pdf', $files);
    }

    public function testFilesWithDepthZero(): void
    {
        $files = $this->list->files($this->publicPath . 'media', [], '==0');
        // Should only return top-level files, not gallery/* files
        $this->assertNotContains('img1.jpg', $files);
        $this->assertContains('photo.jpg', $files);
    }

    public function testFilesWithDepthNull(): void
    {
        $files = $this->list->files($this->publicPath . 'media', [], null);
        // With null depth, should find files in subdirectories too
        $filenames = array_map('basename', $files);
        $this->assertContains('photo.jpg', $filenames);
        $this->assertContains('img1.jpg', $filenames);
    }

    public function testFilesAbsolutePathOption(): void
    {
        $files = $this->list->files($this->publicPath . 'media', [], '==0', true);
        foreach ($files as $file) {
            $this->assertStringContainsString($this->publicPath, str_replace('\\', '/', $file));
        }
    }

    public function testFilesRelativePathOption(): void
    {
        $files = $this->list->files($this->publicPath . 'media', [], '==0', false);
        foreach ($files as $file) {
            // Should be just filenames, no path separators
            $this->assertStringNotContainsString('/', $file);
            $this->assertStringNotContainsString('\\', $file);
        }
    }

    public function testFilesNonExistentDirectory(): void
    {
        $files = $this->list->files($this->publicPath . 'nonexistent');
        $this->assertSame([], $files);
    }

    // ── getMediaFolders() ────────────────────────────────────────

    public function testGetMediaFoldersRecursive(): void
    {
        $folders = $this->list->getMediaFolders($this->publicPath, 'media/');
        $this->assertContains('media/gallery/', $folders);
    }

    public function testGetMediaFoldersEmptyDirectory(): void
    {
        $folders = $this->list->getMediaFolders($this->publicPath, 'fonts/');
        $this->assertSame([], $folders);
    }

    public function testGetMediaFoldersNonExistentDirectory(): void
    {
        $folders = $this->list->getMediaFolders($this->publicPath, 'nonexistent/');
        $this->assertSame([], $folders);
    }

    // ── parseLayoutFiles() ───────────────────────────────────────

    public function testParseLayoutFilesFindsImages(): void
    {
        // Pre-add files that templates reference
        $this->list->add(new MediasFile('media/', 'photo.jpg'));

        $this->list->parseLayoutFiles($this->fixturesPath . 'templates/', ['tmpl']);

        $item = $this->list->get('media/photo.jpg');
        $this->assertGreaterThanOrEqual(1, count($item->tmpl));
    }

    public function testParseLayoutFilesFindsLinks(): void
    {
        $this->list->add(new MediasFile('media/', 'document.pdf'));

        $this->list->parseLayoutFiles($this->fixturesPath . 'templates/', ['tmpl']);

        $item = $this->list->get('media/document.pdf');
        $this->assertGreaterThanOrEqual(1, count($item->tmpl));
    }

    public function testParseLayoutFilesCreatesNewEntries(): void
    {
        // Don't pre-add. parseDomAsset will create the entry.
        $this->list->parseLayoutFiles($this->fixturesPath . 'templates/', ['tmpl']);

        // page.tmpl has <img src="media/photo.jpg">
        $this->assertTrue($this->list->has('media/photo.jpg'));
    }

    public function testParseLayoutFilesSkipsFilesWithoutImgOrA(): void
    {
        $beforeCount = count($this->list->all());
        // no-images.tmpl has no <img> or <a> tags, nothing should be added from it
        $this->list->parseLayoutFiles($this->fixturesPath . 'templates/', ['tmpl']);

        // We can verify by checking that no-images.tmpl content didn't add bad entries
        $this->assertFalse($this->list->has('Just text'));
    }

    public function testParseLayoutFilesWithCallback(): void
    {
        $callbackCalled = false;
        $cb = function (\stdClass $data) use (&$callbackCalled) {
            $callbackCalled = true;
            return $data;
        };

        $this->list->parseLayoutFiles($this->fixturesPath . 'templates/', ['tmpl'], $cb);
        $this->assertTrue($callbackCalled);
    }

    // ── parseCssFiles() ──────────────────────────────────────────

    public function testParseCssFilesFindsImageUrls(): void
    {
        $this->list->parseCssFiles($this->fixturesPath . 'css/', ['css']);

        // main.css has url('../images/banner.svg') → assets/images/banner.svg
        $this->assertTrue($this->list->has('assets/images/banner.svg'));
    }

    public function testParseCssFilesFindsFontUrls(): void
    {
        $this->list->parseCssFiles($this->fixturesPath . 'css/', ['css']);

        // main.css has url('../../fonts/roboto.woff') → fonts/roboto.woff
        $this->assertTrue($this->list->has('fonts/roboto.woff'));
    }

    public function testParseCssFilesAddsPresenceInCss(): void
    {
        $this->list->parseCssFiles($this->fixturesPath . 'css/', ['css']);

        $item = $this->list->get('assets/images/banner.svg');
        $this->assertGreaterThanOrEqual(1, count($item->css));
        $this->assertStringContainsString('css-image', $item->css[0]->type);
    }

    public function testParseCssFilesFontPresenceInCss(): void
    {
        $this->list->parseCssFiles($this->fixturesPath . 'css/', ['css']);

        $item = $this->list->get('fonts/roboto.woff');
        $this->assertGreaterThanOrEqual(1, count($item->css));
        $this->assertStringContainsString('css-font', $item->css[0]->type);
    }

    // ── parseJsFiles() ──────────────────────────────────────────

    public function testParseJsFilesFindsAssetReferences(): void
    {
        $this->list->parseJsFiles($this->fixturesPath . 'js/', ['js']);

        // app.js has "assets/images/icons/icon1.png" and "assets/images/banner.svg"
        $this->assertTrue($this->list->has('assets/images/icons/icon1.png'));
        $this->assertTrue($this->list->has('assets/images/banner.svg'));
    }

    public function testParseJsFilesAddsPresenceInJs(): void
    {
        $this->list->parseJsFiles($this->fixturesPath . 'js/', ['js']);

        $item = $this->list->get('assets/images/icons/icon1.png');
        $this->assertGreaterThanOrEqual(1, count($item->js));
        $this->assertStringContainsString('js-image', $item->js[0]->type);
    }

    public function testParseJsFilesSkipsFilesWithNoAssets(): void
    {
        $beforeCount = count($this->list->all());
        // Only parse no-assets.js by putting it in its own directory
        // Instead, verify that no-assets.js content doesn't create entries
        $this->list->parseJsFiles($this->fixturesPath . 'js/', ['js']);

        // no-assets.js has no assets/ references, so only app.js refs should be found
        $this->assertFalse($this->list->has('hello'));
    }
}
