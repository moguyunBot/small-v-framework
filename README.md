# Small V Framework

基于 Webman 的现代化 PHP 后台管理框架，集成 Vue 3 与插件系统。

## ✨ 特性

- 🚀 **高性能** - 基于 Workerman 的常驻内存 PHP 框架，性能远超传统 FPM
- 🎨 **现代化 UI** - Bootstrap 5 + 自研 ModernAlert 弹窗系统
- ⚡ **Vue 3** - 表单/列表页面使用 Vue 3 驱动，无需编译
- 🔐 **完善权限** - 基于角色的菜单权限控制，支持插件独立权限
- 🧩 **插件系统** - 支持插件安装、卸载、启用、停用，插件拥有独立后台
- 📦 **开箱即用** - 内置管理员、角色、菜单、配置、插件等核心功能
- 🌍 **环境变量** - 支持 `.env` 配置，敏感信息不入代码

## 🛠️ 技术栈

### 后端
- **框架**: [Webman](https://www.workerman.net/webman) - 基于 Workerman 的高性能 PHP 框架
- **ORM**: ThinkORM - 强大的数据库 ORM
- **模板引擎**: ThinkTemplate - 灵活的模板引擎
- **环境变量**: vlucas/phpdotenv

### 前端
- **框架**: Vue 3 - 渐进式 JavaScript 框架（CDN 引入，无需编译）
- **UI**: Bootstrap 5 - 现代化响应式 UI
- **弹窗**: ModernAlert - 自研轻量级弹窗组件（纯原生 JS，无依赖）
- **图标**: Material Design Icons
- **编辑器**: WangEditor 富文本编辑器
- **树形**: jsTree

## 📦 安装

### 环境要求

- **PHP >= 8.4**
- MySQL >= 5.7
- Composer
- Linux / macOS（Workerman 在 Windows 下功能受限）

### 安装步骤

1. 克隆项目
```bash
git clone https://gitee.com/4620337/small-v-framework.git
cd small-v-framework
```

2. 安装依赖
```bash
composer install
```

3. 配置环境变量
```bash
cp .env.example .env
# 编辑 .env，配置数据库连接信息
```

4. 导入数据库
```bash
# 将 database.sql 导入到你的 MySQL 数据库
```

5. 启动服务
```bash
php start.php start
# 守护进程模式
php start.php start -d
```

6. 访问后台
```
http://localhost:8787/admin
```

默认账号：`admin` 密码：`admin123`

## 🔧 配置

编辑 `.env` 文件：

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASSWORD=your_password
```

## 📚 核心功能

### 1. 权限管理
- **菜单管理** - 支持多级菜单，灵活配置 href 和图标
- **角色管理** - 基于角色的权限控制，支持超级管理员（`*`）
- **账户管理** - 管理员账户的增删改查

### 2. 插件系统
- 插件扫描、安装、卸载、启用、停用、删除
- 插件拥有独立后台路由、菜单、权限、配置
- 插件后台继承主后台 `Base` 控制器，菜单自动切换为插件菜单
- 插件权限通过 `Auth` 中间件统一验证，精确到控制器方法级别

### 3. 系统配置
- 分组配置管理，支持插件独立配置分组
- 支持文本、图片、开关、下拉等多种配置类型

### 4. 开发工具
- 内置调试控制器，快速验证功能

## 🧩 插件开发规范

插件目录结构：
```
plugin/{identifier}/
├── app/
│   ├── admin/controller/   # 后台控制器（继承 app\admin\controller\Base）
│   ├── admin/view/         # 后台视图
│   ├── controller/         # 前台控制器
│   ├── model/              # 数据模型
│   └── view/               # 前台视图
├── config/
│   ├── app.php             # 插件基本信息
│   ├── menu.php            # 后台菜单配置
│   ├── middleware.php      # 中间件配置（key: admin）
│   └── view.php            # 视图配置
└── install.sql             # 安装 SQL
```

插件中间件配置（`plugin/{name}/config/middleware.php`）：
```php
return [
    'admin' => [
        \app\admin\middleware\Auth::class,
    ],
];
```

## 🎨 ModernAlert 弹窗系统

自研现代化弹窗组件，替代 layer.js 和 sweetalert2。

```javascript
// 提示/确认/输入
ModernAlert.alert('提示信息');
ModernAlert.confirm('确定删除？').then(ok => { if (ok) { ... } });
ModernAlert.prompt('请输入名称').then(val => { ... });

// Toast
ModernAlert.success('操作成功');
ModernAlert.error('操作失败');
ModernAlert.warning('请注意');
ModernAlert.info('提示');

// 加载
ModernAlert.loading('加载中...');
ModernAlert.closeLoading();
```

## 🔥 Vue 3 全局 Mixin

所有页面通过 `createVueApp()` 创建实例，自动注入以下方法：

| 分类 | 方法 |
|------|------|
| 弹窗 | `alert` `confirm` `prompt` `success` `error` `warning` `info` |
| 加载 | `showLoading` `hideLoading` |
| 请求 | `post` `get` `submitForm` `deleteConfirm` |
| 工具 | `formatDate` `copyToClipboard` `debounce` `throttle` |

## 📖 参考文档

- [Webman 官方文档](https://www.workerman.net/doc/webman)
- [Vue 3 官方文档](https://vuejs.org/)
- [Bootstrap 5 文档](https://getbootstrap.com/)

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 License

MIT License
