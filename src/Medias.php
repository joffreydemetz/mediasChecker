<?php

/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JDZ\Medias;

use JDZ\Medias\MediasList;
use JDZ\Medias\MediasFolder;

/**
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class Medias
{
  private const DEFAULT_MEDIA_FOLDER = 'media';
  public static string $mediaRootFolder = self::DEFAULT_MEDIA_FOLDER;

  protected string $publicPath;
  protected MediasList $mediaList;

  public function __construct(string $publicPath)
  {
    $this->publicPath = $publicPath;
    $this->mediaList = new MediasList();
  }

  public function getMedialist(): MediasList
  {
    return $this->mediaList;
  }

  public function loadMediaFolders(array $configFolders = []): array
  {
    $folders = [];

    $folders[self::$mediaRootFolder . '/'] = new MediasFolder(self::$mediaRootFolder . '/', 'media', [
      'width' => 1200,
      'height' => 1200,
    ]);

    foreach ($configFolders as $folder) {
      $extraData = (array)$folder;
      unset($extraData['name']);
      unset($extraData['path']);
      unset($extraData['type']);

      $folders[self::$mediaRootFolder . '/' . $folder->name . '/'] = new MediasFolder(self::$mediaRootFolder . '/' . $folder->name . '/', 'media', $extraData);
    }

    $mediaFolders = $this->mediaList->getMediaFolders($this->publicPath, self::$mediaRootFolder . '/');
    foreach ($mediaFolders as $mediaFolder) {
      if (!isset($folders[$mediaFolder])) {
        $folders[$mediaFolder] = new MediasFolder($mediaFolder, 'media', [
          'width' => 1200,
          'height' => 1200,
        ]);
      }
    }

    $folders['fonts/'] = new MediasFolder('fonts/', 'fonts');

    $folders['assets/images/'] = new MediasFolder('assets/images/', 'assets', [
      'width' => 1200,
      'height' => 1200,
    ]);

    $assetsFolders = $this->mediaList->getMediaFolders($this->publicPath, 'assets/images/');
    foreach ($assetsFolders as $assetFolder) {
      if (!isset($folders[$assetFolder])) {
        $folders[$assetFolder] = new MediasFolder($assetFolder, 'assets', [
          'width' => 1200,
          'height' => 1200,
        ]);
      }
    }

    return $folders;
  }

  public function loadMediafiles(array $folders): array
  {
    $files = [];
    foreach ($folders as $folder) {
      foreach ($this->mediaList->files($this->publicPath . '/' . $folder->path) as $file) {
        $path = str_replace('.', '_', $folder->path . $file);
        $files[$path] = (object)[
          'folder' => $folder->path,
          'name' => $file,
          'type' => $folder->type,
        ];
      }
    }

    return $files;
  }
}
