#!/bin/bash

# 自动更新版本号脚本

# 读取当前版本号
VERSION=$(grep -o '"version": "[0-9.]*"' info.json | cut -d '"' -f 4)

# 拆分版本号
IFS='.' read -r MAJOR MINOR PATCH <<< "$VERSION"

# 递增补丁版本号
NEW_PATCH=$((PATCH + 1))
NEW_VERSION="$MAJOR.$MINOR.$NEW_PATCH"

# 更新 info.json 文件
sed -i '' "s/\"version\": \"$VERSION\"/\"version\": \"$NEW_VERSION\"/g" info.json

# 显示更新结果
echo "版本号已更新：$VERSION -> $NEW_VERSION"
