#!/bin/bash

# 自动文档更新脚本

# 更新版本号
if [ -f "update_version.sh" ]; then
    bash update_version.sh
else
    echo "update_version.sh 文件不存在，跳过版本更新"
fi

# 获取当前版本号
VERSION=$(grep -o '"version": "[0-9.]*"' info.json | cut -d '"' -f 4)

# 获取当前日期
DATE=$(date +"%Y-%m-%d")

# 创建变更日志文件
CHANGELOG_FILE="docs/changelog/${VERSION}.md"

# 检查变更日志文件是否存在
if [ ! -f "$CHANGELOG_FILE" ]; then
    cat > "$CHANGELOG_FILE" << EOF
# $VERSION ($DATE)

## 功能变更

## Bug修复

## 性能优化

## 其他变更
EOF
    echo "已创建变更日志文件: $CHANGELOG_FILE"
else
    echo "变更日志文件已存在: $CHANGELOG_FILE"
fi

# 显示更新结果
echo "文档更新完成！"
echo "当前版本: $VERSION"
echo "变更日志: $CHANGELOG_FILE"
