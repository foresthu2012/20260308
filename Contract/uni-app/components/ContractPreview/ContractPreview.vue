<template>
  <view class="contract-preview-modal" v-if="visible" :class="{ fullscreen: isFullscreen }">
    <!-- 顶部工具栏 -->
    <view class="preview-toolbar">
      <view class="toolbar-left">
        <text class="toolbar-title">{{ title }}</text>
        <text class="file-type-badge">{{ fileTypeLabel }}</text>
      </view>
      <view class="toolbar-right">
        <!-- 缩放控制 -->
        <view class="zoom-controls" v-if="supportsZoom">
          <text class="tool-btn" @click="zoomOut">➖</text>
          <text class="zoom-level">{{ zoomLevel }}%</text>
          <text class="tool-btn" @click="zoomIn">➕</text>
        </view>
        <!-- 操作按钮 -->
        <text class="tool-btn" @click="handleDownload" v-if="canDownload">📥</text>
        <text class="tool-btn" @click="toggleFullscreen">⛶</text>
        <text class="tool-btn close-btn" @click="handleClose">✕</text>
      </view>
    </view>

    <!-- 预览内容区 -->
    <view class="preview-content">
      <!-- 加载状态 -->
      <view v-if="loading" class="loading-container">
        <view class="loading-spinner"></view>
        <text class="loading-text">{{ loadingText }}</text>
      </view>

      <!-- 错误状态 -->
      <view v-else-if="error" class="error-container">
        <text class="error-icon">⚠️</text>
        <text class="error-text">{{ error }}</text>
        <view class="error-actions">
          <button class="retry-btn" @click="retryLoad">重试</button>
          <button class="download-btn" @click="handleDownload" v-if="fileUrl">下载文件</button>
        </view>
      </view>

      <!-- 正常内容 -->
      <view v-else class="content-wrapper">
        <!-- 富文本内容 -->
        <scroll-view 
          v-if="isRichText" 
          class="content-scroll"
          scroll-y
          :scroll-with-animation="true"
        >
          <view class="rich-text-content">
            <rich-text :nodes="processedContent"></rich-text>
          </view>
        </scroll-view>

        <!-- 图片内容 -->
        <scroll-view 
          v-else-if="isImage" 
          class="content-scroll"
          scroll-y
          scroll-x
          :scroll-with-animation="true"
          :enhanced="true"
          :show-scrollbar="false"
        >
          <view class="image-container">
            <image 
              :src="imageSrc" 
              :style="imageStyle"
              mode="widthFix"
              @load="onImageLoad"
              @error="onImageError"
              show-menu-by-longpress="true"
            />
          </view>
        </scroll-view>

        <!-- PDF 内容 -->
        <view v-else-if="isPdf" class="pdf-container">
          <view class="pdf-info">
            <text class="pdf-icon">📄</text>
            <text class="pdf-text">PDF 文档</text>
            <text class="pdf-hint">请点击下方按钮操作 PDF 文件</text>
          </view>
          <view class="pdf-actions">
            <button class="pdf-btn primary" @click="openPdfExternal">打开 PDF</button>
            <button class="pdf-btn" @click="handleDownload">下载到本地</button>
          </view>
        </view>

        <!-- 不支持的文件类型 -->
        <view v-else class="unsupported-container">
          <text class="unsupported-icon">📁</text>
          <text class="unsupported-text">暂不支持此文件格式预览</text>
          <button class="download-btn" @click="handleDownload">下载文件</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { img } from '@/utils/common'

interface Props {
  visible: boolean
  fileUrl?: string
  content?: string
  title?: string
  fileType?: 'auto' | 'richtext' | 'image' | 'pdf'
}

const props = withDefaults(defineProps<Props>(), {
  fileUrl: '',
  content: '',
  title: '合同预览',
  fileType: 'auto'
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'close'): void
}>()

// 状态
const loading = ref(true)
const loadingText = ref('正在加载...')
const error = ref('')
const isFullscreen = ref(false)

