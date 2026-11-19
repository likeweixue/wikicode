# Wikicode

> **Collaborative Code Archive** — 一个用 PHP 8 原生写的轻量级代码托管平台。

在线体验：https://wikicode.likeweixue.com

Wikicode 是一个「小但全」的类 GitHub 代码托管平台，专为开发者、小团队或个人作品归档设计。不依赖 Composer 框架、不依赖 Node 构建工具 — 拉下来直接跑 `docker compose up` 就能用。

## ✨ 核心功能

- **Git Smart HTTP**：通过 HTTPS 直接 `git clone` / `git push`，支持 Deploy Token 免密推送
- **仓库浏览**：目录树、Markdown README 渲染（带自动生成的目录侧栏）、文件历史、代码搜索
- **在线编辑**：直接在网页上提交修改、创建新文件、上传二进制
- **标签与发行版**：版本号、标题、Markdown 版本说明、多文件附件（单个最大 64 MB）、预发行版
- **社区**：分区 / 板块 / 主题 / 回复 的论坛，支持 Markdown 与 @ 提及
- **部署令牌（Deploy Token）**：后台一键生成、scope 控制读写、SHA-256 hash 存库、随时吊销
- **多语言 & 明暗主题**：中文 / 英文、亮色 / 暗色切换，用户偏好记忆
- **后台可配置**：顶部导航菜单（添加 / 删除 / 隐藏 / 排序）、SMTP 邮件、基础站点设置

## 🛠 技术栈

| 层 | 技术 |
|---|---|
| 语言 | PHP 8.3（无框架，零 Composer 依赖） |
| Web 服务器 | Apache 2.4 + mod_php（容器内） |
| 数据库 | MySQL 8.4 |
| Git | `git` 二进制（bare 仓库 + `git http-backend`） |
| 前端 | 原生 HTML / CSS / JS，橙色 `#ff7500` 强调色，细线网格风格 |
| 容器 | Docker Compose（app + mysql 两容器） |

## 🚀 快速开始

### 要求

- Docker & Docker Compose
- 至少 2 GB 可用内存

### 本地启动

```bash
git clone https://github.com/likeweixue/wikicode.git
cd wikicode

# 可选：复制并修改 .env
cp .env.example .env

# 启动（首次启动会自动建库、跑迁移、创建管理员）
docker compose up -d --build

# 打开浏览器
open http://localhost:8088
```

### 默认管理员账号

| 项 | 值 |
|---|---|
| 用户名 | `admin` |
| 密码 | `WikiCode2026!` |
| 邮箱 | `admin@wikicode.local` |

**请在首次登录后立即修改密码。**

### 环境变量

在 `.env` 中可覆盖：

```env
# 应用
APP_URL=http://localhost:8088
APP_KEY=local-development-key-change-me

# 初始管理员（仅首次启动生效）
ADMIN_USERNAME=admin
ADMIN_EMAIL=admin@wikicode.local
ADMIN_PASSWORD=WikiCode2026!

# MySQL
MYSQL_DATABASE=wikicode
MYSQL_USER=wikicode
MYSQL_PASSWORD=wikicode_local_2026
MYSQL_ROOT_PASSWORD=wikicode_root_2026
```

## 🔑 通过 Git 推送代码

Wikicode 内置 Git Smart HTTP，有两种认证方式：

### 方式 A：Deploy Token（推荐）

1. 登录 → **后台 → 部署令牌** → 点「生成令牌」，选 scope：
   - **读写** — 允许 `git push`
   - **只读** — 仅允许 `git clone` / `git pull`
2. 记下明文（**只显示一次**）
3. 在你的机器上：

```bash
git remote add wikicode https://oauth2:YOUR_TOKEN@wikicode.likeweixue.com/<owner>/<repo>.git
git push wikicode main
git push wikicode v0.1.0
```

### 方式 B：账号密码

