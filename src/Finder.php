<?php

declare(strict_types=1);

namespace chaser\utils;

/**
 * 文件扫描器
 *
 * @package chaser\utils
 */
class Finder
{
    /**
     * 目录解析列表
     *
     * @var array
     */
    private static array $lists = [];

    /**
     * 展示目录或文件的详情数组
     *
     * @param string $path
     * @return array|null
     */
    public static function list(string $path): ?array
    {
        if (false === $path = realpath($path)) {
            return null;
        }

        return self::searchOrAnalyse($path);
    }

    /**
     * 扫描目录中的文件数组
     *
     * @param string $dir
     * @param string $ext
     * @param bool $deep
     * @return array|null
     */
    public static function scan(string $dir, string $ext = '.php', bool $deep = true): ?array
    {
        if (!is_dir($dir) || false === $dir = realpath($dir)) {
            return null;
        }

        $list = self::searchOrAnalyse($dir)['subs'];
        return $deep ? self::depthSearchFiles($list, $ext) : self::searchFiles($list, $ext);
    }

    /**
     * 扫描目录中的类库
     *
     * @param string $dir
     * @param string|null $namespace
     * @param bool $deep
     * @return array|null
     */
    public static function class(string $dir, string $namespace = null, bool $deep = true): ?array
    {
        if (!is_dir($dir) || false === $dir = realpath($dir)) {
            return null;
        }

        $list = self::searchOrAnalyse($dir)['subs'];
        return $deep ? self::depthSearchClasses($list, $namespace) : self::searchClasses($list, $namespace);
    }

    /**
     * 查询解析结果（无则解析）
     *
     * @param string $path
     * @param array $lists
     * @return array
     */
    private static function searchOrAnalyse(string $path, array &$lists = []): ?array
    {
        if (isset($lists[$path])) {
            return $lists[$path];
        }

        foreach ($lists as $subPath => $list) {
            if (str_starts_with($path, $subPath . DIRECTORY_SEPARATOR)) {
                return self::searchOrAnalyse(substr($path, strlen($subPath) + 1), $list['subs']);
            }
        }

        return self::$lists[$path] ??= self::depthAnalyse($path);
    }

    /**
     * 目录文件深度解析
     *
     * @param string $path
     * @return array
     */
    private static function depthAnalyse(string $path): array
    {
        if (is_dir($path)) {
            $size = 0;
            $subs = [];
            $dir = dir($path);
            while (false !== ($name = $dir->read())) {
                if ($name !== '.' && $name !== '..') {
                    $subPath = $path . DIRECTORY_SEPARATOR . $name;
                    $list = self::depthAnalyse($subPath);
                    $size += $list['size'];
                    $subs[$name] = $list;
                }
            }
            $dir->close();
        } else {
            $size = filesize($path);
            $subs = null;
        }

        return compact('size', 'subs');
    }

    /**
     * 搜索文件
     *
     * @param array $list
     * @param string $ext
     * @return array
     */
    private static function searchFiles(array $list, string $ext = '.php'): array
    {
        $result = [];

        foreach ($list as $name => $detail) {
            if (str_ends_with($name, $ext) && !isset($detail['subs'])) {
                $result[] = $name;
            }
        }

        return $result;
    }

    /**
     * 深度搜索文件
     *
     * @param array $list
     * @param string $ext
     * @param string|null $prefix
     * @param array $files
     * @return array
     */
    private static function depthSearchFiles(array $list, string $ext = '.php', string $prefix = null, array $files = []): array
    {
        foreach ($list as $name => $detail) {
            if (isset($detail['subs'])) {
                $files = self::depthSearchFiles($detail['subs'], $ext, self::path($name, $prefix), $files);
            } elseif (str_ends_with($name, $ext)) {
                $files[] = self::path($name, $prefix);
            }
        }

        return $files;
    }

    /**
     * 路径补足
     *
     * @param string $path
     * @param string|null $prefix
     * @return string
     */
    private static function path(string $path, string $prefix = null): string
    {
        return $prefix === null ? $path : $prefix . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * 搜索文件
     *
     * @param array $list
     * @param string|null $namespace
     * @return array
     */
    private static function searchClasses(array $list, string $namespace = null): array
    {
        $class = [];

        foreach ($list as $name => $detail) {
            if (str_ends_with($name, '.php') && !isset($detail['subs'])) {
                $class[] = $namespace === null ? substr($name, 0, -4) : $namespace . '\\' . substr($name, 0, -4);
            }
        }

        return $class;
    }

    /**
     * 深度搜索文件
     *
     * @param array $list
     * @param string|null $namespace
     * @param array $classes
     * @return array
     */
    private static function depthSearchClasses(array $list, string $namespace = null, array $classes = []): array
    {
        foreach ($list as $name => $detail) {
            if (isset($detail['subs'])) {
                $classes = self::depthSearchClasses($detail['subs'], self::namespace($name, $namespace), $classes);
            } elseif (str_ends_with($name, '.php')) {
                $classes[] = self::namespace(substr($name, 0, -4), $namespace);
            }
        }

        return $classes;
    }

    /**
     * 命名空间补足
     *
     * @param string $namespace
     * @param string|null $prefix
     * @return string
     */
    private static function namespace(string $namespace, string $prefix = null): string
    {
        return $prefix === null ? $namespace : $prefix . '\\' . $namespace;
    }
}
