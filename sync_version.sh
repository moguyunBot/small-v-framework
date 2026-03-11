#!/bin/bash
# 从 Gitee 同步 version.json 到 public 目录
# 可加入宝塔计划任务，每小时执行一次

SOURCE="https://gitee.com/4620337/small-v-framework/raw/main/version.json"
DEST="/www/wwwroot/www.xvv.cc/public/version.json"

curl -m 30 -fsSL "$SOURCE" -o "${DEST}.tmp" && mv "${DEST}.tmp" "$DEST" && echo "[$(date)] version.json synced" || echo "[$(date)] sync failed"
