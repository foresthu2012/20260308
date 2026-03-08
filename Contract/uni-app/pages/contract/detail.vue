<template>
  <view class="container">
    <!-- 页面主体 -->
    <view class="main-content">
      <view class="header-section">
        <view class="title">{{ detail.title }}</view>
        <view class="status-badge" :class="detail.status == 1 ? 'signed' : 'pending'">
          {{ detail.status == 1 ? '已签署' : '待签署' }}
        </view>
      </view>
      
      <!-- 合同文件预览卡片 -->
      <view class="section-card" v-if="detail.file_path || detail.sign_image">
        <view class="section-title">合同文件</view>
        
        <!-- 合同文件预览 - 点击直接进入全屏预览 -->
        <view class="contract-preview-box" @click="openFullscreenPreview">
          <image 
            v-if="detail.status == 1 && detail.sign_image"
            :src="img(detail.sign_image)" 
            mode="widthFix" 
            class="preview-image"
          />
          <image 
            v-else-if="detail.file_path"
            :src="img(detail.file_path)" 
            mode="widthFix" 
            class="preview-image"
            @error="onImageError"
          />
          <view class="preview-overlay">
            <text class="overlay-icon">👆</text>
            <text class="overlay-text">点击查看合同</text>
          </view>
        </view>
        
        <view class="file-type-hint">
          <text>点击上方预览合同详情</text>
        </view>
      </view>
      
      <!-- 待签署：使用微信身份签署 -->
      <view class="section-card sign-section" v-if="detail.status == 0">
        <view class="section-title">使用微信身份签署</view>
        <view class="wechat-sign-intro">
          <view class="intro-icon">📱</view>
          <text class="intro-text">授权后使用您的微信身份完成合同签署，系统将生成与您微信身份绑定的电子签章，具有法律效力。</text>
        </view>
        <view class="sign-tips">
          <view class="tip-item">
            <text class="tip-icon">🔐</text>
            <text class="tip-text">身份由微信验证，安全可追溯</text>
          </view>
          <view class="tip-item">
            <text class="tip-icon">📜</text>
            <text class="tip-text">受《电子签名法》保护，与手写签名同效</text>
          </view>
        </view>
        
        <!-- 协议勾选 -->
        <view class="agreement-section" @click="toggleAgreement">
          <view class="checkbox" :class="{ checked: agreed }">
            <text v-if="agreed" class="check-icon">✓</text>
          </view>
          <text class="agreement-text">我已阅读并同意使用微信身份签署本合同，具有法律效力</text>
        </view>
        
        <!-- 签署按钮 -->
        <view class="action-btns">
          <button 
            class="btn-primary wechat-btn" 
            type="default" 
            :loading="signing"
            :disabled="!agreed || signing"
            :class="{ disabled: !agreed || signing }"
            @click="handleConfirm"
          >
            {{ signing ? '正在使用微信身份签署...' : '微信授权并签署' }}
          </button>
        </view>
        
        <view class="signing-guide">
          <view class="guide-title">签署步骤</view>
          <view class="guide-list">
            <view class="guide-item">
              <text class="guide-icon">1</text>
              <text class="guide-text">点击上方合同预览，仔细阅读合同内容</text>
            </view>
            <view class="guide-item">
              <text class="guide-icon">2</text>
              <text class="guide-text">勾选同意协议，点击「微信授权并签署」</text>
            </view>
            <view class="guide-item">
              <text class="guide-icon">3</text>
              <text class="guide-text">在弹窗中授权微信登录，合同即时生效</text>
            </view>
          </view>
        </view>
      </view>
      
      <!-- 已签署：签署信息区域 -->
      <view class="section-card signed-result" v-if="detail.status == 1">
        <view class="section-title">签署信息</view>
        <view class="signature-display">
          <text class="signed-label">✓ 合同已签署</text>
          <view class="sign-method-tag">
            <text class="method-icon">📱</text>
            <text class="method-text">微信身份（电子签章）</text>
          </view>
          <view class="sign-meta">
            <text>签署时间：{{ detail.sign_time }}</text>
          </view>
          <view class="watermark">已签署</view>
        </view>
      </view>
    </view>
    
    <!-- 全屏预览遮罩层（与 list.vue 保持一致） -->
    <view v-if="fullscreenPreview.visible" class="fullscreen-preview mobile-adapted" :class="{ 'is-image': fullscreenPreview.isImage, 'is-pdf': fullscreenPreview.isPdf }">
      <view class="preview-header">
        <text class="preview-title">{{ fullscreenPreview.title }}</text>
        <view class="preview-close-wrap" @click="closeFullscreenPreview">
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
      <view v-if="detail.status == 0" class="preview-footer">
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
            @click="handleSignFromPreview"
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
import { onLoad } from '@dcloudio/uni-app'
import { getContractInfo, confirmSign } from '@/addon/Contract/api/contract'
import { img } from '@/utils/common'

// 合同详情
const detail = ref<any>({})
const contractId = ref(0)
const signing = ref(false)
const agreed = ref(false)

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

