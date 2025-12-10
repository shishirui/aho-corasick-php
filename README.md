# Aho-Corasick 黑名单词库匹配示例

这是一个使用 PHP 实现的 Aho-Corasick 算法示例,用于高效匹配黑名单词库。

## 什么是 Aho-Corasick 算法?

Aho-Corasick 算法是一种字符串匹配算法,由 Alfred V. Aho 和 Margaret J. Corasick 于 1975 年发明。它可以在一次扫描中同时查找多个模式串,特别适合以下场景:

- 敏感词过滤
- 黑名单关键词检测
- 内容审核系统
- 日志分析

## 算法优势

**时间复杂度**: O(n + m + z)
- n: 文本长度
- m: 所有模式串的总长度
- z: 匹配的数量

相比于对每个关键词单独搜索(复杂度 O(n × k),k 为关键词数量),Aho-Corasick 算法效率更高。

## 项目结构

```
.
├── composer.json          # Composer 配置文件
├── src/
│   └── AhoCorasick.php   # Aho-Corasick 算法实现
├── cache/                # 缓存目录
├── example.php           # 使用示例
└── README.md            # 说明文档
```

## 快速开始

### 1. 运行示例

```bash
php example.php
```

### 2. 基本使用

```php
<?php

require_once __DIR__ . '/src/AhoCorasick.php';

use Example\AhoCorasick;

// 创建实例
$ac = new AhoCorasick();

// 定义黑名单
$blacklist = ['色情', '赌博', '暴力', '毒品'];

// 构建字典树
$ac->buildTrie($blacklist);

// 检测文本
$text = '这里包含赌博内容';

if ($ac->containsBlacklist($text)) {
    echo "发现敏感词!\n";
    
    // 获取匹配详情
    $matches = $ac->search($text);
    print_r($matches);
    
    // 替换敏感词
    $filtered = $ac->replaceBlacklist($text);
    echo "过滤后: $filtered\n";
}
```

## 核心功能

### 1. 构建字典树

```php
$ac->buildTrie($keywords);
```

将关键词列表构建成字典树(Trie)并计算失败指针。

**缓存支持**: 字典树构建后可以导出为数组并序列化缓存,下次使用时直接从缓存加载,提升性能。

### 2. 搜索匹配

```php
$results = $ac->search($text);
```

返回所有匹配项,包含关键词、位置等信息:

```php
[
    [
        'keyword' => '赌博',
        'position' => 4,
        'end_position' => 5
    ]
]
```

### 3. 检测是否包含黑名单

```php
$hasBlacklist = $ac->containsBlacklist($text);
```

返回布尔值,快速判断文本是否包含任何黑名单关键词。

### 4. 替换敏感词

```php
$filtered = $ac->replaceBlacklist($text, '*');
```

将文本中的敏感词替换为指定字符(默认为 `*`)。

## 算法原理

Aho-Corasick 算法包含三个主要部分:

### 1. Trie 树构建

将所有关键词插入到字典树中,每个节点代表一个字符。

### 2. 失败指针(Failure Link)

当匹配失败时,失败指针指向最长的后缀匹配位置,避免重复比较。

### 3. 输出链接(Output Link)

记录每个节点对应的所有匹配关键词,包括通过失败指针可达的匹配。

## 应用场景

1. **内容审核**: 检测用户发布的内容是否包含敏感词
2. **评论过滤**: 自动过滤评论中的不当言论
3. **广告拦截**: 检测和过滤广告关键词
4. **安全检测**: 检测恶意代码或危险字符串
## 性能特点

- ✅ 一次扫描找出所有匹配
- ✅ 不受关键词数量影响(时间复杂度不包含 k)
- ✅ 支持 UTF-8 多字节字符
- ✅ 内存效率高
- ✅ 适合大规模关键词匹配
- ✅ 支持缓存,避免重复构建字典树

**性能提升**: 使用缓存后,加载速度可提升 10-100 倍(取决于词库大小)。推荐在生产环境中使用缓存。字节字符
- ✅ 内存效率高
## 扩展用法

### 从文件加载黑名单

```php
$blacklist = file('blacklist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ac->buildTrie($blacklist);
```

### 使用缓存优化性能

构建字典树是一个相对耗时的操作,特别是当黑名单词库很大时。使用缓存可以显著提升性能:

```php
$cacheFile = __DIR__ . '/cache/aho_corasick.cache';
$cacheMaxAge = 86400; // 缓存有效期 24 小时

// 尝试从缓存加载
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheMaxAge) {
## 注意事项

1. 字典树构建后可重复使用,不需要每次都重建
2. 支持中文、英文等 UTF-8 字符
3. 关键词区分大小写,如需不区分可以预处理转小写
4. 适合静态黑名单,动态更新需要重建字典树
5. 使用缓存时注意设置合适的过期时间,词库更新后需清除缓存
6. 确保 `cache/` 目录有写入权限
    if ($ac !== null) {
        echo "从缓存加载成功\n";
    }
}

// 缓存不存在或加载失败,重新构建
if ($ac === null) {
    $ac = new AhoCorasick();
    $ac->buildTrie($blacklist);
    
    // 导出并保存到缓存
    $data = $ac->exportData();
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    file_put_contents($cacheFile, json_encode($data));
    echo "字典树已缓存\n";
}
```

**缓存 API**:
- `exportData()`: 将字典树导出为可序列化的数组
- `importData($data)`: 从数组数据导入字典树
- `createFromData($data)`: 静态方法,从数组数据创建新实例

### 自定义替换规则

```php
// 使用不同长度的星号
$filtered = $ac->replaceBlacklist($text, '█');

// 或者完全删除敏感词
$matches = $ac->search($text);
foreach ($matches as $match) {
    $text = str_replace($match['keyword'], '', $text);
}
```

## 注意事项

1. 字典树构建后可重复使用,不需要每次都重建
2. 支持中文、英文等 UTF-8 字符
3. 关键词区分大小写,如需不区分可以预处理转小写
4. 适合静态黑名单,动态更新需要重建字典树

## 许可证

MIT License

## 参考资料

- [Aho-Corasick Algorithm (Wikipedia)](https://en.wikipedia.org/wiki/Aho%E2%80%93Corasick_algorithm)
- [字符串匹配算法](https://oi-wiki.org/string/ac-automaton/)
