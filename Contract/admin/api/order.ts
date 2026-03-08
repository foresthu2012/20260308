import request from '@/utils/request'

export function getOrderList(params: Record<string, any>) {
    return request.get('Contract/Shop/order/lists', { params })
}
