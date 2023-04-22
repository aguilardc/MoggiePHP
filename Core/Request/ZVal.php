<?php

# ============================================================================ #

/**
 *  M O G G I E: a PHP micro-framework.
 *
 * @copyright   Copyright (c) 2023, Nevison Aguilar <aguilardc1105@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 *
 */

namespace Core\Request;

final class ZVal
{
    public mixed $context;
    private mixed $data;
    private int $position = 0;

    public function stream_open(): bool
    {
        $this->data = stream_context_get_options($this->context)['ZValStream']['data'];
        stream_context_set_option($this->context, 'ZValStream', 'data', null);

        return true;
    }


    public function stream_read(int $count): string
    {
        $result = substr($this->data, $this->position, $count);
        $this->position += strlen($result);

        return $result;
    }


    public function stream_write(string $data): int
    {
        $this->data = substr_replace($this->data, $data, $this->position, strlen($data));
        $this->position += strlen($data);

        return strlen($data);
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    public function stream_eof(): bool
    {
        return strlen($this->data) <= $this->position;
    }

    public function stream_stat(): array
    {
        return [
            'mode' => 33206, // POSIX_S_IFREG | 0666
            'nlink' => 1,
            'rdev' => -1,
            'size' => strlen($this->data),
            'blksize' => -1,
            'blocks' => -1,
        ];
    }

    public function stream_seek(int $offset, int $whence): bool
    {
        if (SEEK_SET === $whence && (0 <= $offset && strlen($this->data) >= $offset)) {
            $this->position = $offset;
        } elseif (SEEK_CUR === $whence && 0 <= $offset) {
            $this->position += $offset;
        } elseif (SEEK_END === $whence && (0 > $offset && 0 <= $offset = strlen($this->data) + $offset)) {
            $this->position = $offset;
        } else {
            return false;
        }

        return true;
    }

    public function stream_set_option(): bool
    {
        return true;
    }

    public function stream_truncate(int $new_size): bool
    {
        if ($new_size) {
            $this->data = substr($this->data, 0, $new_size);
            $this->position = min($this->position, $new_size);
        } else {
            $this->data = '';
            $this->position = 0;
        }

        return true;
    }
}
