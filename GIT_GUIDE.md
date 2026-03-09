# Git 使用指南

## 当前状态
✅ Git 仓库已初始化
✅ 已创建初始提交（删除插件系统并合并 ConfigGroup）

## 常用命令

### 1. 查看当前状态
```bash
cd /www/wwwroot/www.xvv.cc
git status
```

### 2. 查看提交历史
```bash
git log --oneline
git log --graph --oneline --all
```

### 3. 提交新的更改
```bash
# 查看修改了哪些文件
git status

# 添加所有修改的文件
git add .

# 或者添加指定文件
git add app/admin/controller/Config.php

# 提交更改
git commit -m "描述你的更改"
```

### 4. 查看文件差异
```bash
# 查看未暂存的修改
git diff

# 查看已暂存的修改
git diff --staged

# 查看指定文件的修改
git diff app/admin/controller/Config.php
```

### 5. 还原文件
```bash
# 还原工作区的文件（未 add）
git checkout -- 文件名

# 还原已暂存的文件（已 add 但未 commit）
git reset HEAD 文件名
git checkout -- 文件名

# 回退到上一个提交
git reset --hard HEAD^

# 回退到指定提交
git log --oneline  # 先查看提交历史
git reset --hard 提交ID
```

### 6. 创建分支
```bash
# 创建新分支
git branch 分支名

# 切换分支
git checkout 分支名

# 创建并切换到新分支
git checkout -b 分支名

# 查看所有分支
git branch -a
```

### 7. 查看某个提交的详细信息
```bash
git show 提交ID
```

### 8. 撤销最后一次提交（保留修改）
```bash
git reset --soft HEAD^
```

### 9. 撤销最后一次提交（丢弃修改）
```bash
git reset --hard HEAD^
```

## 推荐工作流程

### 日常开发
1. 开始工作前查看状态：`git status`
2. 修改代码
3. 查看修改：`git diff`
4. 添加修改：`git add .`
5. 提交：`git commit -m "描述"`

### 实验性功能
1. 创建新分支：`git checkout -b feature-xxx`
2. 在新分支上开发
3. 测试通过后合并到主分支：
   ```bash
   git checkout main
   git merge feature-xxx
   ```

### 紧急回滚
```bash
# 查看历史
git log --oneline

# 回退到指定版本
git reset --hard 提交ID

# 或者回退到上一个版本
git reset --hard HEAD^
```

## 提交信息规范

建议使用以下前缀：
- `feat:` 新功能
- `fix:` 修复 bug
- `refactor:` 重构代码
- `style:` 代码格式调整
- `docs:` 文档更新
- `test:` 测试相关
- `chore:` 构建/工具相关

示例：
```bash
git commit -m "feat: 添加用户导出功能"
git commit -m "fix: 修复配置保存失败的问题"
git commit -m "refactor: 优化数据库查询性能"
```

## 注意事项

1. ⚠️ `.env` 文件已被忽略，不会提交到 Git
2. ⚠️ `/vendor/` 目录已被忽略
3. ⚠️ `/public/uploads/` 目录已被忽略
4. ⚠️ 使用 `git reset --hard` 会永久丢失未提交的修改，请谨慎使用

## 快速参考

| 操作 | 命令 |
|------|------|
| 查看状态 | `git status` |
| 查看历史 | `git log --oneline` |
| 提交更改 | `git add . && git commit -m "描述"` |
| 回退一个版本 | `git reset --hard HEAD^` |
| 查看差异 | `git diff` |
| 创建分支 | `git checkout -b 分支名` |
