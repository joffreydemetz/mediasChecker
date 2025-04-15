<?php

/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JDZ\Medias;

/**
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class MediasFile
{
  public string $folder;
  public string $name;
  public string $type;

  public bool $root;
  public bool $ignore = false;
  public bool $physical = false;
  public int $occurences = 0;
  public array $db = [];
  public array $tmpl = [];
  public array $css = [];
  public array $js = [];

  public function __construct(string $folder, string $name, string $type = 'media')
  {
    $this->folder = $folder;
    $this->name = $name;
    $this->type = $type;
  }

  public function isPhysical(bool $physical = true)
  {
    $this->physical = true;
    return $this;
  }

  public function getOccurences(string $rootPath): array
  {
    $occurences = [];

    foreach ($this->db as $el) {
      $occurences[] = $el->path . ' (' . $el->type . ')';
    }

    foreach ($this->tmpl as $el) {
      $occurences[] = str_replace($rootPath, '', $el->path) . ' (' . $el->type . ')';
    }

    foreach ($this->css as $el) {
      $occurences[] = str_replace($rootPath, '', $el->path) . ' (' . $el->type . ')';
    }

    return array_unique($occurences);
  }
}
