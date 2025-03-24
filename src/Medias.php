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

    $folders['media/'] = new MediasFolder('media/', 'media', [
      'noPng' => false,
      'system' => true,
      'yml' => false,
      'width' => 1200,
      'height' => 1200,
    ]);

    foreach ($configFolders as $folder) {
      $extraData = (array)$folder;
      unset($extraData['name']);
      unset($extraData['path']);
      unset($extraData['type']);
      $extraData['yml'] = true;

      $folders['media/' . $folder->name . '/'] = new MediasFolder('media/' . $folder->name . '/', 'media', $extraData);
    }

    $mediaFolders = $this->mediaList->getMediaFolders($this->publicPath, 'media/');
    foreach ($mediaFolders as $mediaFolder) {
      if (!isset($folders[$mediaFolder])) {
        $folders[$mediaFolder] = new MediasFolder($mediaFolder, 'media', [
          'noPng' => false,
          'system' => true,
          'yml' => false,
          'width' => 1200,
          'height' => 1200,
        ]);
      }
    }

    $folders['fonts/'] = new MediasFolder('fonts/', 'fonts', [
      'noPng' => true,
      'system' => true,
      'yml' => false,
    ]);

    $folders['assets/images/'] = new MediasFolder('assets/images/', 'assets', [
      'noPng' => false,
      'system' => true,
      'width' => 1200,
      'height' => 1200,
      'yml' => false,
    ]);

    $assetsFolders = $this->mediaList->getMediaFolders($this->publicPath, 'assets/images/');
    foreach ($assetsFolders as $assetFolder) {
      if (!isset($folders[$assetFolder])) {
        $folders[$assetFolder] = new MediasFolder($assetFolder, 'assets', [
          'noPng' => false,
          'system' => true,
          'yml' => false,
          'width' => 1200,
          'height' => 1200,
        ]);
      }
    }

    $usersFolders = $this->mediaList->getMediaFolders($this->publicPath, 'users/');
    foreach ($usersFolders as $usersFolder) {
      if (!isset($folders[$usersFolder])) {
        $folders[$usersFolder] = new MediasFolder($usersFolder, 'assets', [
          'noPng' => false,
          'system' => false,
          'yml' => false,
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
