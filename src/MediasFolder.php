<?php

/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JDZ\Medias;

use JDZ\Utils\Data as jData;

/**
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class MediasFolder
{
  public string $name;
  public string $path;
  public string $type;
  public jData $extraData;

  public function __construct(string $name, string $path, string $type, array $extraData = [])
  {
    $this->name = $name;
    $this->path = $path;
    $this->type = $type;
    $this->extraData = new jData();

    if ($extraData) {
      $this->extraData->sets($extraData);
    }
  }

  public function all(): array
  {
    return array_merge([
      'name' => $this->name,
      'path' => $this->path,
      'type' => $this->type,
    ], $this->extraData->all());
  }


  public function sets(array $properties)
  {
    foreach ($properties as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  public function set(string $name, mixed $value)
  {
    if (property_exists($this, $name)) {
      $this->{$name} = $value;
    } else {
      $this->extraData->set($name, $value);
    }
    return false;
  }
}