// 内容
const imageSrc = ref('')
const processedContent = ref('')

// 缩放
const zoomLevel = ref(100)
const minZoom = 50
const maxZoom = 200

// 检测文件类型
const detectFileType = () => {
  if (props.content && props.fileType === 'richtext') return 'richtext'
  if (props.fileUrl) {
    const url = props.fileUrl.toLowerCase()
    if (url.includes('.pdf')) return 'pdf'
    if (/\.(png|jpg|jpeg|gif|bmp|webp)$/i.test(props.fileUrl)) return 'image'
  }
  return 'richtext'
}

const fileTypeResult = ref(detectFileType())

// 计算属性
const isRichText = computed(() => fileTypeResult.value === 'richtext')
const isImage = computed(() => fileTypeResult.value === 'image')
const isPdf = computed(() => fileTypeResult.value === 'pdf')

const fileTypeLabel = computed(() => {
  const labels: Record<string, string> = { richtext: '富文本', image: '图片', pdf: 'PDF' }
  return labels[fileTypeResult.value] || '文件'
})

const supportsZoom = computed(() => isRichText.value || isImage.value)
const canDownload = computed(() => !!props.fileUrl)

const imageStyle = computed(() => ({
  width: `${zoomLevel.value}%`,
  height: 'auto'
}))

// 加载内容
const loadContent = async () => {
  loading.value = true
  error.value = ''
  
  try {
    fileTypeResult.value = detectFileType()
    
    if (isRichText.value) {
      loadingText.value = '正在加载合同内容...'
      if (!props.content) {
        error.value = '没有可预览的内容'
      } else {
        processedContent.value = props.content
          .replace(/style=["'][^"']*["']/gi, '')
          .replace(/width=["'][^"']*["']/gi, '')
          .replace(/height=["'][^"']*["']/gi, '')
          .replace(/<img([^>]*)>/gi, (match: string, attrs: string) => {
            return `<img ${attrs} style="max-width:100%;width:100%;height:auto;display:block;margin:10rpx 0;" />`
          })
      }
    } else if (isImage.value) {
      loadingText.value = '正在加载图片...'
      if (!props.fileUrl) {
        error.value = '没有可预览的图片'
      } else {
        imageSrc.value = img(props.fileUrl)
      }
    } else if (isPdf.value) {
      loadingText.value = '正在准备 PDF...'
      if (!props.fileUrl) {
        error.value = '没有可预览的 PDF 文件'
      }
    }
    
    loading.value = false
  } catch (err: any) {
    error.value = err.message || '加载失败'
    loading.value = false
  }
}

// 图片加载
const onImageLoad = () => {}
const onImageError = () => { error.value = '图片加载失败' }

// 缩放
const zoomIn = () => { zoomLevel.value = Math.min(zoomLevel.value + 10, maxZoom) }
const zoomOut = () => { zoomLevel.value = Math.max(zoomLevel.value - 10, minZoom) }
const resetZoom = () => { zoomLevel.value = 100 }

// 全屏
const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
  if (!isFullscreen.value) resetZoom()
}

// 下载
const handleDownload = () => {
  if (!props.fileUrl) return
  uni.showLoading({ title: '下载中...' })
  uni.downloadFile({
    url: img(props.fileUrl),
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
    fail: () => { uni.hideLoading(); uni.showToast({ title: '下载失败', icon: 'none' }) }
  })
}

// 外部打开 PDF
const openPdfExternal = () => {
  if (!props.fileUrl) return
  uni.showLoading({ title: '准备中...' })
  uni.downloadFile({
    url: img(props.fileUrl),
    success: (res) => {
      uni.hideLoading()
      if (res.statusCode === 200) {
        uni.openDocument({ filePath: res.tempFilePath, showMenu: true, fail: () => uni.showToast({ title: '无法打开', icon: 'none' }) })
      }
    },
    fail: () => { uni.hideLoading(); uni.showToast({ title: '打开失败', icon: 'none' }) }
  })
}

