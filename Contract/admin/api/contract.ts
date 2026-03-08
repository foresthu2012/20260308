import request from '@/utils/request'

export function getContractList(params: Record<string, any>) {
  return request.get('Contract/contract', { params })
}

export function getContractInfo(id: number) {
  return request.get(`Contract/contract/${id}`)
}

export function addContract(data: Record<string, any>) {
  return request.post('Contract/contract', data)
}

export function editContract(id: number, data: Record<string, any>) {
  return request.put(`Contract/contract/${id}`, data)
}

export function deleteContract(id: number) {
  return request.delete(`Contract/contract/${id}`)
}