// 获取文件类型
const getFileType = (filePath: string): string => {
  if (!filePath) return 'image'
  const ext = filePath.split('.').pop()?.toLowerCase() || ''
  return ext === 'pdf' ? 'pdf' : 'image'
}

// 页面加载
onLoad((options) => {
  if (options?.id) {
    contractId.value = options.id
    getDetail()
  }
})

// 获取合同详情
const getDetail = async () => {
  try {
    const res = await getContractInfo(contractId.value)
    detail.value = res.data
  } catch (error) {
    console.error(error)
    uni.showToast({ title: '获取合同信息失败', icon: 'none' })
  }
}

// 图片加载错误
const onImageError = () => {
  console.error('合同图片加载失败')
}

// 切换协议勾选
const toggleAgreement = () => {
  agreed.value = !agreed.value
}

// 打开全屏预览
const openFullscreenPreview = () => {
  let filePath = ''
  let title = ''
  
  if (detail.value.status == 1 && detail.value.sign_image) {
    filePath = detail.value.sign_image
    title = '已签署合同'
  } else if (detail.value.file_path) {
    filePath = detail.value.file_path
    title = '合同预览'
  }
  
  if (!filePath) {
    uni.showToast({ title: '没有可预览的文件', icon: 'none' })
    return
  }
  
  const fileUrl = img(filePath)
  const isPdf = getFileType(filePath) === 'pdf'
  
  fullscreenPreview.value = {
    visible: true,
    url: fileUrl,
    title: title,
    isImage: !isPdf,
    isPdf: isPdf,
    loading: true,
    error: ''
  }
  
  if (isPdf) {
    loadPdfPreview(fileUrl)
  }
}

// 关闭全屏预览
const closeFullscreenPreview = () => {
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

// 确认签署（使用微信身份）- 从详情页
const handleConfirm = () => {
  if (!agreed.value) {
    uni.showToast({ title: '请先同意签署协议', icon: 'none' })
    return
  }
  if (signing.value) return

  uni.showModal({
    title: '使用微信身份签署',
    content: '授权后将使用您的微信身份完成签署，系统生成电子签章，具有法律效力且不可撤销。确认继续？',
    success: (res) => {
      if (res.confirm) {
        doSign()
      }
    }
  })
}

// 从预览页签署
const handleSignFromPreview = () => {
  if (!agreed.value) {
    uni.showToast({ title: '请先同意签署协议', icon: 'none' })
    return
  }
  if (signing.value) return

  uni.showModal({
    title: '确认签署',
    content: '授权后将使用您的微信身份完成签署，系统生成电子签章，具有法律效力且不可撤销。确认继续？',
    success: (res) => {
      if (res.confirm) {
        doSign()
      }
    }
  })
}

// 执行签署
const doSign = () => {
  signing.value = true
  uni.showLoading({ title: '正在使用微信身份签署...', mask: true })
  
  uni.login({
    provider: 'weixin',
    success: (loginRes) => {
      confirmSign(contractId.value, { code: loginRes.code })
        .then((apiRes: any) => {
          uni.hideLoading()
          signing.value = false
          uni.showToast({ title: '签署成功，微信身份已记录', icon: 'success' })
          
          detail.value.status = 1
          detail.value.sign_image = apiRes.data.sign_image
          
          // 关闭预览
          fullscreenPreview.value.visible = false
          
          getDetail()
        })
        .catch((e: any) => {
          uni.hideLoading()
          signing.value = false
          uni.showToast({ title: e.msg || '签署失败', icon: 'none' })
        })
    },
    fail: () => {
      uni.hideLoading()
      signing.value = false
      uni.showToast({ title: '微信授权失败', icon: 'none' })
    }
  })
}
</script>

<style lang="scss" scoped>
.container {
  width: 100%;
  min-height: 100vh;
  padding: 30rpx;
  padding-bottom: calc(30rpx + env(safe-area-inset-bottom));
  background: #f8f8f8;
  box-sizing: border-box;
}

.main-content {
  width: 100%;
  display: block;
  box-sizing: border-box;
}

.header-section {
  background: #fff;
  padding: 30rpx;
  border-radius: 16rpx;
  margin-bottom: 20rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.03);
  width: 100%;
  box-sizing: border-box;
}

.title {
  font-size: 36rpx;
  font-weight: bold;
  color: #333;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-badge {
  font-size: 26rpx;
  padding: 8rpx 20rpx;
  border-radius: 30rpx;
}

.status-badge.pending {
  background: #e6f7ff;
  color: #1890ff;
}

.status-badge.signed {
  background: #f6ffed;
  color: #52c41a;
}

.section-card {
  background: #fff;
  padding: 30rpx;
  border-radius: 16rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.03);
  width: 100%;
  box-sizing: border-box;
}

.section-title {
  font-size: 30rpx;
  font-weight: bold;
  margin-bottom: 20rpx;
  color: #333;
  border-left: 6rpx solid #007aff;
  padding-left: 16rpx;
}