const retryLoad = () => loadContent()
const handleClose = () => emit('update:visible', false)

watch(() => props.visible, (val) => { if (val) loadContent() })
</script>

<style lang="scss" scoped>
.contract-preview-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.95);
  z-index: 9999;
  display: flex;
  flex-direction: column;
}

.preview-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 30rpx;
  background: rgba(0, 0, 0, 0.8);
  
  .toolbar-left {
    display: flex;
    align-items: center;
    gap: 16rpx;
    .toolbar-title { font-size: 30rpx; color: #fff; font-weight: bold; }
    .file-type-badge { font-size: 22rpx; padding: 4rpx 12rpx; background: rgba(255,255,255,0.2); color: #fff; border-radius: 4rpx; }
  }
  
  .toolbar-right {
    display: flex;
    align-items: center;
    gap: 20rpx;
    .zoom-controls {
      display: flex;
      align-items: center;
      background: rgba(255,255,255,0.1);
      border-radius: 30rpx;
      padding: 8rpx 16rpx;
      gap: 12rpx;
      .zoom-level { font-size: 24rpx; color: #fff; min-width: 80rpx; text-align: center; }
    }
    .tool-btn { font-size: 36rpx; padding: 10rpx; color: #fff; &.close-btn { font-size: 40rpx; } }
  }
}

.preview-content {
  flex: 1;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.loading-container, .error-container, .unsupported-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 100%;
  padding: 60rpx;
}

.loading-spinner {
  width: 60rpx;
  height: 60rpx;
  border: 4rpx solid rgba(255,255,255,0.2);
  border-top-color: #007aff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-text { margin-top: 20rpx; font-size: 28rpx; color: rgba(255,255,255,0.8); }
.error-icon { font-size: 80rpx; margin-bottom: 20rpx; }
.error-text { font-size: 28rpx; color: rgba(255,255,255,0.8); margin-bottom: 30rpx; }
.error-actions, .pdf-actions { display: flex; gap: 20rpx; }

.retry-btn, .download-btn, .pdf-btn {
  padding: 16rpx 40rpx;
  font-size: 28rpx;
  border-radius: 40rpx;
  border: none;
}
.retry-btn, .pdf-btn.primary { background: #007aff; color: #fff; }
.download-btn, .pdf-btn { background: rgba(255,255,255,0.2); color: #fff; }

.content-wrapper { width: 100%; height: 100%; }

.content-scroll {
  width: 100%;
  height: 100%;
  padding: 30rpx;
  box-sizing: border-box;
}

.rich-text-content {
  max-width: 100%;
  background: #fff;
  padding: 30rpx;
  border-radius: 12rpx;
  :deep(img) { max-width: 100% !important; height: auto !important; }
  :deep(p) { margin: 16rpx 0; line-height: 1.8; }
}

.image-container {
  display: flex;
  justify-content: center;
  padding: 20rpx;
  image { max-width: 100%; display: block; }
}

.pdf-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100%;
  .pdf-info { display: flex; flex-direction: column; align-items: center; margin-bottom: 40rpx; }
  .pdf-icon { font-size: 120rpx; margin-bottom: 20rpx; }
  .pdf-text { font-size: 32rpx; color: #fff; margin-bottom: 10rpx; }
  .pdf-hint { font-size: 24rpx; color: rgba(255,255,255,0.6); }
}

.unsupported-icon { font-size: 100rpx; margin-bottom: 20rpx; }
.unsupported-text { font-size: 28rpx; color: rgba(255,255,255,0.8); margin-bottom: 30rpx; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 375px) {
  .preview-toolbar { padding: 16rpx 20rpx; .toolbar-left .toolbar-title { font-size: 28rpx; } .zoom-controls { display: none; } }
}
@media (min-width: 768px) {
  .rich-text-content { max-width: 700rpx; margin: 0 auto; }
}
</style>
