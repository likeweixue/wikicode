# Wikicode 设计指南

> 版本 v0.7.0 · 最后更新 2026-11-20

Wikicode 的视觉语言是「**工程蓝图 + 复古印刷品**」的混合体：米色纸张承托墨色文字，橙色作为唯一强调色，直角边框与偏移阴影模拟铅字印刷的压痕，等宽字体的小字标签则像工程图纸上的标注。整体克制、精确、不喧闹。

---

## 1. 设计理念

| 原则 | 说明 |
|---|---|
| **纸张感** | 背景不是纯白，而是偏暖的米色，模拟实体文档/纸张，长时间阅读更柔和。 |
| **墨色优先** | 文字、边框、图标默认用接近纯黑的墨色，而不是灰色，保证印刷级的清晰度。 |
| **单色强调** | 全局只有一个强调色（安全橙）。它只用于「可点击 / 已激活 / 数据」，绝不滥用做装饰。 |
| **硬边直角** | 按钮、卡片、输入框均为 `border-radius: 0`，配合偏移阴影形成「贴纸/印章」质感。 |
| **标注式排版** | 区块标题上方必有一行等宽小字的 `SECTION CODE` 标签，像图纸的图号标注。 |
| **等宽即技术** | 所有元信息（路径、版本、状态、计数）使用等宽字体，传递「这是给开发者看的」信号。 |

---

## 2. 色板

所有颜色通过 CSS 变量定义，位于 `public/assets/app.css` 的 `:root`（浅色）与 `html[data-theme="dark"]`（深色）。

### 2.1 浅色主题（默认）

| 变量 | 色值 | 用途 |
|---|---|---|
| `--bg` | `#f3f0e8` | 页面底色（米纸） |
| `--panel` | `#faf8f1` | 卡片/面板底色 |
| `--panel-strong` | `#fffdf7` | 悬浮/输入框底色 |
| `--ink` | `#171715` | 主文字、边框、图标（墨黑） |
| `--muted` | `#706e66` | 次要文字、说明 |
| `--line` | `#c8c4b8` | 分隔线、弱边框 |
| `--soft` | `#e7e2d6` | 浅填充（hover/计数徽章底） |
| `--soft-2` | `#eeeae0` | 更浅的填充 |
| `--accent` | `#ff7500` | **强调色**（安全橙） |
| `--accent-soft` | `#ffe1c6` | 强调色的浅注入背景 |
| `--accent-ink` | `#1b1005` | 强调按钮上的文字色 |
| `--danger` | `#c33b2e` | 错误/删除 |
| `--success` | `#287a53` | 成功/在线状态 |
| `--info` | `#2971b8` | 信息提示 |
| `--shadow` | `rgba(23,23,21,.14)` | 投影 |
| `--grid` | `rgba(23,23,21,.035)` | 背景网格纹理 |

### 2.2 深色主题

| 变量 | 色值 |
|---|---|
| `--bg` | `#10100f` |
| `--panel` | `#181817` |
| `--panel-strong` | `#1e1e1c` |
| `--ink` | `#f2efe5` |
| `--muted` | `#a6a299` |
| `--line` | `#424039` |
| `--soft` | `#2b2a26` |
| `--accent-soft` | `#4b2c10` |
| `--danger` | `#f27667` |
| `--success` | `#65c596` |
| `--info` | `#79b5ec` |

> 深色主题下 `--accent`（`#ff7500`）保持不变，在暗底上更具警示性，是深色模式里唯一的暖色锚点。

### 2.3 用色铁律

1. **强调色只用于**：链接、激活态 tab、按钮主操作、星标/计数、当前状态。
2. **不要**用强调色做大面积背景填充（除了主键按钮悬停）。
3. 信息层级靠 **墨色 → 灰色 → 浅灰** 三级文字色建立，不靠颜色堆砌。
4. 状态色（danger/success/info）只在真正表达状态时出现，不做装饰。

---

## 3. 字体排印

### 3.1 字体族

```css
--mono: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
--sans: Inter, "PingFang SC", "Microsoft YaHei", "Noto Sans CJK SC", system-ui, sans-serif;
```

- **等宽（mono）**：元信息、代码、路径、标签、计数、logo。是 Wikicode 的「声音」。
- **无衬线（sans）**：正文、标题、描述。中文回退到苹方 / 微软雅黑。

### 3.2 字号阶梯

| 用途 | 字号 | 字体 | 字重 |
|---|---|---|---|
| Hero 主标题 | `clamp(54px, 7.2vw, 106px)` | sans | 800，`letter-spacing: -.065em` |
| 页面标题 h1 | `clamp(30px, 4vw, 48px)` | sans | 800，`letter-spacing: -.04em` |
| 区块标题 h2 | 18px | sans | 700 |
| 正文 | 14px | sans | 400 |
| 仓库名 | 18px | **mono** | 700，`letter-spacing: -.4px` |
| 导航 | 13px | sans | 600 |
| 按钮 | 12px | sans | 700 |
| Section Code 标注 | 10px | **mono** | 400，`letter-spacing: 1.25px`，大写 |
| 徽章/标签 | 9px | **mono**，大写，`letter-spacing: .4–1px` |
| 顶部状态栏 | 9px | **mono**，`letter-spacing: .65px` |
| Logo 主字 | 22px | **mono** | 800，`letter-spacing: -1.2px` |
| Logo 副标 | 8px | **mono**，`letter-spacing: 1.15px` |