.contract-preview-box {
  position: relative;
  width: 100%;
  border-radius: 12rpx;
  overflow: hidden;
  background: #f5f5f5;
  margin-bottom: 20rpx;
  
  .preview-image {
    width: 100%;
    display: block;
  }
  
  .preview-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    
    .overlay-icon {
      font-size: 60rpx;
      margin-bottom: 16rpx;
    }
    
    .overlay-text {
      color: #fff;
      font-size: 28rpx;
    }
  }
}

.file-type-hint {
  text-align: center;
  padding: 16rpx;
  background: #f8fbff;
  border-radius: 8rpx;
  
  text {
    font-size: 24rpx;
    color: #666;
  }
}

.sign-section .section-title {
  margin-bottom: 24rpx;
}

.wechat-sign-intro {
  display: flex;
  align-items: flex-start;
  gap: 16rpx;
  padding: 24rpx;
  background: linear-gradient(135deg, #f0f9ff 0%, #e8f4ff 100%);
  border-radius: 12rpx;
  margin-bottom: 20rpx;
  border: 1rpx solid rgba(0, 122, 255, 0.12);
  .intro-icon {
    font-size: 40rpx;
    flex-shrink: 0;
  }
  .intro-text {
    font-size: 28rpx;
    color: #333;
    line-height: 1.6;
    flex: 1;
  }
}

.sign-tips {
  background: #f8fbff;
  border-radius: 12rpx;
  padding: 20rpx;
  margin-bottom: 20rpx;
}

.tip-item {
  display: flex;
  align-items: center;
  margin-bottom: 12rpx;
  
  &:last-child {
    margin-bottom: 0;
  }
  
  .tip-icon {
    font-size: 28rpx;
    margin-right: 12rpx;
  }
  
  .tip-text {
    font-size: 26rpx;
    color: #666;
  }
}

.agreement-section {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
  padding: 20rpx;
  background: #f8f8f8;
  border-radius: 12rpx;
  margin-bottom: 20rpx;
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
  font-size: 26rpx;
  color: #666;
  line-height: 1.6;
}

.action-btns {
  display: flex;
  justify-content: center;
  padding: 20rpx 0;
}

.btn-primary {
  width: 100%;
  font-size: 32rpx;
  height: 88rpx;
  line-height: 88rpx;
  border-radius: 44rpx;
  background: linear-gradient(135deg, #007aff, #0051cf);
  color: #fff;
  border: none;
  box-shadow: 0 4rpx 12rpx rgba(0,122,255,0.3);
}

.btn-primary::after {
  border: none;
}

.btn-primary:active {
  opacity: 0.9;
  transform: scale(0.98);
}

.btn-primary.wechat-btn {
  background: linear-gradient(135deg, #07c160 0%, #06ad56 100%);
  box-shadow: 0 4rpx 12rpx rgba(7, 193, 96, 0.35);
  
  &.disabled {
    background: #d9d9d9;
    box-shadow: none;
    color: #999;
  }
}

.signing-guide {
  margin-top: 25rpx;
  padding: 20rpx;
  background: #f8fbff;
  border-radius: 12rpx;
  border-left: 4rpx solid #409eff;
}

.guide-title {
  font-size: 26rpx;
  color: #409eff;
  font-weight: bold;
  margin-bottom: 15rpx;
}

.guide-list {
  display: flex;
  flex-direction: column;
}

.guide-item {
  display: flex;
  align-items: center;
  margin-bottom: 12rpx;
  
  .guide-icon {
    font-size: 24rpx;
    color: #fff;
    font-weight: bold;
    margin-right: 16rpx;
    min-width: 40rpx;
    height: 40rpx;
    line-height: 40rpx;
    text-align: center;
    background: #07c160;
    border-radius: 50%;
  }
  
  .guide-text {
    font-size: 24rpx;
    color: #666;
    flex: 1;
    line-height: 1.5;
  }
}

.signature-display {
  text-align: center;
  padding: 40rpx 20rpx;
  position: relative;
  
  .signed-label {
    font-size: 36rpx;
    color: #52c41a;
    font-weight: bold;
    display: block;
    margin-bottom: 16rpx;
  }

  .sign-method-tag {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 10rpx 20rpx;
    background: rgba(7, 193, 96, 0.1);
    border: 1rpx solid rgba(7, 193, 96, 0.3);
    border-radius: 30rpx;
    margin-bottom: 20rpx;
    .method-icon { font-size: 28rpx; }
    .method-text { font-size: 26rpx; color: #07c160; font-weight: 500; }
  }
  
  .sign-meta {
    text-align: right;
    font-size: 24rpx;
    color: #999;
    margin-top: 20rpx;
    border-top: 1rpx solid #eee;
    padding-top: 20rpx;
  }
  
  .watermark {
    position: absolute;
    top: 40rpx;
    right: 40rpx;
    font-size: 100rpx;
    color: rgba(82,196,26,0.15);
    font-weight: bold;
    transform: rotate(-30deg);
    border: 6rpx solid rgba(82,196,26,0.15);
    padding: 10rpx 40rpx;
    border-radius: 20rpx;
    pointer-events: none;
  }
}

// 全屏预览样式（与 list.vue 保持一致）
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
