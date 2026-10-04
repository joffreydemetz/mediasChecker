# jdz/mediaschecker

Check media usage on a website: inventory the files under the public folder (`media/`, `fonts/`, `assets/images/`, `users/`), then record every place they are referenced — database values and HTML content, templates, CSS and JS files. The result tells you which files are used, which ones are unused, and which references point to a missing file.

## Installation

```bash
composer require jdz/mediaschecker
```

## Requirements

- PHP 8.2 or higher
- symfony/finder ^7.4

## Usage

Adapted from `examples/example_external_folders.php`:

```php
use JDZ\Medias\Medias;
use JDZ\Medias\MediasFolder;

$publicPath = '/var/www/site/public';

$medias = new Medias($publicPath);
$list = $medias->getMedialist();

// 1. Folders to scan (path relative to the public folder, type, extra data)
$folders = [];
$folders['media/'] = new MediasFolder('media/', 'media', ['width' => 1200, 'height' => 1200]);
foreach ($list->getMediaFolders($publicPath . '/', 'media/') as $path) {
    $folders[$path] ??= new MediasFolder($path, 'media');
}
$folders['fonts/'] = new MediasFolder('fonts/', 'fonts');
$folders['assets/images/'] = new MediasFolder('assets/images/', 'assets');

// 2. Files on disk — registered as physical
$list->loadFiles($medias->loadMediafiles($folders));

// 3. References
$list->parseLayoutFiles('/var/www/site/templates', ['tmpl']);
$list->parseCssFiles($publicPath . '/css');
$list->parseJsFiles($publicPath . '/js');
$list->parseDatabaseFieldValue('gallery/img1.jpg', 'article', 'image', 'media/');
$list->parseDatabaseContentValue($articleHtml, 'article', 'content');

// 4. Report
foreach ($list->all() as $file) {
    if ($file->physical && 0 === $file->occurences) {
        // unused file
    }
    if (!$file->physical) {
        // referenced, but not on disk
    }
}
```

## API

Namespace `JDZ\Medias\`.

| Class | Description |
|-------|-------------|
| `Medias` | Entry point: `new Medias(string $publicPath)`, `getMedialist()`, `loadMediaFolders(array $configFolders = [])` (default folder set: `media/` and its subfolders, `fonts/`, `assets/images/` and its subfolders), `loadMediafiles(array $folders)`. The static `Medias::$mediaRootFolder` (default `media`) is the folder HTML content is matched against. |
| `MediasList` | The registry: `loadFiles()`, `parseLayoutFiles()`, `parseCssFiles()`, `parseJsFiles()`, `parseDatabaseFieldValue()`, `parseDatabaseContentValue()`, `add()`, `has()`, `get()`, `all()`, `getMediaFolders()`, `files()`. Files and folders whose name starts with `_` are skipped. |
| `MediasFile` | One file: `folder`, `name`, `type`, `physical`, `occurences`, and the `db` / `tmpl` / `css` / `js` reference lists; `getOccurences(string $rootPath)`. |
| `MediasFolder` | One folder to scan: `path`, `type` and free-form extra data (`all()`, `set()`, `sets()`). |
| `ExtraData` | Key/value bag behind `MediasFolder`. |

`getMediaFolders(string $basePath, string $path)` concatenates both as-is: give the base path a trailing slash (the same goes for the `Medias` public path when you use `loadMediaFolders()`).

The parsers take an optional callback (`?callable $cb`) that receives each parsed reference (with the source `filename`) and returns it, so a site can rewrite paths before they are registered.

## Testing

```bash
vendor/bin/phpunit
```

## License

MIT — see the LICENSE file.
