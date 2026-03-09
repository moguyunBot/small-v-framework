# Small V Framework

基于 Webman 的现代化 PHP 后台管理框架，集成 Vue 3 和现代化 UI 组件。

## ✨ 特性

- 🚀 **高性能** - 基于 Workerman 的超高性能 PHP 框架
- 🎨 **现代化 UI** - Bootstrap 5 + 自定义弹窗系统
- ⚡ **Vue 3** - 最新的 Vue 3 框架，更快更强
- 🔐 **安全可靠** - 完善的权限管理和安全防护
- 📦 **开箱即用** - 内置常用功能，快速开发
- 🌍 **环境变量** - 支持 .env 配置，安全便捷

## 🛠️ 技术栈

### 后端
- **框架**: [Webman](https://www.workerman.net/webman) - 基于 Workerman 的高性能 PHP 框架
- **ORM**: ThinkORM - 强大的数据库 ORM
- **模板引擎**: ThinkTemplate - 灵活的模板引擎
- **环境变量**: vlucas/phpdotenv - 环境配置管理

### 前端
- **框架**: Vue 3.4.21 - 渐进式 JavaScript 框架
- **UI 框架**: Bootstrap 5 - 现代化响应式 UI 框架
- **弹窗系统**: Modern Alert - 自研轻量级弹窗组件
- **图标**: Material Design Icons - 丰富的图标库
- **编辑器**: WangEditor - 富文本编辑器
- **树形组件**: jsTree - 树形结构组件

### 开发工具
- **包管理**: Composer - PHP 依赖管理
- **版本控制**: Git - 代码版本管理

## 📦 安装

### 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- Composer

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
# 编辑 .env 文件，配置数据库等信息
```

4. 导入数据库
```bash
# 导入 database.sql 文件到你的数据库
```

5. 启动服务
```bash
php start.php start
```

6. 访问后台
```
http://localhost:8787/admin
```

## 🔧 配置

### 数据库配置

编辑 `.env` 文件：

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 📚 核心功能

### 1. 权限管理
- 菜单管理 - 灵活的菜单配置
- 角色管理 - 基于角色的权限控制
- 账户管理 - 管理员账户管理

### 2. 系统设置
- 基础配置 - 系统基本信息配置
- 环境变量 - 安全的配置管理

### 3. 开发工具
- 表单构建器 - 可视化表单生成工具

## 🎨 Modern Alert 弹窗系统

自研的现代化弹窗组件，替代 layer.js 和 sweetalert2。

### 基础用法

```javascript
// 提示框
ModernAlert.alert('这是一条提示信息');

// 确认框
ModernAlert.confirm('确定要删除吗？').then(confirmed => {
    if (confirmed) {
        // 用户点击了确定
    }
});

// 输入框
ModernAlert.prompt('请输入名称').then(value => {
    if (value !== null) {
        // 用户输入了内容
    }
});

// Toast 消息
ModernAlert.success('操作成功');
ModernAlert.error('操作失败');
ModernAlert.warning('请注意');
ModernAlert.info('提示信息');

// 加载动画
ModernAlert.loading('加载中...');
ModernAlert.closeLoading();
```

### 特性

- ✅ 纯原生 JavaScript，无依赖
- ✅ 现代化设计，美观大方
- ✅ 支持 Promise，易于使用
- ✅ 响应式设计，移动端友好
- ✅ 支持键盘操作（ESC/Enter）
- ✅ 支持无障碍访问
- ✅ 轻量级，仅 21KB

## 🔥 Vue 3 集成

### 全局 Mixin

所有 Vue 应用自动包含全局 mixin，提供以下方法：

```javascript
createVueApp({
    data() {
        return {
            form: {
                name: '',
                email: ''
            }
        };
    },
    methods: {
        async submit() {
            // 表单提交
            await this.submitForm('', this.form, {
                successText: '提交成功',
                redirect: 'index'
            });
        },
        async deleteItem(id) {
            // 删除确认
            await this.deleteConfirm(id, 'del', {
                message: '确定要删除这条记录吗？'
            });
        }
    }
}).mount('#app');
```

### 可用方法

**弹窗方法**
- `this.alert()` - 提示框
- `this.confirm()` - 确认框
- `this.prompt()` - 输入框
- `this.success()` - 成功提示
- `this.error()` - 错误提示
- `this.warning()` - 警告提示
- `this.info()` - 信息提示

**加载方法**
- `this.showLoading()` - 显示加载
- `this.hideLoading()` - 隐藏加载

**AJAX 方法**
- `this.post()` - POST 请求
- `this.get()` - GET 请求
- `this.submitForm()` - 表单提交
- `this.deleteConfirm()` - 删除确认

**工具方法**
- `this.formatDate()` - 格式化日期
- `this.copyToClipboard()` - 复制到剪贴板
- `this.debounce()` - 防抖
- `this.throttle()` - 节流

## 📖 文档

- [Webman 官方文档](https://www.workerman.net/doc/webman)
- [Vue 3 官方文档](https://vuejs.org/)
- [Bootstrap 5 文档](https://getbootstrap.com/)

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 License

MIT License

## 🙏 致谢

- [Webman](https://www.workerman.net/webman) - 高性能 PHP 框架
- [Workerman](https://www.workerman.net) - PHP Socket 服务器框架
- [Vue.js](https://vuejs.org/) - 渐进式 JavaScript 框架
- [Bootstrap](https://getbootstrap.com/) - 前端 UI 框架
