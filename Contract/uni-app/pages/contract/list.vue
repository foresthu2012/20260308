<template>
    <view class="container">
        <view class="tabs">
            <view class="tab-item" :class="{ active: status === 0 }" @click="changeTab(0)">
                <text>待签署</text>
                <view class="line" v-if="status === 0"></view>
            </view>
            <view class="tab-item" :class="{ active: status === 1 }" @click="changeTab(1)">
                <text>已签署</text>
                <view class="line" v-if="status === 1"></view>
            </view>
        </view>

        <view class="list-container">
            <view class="card-list" v-if="list.length > 0">
                <view class="card-item" v-for="(item, index) in list" :key="index">
                    <view class="card-header" @click="toPreview(item)">
                        <text class="card-title">{{ item.title }}</text>
                        <view class="status-tag" :class="item.status == 0 ? 'tag-pending' : 'tag-success'">
                            {{ item.status == 0 ? '待签署' : '已签署' }}
                        </view>
                    </view>
                    <view class="card-body" @click="toPreview(item)">
                        <view class="info-row" v-if="item.order_id">
                            <text class="label">关联订单</text>
                            <text class="value">{{ item.order_id }}</text>
                        </view>
                        <view class="info-row">
                            <text class="label">创建时间</text>
                            <text class="value">{{ item.create_time }}</text>
                        </view>
                        <view class="info-row" v-if="item.status == 1 && item.sign_time">
                            <text class="label">签署时间</text>
                            <text class="value">{{ item.sign_time }}</text>
                        </view>
                    </view>
                    <view class="card-footer">
                        <text class="btn-text" @click="toPreview(item)">{{ item.status == 0 ? '点击预览并签署' : '查看详情' }}</text>
                    </view>
                </view>
                <uni-load-more :status="loadingStatus"></uni-load-more>
            </view>
            <view v-else class="empty">
                <text>暂无合同数据</text>
            </view>
        </view>

        <!-- 全屏预览遮罩层（点击合同直接弹出，适配手机屏） -->
        <view v-if="fullscreenPreview.visible" class="fullscreen-preview mobile-adapted" :class="{ 'is-image': fullscreenPreview.isImage, 'is-pdf': fullscreenPreview.isPdf }">
            <view class="preview-header">
                <text class="preview-title">{{ fullscreenPreview.title }}</text>
                <view class="preview-close-wrap" @click="closePreview">
                    <text class="preview-close">✕</text>
                </view>
            </view>
            
            <!-- PDF 全屏预览 -->
            <view v-if="fullscreenPreview.isPdf" class="pdf-preview-container">
                <view v-if="fullscreenPreview.loading" class="preview-loading">
                    <view class="loading-spinner"></view>
                    <text>正在加载 PDF...</text>
                </view>
                <view v-else-if="fullscreenPreview.error" class="preview-error">
                    <text>{{ fullscreenPreview.error }}</text>
                    <button class="retry-btn" @click="retryPdfPreview">重试</button>
                    <button class="download-btn" @click="downloadPdf">下载 PDF</button>
                </view>
                <view v-else class="pdf-actions">
                    <button class="action-btn primary" @click="openPdfExternal">打开 PDF</button>
                    <button class="action-btn" @click="downloadPdf">下载到本地</button>
                </view>
            </view>
            
            <!-- 图片全屏预览：宽度适配屏幕，可上下滑动 -->
            <scroll-view v-else-if="fullscreenPreview.isImage" class="image-preview-scroll" scroll-y :show-scrollbar="false" enhanced>
                <view class="image-preview-container">
                    <image
                        :src="fullscreenPreview.url"
                        mode="widthFix"
                        class="fullscreen-image"
                        @load="onFullscreenImageLoad"
                        @error="onFullscreenImageError"
                        show-menu-by-longpress="true"
                    />
                </view>
                <view v-if="fullscreenPreview.loading" class="preview-loading overlay-loading">
                    <view class="loading-spinner"></view>
                    <text>图片加载中...</text>
                </view>
            </scroll-view>

            <!-- 底部固定操作栏（仅待签署合同显示） -->
            <view v-if="currentContract && currentContract.status == 0" class="preview-footer">
                <view class="footer-content">
                    <view class="agreement-section" @click="toggleAgreement">
                        <view class="checkbox" :class="{ checked: agreed }">
                            <text v-if="agreed" class="check-icon">✓</text>
                        </view>
                        <text class="agreement-text">我已阅读并同意使用微信身份签署本合同，具有法律效力</text>
                    </view>
                    <button
                        class="sign-btn"
                        :class="{ disabled: !agreed || signing }"
                        :disabled="!agreed || signing"
                        @click="handleSign"
                    >
                        {{ signing ? '签署中...' : '立即签署' }}
                    </button>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { getContractList, confirmSign } from '@/addon/Contract/api/contract'
