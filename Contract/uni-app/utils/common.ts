/**
 * 通用工具函数
 */

/**
 * 图片路径处理函数
 * @param path 图片路径
 * @returns完整的图片URL
 */
export function img(path: string): string {
    if (!path) return ''

    // 如果已经是完整URL，直接返回
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }

    // 获取基础URL
    let baseUrl = ''
    // 优先使用环境变量
    if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_BASE_URL) {
        baseUrl = import.meta.env.VITE_BASE_URL
    }
    // 其次使用 window.location
    else if (typeof window !== 'undefined' && window.location) {
        baseUrl = window.location.origin
    }
    // 最后使用默认值
    else {
        baseUrl = 'http://localhost:8080' // 默认开发服务器地址
    }

    // 避免重复添加 /upload/ 前缀
    if (path.startsWith('/')) {
        return baseUrl + path
    } else if (path.startsWith('upload/')) {
        return baseUrl + '/' + path
    } else {
        return baseUrl + '/upload/' + path
    }
}

/**
 *格化时间
 * @param timestamp 时间戳
 * @returns格化的时间字符串
 */
export function formatTime(timestamp: number): string {
    if (!timestamp) return ''
    
    const date = new Date(timestamp * 1000)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    const seconds = String(date.getSeconds()).padStart(2, '0')
    
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

/**
 * 格式化日期
 * @param timestamp 时间戳
 * @returns 格式化的日期字符串
 */
export function formatDate(timestamp: number): string {
    if (!timestamp) return ''
    
    const date = new Date(timestamp * 1000)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    
    return `${year}-${month}-${day}`
}