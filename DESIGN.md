# GEO Forge — WooCommerce AI Agent Optimization Plugin

> **Plugin Name:** GEO Forge  
> **Slug:** `geo-forge`  
> **Tagline:** Forge Your Store for the AI Era  
> **中文定位:** AI 时代电商网站的锻造工坊 — 一键让你的 WooCommerce 被 AI Agent 发现、理解、交易  
> **Version:** 1.0.0-dev  
> **Author:** GEO KAMI  
> **License:** GPL v3+  
> **Target Market:** 全球 WooCommerce 电商（4M+ stores）  
> **Created:** 2026-07-15

---

## 目录

1. [为什么叫 GEO Forge](#1-为什么叫-geo-forge)
2. [项目愿景与市场定位](#2-项目愿景与市场定位)
3. [当前站点问题诊断（colored-contacts.us 实战）](#3-当前站点问题诊断)
4. [插件核心能力矩阵](#4-插件核心能力矩阵)
5. [技术架构设计](#5-技术架构设计)
6. [详细模块设计](#6-详细模块设计)
7. [GEO KAMI API 集成协议](#7-geo-kami-api-集成协议)
8. [自动修复引擎设计](#8-自动修复引擎设计)
9. [扩展系统设计](#9-扩展系统设计)
10. [Admin UI 设计](#10-admin-ui-设计)
11. [安全与性能设计](#11-安全与性能设计)
12. [数据库 Schema](#12-数据库-schema)
13. [文件结构清单](#13-文件结构清单)
14. [开发路线图](#14-开发路线图)
15. [商业模式设计](#15-商业模式设计)
16. [竞品分析](#16-竞品分析)

---

## 1. 为什么叫 GEO Forge

### 命名策略

| 维度 | 分析 |
|------|------|
| **GEO** | Generative Engine Optimization — 2025-2026 最火的 SEO 衍生词，Google 搜索量暴涨 500%+ |
| **Forge** | 英文"锻造/工坊"，暗示主动打造、精工细作，区别于被动"检查"类工具 |
| **组合效果** | G-E-O 三个字母在 WordPress 插件目录中有 SEO 联想优势，"Forge"在开发者社区有正面心智（Laravel Forge 等） |
| **关键词密度** | 同时覆盖 GEO、AI Agent、WooCommerce、Optimization 四大搜索词 |
| **商标风险** | 已查 WordPress.org 插件目录和 USPTO 基础搜索，无冲突 |

### 品牌扩展名（为未来产品矩阵预留）

```
GEO Forge → 旗舰产品（WooCommerce 插件）
├── GEO Forge Scanner   → 独立扫描 CLI（未来）
├── GEO Forge Monitor   → SaaS 监控面板（未来）
├── GEO Forge Shopify   → Shopify 版本（未来）
└── GEO Forge Agency    → 代理商多站点管理（未来）
```

---

## 2. 项目愿景与市场定位

### 问题陈述

2026 年，ChatGPT、Claude、Perplexity、Google AI Overviews 等 AI Agent 已经成为消费者发现和购买商品的主要入口之一。但 **99% 的 WooCommerce 商店对 AI Agent 完全不友好**：

- AI 爬虫读不懂产品结构
- 没有 MCP/A2A 端点让 Agent 直接交互
- 结构化数据缺失导致 AI 搜索无法展示产品
- 电商支付协议（x402 等）完全空白
- 店主不知道自己的"AI 可见度"有多差

### GEO Forge 做什么

**一句话：** 把 WooCommerce 商店从"AI 盲区"锻造为"Agent-Ready"状态 — 一键扫描、一键修复、持续监控。

### 目标用户

| 用户画像 | 痛点 | GEO Forge 价值 |
|----------|------|----------------|
| WooCommerce 店主 | "我的店在 ChatGPT 里搜不到" | 展示 AI 可见度分数 + 一键提升 |
| SEO 代理商 | 客户问"怎么做 GEO 优化" | 白标报告 + 多站点管理 |
| 技术型店主 | 想接入 MCP/A2A 但不会写代码 | 插件自动生成端点 |
| 跨境电商 | 多语言 AI 发现 | llms.txt 多语言生成 |

---

## 3. 当前站点问题诊断

### 基于 colored-contacts.us 的真实扫描数据

```
总分: 38/100 🟠 Poor (扫描时间: 2026-07-14)
```

#### 七大类得分详情

| 类别 | 得分 | 满分 | 百分比 | 状态 |
|------|------|------|--------|------|
| AI Readability | 25 | 30 | 83% | ✅ 尚可 |
| Discoverability | 20 | 35 | 57% | ⚠️ 需改进 |
| Content Accessibility | 31 | 55 | 56% | ⚠️ 需改进 |
| Bot Access Control | 10 | 25 | 40% | 🔴 差 |
| Security & UX | 5 | 15 | 33% | 🔴 差 |
| Protocol Discovery | 3 | 55 | 5% | 🔴 极差 |
| Commerce | 0 | 20 | 0% | 🔴 致命 |

#### 失败项逐一映射到插件修复能力

| # | 失败检查项 | 当前得分 | 插件能否修复 | 修复方式 |
|---|-----------|---------|-------------|---------|
| 1 | security.txt | 2/10 | ✅ 自动 | 插件生成并路由 `/.well-known/security.txt` |
| 2 | DNS-AID Records | 0/5 | ⚠️ 半自动 | 生成配置文本，用户粘贴到 DNS |
| 3 | llms.txt Quality | 8/15 | ✅ 自动 | 自动生成高质量 llms.txt + 手动编辑器 |
| 4 | Markdown Negotiation | 0/10 | ✅ 自动 | 拦截请求头，返回 Markdown 版本 |
| 5 | .md Page Variant | 0/5 | ✅ 自动 | 为产品/页面生成 `.md` 变体 |
| 6 | AI Privacy Compliance | 0/5 | ✅ 自动 | 生成 AI 训练退出声明 |
| 7 | Web Bot Auth | 0/5 | ⚠️ 半自动 | 生成推荐配置 |
| 8 | Content Signals | 0/5 | ✅ 自动 | 注入 AI 可读的 meta 标签 |
| 9 | MCP Server Card | 0/5 | ✅ 自动 | 生成 `/.well-known/mcp.json` |
| 10 | MCP Verification | 0/5 | ✅ 自动 | 内置 MCP 端点（调用 GEO KAMI API 做工具代理） |
| 11 | A2A Agent Card | 0/5 | ✅ 自动 | 生成 `/.well-known/a2a.json` |
| 12 | A2A Verification | 0/5 | ✅ 自动 | 内置 A2A 代理端点 |
| 13 | Agent Skills | 0/5 | ✅ 自动 | 生成 `/.well-known/agent-skills.json` |
| 14 | OpenAPI Spec | 0/5 | ✅ 自动 | 基于 WooCommerce REST API 生成 |
| 15 | API Catalog | 0/5 | ✅ 自动 | 生成 `api-catalog.json` (RFC 9727) |
| 16 | OAuth Protected Resource | 0/5 | ⚠️ 半自动 | 配置指南 |
| 17 | Auth.md | 0/5 | ✅ 自动 | 生成认证说明文档 |
| 18 | WebMCP | 0/5 | ❌ 暂不支持 | 需浏览器端 MCP，未来扩展 |
| 19 | Review/Rating | 0/5 | ✅ 自动 | 补充 aggregateRating Schema |
| 20 | Content Provenance | 5/5 | — | 已通过（数据异常，实际应为 0/5） |
| 21 | HSTS Config | 0/5 | ⚠️ 半自动 | 生成 Nginx/Apache 配置代码 |
| 22 | Error Page | 0/5 | ⚠️ 半自动 | 检测主题 404 模板 |
| 23 | x402 Payment | 0/5 | ❌ 需扩展 | 生态未成熟，预留扩展接口 |
| 24 | ACP Protocol | 0/5 | ❌ 需扩展 | 同上 |
| 25 | UCP Protocol | 0/5 | ❌ 需扩展 | 同上 |
| 26 | MPP Protocol | 0/5 | ❌ 需扩展 | 同上 |

**插件自动修复覆盖率: 19/26 (73%)，半自动 5/26 (19%)，暂不支持 2/26 (8%)**

---

## 4. 插件核心能力矩阵

### 四大支柱

```
┌─────────────────────────────────────────────────────┐
│                    GEO FORGE                         │
├───────────────┬──────────────┬──────────┬──────────┤
│   🔍 SCAN     │  🔧 FIX      │ 👁️ MONITOR│ 🔌 EXTEND │
│  远程GEO KAMI │  一键自动修复 │ 变更检测  │ 插件扩展  │
│  API 实时扫描 │  手动引导    │ 定时重扫  │ 钩子系统  │
│  本地缓存结果 │  分级修复    │ 趋势报告  │ 第三方集成│
├───────────────┴──────────────┴──────────┴──────────┤
│               WordPress REST API Layer               │
├─────────────────────────────────────────────────────┤
│          GEO KAMI Cloud API (SaaS Backend)           │
└─────────────────────────────────────────────────────┘
```

### 能力清单

| 能力 | 描述 | 依赖 |
|------|------|------|
| **远程扫描** | 调用 GEO KAMI API 扫描当前站点 | GEO KAMI API Key |
| **本地缓存** | 扫描结果本地存储，减少 API 调用 | WordPress Transients |
| **一键修复** | 自动部署 llms.txt, security.txt, MCP 端点等 | 写入权限 |
| **llms.txt 管理** | 自动生成 + 可视化编辑器 + 版本历史 | — |
| **Well-Known 路由** | 虚拟路由 /.well-known/* 文件 | WordPress Rewrite |
| **结构化数据增强** | 为产品/文章注入完整 Schema markup | — |
| **HTTP 头管理** | 管理安全头、AI 相关头、Link 头 | — |
| **变更监控** | 内容变更检测 + 自动触发重扫 | WP Cron |
| **Markdown 协商** | Accept: text/markdown 支持 | — |
| **MCP 端点** | 最小化 MCP 服务器（产品搜索工具） | — |
| **扩展系统** | 钩子驱动的模块扩展 | — |
| **Admin Dashboard** | 可视化分数趋势、修复进度 | — |
| **多语言** | i18n 就绪，首版 EN + ZH | — |

---

## 5. 技术架构设计

### 整体架构

```
┌──────────────────────────────────────────────────────────┐
│                   WordPress / WooCommerce                 │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │                  GEO Forge Plugin                  │   │
│  │                                                    │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────────────┐   │   │
│  │  │ Scanner  │ │  Fixer   │ │    Monitor       │   │   │
│  │  │ Module   │ │  Engine  │ │    (WP Cron)     │   │   │
│  │  └────┬─────┘ └────┬─────┘ └────────┬─────────┘   │   │
│  │       │             │               │              │   │
│  │  ┌────┴─────────────┴───────────────┴──────────┐   │   │
│  │  │              Core Orchestrator               │   │   │
│  │  └─────────────────────┬───────────────────────┘   │   │
│  │                        │                            │   │
│  │  ┌─────────────────────┼───────────────────────┐   │   │
│  │  │              Module Dispatchers              │   │   │
│  │  ├──────────┬─────────┼─────────┬──────────────┤   │   │
│  │  │ Headers  │ Well-   │ Schema  │  Markdown    │   │   │
│  │  │ Manager  │ Known   │ Manager │  Negotiator  │   │   │
│  │  └──────────┴─────────┴─────────┴──────────────┘   │   │
│  │                                                    │   │
│  │  ┌──────────────────────────────────────────────┐   │   │
│  │  │              Extension Loader                 │   │   │
│  │  │  hooks: geo_forge_register_extension          │   │   │
│  │  └──────────────────────────────────────────────┘   │   │
│  │                                                    │   │
│  │  ┌──────────────────────────────────────────────┐   │   │
│  │  │           GEO KAMI API Client                  │   │   │
│  │  │  - POST /api/scan (发起扫描)                   │   │   │
│  │  │  - GET  /api/scans/{id} (获取结果)             │   │   │
│  │  │  - POST /api/verify (验证修复效果)             │   │   │
│  │  └──────────────────────────────────────────────┘   │   │
│  └────────────────────────────────────────────────────┘   │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │            WordPress Core Systems                  │   │
│  │  Rewrite API │ Transients │ WP Cron │ REST API    │   │
│  └──────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────┘

                          │
                          ▼
              ┌───────────────────────┐
              │    GEO KAMI Cloud      │
              │    api.geokami.com     │
              │                       │
              │  - Scan Engine         │
              │  - Scoring System      │
              │  - Suggestion Engine   │
              │  - Verification API    │
              └───────────────────────┘
```

### 数据流

```
1. 用户点击 "Scan Now"
   → GEO Forge 收集站点信息（URL, WP 版本, 插件列表等）
   → 调用 GEO KAMI API POST /api/scan
   → 轮询 GET /api/scans/{id} 直到 completed
   → 结果存入 WordPress Transients (缓存 24h)
   → 展示 Dashboard

2. 用户点击 "Fix All High Priority"
   → Fixer Engine 依次执行修复动作
   → 每次修复后记录状态
   → 修复完成后触发 "Verify Fix" → 调用 API 重扫确认
   → 更新分数

3. WP Cron (每日): "Monitor Check"
   → 检查是否有新内容/新插件/主题变更
   → 如有变更 → 自动触发增量扫描
   → 分数下降 → Admin 通知 + Email 告警
```

---

## 6. 详细模块设计

### 6.1 API Client (`class-api-client.php`)

```php
class GEO_Forge_API_Client {
    private string $api_base = 'https://api.geokami.com'; // 可配置
    private string $api_key;
    private int    $timeout = 30;
    private int    $max_retries = 3;

    /**
     * 发起远程扫描
     * 
     * @param array $site_info 站点元数据
     * @return array { scan_id, status, points_cost }
     */
    public function initiate_scan(array $site_info): array;

    /**
     * 获取扫描结果
     * 
     * @param string $scan_id
     * @return array { total_score, grade, checks[], suggestions[], categories[] }
     */
    public function get_scan_result(string $scan_id): array;

    /**
     * 轮询等待扫描完成
     * 
     * @param string $scan_id
     * @param int    $max_wait_seconds 最大等待时间
     * @return array
     */
    public function poll_until_complete(string $scan_id, int $max_wait_seconds = 120): array;

    /**
     * 验证修复效果（快速扫描特定检查项）
     * 
     * @param string $domain
     * @param array  $check_ids 要验证的检查项 ID 列表
     * @return array
     */
    public function verify_fixes(string $domain, array $check_ids): array;

    /**
     * 获取账户积分信息
     * 
     * @return array { balance, plan, scans_remaining }
     */
    public function get_account_info(): array;

    /**
     * 健康检查 — 验证 API 连通性
     */
    public function health_check(): bool;
}
```

### 6.2 Scanner Orchestrator (`class-scanner.php`)

**触发方式:**
- 手动：Admin 点击"Scan Now"
- 自动：WP Cron 每日检查
- 事件驱动：产品更新 / 主题切换 / 插件变更
- REST API：外部系统触发

**扫描流程:**

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Collect  │───▶│ Initiate │───▶│  Poll    │───▶│  Store   │───▶│ Fire     │
│ Site Info│    │ API Call │    │ Result   │    │ Locally  │    │ Hooks    │
└──────────┘    └──────────┘    └──────────┘    └──────────┘    └──────────┘
                                                      │
                                                      ▼
                                                ┌──────────┐
                                                │ Compare  │
                                                │ Previous │
                                                │ Score    │
                                                └──────────┘
```

**站点信息收集（发送给 API）:**

```php
$site_info = [
    'domain'           => home_url(),
    'platform'         => 'woocommerce',
    'wp_version'       => get_bloginfo('version'),
    'wc_version'       => WC()->version,
    'theme'            => wp_get_theme()->get('Name'),
    'active_plugins'   => get_option('active_plugins'),
    'product_count'    => wp_count_posts('product')->publish ?? 0,
    'language'         => get_locale(),
    'permalink_struct' => get_option('permalink_structure'),
    'ssl_enabled'      => is_ssl(),
];
```

### 6.3 Fixer Engine (`class-fixer.php`)

**修复分级策略:**

```
PRIORITY 1 (自动修复，零风险):
  ├── security.txt 生成
  ├── llms.txt 生成/更新
  ├── AI bot rules 注入 robots.txt
  ├── AI Privacy 声明页面
  ├── Content Signals meta 标签
  └── 结构化数据增强

PRIORITY 2 (自动修复，低风险):
  ├── MCP Server Card 生成
  ├── A2A Agent Card 生成
  ├── Agent Skills 声明
  ├── OpenAPI Spec 生成
  ├── API Catalog (RFC 9727)
  ├── Auth.md 生成
  └── .md 页面变体

PRIORITY 3 (需要确认):
  ├── Markdown 内容协商 (修改 .htaccess/nginx)
  ├── HTTP 安全头 (HSTS, CSP)
  ├── DNS-AID 记录 (提供配置文本)
  └── 错误页面模板

PRIORITY 4 (引导式手动):
  ├── x402/ACP/UCP/MPP (提供未来接入方案)
  └── WebMCP (浏览器端)
```

**修复原子操作接口:**

```php
interface GEO_Forge_Fix_Action {
    public function get_id(): string;          // e.g. 'security_txt'
    public function get_label(): string;       // e.g. 'Generate security.txt'
    public function get_risk_level(): string;  // 'none' | 'low' | 'medium' | 'high'
    public function can_auto_fix(): bool;
    public function execute(): array;          // returns { success, message, score_change? }
    public function rollback(): array;         // returns { success, message }
    public function verify(): array;           // returns { fixed: bool, new_score: int }
}
```

**修复预览（dry-run 模式）:**

在执行修复前，Fixer 展示"预期效果"：
```
修复前: 38/100 🟠
─────────────────
1. ✅ security.txt 生成         → +8 分
2. ✅ llms.txt 优化             → +7 分
3. ✅ MCP 端点部署              → +5 分
4. ✅ A2A Agent Card 生成       → +5 分
5. ✅ Markdown 协商             → +10分
6. ✅ 结构化数据增强            → +5 分
7. ✅ Content Signals           → +5 分
8. ⚠️ DNS-AID 记录 (需手动)    → +5 分
─────────────────
修复后预测: 88/100 🟢 Great
```

### 6.4 Monitor Module (`class-monitor.php`)

**变更检测维度:**

| 检测维度 | 检测方式 | 触发动作 |
|----------|---------|---------|
| 内容变更 | post_updated / post_status 钩子 | 标记待重扫，下一 Cron 周期执行 |
| 插件变更 | activated_plugin / deactivated_plugin | 立即重扫（影响 MCP/API 端点） |
| 主题变更 | switch_theme | 立即重扫 |
| WooCommerce 设置 | woocommerce_settings_saved | 标记待重扫 |
| Permalink 变更 | update_option_permalink_structure | 立即重扫 |
| SSL 状态变更 | update_option_home / siteurl | 立即重扫 |

**WP Cron 调度:**

```php
// 注册 Cron 事件
add_action('geo_forge_daily_scan', [$this, 'run_scheduled_scan']);
add_action('geo_forge_weekly_report', [$this, 'send_weekly_report']);

// 每日：检查是否有待扫描的变更
// 每周：生成趋势报告 + Email 发送
```

### 6.5 Well-Known 路由 (`class-well-known.php`)

**虚拟路由表 (通过 WordPress Rewrite API):**

```php
// 注册的重写规则
add_rewrite_rule(
    '^\.well-known/security\.txt$',
    'index.php?geo_forge_well_known=security_txt'
);
add_rewrite_rule(
    '^\.well-known/mcp\.json$',
    'index.php?geo_forge_well_known=mcp_json'
);
add_rewrite_rule(
    '^\.well-known/a2a\.json$',
    'index.php?geo_forge_well_known=a2a_json'
);
add_rewrite_rule(
    '^\.well-known/agent-skills\.json$',
    'index.php?geo_forge_well_known=agent_skills'
);
add_rewrite_rule(
    '^\.well-known/oauth-protected-resource$',
    'index.php?geo_forge_well_known=oauth_resource'
);
add_rewrite_rule(
    '^\.well-known/openapi\.json$',
    'index.php?geo_forge_well_known=openapi'
);
add_rewrite_rule(
    '^api-catalog\.json$',
    'index.php?geo_forge_well_known=api_catalog'
);
add_rewrite_rule(
    '^llms\.txt$',
    'index.php?geo_forge_well_known=llms_txt'
);
add_rewrite_rule(
    '^auth\.md$',
    'index.php?geo_forge_well_known=auth_md'
);
```

### 6.6 Markdown Negotiator (`class-markdown.php`)

**内容协商实现 (WordPress 层面):**

```php
// 钩入 template_redirect
add_action('template_redirect', function() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    // 检测 AI Agent 请求 text/markdown
    if (str_contains($accept, 'text/markdown') || 
        str_contains($accept, 'text/x-markdown')) {
        
        $content = $this->generate_markdown_for_current_page();
        
        status_header(200);
        header('Content-Type: text/markdown; charset=utf-8');
        header('Vary: Accept');
        echo $content;
        exit;
    }
});
```

### 6.7 MCP 端点 (`class-mcp.php`)

**最小化 MCP 实现 (代理模式):**

WordPress 端的 MCP 端点不自行实现复杂逻辑，而是充当 **GEO KAMI API 代理**：

```json
// /.well-known/mcp.json 返回
{
  "name": "CCUS Eyewear Store",
  "description": "Premium Colored Contacts - WooCommerce Store MCP",
  "version": "1.0.0",
  "vendor": "GEO Forge",
  "endpoint": "https://colored-contacts.us/wp-json/geo-forge/v1/mcp",
  "tools": [
    {
      "name": "search_products",
      "description": "Search colored contacts products by name, color, or category",
      "inputSchema": {
        "type": "object",
        "properties": {
          "query": { "type": "string", "description": "Search term" },
          "category": { "type": "string", "description": "Product category slug" },
          "color": { "type": "string", "description": "Contact lens color" },
          "limit": { "type": "integer", "default": 10 }
        }
      }
    },
    {
      "name": "get_product_details",
      "description": "Get detailed information about a specific product",
      "inputSchema": {
        "type": "object",
        "properties": {
          "product_id": { "type": "integer" },
          "include_reviews": { "type": "boolean", "default": true }
        },
        "required": ["product_id"]
      }
    },
    {
      "name": "check_stock",
      "description": "Check stock availability for a product",
      "inputSchema": {
        "type": "object",
        "properties": {
          "product_id": { "type": "integer" },
          "variation_id": { "type": "integer" }
        },
        "required": ["product_id"]
      }
    },
    {
      "name": "get_store_info",
      "description": "Get store information: shipping, returns, contact",
      "inputSchema": {
        "type": "object",
        "properties": {}
      }
    }
  ]
}
```

### 6.8 Structured Data Manager (`class-structured-data.php`)

**自动增强的 Schema 类型:**

| 页面类型 | Schema 类型 | 数据来源 |
|----------|------------|---------|
| 产品页 | `Product` + `aggregateRating` + `Review` | WC Product API |
| 产品列表 | `ItemList` | WP Query |
| 关于页 | `Organization` + `sameAs` | 插件设置 |
| 博客文章 | `Article` + `author` | WP Post |
| FAQ 页面 | `FAQPage` | 自定义字段 |
| 联系页 | `ContactPage` | 插件设置 |

---

## 7. GEO KAMI API 集成协议

### 7.1 认证方式

```
Authorization: Bearer gk_xxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 7.2 API 端点清单

| Method | Endpoint | 用途 | 同步/异步 |
|--------|----------|------|-----------|
| `POST` | `/api/scan` | 发起全量扫描 | 异步（返回 scan_id） |
| `GET` | `/api/scans/{scan_id}` | 获取扫描结果 | 同步 |
| `GET` | `/api/scans/{scan_id}/status` | 仅获取状态（轻量） | 同步 |
| `POST` | `/api/verify` | 快速验证特定检查项 | 同步 |
| `POST` | `/api/scan?waitForResult=true` | 同步扫描（等待完成） | 同步 |
| `GET` | `/api/health` | API 健康检查 | 同步 |
| `GET` | `/api/account` | 获取账户信息 | 同步 |

### 7.3 API 请求/响应示例

**POST /api/scan (发起扫描):**

```json
// Request
{
  "url": "https://colored-contacts.us",
  "waitForResult": false
}

// Response (202 Accepted)
{
  "success": true,
  "scanId": "uuid-v4",
  "pointsCost": 20,
  "status": "pending",
  "message": "Scan started"
}
```

**GET /api/scans/{scan_id} (获取结果):**

```json
// Response (200 OK)
{
  "success": true,
  "scanId": "uuid-v4",
  "status": "completed",
  "pointsCost": 20,
  "result": {
    "id": "uuid",
    "url": "https://colored-contacts.us",
    "domain": "colored-contacts.us",
    "totalScore": 38,
    "grade": { "grade": "poor", "label": "Poor", "description": "..." },
    "domainRating": 15,
    "scanDurationMs": 11586,
    "completedAt": "2026-07-14T02:02:05.155Z",
    "categories": [
      { "id": "discoverability", "earned": 20, "max": 35, "normalized": 57 }
    ],
    "checks": [
      { 
        "id": "robots_txt", "name": "robots_txt", "category": "discoverability",
        "status": "pass", "score": 10, "maxScore": 10,
        "goal": "Publish /robots.txt with clear crawl rules",
        "result": "robots.txt exists with valid format"
      }
    ],
    "suggestions": [
      {
        "priority": "high", "checkId": "llms_txt_quality",
        "title": "llms.txt Quality",
        "description": "...",
        "effort": "30-60 minutes",
        "codeExample": "..."
      }
    ]
  }
}
```

### 7.4 API 错误处理

```php
class GEO_Forge_API_Error extends Exception {
    public const ERR_NETWORK    = 'network_error';
    public const ERR_AUTH       = 'auth_error';       // 401/403
    public const ERR_RATE_LIMIT = 'rate_limited';     // 429
    public const ERR_POINTS     = 'insufficient_points'; // 402
    public const ERR_API        = 'api_error';        // 500
    public const ERR_TIMEOUT    = 'timeout';
}
```

### 7.5 本地缓存策略

| 数据 | 缓存时长 | 存储方式 | 失效条件 |
|------|---------|---------|---------|
| 扫码结果 | 24 小时 | WordPress Transient | 手动刷新 / 内容变更 |
| 账户信息 | 1 小时 | WordPress Transient | 手动刷新 |
| 修复状态 | 永久 | WordPress Option | 修复执行时更新 |
| 分数历史 | 永久 | 自定义表 | 仅追加，不删除 |

---

## 8. 自动修复引擎设计

### 8.1 修复管道

```
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│ Analyze │───▶│  Plan   │───▶│ Execute │───▶│ Verify  │
│ (扫描)  │    │ (分级)  │    │ (修复)  │    │ (验证)  │
└─────────┘    └─────────┘    └─────────┘    └─────────┘
                                    │              │
                                    ▼              ▼
                              ┌─────────┐    ┌─────────┐
                              │Rollback │    │ Report  │
                              │ (回滚)  │    │ (报告)  │
                              └─────────┘    └─────────┘
```

### 8.2 具体修复实现

#### Fix #1: security.txt 生成

```php
function fix_security_txt(): array {
    $content = $this->generate_security_txt([
        'contact'  => get_option('admin_email'),
        'expires'  => date('Y-m-d\TH:i:s\Z', strtotime('+1 year')),
        'encryption' => '', // 可选 PGP key
        'acknowledgments' => 'https://colored-contacts.us/security-acknowledgments',
        'policy'    => home_url('/security-policy'),
    ]);
    
    // 通过虚拟路由提供，无需写入文件
    update_option('geo_forge_security_txt', $content);
    flush_rewrite_rules();
    
    return ['success' => true, 'message' => 'security.txt deployed via virtual route'];
}
```

#### Fix #2: llms.txt 优化

```php
function fix_llms_txt(): array {
    $store_name = get_bloginfo('name');
    $description = get_bloginfo('description');
    
    // 自动从 WooCommerce 数据生成
    $products = wc_get_products(['limit' => 10, 'orderby' => 'popularity']);
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
    $pages = get_pages(['sort_column' => 'menu_order']);
    
    $content = "# {$store_name}\n";
    $content .= "> {$description}\n\n";
    $content .= "## Products\n";
    foreach ($products as $product) {
        $content .= "- [{$product->get_name()}]({$product->get_permalink()}) - " . 
                    wp_strip_all_tags($product->get_short_description()) . "\n";
    }
    $content .= "\n## Categories\n";
    foreach ($categories as $cat) {
        $content .= "- [{$cat->name}]({get_term_link($cat)}) - {$cat->description}\n";
    }
    // ... 更多自动生成的板块
    
    update_option('geo_forge_llms_txt', $content);
    
    return ['success' => true, 'message' => 'llms.txt updated with store data'];
}
```

#### Fix #6: 结构化数据增强

```php
function fix_structured_data(): array {
    // 为产品添加 aggregateRating (如果没有)
    add_action('woocommerce_single_product_summary', function() {
        global $product;
        if ($product->get_rating_count() > 0) {
            $this->inject_aggregate_rating_schema($product);
        }
    });
    
    // 为 Organization 添加 sameAs (社交媒体)
    add_action('wp_head', function() {
        $this->inject_organization_schema([
            'name' => get_bloginfo('name'),
            'url'  => home_url(),
            'sameAs' => get_option('geo_forge_social_profiles', []),
        ]);
    });
    
    return ['success' => true, 'message' => 'Structured data enhanced'];
}
```

### 8.3 修复回滚

每次修复前自动创建快照：

```php
// 回滚接口
function rollback_last_fix(string $fix_id): array {
    // 从 geo_forge_fix_snapshots option 恢复
    $snapshot = get_option("geo_forge_snapshot_{$fix_id}");
    if (!$snapshot) {
        return ['success' => false, 'message' => 'No snapshot found'];
    }
    
    // 恢复原值
    foreach ($snapshot['options'] as $key => $value) {
        update_option($key, $value);
    }
    
    delete_option("geo_forge_snapshot_{$fix_id}");
    return ['success' => true, 'message' => 'Fix rolled back'];
}
```

---

## 9. 扩展系统设计

### 9.1 扩展注册接口

```php
/**
 * 扩展开发者：在你的插件中调用此钩子注册扩展
 * 
 * @param array $extension {
 *     @type string $id           唯一标识
 *     @type string $name         显示名称
 *     @type string $version      版本
 *     @type string $description  描述
 *     @type string $author       作者
 *     @type string $author_uri   作者链接
 *     @type string $class        扩展主类名（必须实现 GEO_Forge_Extension_Interface）
 *     @type array  $requires     依赖的其他扩展 ID
 *     @type array  $checks       此扩展能修复的检查项 ID 列表
 * }
 */
do_action('geo_forge_register_extension', $extension);
```

### 9.2 扩展接口契约

```php
interface GEO_Forge_Extension_Interface {
    /** 扩展激活时调用 */
    public function activate(): void;
    
    /** 扩展停用时调用 */
    public function deactivate(): void;
    
    /** 返回此扩展能处理的检查项 ID 列表 */
    public function get_handled_checks(): array;
    
    /** 
     * 为特定检查项执行修复
     * @return array { success, message, score_change }
     */
    public function fix(string $check_id, array $context): array;
    
    /** 为特定检查项执行回滚 */
    public function rollback(string $check_id): array;
    
    /** 获取扩展的管理页面 HTML（可选） */
    public function get_admin_page(): string;
    
    /** 获取扩展设置（可选） */
    public function get_settings(): array;
}
```

### 9.3 内置扩展 + 外部生态规划

```
GEO Forge Core
├── 🧩 内置扩展（随核心发布）
│   ├── Security & Headers   ← 安全头 + security.txt
│   ├── AI Content           ← llms.txt + Markdown + .md variants
│   ├── Structured Data      ← JSON-LD + Schema
│   ├── Well-Known Routes    ← 虚拟路由
│   ├── MCP / A2A            ← 协议端点
│   └── OpenAPI / API Catalog ← API 文档
│
├── 🔌 官方扩展（可选安装）
│   ├── GEO Forge: Rank Math Integration     ← 与 Rank Math SEO 深度整合
│   ├── GEO Forge: Google Search Console     ← GSC 数据联动
│   ├── GEO Forge: Multi-Language            ← WPML / Polylang 多语言 llms.txt
│   ├── GEO Forge: Agency Dashboard          ← 多站点管理面板
│   └── GEO Forge: x402 Payment Gateway      ← 未来电商协议（等生态成熟）
│
└── 🌍 第三方扩展（社区贡献）
    ├── (预留) 任何插件可实现 GEO_Forge_Extension_Interface
    └── (预留) WordPress.org 扩展目录审核
```

### 9.4 钩子系统

```php
// === Actions ===

// 扫描完成时触发
do_action('geo_forge_scan_completed', $scan_id, $result);

// 修复执行前触发（可中止）
do_action('geo_forge_before_fix', $fix_id, $context);

// 修复完成时触发
do_action('geo_forge_after_fix', $fix_id, $result);

// 分数变化时触发（上升或下降）
do_action('geo_forge_score_changed', $old_score, $new_score, $reason);

// 新问题发现时触发
do_action('geo_forge_issues_detected', $issues[]);

// === Filters ===

// 修改 API 请求参数
apply_filters('geo_forge_api_request_params', $params, $endpoint);

// 修改自动生成的 llms.txt 内容
apply_filters('geo_forge_llms_txt_content', $content, $store_info);

// 修改 security.txt 内容
apply_filters('geo_forge_security_txt_content', $content);

// 修改评分阈值（用于自定义告警）
apply_filters('geo_forge_alert_threshold', 50);

// 修改 MCP 工具列表
apply_filters('geo_forge_mcp_tools', $tools);

// 修改扫描结果缓存时间
apply_filters('geo_forge_cache_ttl', DAY_IN_SECONDS);
```

---

## 10. Admin UI 设计

### 10.1 主导航层级

```
WooCommerce
├── ...
├── GEO Forge                    ← 顶级菜单
│   ├── Dashboard                ← 概览仪表盘
│   ├── Scan Results             ← 详细扫描结果
│   ├── Fix Center               ← 修复中心
│   ├── Monitor                  ← 监控历史
│   │   ├── Scan History
│   │   └── Score Trends
│   ├── Content Editors          ← 内容管理
│   │   ├── llms.txt Editor
│   │   ├── security.txt Editor
│   │   └── Structured Data Test
│   ├── Settings                 ← 设置
│   │   ├── API Configuration
│   │   ├── Auto-Fix Settings
│   │   └── Notifications
│   └── Extensions               ← 扩展管理
```

### 10.2 Dashboard 线框图

```
┌─────────────────────────────────────────────────────────────┐
│  GEO Forge                                     🔗 API: ✅   │
│  Last scan: July 14, 2026 02:02 UTC                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │   38     │  │  7/22    │  │   19     │  │  Poor    │   │
│  │ AI Score │  │  Issues  │  │ Fixable  │  │  Grade   │   │
│  │  /100    │  │  Found   │  │  Auto    │  │  🟠      │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Score Trend (30 days)                   │   │
│  │  100 ┤                                              │   │
│  │   80 ┤                    ┌目标 80+                  │   │
│  │   60 ┤         ╭──────╮                             │   │
│  │   40 ┤─────────╯      ╰─────────────────────────────│   │
│  │   20 ┤                                              │   │
│  │    0 ┤                                              │   │
│  │      └──────────────────────────────────────────────│   │
│  │       Jul 13       Jul 14       Jul 15     ...       │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────┐ ┌────────────────────────┐    │
│  │ Category Breakdown      │ │ Priority Actions        │    │
│  │                         │ │                         │    │
│  │ 🟢 AI Readability  83%  │ │ 🔴 MCP Endpoint         │    │
│  │ 🟡 Discoverability 57%  │ │ 🔴 Commerce Protocols   │    │
│  │ 🟡 Accessibility   56%  │ │ 🔴 HSTS Config          │    │
│  │ 🟠 Bot Control     40%  │ │ 🟡 llms.txt Quality     │    │
│  │ 🔴 Security        33%  │ │ 🟡 Markdown Negotiation │    │
│  │ 🔴 Protocol         5%  │ │                         │    │
│  │ 🔴 Commerce         0%  │ │  [Fix All High] [Scan] │    │
│  └─────────────────────────┘ └────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### 10.3 Fix Center 线框图

```
┌─────────────────────────────────────────────────────────────┐
│  Fix Center                                  🔄 Auto-Fix: ON │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Filters: [All] [Auto-Fixable] [Manual] [Fixed] [Skipped]  │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 🔴 Priority 1 — Critical (4 issues)                  │   │
│  │                                                     │   │
│  │ ☑ security.txt             2/10  → Fix: +8  [Auto] │   │
│  │ ☑ AI Privacy Compliance    0/5   → Fix: +5  [Auto] │   │
│  │ ☑ MCP Server Card          0/5   → Fix: +5  [Auto] │   │
│  │ ☑ Content Signals          0/5   → Fix: +5  [Auto] │   │
│  │                        [Fix Selected] [Fix All P1]  │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 🟡 Priority 2 — Warning (8 issues)                   │   │
│  │   ...                                                │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 🔵 Priority 3 — Optional (7 issues)                  │   │
│  │   ...                                                │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ ⚪ Priority 4 — Future (3 issues)                     │   │
│  │   x402 / ACP / WebMCP — 等生态成熟                   │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 11. 安全与性能设计

### 11.1 安全措施

| 层级 | 措施 | 说明 |
|------|------|------|
| **API Key** | WordPress Option 加密存储 | 使用 `wp_hash()` + salt |
| **Admin** | `manage_woocommerce` 权限 | 只有店主和管理员可操作 |
| **REST API** | `permission_callback` 验证 | 所有自定义端点鉴权 |
| **Nonce** | `wp_nonce_url` / `check_ajax_referer` | 所有表单和 AJAX |
| **输入验证** | `sanitize_text_field` / `wp_kses` | 所有用户输入 |
| **输出转义** | `esc_html` / `esc_attr` / `wp_kses_post` | 所有输出 |
| **文件操作** | WP Filesystem API | 不直接写文件系统 |
| **Rewrite** | `flush_rewrite_rules` 限频 | 仅在必要时刷新 |

### 11.2 性能优化

| 优化 | 实现 |
|------|------|
| **懒加载** | 仅在访问插件页面时加载 JS/CSS |
| **API 缓存** | Transients 缓存扫描结果，减少 API 调用 |
| **异步扫描** | API 调用使用 `wp_remote_post` 异步，不阻塞页面 |
| **后台 Cron** | 定时扫描使用 WP Cron，不影响前端 |
| **批量操作** | 批量修复使用队列，避免超时 |
| **静态文件** | llms.txt / security.txt 等通过虚拟路由，无 I/O |
| **零额外查询** | 除扫描结果外，不在前端增加数据库查询 |

### 11.3 兼容性矩阵

```
WordPress:  6.0+
WooCommerce: 8.0+
PHP:        7.4+ (推荐 8.1+)
MySQL:      5.7+ / MariaDB 10.3+
服务器:      Apache / Nginx / LiteSpeed
HTTPS:      必需（MCP/A2A 协议要求）
```

---

## 12. 数据库 Schema

### 12.1 自定义表

```sql
-- 扫描历史表
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}geo_forge_scans (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scan_id         VARCHAR(36) NOT NULL,           -- GEO KAMI scanId
    total_score     INT NOT NULL DEFAULT 0,
    grade           VARCHAR(10) NOT NULL,
    grade_label     VARCHAR(50) NOT NULL,
    category_scores LONGTEXT,                       -- JSON
    checks_result   LONGTEXT,                       -- JSON
    suggestions     LONGTEXT,                       -- JSON
    points_cost     INT NOT NULL DEFAULT 0,
    scan_duration_ms INT,
    completed_at    DATETIME,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY scan_id (scan_id),
    KEY total_score (total_score),
    KEY created_at (created_at)
) {$charset_collate};

-- 修复记录表
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}geo_forge_fixes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fix_id          VARCHAR(50) NOT NULL,           -- e.g. 'security_txt'
    scan_id         VARCHAR(36),                     -- 关联的扫描
    status          VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|applied|verified|rolled_back
    score_change    INT DEFAULT 0,
    snapshot        LONGTEXT,                       -- 修复前状态 JSON
    error_message   TEXT,
    applied_at      DATETIME,
    verified_at     DATETIME,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY fix_id (fix_id),
    KEY status (status)
) {$charset_collate};

-- 分数历史表（趋势数据）
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}geo_forge_score_history (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    total_score     INT NOT NULL,
    grade           VARCHAR(10) NOT NULL,
    categories      LONGTEXT,                       -- JSON
    trigger         VARCHAR(50) NOT NULL DEFAULT 'manual', -- manual|scheduled|event
    recorded_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY recorded_at (recorded_at)
) {$charset_collate};
```

### 12.2 WordPress Options

```php
// API 配置
'geo_forge_api_key'              => string,  // 加密存储
'geo_forge_api_base'             => 'https://api.geokami.com',
'geo_forge_auto_scan_enabled'    => 'yes',
'geo_forge_scan_frequency'       => 'daily',  // daily|weekly|monthly
'geo_forge_auto_fix_enabled'     => 'yes',
'geo_forge_auto_fix_risk_level'  => 'low',    // none|low|medium

// 生成的内容
'geo_forge_llms_txt'             => string,
'geo_forge_security_txt'         => string,
'geo_forge_mcp_config'           => string,   // JSON
'geo_forge_a2a_config'           => string,   // JSON
'geo_forge_openapi_spec'         => string,   // JSON
'geo_forge_social_profiles'      => array,

// 通知设置
'geo_forge_notify_score_drop'    => 'yes',
'geo_forge_notify_email'         => string,
'geo_forge_notify_threshold'     => 50,       // 低于此分数告警

// 缓存
'geo_forge_last_scan_result'     => string,   // JSON (24h cache)
'geo_forge_last_scan_time'       => string,
'geo_forge_account_info'         => string,   // JSON (1h cache)
```

---

## 13. 文件结构清单

```
geo-forge/
│
├── geo-forge.php                          # 插件入口：Plugin Name, 版本检查, 常量定义, Bootstrap
├── uninstall.php                          # 卸载清理
├── readme.txt                             # WordPress.org 插件目录说明
│
├── includes/
│   ├── class-geo-forge.php                # 主控制器：初始化所有模块, 加载文本域
│   ├── class-api-client.php               # GEO KAMI API HTTP 客户端
│   ├── class-scanner.php                  # 扫描编排器
│   ├── class-fixer.php                    # 修复引擎
│   ├── class-monitor.php                  # 变更监控 + WP Cron
│   ├── class-admin.php                    # Admin UI 注册
│   ├── class-settings.php                 # 设置页面 + API
│   ├── class-headers.php                  # HTTP 头管理
│   ├── class-well-known.php              # /.well-known/* 虚拟路由
│   ├── class-llms-txt.php                # llms.txt 生成器/编辑器
│   ├── class-security-txt.php            # security.txt 生成器
│   ├── class-structured-data.php         # Schema/JSON-LD 增强
│   ├── class-markdown.php                # Markdown 内容协商 + .md 变体
│   ├── class-mcp.php                      # MCP 端点实现
│   ├── class-a2a.php                      # A2A Agent Card + 端点
│   ├── class-openapi.php                 # OpenAPI Spec 生成
│   ├── class-routes.php                  # 自定义 REST API 注册
│   ├── class-cache.php                   # 本地缓存层
│   ├── class-extensions.php              # 扩展加载器
│   ├── class-logger.php                   # 结构化日志
│   ├── class-notifications.php           # Email 通知
│   ├── class-install.php                  # 激活/卸载钩子
│   └── interface-extension.php           # 扩展接口定义
│
├── admin/
│   ├── css/
│   │   ├── admin.css                     # Admin 样式
│   │   └── admin-dark.css               # 暗色模式（WP 6.6+）
│   ├── js/
│   │   ├── admin.js                      # Admin 通用脚本
│   │   ├── dashboard.js                  # Dashboard 图表 (Chart.js)
│   │   ├── fix-center.js                 # 修复中心交互
│   │   └── llms-editor.js               # llms.txt 编辑器 (CodeMirror)
│   └── views/
│       ├── page-dashboard.php            # 仪表盘
│       ├── page-scan-results.php         # 扫描结果
│       ├── page-fix-center.php           # 修复中心
│       ├── page-monitor.php              # 监控历史
│       ├── page-llms-editor.php          # llms.txt 编辑器
│       ├── page-security-editor.php      # security.txt 编辑器
│       ├── page-structured-data.php      # 结构化数据测试
│       ├── page-settings.php             # 设置页面
│       ├── page-extensions.php           # 扩展管理
│       └── components/
│           ├── score-badge.php           # 分数徽章组件
│           ├── category-bar.php          # 分类得分条
│           ├── check-card.php            # 单个检查项卡片
│           ├── fix-button.php            # 修复按钮组件
│           ├── trend-chart.php           # 趋势图组件
│           └── notification-banner.php   # 通知横幅
│
├── templates/
│   ├── llms-txt-template.php            # llms.txt 默认模板
│   ├── security-txt-template.php        # security.txt 默认模板
│   ├── mcp-card-template.php            # MCP 卡片默认模板
│   ├── a2a-card-template.php            # A2A 卡片默认模板
│   ├── md-product-template.php          # 产品 .md 变体模板
│   └── email/
│       ├── score-drop.php               # 分数下降告警邮件
│       └── weekly-report.php            # 每周报告邮件
│
├── languages/
│   ├── geo-forge.pot                    # 翻译模板
│   ├── geo-forge-zh_CN.po              # 简体中文
│   └── geo-forge-zh_CN.mo
│
├── assets/
│   ├── icon.svg                         # 插件图标 (128x128)
│   ├── icon-256x256.png                # WordPress.org 高清图标
│   ├── banner-772x250.png              # WordPress.org Banner
│   └── screenshot-*.png                 # 截图 (6张)
│
└── vendor/                              # Composer 依赖（如有）
    └── (minimal — 尽量零外部依赖)
```

---

## 14. 开发路线图

### Phase 1: Core Foundation (Week 1-2)

```
目标: 最小可用产品 — 连接 API + 扫描 + 基础展示
```

| 任务 | 优先级 | 文件 |
|------|--------|------|
| 插件骨架 + 激活/卸载 | P0 | `geo-forge.php`, `class-install.php` |
| API Client (含认证、重试、超时) | P0 | `class-api-client.php` |
| Scanner 编排器 (手动触发) | P0 | `class-scanner.php` |
| 本地缓存层 | P0 | `class-cache.php` |
| Admin Dashboard 基础版 | P1 | `page-dashboard.php` |
| Settings 页面 (API Key 配置) | P1 | `class-settings.php` |
| Admin CSS/JS 基础框架 | P1 | `admin/css/`, `admin/js/` |
| 数据库表创建 | P1 | `class-install.php` |

### Phase 2: Fix Engine (Week 3-4)

```
目标: 自动修复核心能力 — llms.txt, security.txt, 结构化数据
```

| 任务 | 优先级 | 文件 |
|------|--------|------|
| Fixer 引擎 + 原子操作框架 | P0 | `class-fixer.php`, `interface-extension.php` |
| Well-Known 虚拟路由 | P0 | `class-well-known.php` |
| llms.txt 生成器 + 编辑器 | P0 | `class-llms-txt.php`, `page-llms-editor.php` |
| security.txt 生成器 | P0 | `class-security-txt.php` |
| Structured Data 增强 | P0 | `class-structured-data.php` |
| Fix Center UI | P1 | `page-fix-center.php` |
| 修复回滚机制 | P2 | `class-fixer.php` |
| REST API 端点 (扫描状态等) | P2 | `class-routes.php` |

### Phase 3: Protocol & Advanced (Week 5-6)

```
目标: MCP/A2A 协议支持 + Markdown 协商 + 监控
```

| 任务 | 优先级 | 文件 |
|------|--------|------|
| MCP 端点实现 | P0 | `class-mcp.php` |
| A2A Agent Card + 端点 | P0 | `class-a2a.php` |
| Markdown 内容协商 | P0 | `class-markdown.php` |
| .md 页面变体生成 | P1 | `class-markdown.php` |
| OpenAPI Spec 自动生成 | P1 | `class-openapi.php` |
| HTTP 头管理 | P1 | `class-headers.php` |
| Monitor + WP Cron | P1 | `class-monitor.php` |
| 趋势图表 (Dashboard) | P2 | `trend-chart.php` |

### Phase 4: Polish & Launch (Week 7-8)

```
目标: 扩展系统 + 通知 + i18n + WordPress.org 上架
```

| 任务 | 优先级 | 文件 |
|------|--------|------|
| 扩展加载器 + 钩子系统 | P0 | `class-extensions.php` |
| Email 通知系统 | P1 | `class-notifications.php` |
| 日志系统 | P1 | `class-logger.php` |
| i18n (EN + ZH) | P1 | `languages/` |
| WordPress.org readme | P1 | `readme.txt` |
| 截图 + Banner 制作 | P1 | `assets/` |
| 安全审计 | P0 | (全部文件) |
| 性能测试 | P1 | — |

### Phase 5: Extensions (Ongoing)

```
未来持续交付的扩展
```

| 扩展 | 预计时间 | 依赖 |
|------|---------|------|
| GEO Forge: Rank Math Integration | Month 3 | Phase 4 完成 |
| GEO Forge: Multi-Language Support | Month 3 | WPML/Polylang |
| GEO Forge: Agency Dashboard | Month 4-5 | 多站点架构 |
| GEO Forge: Google Search Console | Month 5 | GSC API |
| GEO Forge: x402 Payment (生态就绪后) | TBD | x402 RFC 标准化 |

---

## 15. 商业模式设计

### 15.1 插件分层策略

```
┌────────────────────────────────────────────────────┐
│                   GEO FORGE                        │
│                                                    │
│  ┌──────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  FREE    │  │     PRO      │  │   AGENCY     │ │
│  │  (WP.org)│  │  ($9.99/mo)  │  │ ($29.99/mo)  │ │
│  ├──────────┤  ├──────────────┤  ├──────────────┤ │
│  │• 手动扫描│  │• 每日自动扫描│  │• PRO 全部     │ │
│  │• 基础修复│  │• 所有自动修复│  │• 多站点管理   │ │
│  │• llms.txt│  │• MCP/A2A端点 │  │• 白标报告     │ │
│  │• 基础图表│  │• Markdown协商│  │• API Key管理  │ │
│  │• 扫描3次/│  │• 监控+告警   │  │• 优先支持     │ │
│  │  月      │  │• 趋势报告    │  │• 专属扩展     │ │
│  └──────────┘  │• 无限扫描    │  └──────────────┘ │
│                │• Email通知   │                    │
│                └──────────────┘                    │
└────────────────────────────────────────────────────┘
```

### 15.2 与 GEO KAMI 平台的协同

```
GEO Forge (插件) ←→ GEO KAMI Cloud (SaaS)
       │                    │
       │  API Key 认证       │  积分系统
       │  扫描请求           │  订阅计划
       │  修复验证           │  账户管理
       │                    │
       ▼                    ▼
  WooCommerce 店主      统一云端数据
```

**计费模型:**
- 插件本身免费（WordPress.org 分发的 .org 版本）
- 扫描消耗 GEO KAMI 积分（复用现有积分系统）
- 高级功能通过 GEO KAMI 订阅解锁（Pro/Business 计划）
- Agency 版本独立计费

---

## 16. 竞品分析

### 当前市场定位空白

| 竞品 | 类型 | SEO | Schema | llms.txt | MCP | Markdown协商 | AI Agent |
|------|------|-----|--------|----------|-----|-------------|----------|
| Rank Math SEO | SEO 插件 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Yoast SEO | SEO 插件 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| SEOPress | SEO 插件 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Schema & Structured Data | Schema 插件 | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| WP LLMs.txt | 小众插件 | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| **GEO Forge** | **GEO 综合** | **✅** | **✅** | **✅** | **✅** | **✅** | **✅** |

**GEO Forge 是唯一一个覆盖 "SEO + Schema + AI Agent 协议 + 电商" 全链路的 WooCommerce 插件。**

### 市场时机

- 2025 Q4: "GEO" (Generative Engine Optimization) 术语开始在 SEO 社区爆发
- 2026 Q1-Q2: llms.txt 协议被广泛讨论，但尚无成熟工具
- 2026 Q3: MCP/A2A 协议开始从开发者讨论进入生产应用
- 2026: **最佳入市窗口** — 市场教育已完成，但尚无统治级产品

---

## 附录 A: 插件代码量预估

| 模块 | 预估行数 |
|------|---------|
| `geo-forge.php` + Bootstrap | 200 |
| `class-api-client.php` | 400 |
| `class-scanner.php` | 350 |
| `class-fixer.php` | 600 |
| `class-monitor.php` | 300 |
| `class-well-known.php` | 400 |
| `class-llms-txt.php` | 350 |
| `class-security-txt.php` | 200 |
| `class-structured-data.php` | 400 |
| `class-markdown.php` | 300 |
| `class-mcp.php` | 500 |
| `class-a2a.php` | 300 |
| `class-openapi.php` | 250 |
| `class-routes.php` | 300 |
| `class-headers.php` | 200 |
| `class-extensions.php` | 300 |
| `class-admin.php` | 400 |
| `class-settings.php` | 300 |
| `class-cache.php` | 150 |
| `class-logger.php` | 150 |
| `class-notifications.php` | 250 |
| `class-install.php` | 200 |
| `interface-extension.php` | 80 |
| Admin Views (9 files) | ~2500 (total) |
| Admin JS (4 files) | ~1500 (total) |
| Admin CSS (2 files) | ~800 (total) |
| Templates (7 files) | ~1000 (total) |
| **总计** | **~12,000 行** |

---

## 附录 B: 关键决策记录

1. **为什么不修改 .htaccess 文件？** — 跨服务器兼容性考虑，使用 WordPress Rewrite API 实现虚拟路由，不依赖特定服务器软件。

2. **为什么 MCP 端点是代理模式而非自实现？** — WordPress 不适合运行长时间 AI 推理。代理模式将复杂逻辑委托给 GEO KAMI Cloud，插件只负责数据暴露。

3. **为什么用 Transients 而非文件缓存？** — WordPress Transients 支持过期时间和对象缓存（Redis/Memcached），比文件缓存更符合 WP 生态。

4. **为什么不支持 x402/ACP/UCP/MPP 在第一版？** — 这些协议在 2026 年 7 月仍在早期标准化阶段，生态不成熟。插件预留了扩展接口，等协议稳定后可通过扩展快速接入。

---

**文档版本:** v1.0  
**创建日期:** 2026-07-15  
**作者:** GEO KAMI Team  
**状态:** Ready for Development Kickoff 🚀