### 3.3 排版规则

- 标题一律 **负字距**（`letter-spacing` 为负），制造紧凑的现代感。
- 等宽小字标签一律 **大写 + 正字距**，和标题形成「大紧小松」的对比节奏。
- 正文行高 `1.85`（阅读友好），UI 文本行高 `1.2–1.4`。

---

## 4. 间距、圆角与阴影

### 4.1 圆角

- **全局直角**：`border-radius: 0`。
- 唯一例外：状态点、计数徽章（`border-radius: 9px` / `50%`）、头像等圆点元素。

> 直角是 Wikicode 识别度最高的特征之一，新增组件请保持直角。

### 4.2 偏移阴影（标志性交互）

按钮悬停：

```css
.btn:hover {
    transform: translate(-2px, -2px);          /* 向左上抬起 */
    box-shadow: 3px 3px 0 var(--ink);          /* 右下实心阴影，无模糊 */
}
```

- 阴影是 **实心、无模糊、3px** 的，模拟贴纸/印章的偏移，而不是 Material Design 的柔和投影。
- 激活/悬停用 `box-shadow: inset 3px 0 0 var(--accent)` 在左侧注入一条 3px 强调色竖条，表示「当前选中」。

### 4.3 间距基准

以 4px 为基本单位，常用：`4 / 8 / 12 / 16 / 20 / 24 / 32 / 44`。

---

## 5. 组件规范

### 5.1 按钮（.btn）

```css
.btn {
    min-height: 40px; padding: 9px 17px;
    border: 1px solid var(--ink);        /* 墨色实边 */
    background: var(--panel);
    font: 700 12px var(--sans);
    border-radius: 0;
}
```

| 变体 | 类名 | 外观 |
|---|---|---|
| 默认 | `.btn` | 透明底 + 墨边，hover 偏移阴影 |
| 主操作 | `.btn-primary` | 强调色底，hover 偏移阴影 |
| 次级 | `.btn-ghost` | 无边框/弱边框 |
| 危险 | `.btn-danger` | danger 色边，hover danger 实底 |
| 小号 | `.btn-small` / `.btn-mini` | 32px / 28px 高，11px 字 |

### 5.2 徽章（.badge）

9px 等宽大写，1px 边框，用于仓库可见性、状态、角色。激活态用强调色注入背景。

### 5.3 卡片（.panel / 仓库卡片）

- 1px `var(--border)` 边框，`var(--panel)` 底色。
- 标题左对齐，上方一行 `SECTION CODE` 小字标注。
- 卡片之间用间距分隔，不用阴影堆叠（保持纸张平面感）。

### 5.4 区块标注（.section-code）

每个区块标题上方都有：

```
PUBLIC ARCHIVE / EXPLORE      ← 10px mono, 大写, 灰色
探索                            ← 大标题
```

这是 Wikicode 的标志性版式，新增页面请沿用。

---

## 6. 图标规范

图标统一由 `app/Core/Icons.php` 管理，**全部为手绘线性 SVG**，不使用 emoji 或外部图标库。

### 6.1 图标规范

- 画布：`viewBox="0 0 24 24"`
- 样式：`fill="none"`，`stroke="currentColor"`，`stroke-width="1.6"`
- 端点：`stroke-linecap="round"`，`stroke-linejoin="round"`
- 颜色：用 `currentColor`，自动继承父元素文字色 —— 激活时随强调色变色，深色模式自动反色。
- 尺寸：默认 16×16，通过 `width`/`height` 属性控制。

### 6.2 设计原则

- 用**最少的线条**表达语义，不做填充、不加渐变。
- 风格统一：全部 1.6px 等粗线条、圆角端点。
- 未知/未注册的 key 回退到「四宫格」通用图标（`.other`），保证不破版。
- 分类图标 key 与分类 `slug` 一致，存于 `categories.icon` 字段。

> 严禁在界面里使用 emoji 作为功能图标（苹果表情与整体风格冲突）。emoji 仅可出现在用户提交的 Markdown 正文里。

---

## 7. 布局

- 顶部：`system-strip`（9px 状态栏）→ 主导航（logo + 导航 + 主题切换 + 用户）。
- 内容容器：`.shell`（最大宽度居中，两侧留白）。
- 探索页三栏：`grid-template-columns: 220px 1fr 300px`，≤1180px 右栏落底，≤760px 全部单列。
- 响应式断点：`1180px` / `760px`。

---

## 8. 动效

- 全局克制，只在交互反馈时出现：
  - 按钮 hover：`transform .15s` + `box-shadow .15s`
  - 链接/卡片：`color .12s` / `background .12s`
- 不使用入场动画、不使用弹性曲线，保持印刷品的「静态精确」感。
- 平滑滚动仅用于面板内跳转（如切换文档 tab）。

---

## 9. 无障碍与国际化

- 所有图标 `aria-hidden="true"`，文字信息不依赖图标单独传达。
- 主题切换有 `data-theme` 属性，色板完全变量化，新增主题只需覆盖变量。
- 文案走 `t()` 翻译函数，语言包在 `lang/zh-CN.php` 与 `lang/en.php`；英文缺省回退到 key 本身。

---

*本文档随版本演进。新增视觉元素时，请先对照本指南确认是否符合「纸张 · 墨色 · 单色橙 · 直角 · 等宽标注」的基调。*
