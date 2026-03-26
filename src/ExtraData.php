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
class ExtraData
{
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function sets(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function set(string $name, mixed $value): self
    {
        $this->data[$name] = $value;
        return $this;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }
}
