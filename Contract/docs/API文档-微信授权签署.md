# 合同签署 API 文档

## 版本信息
- 版本: v1.1.2
- 更新时间: 2026-03-08
- 签署方式: 微信授权签署(电子签章)

## 概述

合同签署插件提供基于微信小程序的电子合同签署功能。用户通过微信授权完成合同签署,系统自动生成电子签章并合成到合同文件中。

### 技术架构

```
前端(uni-app) → API接口 → 业务逻辑层 → 图像处理(GD) → 数据存储
    ↓                ↓            ↓              ↓          ↓
 微信小程序       Controller   Service        GD库      MySQL
   登录            控制器        服务层        图像合成      数据库
```

### 签署流程

```
1. 用户查看合同 → 点击"立即签署"按钮
2. 确认签署协议 → 弹出确认对话框
3. 调用微信登录 → 获取登录code
4. 发送签署请求 → 后端验证code
5. 获取用户OpenID → 微信API验证
6. 防重复检查 → 检查是否已签署
7. 生成电子签章 → 使用GD库创建签章图片
8. 合成签章到合同 → 图像处理合成
9. 保存签署记录 → 存储到数据库
10. 更新合同状态 → 标记为已签署
```

---

## API 接口列表

### 1. 获取合同列表

**接口地址:** `GET /Contract/contract`

**请求参数:**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | int | 否 | 页码,默认1 |
| limit | int | 否 | 每页数量,默认10 |
| status | int | 否 | 签署状态: 0待签署 1已签署 |

**响应示例:**
```json
{
    "code": 1,
    "message": "success",
    "data": {
        "data": [
            {
                "id": 1,
                "title": "服务合同",
                "file_path": "upload/contract/xxx.jpg",
                "status": 0,
                "sign_image": "",
                "sign_time": "",
                "create_time": "2026-03-08 10:00:00",
                "order_id": 1001
            }
        ],
        "total": 100
    }
}
```

---

### 2. 获取合同详情

**接口地址:** `GET /Contract/contract/{id}`

**路径参数:**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | int | 是 | 合同ID |

**响应示例:**
```json
{
    "code": 1,
    "message": "success",
    "data": {
        "id": 1,
        "title": "服务合同",
        "file_path": "upload/contract/xxx.jpg",
        "file_ext": "jpg",
        "member_id": 1,
        "order_id": 1001,
        "status": 0,
        "sign_image": "",
        "sign_time": "",
        "create_time": "2026-03-08 10:00:00",
        "member": {
            "member_id": 1,
            "nickname": "张三",
            "mobile": "13800138000"
        },
        "order": {
            "order_id": 1001,
            "order_no": "20260308100001"
        }
    }
}
```

---

### 3. 微信授权签署

**接口地址:** `POST /Contract/contract/confirm_sign/{id}`

**路径参数:**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | int | 是 | 合同ID |

**请求参数:**
| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| code | string | 是 | 微信登录code(通过uni.login获取) |

**前端调用示例:**
```typescript
// uni-app 调用示例
uni.login({
    provider: 'weixin',
    success: (loginRes) => {
        if (loginRes.code) {
            confirmSign(contractId, { code: loginRes.code })
                .then(res => {
                    console.log('签署成功', res)
                })
                .catch(err => {
                    console.error('签署失败', err)
                })
        }
    }
})
```

**响应示例 - 成功:**
```json
{
    "code": 1,
    "message": "success",
    "data": {
        "contract_id": 1,
        "sign_image": "upload/contract_sign/signed_contract_1759929600_1234.jpg",
        "sign_time": 1759929600,
        "message": "签署成功"
    }
}
```

**响应示例 - 失败:**
```json
{
    "code": 0,
    "message": "WECHAT_MINIAPP_NOT_CONFIGURED"
}
```

