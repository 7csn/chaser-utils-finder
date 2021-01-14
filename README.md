## 文件扫描器

文件扫描器，主要用于分析目录结构及数据大小。另外，提供检索目录指定后缀文件、递归检索目录指定后缀文件、简单检索目录类。

### 运行环境

- PHP >= 8.0

### 安装

```
composer require 7csn/utils-finder
```

### 应用说明

```php
use chaser\utils\Finder;

# 获取文件或文件夹详情，非文件或文件夹返回 null
Finder::list(string $path): ?array;

# （递归）检索文件夹内指定后缀文件，非文件夹返回 null
Finder::scan(string $dir, string $ext = '.php', bool $deep = true): ?array;

# 检索文件夹内类库，非文件夹返回 null
# $namespace 为命名空间前缀
Finder::class(string $dir, string $namespace = null, bool $deep = true): ?array;
```
