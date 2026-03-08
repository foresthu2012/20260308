<template>
  <el-dialog 
    v-model="dialogVisible" 
    :title="title" 
    width="90vw" 
    :fullscreen="isFullscreen"
    class="file-preview-dialog"
    destroy-on-close
    @closed="onClosed"
  >
    <div class="preview-container" ref="previewContainer">
      <!-- PDF 预览 - 使用原生 embed -->
      <div v-if="isPdf" class="pdf-preview-wrapper">
        <div class="pdf-toolbar" v-if="showToolbar">
          <div class="toolbar-left">
            <el-button @click="downloadPdf" :icon="Download" circle>下载</el-button>
          </div>
          <div class="toolbar-center">
            <span class="pdf-hint-text">点击右上角按钮下载 PDF 文件</span>
          </div>
          <div class="toolbar-right">
            <el-button @click="zoomOut" :icon="Minus" circle />
            <el-button @click="zoomIn" :icon="Plus" circle />
            <el-button @click="toggleFullscreen" :icon="isFullscreen ? FullScreen : Aim" circle />
          </div>
        </div>
        
        <div class="pdf-content" ref="pdfContainer">
          <embed
            :src="fileUrl"
            type="application/pdf"
            class="pdf-embed"
          />
        </div>
      </div>

      <!-- 图片预览 -->
      <div v-else-if="isImage" class="image-preview-wrapper">
        <div class="image-toolbar" v-if="showToolbar">
          <div class="toolbar-left">
            <el-button @click="resetImage" :icon="RefreshRight" circle>重置</el-button>
          </div>
          <div class="toolbar-right">
            <el-button @click="zoomOut" :icon="Minus" circle />
            <el-button @click="zoomIn" :icon="Plus" circle />
            <el-button @click="toggleFullscreen" :icon="isFullscreen ? FullScreen : Aim" circle />
          </div>
        </div>
        
        <div class="image-content" ref="imageContainer">
          <el-image
            :src="fileUrl"
            :zoom-rate="1.2"
            :max-scale="5"
            :min-scale="0.3"
            :preview-src-list="[fileUrl]"
            :initial-index="0"
            fit="contain"
            class="preview-image"
          >
            <template #placeholder>
              <div class="image-loading">
                <el-icon class="is-loading"><Loading /></el-icon>
                <span>图片加载中...</span>
              </div>
            </template>
            <template #error>
              <div class="image-error">
                <el-icon><CircleClose /></el-icon>
                <span>图片加载失败</span>
              </div>
            </template>
          </el-image>
        </div>
      </div>

      <!-- 加载状态 -->
      <div v-if="loading" class="loading-state">
        <el-icon class="is-loading" :size="40"><Loading /></el-icon>
        <p>{{ loadingText }}</p>
        <el-progress v-if="downloadProgress > 0" :percentage="downloadProgress" :stroke-width="3" />
      </div>

      <!-- 错误状态 -->
      <div v-if="error" class="error-state">
        <el-icon :size="40"><WarningFilled /></el-icon>
        <p>{{ error }}</p>
        <el-button type="primary" @click="retryLoad">重试</el-button>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="close">关闭</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { 
  Loading, 
  CircleClose, 
  WarningFilled, 
  Plus, 
  Minus, 
  FullScreen, 
  Aim,
  RefreshRight,
  Download
} from '@element-plus/icons-vue'

interface Props {
  modelValue: boolean
  fileUrl: string
  fileName?: string
  showToolbar?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  fileName: '',
  showToolbar: true
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'close'): void
}>()

// 对话框控制
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => props.fileName || '文件预览')

// 文件类型判断
const isPdf = ref(false)
const isImage = ref(false)
const fileType = ref('')

// 加载状态
const loading = ref(true)
const loadingText = ref('正在加载文件...')
const downloadProgress = ref(0)
const error = ref('')

// PDF 相关
const zoomLevel = ref(100)
const pdfContainer = ref<HTMLElement>()

// 图片相关
const imageContainer = ref<HTMLElement>()

// 工具栏
const showToolbar = ref(props.showToolbar)

// 容器引用
const previewContainer = ref<HTMLElement>()

// 全屏状态
const isFullscreen = ref(false)