import { onShow, onPullDownRefresh, onReachBottom } from '@dcloudio/uni-app'

const list = ref([])
const status = ref(0)
const page = ref(1)
const loadingStatus = ref('more')
const isLoaded = ref(false)
const signing = ref(false)
const currentContract = ref<any>(null)

// 全屏预览状态
const fullscreenPreview = ref({
    visible: false,
    url: '',
    title: '',
    isImage: false,
    isPdf: false,
    loading: false,
    error: ''
})

// 协议勾选
const agreed = ref(false)

// 获取文件类型
const getFileType = (filePath: string): string => {
    if (!filePath) return 'image'
    const ext = filePath.split('.').pop()?.toLowerCase() || ''
    return ext === 'pdf' ? 'pdf' : 'image'
}

// 图片处理
const img = (path: string) => {
    if (!path) return ''
    // 如果已经是完整URL，直接返回
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }
    // 如果是相对路径，添加基础URL
    const baseUrl = import.meta.env.VITE_BASE_URL || 'http://localhost:8080'
    // 避免重复添加 /upload/ 前缀
    if (path.startsWith('/')) {
        return baseUrl + path
    } else if (path.startsWith('upload/')) {
        return baseUrl + '/' + path
    } else {
        return baseUrl + '/upload/' + path
    }
}

const getList = async (mode = 'refresh') => {
    if (mode === 'refresh') {
        page.value = 1
        list.value = []
        loadingStatus.value = 'loading'
    } else {
        if (loadingStatus.value === 'noMore') return
        page.value++
    }

    try {
        const res = await getContractList({ 
            page: page.value, 
            limit: 10,
            status: status.value 
        })
        if (mode === 'refresh') {
            list.value = res.data.data
            uni.stopPullDownRefresh()
        } else {
            list.value = [...list.value, ...res.data.data]
        }

        if (list.value.length >= res.data.total) {
            loadingStatus.value = 'noMore'
        } else {
            loadingStatus.value = 'more'
        }
        isLoaded.value = true
    } catch (e) {
        loadingStatus.value = 'more'
        uni.stopPullDownRefresh()
    }
}

const changeTab = (val: number) => {
    status.value = val
    getList('refresh')
}

onShow(() => {
    getList('refresh')
})

onPullDownRefresh(() => {
    getList('refresh')
})

onReachBottom(() => {
    getList('loadmore')
})

// 预览合同（待签署用 file_path，已签署优先用 sign_image）
const toPreview = (item: any) => {
    console.log('toPreview 被调用', item)
    currentContract.value = item
    agreed.value = false // 重置协议勾选状态

    let filePath = ''
    let previewTitle = item.title
    if (item.status == 1 && item.sign_image) {
        filePath = item.sign_image
        previewTitle = item.title ? `${item.title}（已签署）` : '已签署合同'
    } else if (item.file_path) {
        filePath = item.file_path
    }

    console.log('文件路径:', filePath)

    if (!filePath) {
        uni.showToast({ title: '暂无合同文件', icon: 'none' })
        return
    }

    const fileUrl = img(filePath)
    console.log('文件URL:', fileUrl)

    const isPdf = getFileType(filePath) === 'pdf'

    fullscreenPreview.value = {
        visible: true,
        url: fileUrl,
        title: previewTitle,
        isImage: !isPdf,
        isPdf: isPdf,
        loading: true,
        error: ''
    }

    console.log('预览状态:', fullscreenPreview.value)

    if (isPdf) {
        loadPdfPreview(fileUrl)
    }
}

// 关闭预览
const closePreview = () => {
    fullscreenPreview.value.visible = false
}

// 加载 PDF 预览
const loadPdfPreview = (url: string) => {
    fullscreenPreview.value.loading = true
    fullscreenPreview.value.error = ''
    
    uni.downloadFile({
        url: url,
        success: (res) => {
            fullscreenPreview.value.loading = false
            if (res.statusCode === 200) {
                fullscreenPreview.value.pdfTempPath = res.tempFilePath
            } else {
                fullscreenPreview.value.error = 'PDF 下载失败'
            }
        },
        fail: () => {
            fullscreenPreview.value.loading = false
            fullscreenPreview.value.error = 'PDF 加载失败'
        }
    })
}

// 重试 PDF 预览
const retryPdfPreview = () => {
    loadPdfPreview(fullscreenPreview.value.url)
}

