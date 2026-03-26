<?php

namespace JDZ\Medias\Tests\Unit;

use JDZ\Medias\Medias;
use JDZ\Medias\MediasFile;
use JDZ\Medias\MediasList;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MediasListTest extends TestCase
{
    private MediasList $list;

    protected function setUp(): void
    {
        $this->list = new MediasList();
    }

    protected function tearDown(): void
    {
        Medias::$mediaRootFolder = 'media';
    }

    // ── CRUD operations ──────────────────────────────────────────

    public function testAddAndHas(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $this->list->add($file);

        $this->assertTrue($this->list->has('media/photo.jpg'));
    }

    public function testHasReturnsFalseForMissing(): void
    {
        $this->assertFalse($this->list->has('nonexistent'));
    }

    public function testGetReturnsMediasFile(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $this->list->add($file);

        $result = $this->list->get('media/photo.jpg');
        $this->assertSame($file, $result);
    }

    public function testGetReturnsFalseForMissing(): void
    {
        $this->assertFalse($this->list->get('nonexistent'));
    }

    public function testAllReturnsAllFiles(): void
    {
        $this->list->add(new MediasFile('media/', 'a.jpg'));
        $this->list->add(new MediasFile('media/', 'b.png'));

        $this->assertCount(2, $this->list->all());
    }

    public function testAllReturnsEmptyWhenNoFiles(): void
    {
        $this->assertSame([], $this->list->all());
    }

    public function testAddDoesNotOverwriteExisting(): void
    {
        $file1 = new MediasFile('media/', 'photo.jpg');
        $file2 = new MediasFile('media/', 'photo.jpg');
        $this->list->add($file1);
        $this->list->add($file2);

        $this->assertSame($file1, $this->list->get('media/photo.jpg'));
    }

    public function testAddWithExistsTrueSetsPhysical(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $this->list->add($file, true);
        $this->assertTrue($file->physical);
    }

    public function testAddWithExistsFalseDoesNotSetPhysical(): void
    {
        $file = new MediasFile('media/', 'photo.jpg');
        $this->list->add($file, false);
        $this->assertFalse($file->physical);
    }

    // ── loadFiles ────────────────────────────────────────────────

    public function testLoadFilesAddsAllFiles(): void
    {
        $files = [
            (object)['folder' => 'media/', 'name' => 'a.jpg', 'type' => 'media'],
            (object)['folder' => 'media/', 'name' => 'b.png', 'type' => 'media'],
        ];
        $this->list->loadFiles($files);

        $this->assertCount(2, $this->list->all());
        $this->assertTrue($this->list->has('media/a.jpg'));
        $this->assertTrue($this->list->has('media/b.png'));
    }

    public function testLoadFilesSetsPhysicalTrue(): void
    {
        $files = [
            (object)['folder' => 'media/', 'name' => 'a.jpg', 'type' => 'media'],
        ];
        $this->list->loadFiles($files);

        $item = $this->list->get('media/a.jpg');
        $this->assertTrue($item->physical);
    }

    public function testLoadFilesWithEmptyArray(): void
    {
        $this->list->loadFiles([]);
        $this->assertSame([], $this->list->all());
    }

    // ── parseDatabaseFieldValue ──────────────────────────────────

    public function testParseDatabaseFieldValueMediaPath(): void
    {
        $this->list->parseDatabaseFieldValue('photo.jpg', 'articles', 'image', 'media/');

        $this->assertTrue($this->list->has('media/photo.jpg'));
        $item = $this->list->get('media/photo.jpg');
        $this->assertSame('media/', $item->folder);
        $this->assertSame('photo.jpg', $item->name);
        $this->assertSame('media', $item->type);
    }

    public function testParseDatabaseFieldValueAddsToDbArray(): void
    {
        $this->list->parseDatabaseFieldValue('photo.jpg', 'articles', 'image', 'media/');

        $item = $this->list->get('media/photo.jpg');
        $this->assertCount(1, $item->db);
        $this->assertSame('articles.image', $item->db[0]->path);
        $this->assertSame('media.mediafile', $item->db[0]->type);
    }

    public function testParseDatabaseFieldValueIncrementsOccurences(): void
    {
        $this->list->parseDatabaseFieldValue('photo.jpg', 'articles', 'image', 'media/');

        $item = $this->list->get('media/photo.jpg');
        $this->assertSame(1, $item->occurences);
    }

    public function testParseDatabaseFieldValueExistingFileNotDuplicated(): void
    {
        $this->list->parseDatabaseFieldValue('photo.jpg', 'articles', 'image', 'media/');
        $this->list->parseDatabaseFieldValue('photo.jpg', 'pages', 'thumbnail', 'media/');

        $this->assertCount(1, $this->list->all());
        $item = $this->list->get('media/photo.jpg');
        $this->assertCount(2, $item->db);
        $this->assertSame(2, $item->occurences);
    }

    public function testParseDatabaseFieldValueExternalUrlIgnored(): void
    {
        $this->list->parseDatabaseFieldValue('https://example.com/img.jpg', 'articles', 'image', '');
        $this->assertSame([], $this->list->all());
    }

    public function testParseDatabaseFieldValueVendorPathIgnored(): void
    {
        $this->list->parseDatabaseFieldValue('vendor/lib/image.jpg', 'articles', 'image', '');
        $this->assertSame([], $this->list->all());
    }

    public function testParseDatabaseFieldValueNonMediaExtensionIgnored(): void
    {
        $this->list->parseDatabaseFieldValue('readme.txt', 'articles', 'file', 'media/');
        $this->assertSame([], $this->list->all());
    }

    public function testParseDatabaseFieldValueMediaSubfolder(): void
    {
        $this->list->parseDatabaseFieldValue('gallery/img1.jpg', 'articles', 'image', 'media/');

        $this->assertTrue($this->list->has('media/gallery/img1.jpg'));
        $item = $this->list->get('media/gallery/img1.jpg');
        $this->assertSame('media/gallery/', $item->folder);
        $this->assertSame('img1.jpg', $item->name);
    }

    public function testParseDatabaseFieldValueAssetsPath(): void
    {
        $this->list->parseDatabaseFieldValue('banner.svg', 'pages', 'image', 'assets/images/');

        $this->assertTrue($this->list->has('assets/images/banner.svg'));
        $item = $this->list->get('assets/images/banner.svg');
        $this->assertSame('assets', $item->type);
    }

    public function testParseDatabaseFieldValueFontsPath(): void
    {
        $this->list->parseDatabaseFieldValue('roboto.woff', 'config', 'font', 'fonts/');

        $this->assertTrue($this->list->has('fonts/roboto.woff'));
        $item = $this->list->get('fonts/roboto.woff');
        $this->assertSame('fonts', $item->type);
    }

    public function testParseDatabaseFieldValueUsersPath(): void
    {
        $this->list->parseDatabaseFieldValue('avatar.jpg', 'users', 'photo', 'users/john/');

        $this->assertTrue($this->list->has('users/john/avatar.jpg'));
        $item = $this->list->get('users/john/avatar.jpg');
        $this->assertSame('users', $item->type);
    }

    // ── parseDatabaseContentValue ────────────────────────────────

    public function testParseDatabaseContentValueFindsImages(): void
    {
        // Pre-add the file so addPresenceInDatabase can find it
        $this->list->add(new MediasFile('media/', 'photo.jpg'));

        $html = '<p><img src="media/photo.jpg" alt="test"></p>';
        $this->list->parseDatabaseContentValue($html, 'articles', 'content');

        $item = $this->list->get('media/photo.jpg');
        $this->assertCount(1, $item->db);
        $this->assertStringContainsString('content-image', $item->db[0]->type);
    }

    public function testParseDatabaseContentValueFindsLinks(): void
    {
        $this->list->add(new MediasFile('media/', 'document.pdf'));

        $html = '<p><a href="media/document.pdf">Download</a></p>';
        $this->list->parseDatabaseContentValue($html, 'articles', 'content');

        $item = $this->list->get('media/document.pdf');
        $this->assertCount(1, $item->db);
        $this->assertStringContainsString('content-link', $item->db[0]->type);
    }

    public function testParseDatabaseContentValueIgnoresExternalImages(): void
    {
        $html = '<p><img src="https://example.com/img.jpg" alt="External"></p>';
        $this->list->parseDatabaseContentValue($html, 'articles', 'content');

        $this->assertSame([], $this->list->all());
    }

    public function testParseDatabaseContentValueIgnoresNonMediaImages(): void
    {
        $html = '<p><img src="other/photo.jpg" alt="Other"></p>';
        $this->list->parseDatabaseContentValue($html, 'articles', 'content');

        $this->assertSame([], $this->list->all());
    }

    public function testParseDatabaseContentValueMultipleElements(): void
    {
        $this->list->add(new MediasFile('media/', 'a.jpg'));
        $this->list->add(new MediasFile('media/', 'b.pdf'));

        $html = '<img src="media/a.jpg"><a href="media/b.pdf">Link</a>';
        $this->list->parseDatabaseContentValue($html, 'articles', 'content');

        $this->assertCount(1, $this->list->get('media/a.jpg')->db);
        $this->assertCount(1, $this->list->get('media/b.pdf')->db);
    }

    public function testParseDatabaseContentValueEmptyHtml(): void
    {
        $this->list->parseDatabaseContentValue('', 'articles', 'content');
        $this->assertSame([], $this->list->all());
    }

    public function testParseDatabaseContentValueRespectsMediaRootFolder(): void
    {
        Medias::$mediaRootFolder = 'custom';

        $html = '<p><img src="media/photo.jpg" alt="Should be ignored"></p>';
        $this->list->parseDatabaseContentValue($html, 'articles', 'content');

        // Should be ignored because mediaRootFolder is 'custom', not 'media'
        $this->assertSame([], $this->list->all());
    }

    // ── splitFilenameParts via Reflection ─────────────────────────

    private function callSplitFilenameParts(string $file, bool $onlyImages = false): \stdClass|false
    {
        $method = new \ReflectionMethod(MediasList::class, 'splitFilenameParts');
        return $method->invoke($this->list, $file, $onlyImages);
    }

    public function testSplitFilenamePartsMediaRoot(): void
    {
        $data = $this->callSplitFilenameParts('media/photo.jpg');
        $this->assertSame('media/', $data->folder);
        $this->assertSame('photo.jpg', $data->file);
        $this->assertSame('media', $data->type);
        $this->assertTrue($data->root);
    }

    public function testSplitFilenamePartsMediaSubfolder(): void
    {
        $data = $this->callSplitFilenameParts('media/gallery/photo.jpg');
        $this->assertSame('media/gallery/', $data->folder);
        $this->assertSame('photo.jpg', $data->file);
        $this->assertSame('media', $data->type);
        $this->assertFalse($data->root);
    }

    public function testSplitFilenamePartsFonts(): void
    {
        $data = $this->callSplitFilenameParts('fonts/roboto.woff');
        $this->assertSame('fonts/', $data->folder);
        $this->assertSame('roboto.woff', $data->file);
        $this->assertSame('fonts', $data->type);
    }

    public function testSplitFilenamePartsAssetsSubfolder(): void
    {
        $data = $this->callSplitFilenameParts('assets/images/icons/icon1.png');
        $this->assertSame('assets/images/icons/', $data->folder);
        $this->assertSame('icon1.png', $data->file);
        $this->assertSame('assets', $data->type);
    }

    public function testSplitFilenamePartsUsers(): void
    {
        $data = $this->callSplitFilenameParts('users/john/avatar.jpg');
        $this->assertSame('users/john/', $data->folder);
        $this->assertSame('avatar.jpg', $data->file);
        $this->assertSame('users', $data->type);
    }

    public function testSplitFilenamePartsJizyAsset(): void
    {
        $data = $this->callSplitFilenameParts("{{ jizyAsset('media/photo.jpg') }}");
        $this->assertSame('media/', $data->folder);
        $this->assertSame('photo.jpg', $data->file);
        $this->assertTrue($data->asset);
    }

    public function testSplitFilenamePartsJizyImg(): void
    {
        $data = $this->callSplitFilenameParts("{{ jizyImg('media/photo.jpg') }}");
        $this->assertSame('media/', $data->folder);
        $this->assertSame('photo.jpg', $data->file);
        $this->assertTrue($data->asset);
    }

    public function testSplitFilenamePartsExternalUrlReturnsFalse(): void
    {
        $this->assertFalse($this->callSplitFilenameParts('https://example.com/image.jpg'));
        $this->assertFalse($this->callSplitFilenameParts('http://example.com/image.jpg'));
    }

    public function testSplitFilenamePartsVendorReturnsFalse(): void
    {
        $this->assertFalse($this->callSplitFilenameParts('vendor/lib/image.jpg'));
    }

    public function testSplitFilenamePartsUnknownPathReturnsFalse(): void
    {
        $this->assertFalse($this->callSplitFilenameParts('unknown/path/file.jpg'));
    }

    public function testSplitFilenamePartsNonMediaExtensionReturnsFalse(): void
    {
        $this->assertFalse($this->callSplitFilenameParts('media/readme.txt'));
    }

    public function testSplitFilenamePartsLeadingSlash(): void
    {
        $data = $this->callSplitFilenameParts('/media/photo.jpg');
        $this->assertSame('media/', $data->folder);
        $this->assertSame('photo.jpg', $data->file);
    }

    public function testSplitFilenamePartsRelativePath(): void
    {
        $data = $this->callSplitFilenameParts('../fonts/roboto.woff');
        $this->assertNotFalse($data);
        $this->assertTrue($data->up);
        $this->assertSame('fonts', $data->type);
    }

    public function testSplitFilenamePartsOnlyImagesFilterRejectsDocuments(): void
    {
        $this->assertFalse($this->callSplitFilenameParts('media/document.pdf', true));
    }

    public function testSplitFilenamePartsOnlyImagesFilterAcceptsImages(): void
    {
        $data = $this->callSplitFilenameParts('media/photo.jpg', true);
        $this->assertNotFalse($data);
    }

    #[DataProvider('imageExtensionsProvider')]
    public function testSplitFilenamePartsAllImageExtensions(string $path): void
    {
        $data = $this->callSplitFilenameParts($path);
        $this->assertNotFalse($data);
    }

    public static function imageExtensionsProvider(): array
    {
        return [
            'jpg' => ['media/file.jpg'],
            'jpeg' => ['media/file.jpeg'],
            'gif' => ['media/file.gif'],
            'png' => ['media/file.png'],
            'bmp' => ['media/file.bmp'],
            'svg' => ['media/file.svg'],
            'webp' => ['media/file.webp'],
        ];
    }

    #[DataProvider('documentExtensionsProvider')]
    public function testSplitFilenamePartsAllDocumentExtensions(string $path): void
    {
        $data = $this->callSplitFilenameParts($path);
        $this->assertNotFalse($data);
    }

    public static function documentExtensionsProvider(): array
    {
        return [
            'pdf' => ['media/file.pdf'],
            'doc' => ['media/file.doc'],
            'docx' => ['media/file.docx'],
            'xls' => ['media/file.xls'],
            'xlsx' => ['media/file.xlsx'],
            'pptx' => ['media/file.pptx'],
            'odt' => ['media/file.odt'],
            'ods' => ['media/file.ods'],
            'odp' => ['media/file.odp'],
        ];
    }

    #[DataProvider('fontExtensionsProvider')]
    public function testSplitFilenamePartsAllFontExtensions(string $path): void
    {
        $data = $this->callSplitFilenameParts($path);
        $this->assertNotFalse($data);
    }

    public static function fontExtensionsProvider(): array
    {
        return [
            'eot' => ['fonts/file.eot'],
            'svg' => ['fonts/file.svg'],
            'ttf' => ['fonts/file.ttf'],
            'woff' => ['fonts/file.woff'],
            'woff2' => ['fonts/file.woff2'],
        ];
    }

    // ── Constants ────────────────────────────────────────────────

    public function testImagesRegexConstant(): void
    {
        $this->assertSame('jpe?g|gif|png|bmp|svg|webp', MediasList::IMAGES_REGEX);
    }

    public function testDocumentsRegexConstant(): void
    {
        $this->assertSame('pdf|docx?|xlsx?|pptx?|odt|ods|odp', MediasList::DOCUMENTS_REGEX);
    }

    public function testFontsRegexConstant(): void
    {
        $this->assertSame('eot|svg|ttf|woff2?', MediasList::FONTS_REGEX);
    }
}