**错误码说明:**
| 错误码 | 说明 |
|--------|------|
| CONTRACT_ID_REQUIRED | 合同ID不能为空 |
| CODE_REQUIRED | 微信授权码不能为空 |
| CONTRACT_NOT_EXIST | 合同不存在 |
| CONTRACT_SIGNED | 合同已签署 |
| WECHAT_MINIAPP_NOT_CONFIGURED | 微信小程序未配置 |
| WECHAT_API_REQUEST_FAILED | 微信API请求失败 |
| WECHAT_API_RESPONSE_INVALID | 微信API响应无效 |
| WECHAT_OPENID_MISSING | 获取OpenID失败 |
| WECHAT_AUTH_ERROR | 微信授权错误 |
| ALREADY_SIGNED_BY_THIS_USER | 您已经签署过该合同 |
| STAMP_GENERATION_FAILED | 电子签章生成失败 |
| CONTRACT_COMPOSITE_FAILED | 合同合成失败 |
| CONTRACT_FILE_NOT_FOUND | 合同文件不存在 |
| UNSUPPORTED_IMAGE_TYPE | 不支持的图片类型 |
| FAILED_TO_COMPOSITE_IMAGES | 图片合成失败 |
| SIGNATURE_RECORD_SAVE_FAILED | 签署记录保存失败 |
| CONTRACT_UPDATE_FAILED | 合同更新失败 |

---

## 数据库表结构

### 合同表 (addon_contract_contract)

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 合同ID |
| site_id | int | 站点ID |
| title | varchar(255) | 合同标题 |
| file_path | varchar(255) | 合同文件路径(待签署状态显示) |
| file_ext | varchar(10) | 文件扩展名 |
| member_id | int | 会员ID |
| order_id | int | 关联订单ID |
| status | tinyint(1) | 签署状态: 0待签署 1已签署 |
| sign_image | varchar(255) | **已签署合同图片路径(电子签章合成后的最终合同文件)** |
| sign_time | int | 签署时间戳 |
| create_time | int | 创建时间 |
| update_time | int | 更新时间 |
| delete_time | int | 删除时间(软删除) |

### 签署记录表 (addon_contract_signature_record)

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 记录ID |
| site_id | int | 站点ID |
| contract_id | int | 合同ID |
| member_id | int | 会员ID |
| openid | varchar(100) | **微信OpenID(用于防重复签署)** |
| sign_time | int | 签署时间戳 |
| sign_image | varchar(255) | **已签署合同图片路径(电子签章合成后的最终合同文件)** |

---

## 电子签章说明

### 签章样式

电子签章采用圆形设计,包含以下元素:
- 外圆环(红色,3层)
- 内圆环(深红色)
- 虚线装饰
- 主文本:"已签署"
- 英文:"CONFIRMED"
- OpenID后8位(脱敏)
- 签署日期和时间(Y-m-d H:i)

### 签章尺寸

- 原始尺寸: 320px × 320px
- 合成到合同时: 调整为合同宽度的22%
- 位置: 右下角,保留4%的边距

### 签章特点

- 透明背景,支持PNG格式
- 使用GD库动态生成
- 包含签署时间戳
- 防篡改设计

---

## 图片格式支持

### 合同文件支持格式

| 格式 | MIME类型 | 说明 |
|------|----------|------|
| JPEG | image/jpeg, image/jpg | 推荐格式,文件较小 |
| PNG | image/png | 支持透明背景 |
| GIF | image/gif | 动态GIF仅取第一帧 |
| WEBP | image/webp | 现代格式,需PHP 7.2+ |
| BMP | image/bmp | 无压缩格式 |

### 输出格式

- 默认输出: JPEG格式
- 压缩质量: 90
- 文件命名: `signed_contract_{timestamp}_{random}.jpg`
- 保存路径: `upload/contract_sign/`

---

## 签署流程详解

### 前端流程

```typescript
// 1. 用户点击预览合同
toPreview(item) {
    currentContract.value = item
    agreed.value = false
    // 显示合同预览
}

// 2. 用户同意协议并点击签署
handleSign() {
    uni.showModal({
        title: '确认签署',
        content: '授权后将使用您的微信身份完成签署...',
        success: (res) => {
            if (res.confirm) {
                doWeChatSign()
            }
        }
    })
}

// 3. 调用微信登录获取code
doWeChatSign() {
    signing.value = true
    uni.login({
        provider: 'weixin',
        success: (loginRes) => {
            if (loginRes.code) {
                // 4. 发送签署请求
                confirmSign(currentContract.value.id, { code: loginRes.code })
                    .then(res => {
                        // 5. 签署成功
                        uni.showToast({ title: '签署成功！', icon: 'success' })
                        setTimeout(() => {
                            closePreview()
                            getList('refresh')
                        }, 2000)
                    })
            }
        }
    })
}
```