// 外部打开 PDF
const openPdfExternal = () => {
    if (!fullscreenPreview.value.url) return
    
    uni.showLoading({ title: '准备中...' })
    uni.downloadFile({
        url: fullscreenPreview.value.url,
        success: (res) => {
            uni.hideLoading()
            if (res.statusCode === 200) {
                uni.openDocument({ 
                    filePath: res.tempFilePath, 
                    showMenu: true, 
                    fail: () => uni.showToast({ title: '无法打开', icon: 'none' }) 
                })
            } else {
                uni.showToast({ title: '打开失败', icon: 'none' })
            }
        },
        fail: () => { 
            uni.hideLoading()
            uni.showToast({ title: '打开失败', icon: 'none' }) 
        }
    })
}

// 下载 PDF
const downloadPdf = () => {
    if (!fullscreenPreview.value.url) return
    
    uni.showLoading({ title: '下载中...' })
    uni.downloadFile({
        url: fullscreenPreview.value.url,
        success: (res) => {
            uni.hideLoading()
            if (res.statusCode === 200) {
                uni.saveFile({
                    tempFilePath: res.tempFilePath,
                    success: () => uni.showToast({ title: '已保存', icon: 'success' }),
                    fail: () => {
                        uni.openDocument({ filePath: res.tempFilePath, showMenu: true })
                    }
                })
            } else {
                uni.showToast({ title: '下载失败', icon: 'none' })
            }
        },
        fail: () => { 
            uni.hideLoading()
            uni.showToast({ title: '下载失败', icon: 'none' }) 
        }
    })
}

// 全屏图片加载完成
const onFullscreenImageLoad = () => {
    fullscreenPreview.value.loading = false
}

// 全屏图片加载失败
const onFullscreenImageError = () => {
    fullscreenPreview.value.loading = false
    fullscreenPreview.value.error = '图片加载失败'
}

// 签署合同 - 使用微信身份签署
const handleSign = () => {
    if (!agreed.value) {
        uni.showToast({ title: '请先同意签署协议', icon: 'none' })
        return
    }
    if (signing.value) return
    if (!currentContract.value) return

    uni.showModal({
        title: '确认签署',
        content: '授权后将使用您的微信身份完成签署，系统生成电子签章，具有法律效力且不可撤销。确认继续？',
        success: (res) => {
            if (res.confirm) {
                doWeChatSign()
            }
        }
    })
}

// 执行微信签署
const doWeChatSign = () => {
    signing.value = true
    uni.showLoading({ title: '正在签署...', mask: true })

    // 调用 uni.login 获取微信 code
    uni.login({
        provider: 'weixin',
        success: (loginRes) => {
            if (loginRes.code) {
                confirmSign(currentContract.value.id, { code: loginRes.code })
                    .then((apiRes: any) => {
                        uni.hideLoading()
                        signing.value = false

                        uni.showToast({
                            title: '签署成功！',
                            icon: 'success',
                            duration: 2000
                        })

                        // 延迟关闭预览并刷新列表
                        setTimeout(() => {
                            closePreview()
                            getList('refresh')
                        }, 2000)
                    })
                    .catch((e: any) => {
                        uni.hideLoading()
                        signing.value = false
                        uni.showToast({
                            title: e.msg || e.message || '签署失败，请重试',
                            icon: 'none',
                            duration: 3000
                        })
                    })
            } else {
                throw new Error('获取微信授权失败')
            }
        },
        fail: (err) => {
            uni.hideLoading()
            signing.value = false
            console.error('微信登录失败:', err)
            uni.showToast({
                title: '微信授权失败，请重试',
                icon: 'none',
                duration: 3000
            })
        }
    })
}

// 切换协议勾选
const toggleAgreement = () => {
    agreed.value = !agreed.value
}
</script>

<style lang="scss" scoped>
.container {
    background-color: #f8f8f8;
    min-height: 100vh;
}
.tabs {
    display: flex;
    background: #fff;
    padding: 0 30rpx;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2rpx 10rpx rgba(0,0,0,0.05);
    .tab-item {
        flex: 1;
        text-align: center;
        font-size: 30rpx;
        position: relative;
        height: 88rpx;
        line-height: 88rpx;
        color: #666;
        &.active {
            color: #333;
            font-weight: bold;
            font-size: 32rpx;
        }
        .line {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 40rpx;
            height: 6rpx;
            background-color: #007aff;
            border-radius: 3rpx;
        }
    }
}
.list-container {
    padding: 20rpx 30rpx;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}