// 判断文件类型
const checkFileType = () => {
  const url = props.fileUrl.toLowerCase()
  
  if (url.includes('.pdf') || url.includes('application/pdf')) {
    isPdf.value = true
    isImage.value = false
    fileType.value = 'PDF'
  } else if (/\.(png|jpg|jpeg|gif|bmp|webp)$/i.test(props.fileUrl)) {
    isPdf.value = false
    isImage.value = true
    fileType.value = '图片'
  } else {
    isPdf.value = false
    isImage.value = true
    fileType.value = '其他'
  }
}

// 加载文件
const loadFile = async () => {
  loading.value = true
  error.value = ''
  downloadProgress.value = 0
  
  try {
    if (!props.fileUrl) {
      throw new Error('文件地址不能为空')
    }
    
    checkFileType()
    loadingText.value = isPdf.value ? '正在加载 PDF...' : '正在加载图片...'
    
    if (isPdf.value) {
      // 模拟加载进度
      const progressInterval = setInterval(() => {
        if (downloadProgress.value < 90) {
          downloadProgress.value += 20
        }
      }, 200)
      
      await new Promise(resolve => setTimeout(resolve, 1000))
      clearInterval(progressInterval)
      downloadProgress.value = 100
    } else {
      await preloadImage(props.fileUrl)
    }
    
    setTimeout(() => {
      loading.value = false
    }, 500)
    
  } catch (err: any) {
    console.error('文件加载失败:', err)
    error.value = err.message || '文件加载失败，请检查网络连接'
    loading.value = false
  }
}

// 预加载图片
const preloadImage = (url: string) => {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => {
      downloadProgress.value = 100
      resolve(img)
    }
    img.onerror = () => {
      reject(new Error('图片加载失败'))
    }
    img.src = url
  })
}

// 缩放控制
const zoomIn = () => {
  zoomLevel.value = Math.min(zoomLevel.value + 10, 200)
}

const zoomOut = () => {
  zoomLevel.value = Math.max(zoomLevel.value - 10, 50)
}

const resetImage = () => {
  zoomLevel.value = 100
}

// 下载 PDF
const downloadPdf = () => {
  if (props.fileUrl) {
    const link = document.createElement('a')
    link.href = props.fileUrl
    link.download = props.fileName || '合同.pdf'
    link.click()
  }
}

// 全屏切换
const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    previewContainer.value?.requestFullscreen().catch((err) => {
      console.error('全屏失败:', err)
    })
    isFullscreen.value = true
  } else {
    document.exitFullscreen().then(() => {
      isFullscreen.value = false
    })
  }
}

// 监听全屏状态变化
const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

// 重试加载
const retryLoad = () => {
  loading.value = true
  error.value = ''
  loadFile()
}

// 关闭
const close = () => {
  dialogVisible.value = false
}

// 对话框关闭后回调
const onClosed = () => {
  zoomLevel.value = 100
  isFullscreen.value = false
  error.value = ''
  if (document.fullscreenElement) {
    document.exitFullscreen().catch(() => {})
  }
  emit('close')
}

// 监听 URL 变化
watch(() => props.fileUrl, (newVal) => {
  if (newVal && dialogVisible.value) {
    loadFile()
  }
})

// 监听 dialogVisible 变化
watch(() => props.modelValue, (newVal) => {
  if (newVal && props.fileUrl) {
    loadFile()
  }
})

onMounted(() => {
  document.addEventListener('fullscreenchange', handleFullscreenChange)
  
  if (props.modelValue && props.fileUrl) {
    loadFile()
  }
})

onUnmounted(() => {
  document.removeEventListener('fullscreenchange', handleFullscreenChange)
})

// 暴露方法
defineExpose({
  loadFile
})
</script>