### 后端流程

```php
// 1. 参数验证
public function confirmSign(int $id, string $code) {
    // 验证ID和code不能为空
}

// 2. 获取合同并验证状态
$contract = Contract::where(...)->find();
if ($contract->status == 1) {
    throw new \Exception('CONTRACT_SIGNED');
}

// 3. 获取微信小程序配置
$config = Config::getConfig(...);
$appId = $config['value']['appid'];
$appSecret = $config['value']['app_secret'];

// 4. 交换code获取openid
$url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}...";
$response = file_get_contents($url);
$openid = $res['openid'];

// 5. 防重复签署检查
$exists = SignatureRecord::where([
    ['contract_id', '=', $id],
    ['openid', '=', $openid]
])->find();
if ($exists) {
    throw new \Exception('ALREADY_SIGNED_BY_THIS_USER');
}

// 6. 生成电子签章
$stampPath = $this->generateStamp($openid);

// 7. 合成签章到合同
$finalContractPath = $this->compositeStampToContract($contract, $stampPath);

// 8. 保存签署记录
SignatureRecord::save([
    'contract_id' => $id,
    'openid' => $openid,
    'sign_image' => $finalContractPath
]);

// 9. 更新合同状态
$contract->save([
    'status' => 1,
    'sign_image' => $finalContractPath,
    'sign_time' => time()
]);

return ['sign_image' => $finalContractPath];
}
```

---

## 安全性说明

### 1. 防重复签署
- 使用 `openid` 作为唯一标识
- 通过 `signature_record` 表记录签署记录
- 同一openid无法重复签署同一合同

### 2. 微信授权验证
- 使用微信官方 `jscode2session` 接口
- code有效期仅5分钟,一次有效
- openid由微信官方颁发,安全可靠

### 3. 权限控制
- 用户只能签署自己的合同
- 管理员不能删除已签署的合同
- 已签署合同不能编辑

### 4. 数据完整性
- 签署时间戳记录
- 软删除机制保护历史数据
- 事务处理确保数据一致性

---

## 注意事项

### 微信小程序配置
1. 在后台配置微信小程序AppID和AppSecret
2. 确保小程序已开通"开放接口-登录"权限
3. code有效期5分钟,需及时使用

### 图片处理
1. 支持的合同文件格式: JPG、PNG、GIF、WEBP、BMP
2. 推荐使用JPG格式以获得更小的文件大小
3. 输出默认使用JPG格式,质量90

### 存储空间
1. 签署后的合同保存在 `upload/contract_sign/` 目录
2. 建议定期清理旧文件或使用对象存储
3. 确保服务器有足够的磁盘空间

### 性能优化
1. 签署成功后前端显示2秒提示再刷新列表
2. 使用缩略图预览,点击加载大图
3. 后端使用缓存减少数据库查询

---

## 常见问题

### Q1: 签署失败提示"微信小程序未配置"
**A:** 请在后台配置微信小程序的AppID和AppSecret

### Q2: 签署成功但看不到签章
**A:** 签章已合成到合同图片中,请刷新页面重新预览

### Q3: 提示"已经签署过该合同"
**A:** 每个合同只能签署一次,无法重复签署

### Q4: 合同文件格式不支持
**A:** 请上传JPG、PNG、GIF、WEBP或BMP格式的图片

### Q5: 签署后如何下载合同
**A:** 在合同预览页面长按图片可保存到本地

---

## 版本更新记录

### v1.1.2 (2026-03-08)
- ✅ 删除手写签名功能
- ✅ 优化微信授权签署功能
- ✅ 改进电子签章生成和合成
- ✅ 完善错误处理和状态管理
- ✅ 更新数据库字段注释
- ✅ 优化后台管理界面
- ✅ 完善语言包翻译

### v1.1.1 (已删除)
- 从手写签名迁移到微信授权签署

---

## 技术支持

如有问题请联系:
- 技术文档: 查看 `docs/` 目录
- 更新日志: 查看 `docs/changelog/` 目录
- 数据库脚本: 查看 `sql/` 目录