.card-item {
    background: #fff;
    padding: 30rpx;
    border-radius: 16rpx;
    margin-bottom: 20rpx;
    box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.03);
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20rpx;
        padding-bottom: 20rpx;
        border-bottom: 1rpx solid #f0f0f0;
        .card-title {
            font-size: 32rpx;
            font-weight: bold;
            flex: 1;
            margin-right: 20rpx;
            color: #333;
        }
        .status-tag {
            font-size: 24rpx;
            padding: 6rpx 16rpx;
            border-radius: 8rpx;
            &.tag-pending {
                background-color: #e6f7ff;
                color: #1890ff;
            }
            &.tag-success {
                background-color: #f6ffed;
                color: #52c41a;
            }
        }
    }
    .card-body {
        .info-row {
            display: flex;
            margin-bottom: 12rpx;
            font-size: 28rpx;
            .label {
                color: #999;
                width: 140rpx;
            }
            .value {
                color: #666;
                flex: 1;
            }
        }
    }
    .card-footer {
        margin-top: 20rpx;
        padding-top: 20rpx;
        border-top: 1rpx solid #f0f0f0;
        text-align: center;
        .btn-text {
            font-size: 28rpx;
            color: #007aff;
            padding: 12rpx 0;
            display: block;
        }
    }
}
.empty {
    text-align: center;
    padding-top: 200rpx;
    color: #999;
    font-size: 28rpx;
}

// 全屏预览样式（适配手机屏：安全区、触控区、可滑动）
.fullscreen-preview {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}

.fullscreen-preview.mobile-adapted {
    padding-bottom: env(safe-area-inset-bottom);
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24rpx 30rpx;
    padding-top: calc(24rpx + env(safe-area-inset-top));
    background: rgba(0, 0, 0, 0.8);
    flex-shrink: 0;
    .preview-title {
        font-size: 32rpx;
        color: #fff;
        font-weight: bold;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .preview-close-wrap {
        min-width: 88rpx;
        min-height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: -10rpx -10rpx -10rpx 10rpx;
    }
    .preview-close {
        font-size: 44rpx;
        color: #fff;
    }
}

.pdf-preview-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 40rpx;
}

/* 图片预览：占满剩余高度，内容可上下滑动，宽度适配屏幕 */
.image-preview-scroll {
    flex: 1;
    width: 100%;
    height: 0;
    box-sizing: border-box;
    position: relative;
    padding-bottom: 220rpx; /* 为底部操作栏预留空间 */
}
.image-preview-container {
    width: 100%;
    padding: 20rpx;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    .fullscreen-image {
        width: 100%;
        max-width: 100%;
        height: auto;
        display: block;
        vertical-align: top;
    }
}
.preview-loading.overlay-loading {
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    padding: 60rpx;
}

.preview-loading, .preview-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60rpx;
    text {
        color: rgba(255, 255, 255, 0.8);
        font-size: 28rpx;
        margin-top: 20rpx;
    }
}

.preview-error {
    text {
        color: #ff6b6b;
        margin-bottom: 30rpx;
    }
    .retry-btn, .download-btn {
        padding: 16rpx 40rpx;
        font-size: 28rpx;
        border-radius: 40rpx;
        border: none;
        margin: 10rpx;
    }
    .retry-btn {
        background: #007aff;
        color: #fff;
    }
    .download-btn {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }
}

.pdf-actions {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    width: 100%;
    max-width: 400rpx;
    .action-btn {
        width: 100%;
        padding: 24rpx;
        font-size: 32rpx;
        border-radius: 44rpx;
        border: none;
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        &.primary {
            background: #007aff;
        }
    }
}

.loading-spinner {
    width: 60rpx;
    height: 60rpx;
    border: 4rpx solid rgba(255, 255, 255, 0.2);
    border-top-color: #007aff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* 底部固定操作栏 */
.preview-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20rpx);
    padding: 24rpx 30rpx;
    padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
    box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.1);
    z-index: 100;
}

.footer-content {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.agreement-section {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    padding: 12rpx;
}

.checkbox {
    width: 36rpx;
    height: 36rpx;
    min-width: 36rpx;
    border: 2rpx solid #d9d9d9;
    border-radius: 6rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 2rpx;
    transition: all 0.3s ease;

    &.checked {
        background: #07c160;
        border-color: #07c160;

        .check-icon {
            color: #fff;
            font-size: 24rpx;
            font-weight: bold;
        }
    }
}

.agreement-text {
    flex: 1;
    font-size: 24rpx;
    color: #666;
    line-height: 1.6;
}

.sign-btn {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    text-align: center;
    font-size: 32rpx;
    font-weight: bold;
    border-radius: 44rpx;
    background: linear-gradient(135deg, #07c160 0%, #06ad56 100%);
    color: #fff;
    border: none;
    box-shadow: 0 8rpx 24rpx rgba(7, 193, 96, 0.3);
    transition: all 0.3s ease;

    &.disabled {
        background: #d9d9d9;
        box-shadow: none;
        color: #999;
    }

    &:active:not(.disabled) {
        transform: scale(0.98);
        box-shadow: 0 4rpx 12rpx rgba(7, 193, 96, 0.3);
    }

    &::after {
        border: none;
    }
}
</style>
