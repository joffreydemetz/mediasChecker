<?php

/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JDZ\Medias;

use JDZ\Medias\MediasFile;
use Symfony\Component\Finder\Finder;

/**
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class MediasList
{
  const IMAGES_REGEX = 'jpe?g|gif|png|bmp|svg|webp';
  const DOCUMENTS_REGEX = 'pdf|docx?|xlsx?|pptx?|odt|ods|odp';
  const FONTS_REGEX = 'eot|svg|ttf|woff2?';

  private array $files = [];

  public function loadFiles(array $files)
  {
    foreach ($files as $file) {
      $this->add(new MediasFile($file->folder, $file->name, $file->type), true);
    }
  }

  public function parseDatabaseFieldValue(string $value, string $tableName, string $columnName, string $basePath): void
  {
    if (false === ($data = $this->splitFilenameParts($basePath . $value))) {
      return;
    }

    if (false === $this->has($data->folder . $data->file)) {
      $this->add(new MediasFile($data->folder, $data->file, $data->type), false);
    }

    $this->addPresenceInDatabase($data->folder . $data->file, $tableName, $columnName, $data->type . '.mediafile', $data->asset);
  }

  public function parseDatabaseContentValue(string $value, string $tableName, string $columnName)
  {
    $root = $this->loadHtml($value);

    $images = $root->getElementsByTagName('img');
    if ($images->length) {
      foreach ($images as $img) {
        if (false === strpos($img->getAttribute('src'), 'media/')) {
          continue;
        }

        if (false === ($data = $this->parseDomAsset($img->getAttribute('src')))) {
          continue;
        }

        $this->addPresenceInDatabase($data->folder . $data->file, $tableName, $columnName, $data->type . '.content-image');
      }
    }

    $as = $root->getElementsByTagName('a');
    if ($as->length) {
      foreach ($as as $a) {
        if (false === strpos($a->getAttribute('href'), 'media/')) {
          continue;
        }

        if (false === ($data = $this->parseDomAsset($a->getAttribute('href')))) {
          continue;
        }

        $this->addPresenceInDatabase($data->folder . $data->file, $tableName, $columnName, $data->type . '.content-link');
      }
    }
  }

  public function parseLayoutFiles(string $filesPath, array $fileExts = ['tmpl'], ?callable $cb = null)
  {
    $files = $this->files($filesPath, $fileExts, null, true);

    foreach ($files as $file) {
      $file = $this->normalizePath($file);

      if (!@file_exists($file) || false === ($content = \file_get_contents($file))) {
        continue;
      }

      if ('' === $content) {
        continue;
      }

      if (false === strpos($content, '<img ') && false === strpos($content, '<a ')) {
        continue;
      }

      $file = str_replace($filesPath, "", $file);

      $root = $this->loadHtml($content);

      $images = $root->getElementsByTagName('img');
      if ($images->length) {
        foreach ($images as $img) {
          if (false === ($data = $this->parseDomAsset($img->getAttribute('src'), $cb, $file))) {
            continue;
          }
          $this->addPresenceInTemplate($data->folder . $data->file, $file, $data->type . '.template-asset', $data->asset);
        }
      }

      $as = $root->getElementsByTagName('a');
      if ($as->length) {
        foreach ($as as $a) {
          if (false === ($data = $this->parseDomAsset($a->getAttribute('href'), $cb, $file))) {
            continue;
          }
          $this->addPresenceInTemplate($data->folder . $data->file, $file, $data->type . '.template-link', $data->asset);
        }
      }
    }
  }

  public function parseCssFiles(string $filesPath, array $fileExts = ['css'], ?callable $cb = null)
  {
    $files = $this->files($filesPath, $fileExts, null, true);

    $fonts = [];
    $images = [];
    foreach ($files as $file) {
      $file = $this->normalizePath($file);

      if (!@file_exists($file) || false === ($content = \file_get_contents($file))) {
        continue;
      }

      if ('' === $content) {
        continue;
      }

      $file = str_replace($filesPath, "", $file);

      preg_match_all("/url\(['\"]?(((\.\.\/)+)(images|fonts)\/([^'\"]+))['\"\)]?\)/", $content, $m);

      if (count($m[0]) > 0) {
        for ($i = 0, $n = count($m[0]); $i < $n; $i++) {
          if ('fonts' === $m[4][$i]) {
            $type = 'fonts';
            $value = str_replace('../../fonts/', 'fonts/', $m[1][$i]);

            if (false !== ($data = $this->parseDomAsset($value, $cb, $file))) {
              $this->addPresenceInCss($data->folder . $data->file, $file, $data->type . '.css-font', $data->asset);
            }

            continue;
          }

          if ('images' === $m[4][$i]) {
            $type = 'assets';
            $value = str_replace('../images/', 'assets/images/', $m[1][$i]);
            // d($value);

            if (false !== ($data = $this->parseDomAsset($value, $cb, $file))) {
              $this->addPresenceInCss($data->folder . $data->file, $file, $data->type . '.css-image', $data->asset);
            }

            continue;
          }

          throw new \Exception('Unknown type: ' . $m[4][$i] . ' for ' . $m[1][$i]);
        }
      }
    }
  }

  public function parseJsFiles(string $filesPath, array $fileExts = ['js'], ?callable $cb = null)
  {
    $files = $this->files($filesPath, $fileExts, null, true);

    foreach ($files as $file) {
      $file = $this->normalizePath($file);

      if (!@file_exists($file) || false === ($content = \file_get_contents($file))) {
        continue;
      }

      if ('' === $content) {
        continue;
      }

      $file = str_replace($filesPath, "", $file);

      preg_match_all("/(assets\/images\/[^\"]+)/", $content, $m);

      if (count($m[0]) > 0) {
        for ($i = 0, $n = count($m[0]); $i < $n; $i++) {
          $type = 'assets';
          $value = $m[1][$i];

          if (false !== ($data = $this->parseDomAsset($value, $cb, $file))) {
            $this->addPresenceInJs($data->folder . $data->file, $file, $data->type . '.js-image', $data->asset);
          }
        }
      }
    }
  }

  public function add(MediasFile $item, bool $exists = false): void
  {
    $path = str_replace('.', '_', $item->folder . $item->name);

    if (!isset($this->files[$path])) {
      $this->files[$path] = $item;
    }

    if (true === $exists) {
      $item->isPhysical();
    }
  }

  public function has(string $path): bool
  {
    $path = str_replace('.', '_', $path);
    return isset($this->files[$path]);
  }

  public function get(string $path): object|false
  {
    $path = str_replace('.', '_', $path);

    if (true === $this->has($path)) {
      return $this->files[$path];
    }

    return false;
  }

  public function all(): array
  {
    return $this->files;
  }

  public function getMediaFolders(string $basePath, string $path, array $arbo = []): array
  {
    foreach ($this->folders($basePath . $path) as $folder) {
      $arbo[] = $path . $folder . '/';
      $arbo = $this->getMediaFolders($basePath, $path . $folder . '/', $arbo);
    }
    return $arbo;
  }

  public function files(string $dir, array $extensions = [], ?string $depth = '==0', bool $absolutePath = false): array
  {
    $finder = Finder::create()
      ->files()
      ->ignoreUnreadableDirs()
      ->notName(['.DS_Store', 'Thumbs.db'])
      ->notPath(['__MACOSX']);

    if ($depth) {
      $finder->depth($depth);
    }

    $finder->filter(function (\SplFileInfo $file) {
      if ('_' === substr($file->getFilename(), 0, 1)) {
        return false;
      }
    });

    if ($extensions) {
      $finder->name('/\.(' . implode('|', $extensions) . ')$/');
    }

    $files = [];

    if (\is_dir($dir)) {
      $finder->in($dir);

      if ($finder->hasResults()) {
        foreach ($finder as $file) {
          if (true === $absolutePath) {
            $files[] = $file->getPathname();
          } else {
            $files[] = $file->getFilename();
          }
        }
      }
    }

    return $files;
  }

  private function addPresenceInDatabase(string $path, string $table, string $column, string $type, bool $asset = false): void
  {
    // $path = str_replace('.', '_', $path);

    if (false === ($item = $this->get($path))) {
      throw new \Exception('MediasFile ' . $path . ' is not set');
    }

    $item->db[] = (object)[
      'path' => $table . '.' . $column,
      'type' => $type,
      'asset' => $asset,
    ];
    $item->occurences++;
  }

  private function addPresenceInTemplate(string $path, string $filepath, string $type, bool $asset = true): void
  {
    if (false === ($item = $this->get($path))) {
      throw new \Exception('MediasFile ' . $path . ' is not set');
    }

    $item->tmpl[] = (object)[
      'path' => $filepath,
      'type' => $type,
      'asset' => $asset,
    ];
    $item->occurences++;
  }

  private function addPresenceInCss(string $path, string $filepath, string $type, bool $asset = true): void
  {
    if (false === ($item = $this->get($path))) {
      throw new \Exception('MediasFile ' . $path . ' is not set');
    }

    $item->css[] = (object)[
      'path' => $filepath,
      'type' => $type,
      'asset' => $asset,
    ];
    $item->occurences++;
  }

  private function addPresenceInJs(string $path, string $filepath, string $type, bool $asset = true): void
  {
    if (false === ($item = $this->get($path))) {
      throw new \Exception('MediasFile ' . $path . ' is not set');
    }

    $item->js[] = (object)[
      'path' => $filepath,
      'type' => $type,
      'asset' => $asset,
    ];
    $item->occurences++;
  }

  private function loadHtml(string $html): \DOMDocument
  {
    \libxml_use_internal_errors(true);
    $root = new \DOMDocument('1.0', 'utf-8');
    $root->preserveWhiteSpace = false;
    $root->loadHtml('<html>' . $html . '</html>', \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
    return $root;
  }

  private function parseDomAsset(string $value, ?callable $cb = null, string $tmpl = ''): \stdClass|false
  {
    if (false === ($data = $this->splitFilenameParts($value))) {
      return false;
    }

    if (null !== $cb) {
      $data = $cb((object)array_merge((array)$data, ['filename' => $tmpl]));
      $value = $data->folder . $data->file;
    }

    if (false === $this->has($value)) {
      $this->add(new MediasFile($data->folder, $data->file, $data->type), false);
    }

    return $data;
  }

  private function splitFilenameParts(string $file, bool $onlyImages = false): \stdClass|false
  {
    $type = 'media';
    $folder = '';
    $root = false;
    $asset = false;
    $up = false;

    // that's for a jizy asset
    // my homemade js platform
    if (preg_match("/\{\{ jizyAsset\(\'([^\']+)\'\) \}\}/", $file, $m)) {
      $asset = true;
      $file = $m[1];
    }

    // ignore external paths
    if (preg_match("/^https?:\\//", $file)) {
      return false;
    }

    // for css files
    // access to global assets from folder domain
    if (preg_match("/^\.\.\/(.+)$/", $file, $m)) {
      $up = true;
      $file = $m[1];
    }

    $file = ltrim($file, '/');
    // now starts with : 
    //   - media/
    //   - fonts/
    //   - assets/
    //   - users/
    //   - vendor/

    // ignore the vendor folder
    if (preg_match("/^vendor\/.+/", $file)) {
      return false;
    }

    // not an image
    if (true === $onlyImages && !preg_match("/^.+\.(" . self::IMAGES_REGEX . ")$/", $file)) {
      return false;
    }

    // not a file
    if (!preg_match("/^.+\.((" . self::IMAGES_REGEX . ")|(" . self::DOCUMENTS_REGEX . ")|(" . self::FONTS_REGEX . "))$/", $file)) {
      return false;
    }

    if (preg_match("/^fonts\/(.+)$/", $file, $m)) {
      $type = 'fonts';
      $folder = '';
      $file = $m[1];
    } elseif (preg_match("/^assets\/(.+)\/([^\/]+)$/", $file, $m)) {
      $type = 'assets';
      $folder = $m[1] . '/';
      $file = $m[2];
    } elseif (preg_match("/^assets\/(.+)$/", $file, $m)) {
      $type = 'assets';
      $folder = '';
      $file = $m[1];
    } elseif (preg_match("/^users\/(.+)\/([^\/]+)$/", $file, $m)) {
      $type = 'users';
      $folder = $m[1] . '/';
      $file = $m[2];
    } elseif (preg_match("/^users\/(.+)$/", $file, $m)) {
      $type = 'users';
      $folder = '';
      $file = $m[1];
    } elseif (preg_match("/^media\/(.+)\/([^\/]+)$/", $file, $m)) {
      $folder = $m[1] . '/';
      $file = $m[2];
    } elseif (preg_match("/^media\/(.+)$/", $file, $m)) {
      $root = true;
      $folder = '';
      $file = $m[1];
    } else {
      return false;
    }

    return (object)[
      'folder' => $type . '/' . $folder,
      'file' => $file,
      'type' => $type,
      'asset' => $asset,
      'up' => $up,
      'root' => $root,
    ];
  }

  private function folders(string $dir): array
  {
    $finder = Finder::create()
      ->directories()
      ->ignoreUnreadableDirs()
      ->depth('== 0');

    $finder->filter(function (\SplFileInfo $file) {
      if ('_' === substr($file->getFilename(), 0, 1)) {
        return false;
      }
    });

    $folders = [];

    if (\is_dir($dir)) {
      $finder->in($dir);

      if ($finder->hasResults()) {
        foreach ($finder as $folder) {
          $folders[] = $folder->getFilename();
        }
      }
    }

    return $folders;
  }

  private function toIterable($list): iterable
  {
    return \is_array($list) || $list instanceof \Traversable ? $list : [$list];
  }

  private function normalizePath(string $path): string
  {
    return str_replace('\\', '/', $path);
  }
}