<style lang="scss" scoped>
.file-preview-dialog {
  ::v-deep(.el-dialog__body) {
    padding: 0;
  }

  ::v-deep(.el-dialog__header) {
    padding: 16px 20px;
    margin-right: 0;
  }

  ::v-deep(.el-dialog) {
    border-radius: 8px;
    overflow: hidden;
  }

  .preview-container {
    position: relative;
    width: 100%;
    min-height: 60vh;
    max-height: 85vh;
    background: #f5f5f5;
    border-radius: 4px;
    overflow: hidden;
  }

  // PDF 预览样式
  .pdf-preview-wrapper {
    display: flex;
    flex-direction: column;
    height: 70vh;
    min-height: 400px;

    .pdf-toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      background: #fff;
      border-bottom: 1px solid #e4e7ed;
      flex-wrap: wrap;
      gap: 12px;
      flex-shrink: 0;
      
      .toolbar-left,
      .toolbar-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }

      .toolbar-center {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 1;
        min-width: 200px;
      }

      .pdf-hint-text {
        font-size: 14px;
        color: #909399;
        white-space: nowrap;
      }

      @media (max-width: 768px) {
        padding: 8px 12px;
        gap: 8px;
        
        .toolbar-center {
          order: 3;
          width: 100%;
          justify-content: center;
          margin-top: 4px;
        }
        
        .pdf-hint-text {
          font-size: 12px;
        }
        
        .el-button {
          padding: 8px 12px;
          font-size: 12px;
        }
      }
    }

    .pdf-content {
      flex: 1;
      overflow: hidden;
      padding: 0;
      display: flex;
      justify-content: center;
      background: #525659;
      position: relative;

      .pdf-embed {
        width: 100%;
        height: 100%;
        min-height: 400px;
        border: none;
        display: block;
      }
    }
  }

  // 图片预览样式
  .image-preview-wrapper {
    display: flex;
    flex-direction: column;
    height: 70vh;
    min-height: 400px;

    .image-toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      background: #fff;
      border-bottom: 1px solid #e4e7ed;
      flex-wrap: wrap;
      gap: 12px;
      flex-shrink: 0;
      
      .toolbar-left,
      .toolbar-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }
    }

    .image-content {
      flex: 1;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #000;
      padding: 16px;
      position: relative;

      ::v-deep(.el-image__inner) {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
      }

      .preview-image {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        transition: transform 0.3s;
        object-fit: contain;
      }
    }
  }

  // 加载状态
  .loading-state {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    z-index: 10;
    padding: 20px;

    .el-icon {
      color: #409eff;
    }

    p {
      margin-top: 16px;
      color: #606266;
      font-size: 14px;
      text-align: center;
      word-break: break-all;
    }
  }

  // 错误状态
  .error-state {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: rgba(255, 255, 255, 0.95);
    padding: 20px;

    .el-icon {
      color: #f56c6c;
      margin-bottom: 16px;
    }

    p {
      color: #606266;
      font-size: 14px;
      margin-bottom: 20px;
      text-align: center;
      word-break: break-word;
      max-width: 100%;
    }
  }

  .image-loading,
  .image-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #909399;
    text-align: center;

    .el-icon {
      font-size: 40px;
      margin-bottom: 12px;
    }

    span {
      font-size: 14px;
      word-break: break-word;
    }
  }

  .image-error {
    color: #f56c6c;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 12px 20px;
    border-top: 1px solid #e4e7ed;
  }

  // 响应式设计 - 小屏幕
  @media (max-width: 768px) {
    ::v-deep(.el-dialog) {
      width: 95vw !important;
      margin: 10px auto;
    }

    .preview-container {
      min-height: 50vh;
    }

    .pdf-preview-wrapper,
    .image-preview-wrapper {
      height: calc(100vh - 120px);
      min-height: 300px;
    }

    .dialog-footer {
      padding: 8px 12px;
      
      .el-button {
        padding: 8px 16px;
        font-size: 13px;
      }
    }
  }

  // 响应式设计 - 超小屏幕
  @media (max-width: 480px) {
    ::v-deep(.el-dialog__header) {
      padding: 12px 16px;
    }

    .pdf-toolbar,
    .image-toolbar {
      .el-button span {
        display: none;
      }
      
      .el-button {
        padding: 8px;
      }
    }
  }
}

// 全屏状态
.is-fullscreen {
  .preview-container {
    height: 100vh !important;
    max-height: 100vh !important;
  }

  .pdf-preview-wrapper,
  .image-preview-wrapper {
    height: 100vh !important;
  }

  .pdf-content,
  .image-content {
    height: calc(100vh - 60px);
  }
}
</style>
