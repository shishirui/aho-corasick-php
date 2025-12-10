<?php

require_once __DIR__ . '/src/AhoCorasick.php';

use Example\AhoCorasick;

// 黑名单词库
$blacklist = [
    '色情',
    '赌博',
    '暴力',
    '毒品',
    '违法',
    '敏感词',
    '脏话',
    '不良信息'
];

// 缓存文件路径
$cacheFile = __DIR__ . '/cache/aho_corasick.cache';
$cacheMaxAge = 86400; // 缓存有效期 24 小时

$ac = null;

// 检查缓存是否有效
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheMaxAge) {
    echo "从缓存加载黑名单词库...\n";
    
    $cacheContent = file_get_contents($cacheFile);
    if ($cacheContent !== false) {
        $cacheData = json_decode($cacheContent, true);
        if (is_array($cacheData)) {
            $ac = AhoCorasick::createFromData($cacheData);
            if ($ac !== null) {
                echo "✓ 缓存加载成功,共 " . count($blacklist) . " 个关键词\n\n";
            }
        }
    }
    
    if ($ac === null) {
        echo "✗ 缓存加载失败,将重新构建\n";
    }
} else {
    echo "缓存不存在或已过期\n";
}

// 如果缓存加载失败，则重新构建
if ($ac === null) {
    echo "正在构建黑名单词库...\n";
    $ac = new AhoCorasick();
    $ac->buildTrie($blacklist);
    echo "✓ 词库构建完成,共 " . count($blacklist) . " 个关键词\n";
    
    // 导出数据并保存到缓存
    $cacheData = $ac->exportData();
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $json = json_encode($cacheData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($cacheFile, $json) !== false) {
        echo "✓ 已保存到缓存文件: $cacheFile\n\n";
    } else {
        echo "✗ 缓存保存失败\n\n";
    }
}

// 测试文本
$testTexts = [
    '这是一段正常的文本内容',
    '这里包含色情和赌博的内容',
    '请远离毒品和暴力',
    '小心违法的不良信息',
    '这是一段完全健康的内容,没有任何问题'
];

echo "==================== 测试结果 ====================\n\n";

foreach ($testTexts as $index => $text) {
    echo "测试文本 " . ($index + 1) . ": $text\n";
    echo str_repeat('-', 60) . "\n";
    
    // 检测是否包含黑名单
    if ($ac->containsBlacklist($text)) {
        echo "✗ 检测结果: 包含黑名单关键词\n";
        
        // 获取详细匹配信息
        $matches = $ac->search($text);
        echo "匹配详情:\n";
        foreach ($matches as $match) {
            echo "  - 关键词: '{$match['keyword']}' (位置: {$match['position']})\n";
        }
        
        // 替换敏感词
        $filtered = $ac->replaceBlacklist($text);
        echo "过滤后: $filtered\n";
    } else {
        echo "✓ 检测结果: 文本安全,未发现黑名单关键词\n";
    }
    
    echo "\n";
}

echo "==================== 性能测试 ====================\n\n";

// 生成大量文本进行性能测试
$longText = str_repeat('这是一些正常的文本内容。', 100) . '这里有色情内容' . str_repeat('更多正常文本。', 100);

$startTime = microtime(true);
$results = $ac->search($longText);
$endTime = microtime(true);

$executionTime = ($endTime - $startTime) * 1000; // 转换为毫秒

echo "文本长度: " . mb_strlen($longText, 'UTF-8') . " 字符\n";
echo "匹配数量: " . count($results) . " 个\n";
echo "执行时间: " . number_format($executionTime, 4) . " 毫秒\n";

if (!empty($results)) {
    echo "匹配的关键词:\n";
    foreach ($results as $match) {
        echo "  - {$match['keyword']}\n";
    }
}

echo "\n==================== 使用示例总结 ====================\n\n";
echo "Aho-Corasick 算法特点:\n";
echo "1. 时间复杂度: O(n + m + z)\n";
echo "   - n: 文本长度\n";
echo "   - m: 模式串总长度\n";
echo "   - z: 匹配数量\n";
echo "2. 适用场景: 需要同时匹配大量关键词的场景\n";
echo "3. 优势: 一次扫描可以找出所有匹配项,比多次单独搜索效率高得多\n";
