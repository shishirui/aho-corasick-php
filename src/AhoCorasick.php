<?php

namespace Example;

/**
 * Aho-Corasick 自动机节点
 */
class ACNode
{
    public $children = [];    // 子节点
    public $fail = null;      // 失败指针
    public $output = [];      // 输出匹配的关键词
}

/**
 * Aho-Corasick 算法实现
 * 用于高效匹配多个关键词(黑名单词库)
 */
class AhoCorasick
{
    private $root;

    public function __construct()
    {
        $this->root = new ACNode();
    }

    /**
     * 构建字典树(Trie)
     * @param array $keywords 关键词列表
     */
    public function buildTrie(array $keywords)
    {
        foreach ($keywords as $keyword) {
            $this->addKeyword($keyword);
        }
        $this->buildFailureLinks();
    }

    /**
     * 添加单个关键词到字典树
     * @param string $keyword
     */
    private function addKeyword(string $keyword)
    {
        $node = $this->root;
        $length = mb_strlen($keyword, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($keyword, $i, 1, 'UTF-8');
            
            if (!isset($node->children[$char])) {
                $node->children[$char] = new ACNode();
            }
            
            $node = $node->children[$char];
        }

        // 标记关键词结束
        $node->output[] = $keyword;
    }

    /**
     * 构建失败指针(Failure Links)
     * 使用 BFS 广度优先遍历
     */
    private function buildFailureLinks()
    {
        $queue = [];
        
        // 第一层节点的失败指针都指向根节点
        foreach ($this->root->children as $child) {
            $child->fail = $this->root;
            $queue[] = $child;
        }

        // BFS 构建其他层的失败指针
        while (!empty($queue)) {
            $current = array_shift($queue);

            foreach ($current->children as $char => $child) {
                $queue[] = $child;

                // 查找失败指针
                $failNode = $current->fail;
                while ($failNode !== null && !isset($failNode->children[$char])) {
                    $failNode = $failNode->fail;
                }

                if ($failNode === null) {
                    $child->fail = $this->root;
                } else {
                    $child->fail = $failNode->children[$char];
                    // 合并输出
                    $child->output = array_merge($child->output, $child->fail->output);
                }
            }
        }
    }

    /**
     * 搜索文本中的所有匹配项
     * @param string $text 待搜索的文本
     * @return array 返回匹配结果 [['keyword' => '关键词', 'position' => 位置], ...]
     */
    public function search(string $text): array
    {
        $results = [];
        $node = $this->root;
        $length = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');

            // 根据失败指针跳转
            while ($node !== $this->root && !isset($node->children[$char])) {
                $node = $node->fail;
            }

            if (isset($node->children[$char])) {
                $node = $node->children[$char];
            }

            // 输出所有匹配的关键词
            if (!empty($node->output)) {
                foreach ($node->output as $keyword) {
                    $results[] = [
                        'keyword' => $keyword,
                        'position' => $i - mb_strlen($keyword, 'UTF-8') + 1,
                        'end_position' => $i
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * 检查文本是否包含黑名单关键词
     * @param string $text
     * @return bool
     */
    public function containsBlacklist(string $text): bool
    {
        $results = $this->search($text);
        return !empty($results);
    }

    /**
     * 替换文本中的黑名单关键词
     * @param string $text 原文本
     * @param string $replacement 替换字符,默认为 '*'
     * @return string 处理后的文本
     */
    public function replaceBlacklist(string $text, string $replacement = '*'): string
    {
        $results = $this->search($text);
        
        if (empty($results)) {
            return $text;
        }

        // 按位置倒序排列,避免替换时位置偏移
        usort($results, function($a, $b) {
            return $b['position'] - $a['position'];
        });

        foreach ($results as $match) {
            $keywordLength = mb_strlen($match['keyword'], 'UTF-8');
            $stars = str_repeat($replacement, $keywordLength);
            
            $before = mb_substr($text, 0, $match['position'], 'UTF-8');
            $after = mb_substr($text, $match['end_position'] + 1, null, 'UTF-8');
            
            $text = $before . $stars . $after;
        }

        return $text;
    }

    /**
     * 将 Trie 树数据结构导出为数组
     * @return array
     */
    private function exportTrieData(): array
    {
        $data = [];
        $this->exportNode($this->root, $data, 0);
        return $data;
    }

    /**
     * 递归导出节点数据
     * @param ACNode $node
     * @param array &$nodes
     * @param int $nodeId
     */
    private function exportNode(ACNode $node, array &$nodes, int $nodeId): void
    {
        $nodes[$nodeId] = [
            'children' => [],
            'output' => $node->output,
            'fail' => null // 稍后处理
        ];

        static $nextId = 1;
        
        foreach ($node->children as $char => $childNode) {
            $childId = $nextId++;
            $nodes[$nodeId]['children'][$char] = $childId;
            $this->exportNode($childNode, $nodes, $childId);
        }
    }

    /**
     * 从数组数据重建 Trie 树
     * @param array $data
     */
    private function importTrieData(array $data): void
    {
        $nodeMap = [];
        
        // 第一遍：创建所有节点
        foreach ($data as $nodeId => $nodeData) {
            $nodeMap[$nodeId] = new ACNode();
            $nodeMap[$nodeId]->output = $nodeData['output'];
        }
        
        // 第二遍：建立父子关系
        foreach ($data as $nodeId => $nodeData) {
            foreach ($nodeData['children'] as $char => $childId) {
                $nodeMap[$nodeId]->children[$char] = $nodeMap[$childId];
            }
        }
        
        // 第三遍：重建失败指针
        $this->root = $nodeMap[0];
        $this->rebuildFailureLinks();
    }

    /**
     * 重建失败指针（与 buildFailureLinks 相同逻辑）
     */
    private function rebuildFailureLinks(): void
    {
        $queue = [];
        
        foreach ($this->root->children as $child) {
            $child->fail = $this->root;
            $queue[] = $child;
        }

        while (!empty($queue)) {
            $current = array_shift($queue);

            foreach ($current->children as $char => $child) {
                $queue[] = $child;

                $failNode = $current->fail;
                while ($failNode !== null && !isset($failNode->children[$char])) {
                    $failNode = $failNode->fail;
                }

                if ($failNode === null) {
                    $child->fail = $this->root;
                } else {
                    $child->fail = $failNode->children[$char];
                    $child->output = array_merge($child->output, $child->fail->output);
                }
            }
        }
    }

    /**
     * 导出字典树数据为数组格式
     * @return array 可序列化的数组数据
     */
    public function exportData(): array
    {
        return $this->exportTrieData();
    }

    /**
     * 从数组数据导入并构建字典树
     * @param array $data 字典树数据
     * @return bool 是否导入成功
     */
    public function importData(array $data): bool
    {
        if (!is_array($data) || empty($data)) {
            return false;
        }

        try {
            $this->importTrieData($data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 从数组数据创建新实例
     * @param array $data 字典树数据
     * @return AhoCorasick|null 成功返回实例，失败返回 null
     */
    public static function createFromData(array $data): ?AhoCorasick
    {
        $instance = new AhoCorasick();
        return $instance->importData($data) ? $instance : null;
    }
}