```bash
git remote add wikicode https://admin:your_password@wikicode.likeweixue.com/<owner>/<repo>.git
```

> ⚠️ 账号密码方式安全性较低，仅适合快速测试。正式环境请改用 Deploy Token。

### 权限规则

- **公开仓库**：所有人可 clone；只有仓库 owner 或管理员能 push
- **私有仓库**：仅 owner 和管理员可见、可 push
- **Deploy Token**：继承对应用户的仓库权限 + scope 限制

## 📁 目录结构

```
wikicode/
├── app/
│   ├── Controllers/       # 控制器（Repository / Release / Forum / Auth / Admin …）
│   ├── Core/              # 核心类（Router / Database / Auth / CSRF / View …）
│   ├── Services/          # 业务服务（Git / Repository / Release / DeployToken …）
│   └── views/             # 模板（布局 + 各功能页面 + 组件）
├── database/
│   └── migrations/        # SQL 迁移（按文件名排序执行）
├── docker/                # Dockerfile + entrypoint
├── lang/                  # 语言包（zh-CN / en）
├── public/                # Web 根：index.php + assets/
├── .env.example
├── docker-compose.yml
└── README.md
```

## 🗄 数据库与存储

所有持久化数据都在 Docker named volumes 里：

| Volume | 内容 |
|---|---|
| `mysql-data` | MySQL 数据库 |
| `repository-data` | Git bare 仓库（每个仓库一个 `<id>.git` 目录） |
| `release-data` | 发行版附件 |
| `repository-locks` | 并发操作锁文件 |

备份：`docker compose exec mysql mysqldump -u wikicode -p wikicode > backup.sql`

## 🧱 架构简述

```
浏览器 → Apache/PHP（public/index.php 入口）
         ├─ 拦截 *.git 端点 → GitHttpService → git http-backend
         └─ 普通路由 → Router → Controller → Service → View
                         │                         │
                         ├── MySQL（PDO）          └── Git 二进制（bare worktree）
                         └── 内置迁移器（migrations/*.sql）
```

核心设计决策：

- **无框架**：自己实现 Router、容器、CSRF、认证。代码量可控，便于彻底理解与二次开发。
- **bare 仓库 + worktree 编辑**：网页上点「编辑文件」时，系统会在临时 worktree 里改、commit、移除，保持 bare 仓库干净。
- **迁移器**：`database/migrations/*.sql` 按文件名排序执行，支持 `CREATE TABLE IF NOT EXISTS` 幂等重跑。

## 🛡 安全清单

- [x] CSRF token 保护所有写操作
- [x] 密码 `password_hash` / `password_verify`
- [x] Deploy Token 只存 SHA-256 hash，数据库泄露也反推不出明文
- [x] Deploy Token scope 控制（read / write），write 再叠加 owner / admin 权限
- [x] `.git` 端点路径用白名单字符正则匹配，阻止路径穿越
- [x] SQL 全部用 PDO 预处理，不拼接字符串
- [x] 二进制文件有大小限制；上传走 `move_uploaded_file`
- [x] 所有用户输出走 `htmlspecialchars`（`e()` helper）
- [x] CSP / X-Frame-Options / Referrer-Policy / X-Content-Type-Options 已下发

## 📝 发行说明

详见 [GitHub Releases](https://github.com/likeweixue/wikicode/releases)。当前最新：**v0.6.1 部署令牌（Deploy Token）**。

## 🤝 参与贡献

欢迎 PR。基本流程：

1. Fork & 创建分支
2. 保持代码风格（PSR-12 近似）
3. 新增数据库改动写一个 `database/migrations/NNNN_name.sql`
4. 新增字符串记得加进 `lang/zh-CN.php`（英文可省略，会 fallback 到 key 本身）

## 📄 许可证

**MIT License** — 见仓库根目录 `LICENSE`。

---

> Wikicode · 一起搭建的代码仓库 · [在线体验](https://wikicode.likeweixue.com)
