<?php
namespace addon\Contract\app\service\admin\contract;

use addon\Contract\app\model\Contract;
use core\base\BaseAdminService;

/**
 * Contract Service
 * @package addon\Contract\app\service\admin\contract
 */
class ContractService extends BaseAdminService
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new Contract();
    }

    /**
     * Parse file extension from file_path
     */
    public function parseFileExt(string $filePath): string
    {
        if (empty($filePath)) {
            return '';
        }
        $pathInfo = pathinfo($filePath);
        return isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';
    }

    /**
     * Auto set file_ext before save
     */
    public function autoParseFileExt(array $data): array
    {
        if (!empty($data['file_path'])) {
            $data['file_ext'] = $this->parseFileExt($data['file_path']);
        }
        return $data;
    }

    /**
     * Get Contract Page List
     */
    public function getPage(array $where = [], $member_keyword = '')
    {
        $field = 'id, title, file_path, file_ext, member_id, order_id, status, sign_image, sign_time, create_time';
        
        $with = [
            'member' => function($query) {
                $query->field('member_id, nickname, mobile, headimg');
            },
            'order' => function($query) {
                $query->field('order_id, order_no, order_money, status');
            }
        ];
        
        $searchModel = new Contract();
        
        if (!empty($member_keyword)) {
            $searchModel = $searchModel->hasWhere('member', function($query) use ($member_keyword) {
                $query->where('nickname|mobile', 'like', '%' . $member_keyword . '%');
            });
        }
        
        return $this->getPageList($searchModel, $where, $field, 'create_time desc', [], $with);
    }

    /**
     * Add Contract
     */
    public function add(array $data)
    {
        $data = $this->autoParseFileExt($data);
        
        if (empty($data['auth_token'])) {
            $data['auth_token'] = null;
        }
        
        if (!empty($data['order_id'])) {
            try {
                $order = \addon\Contract\app\model\order\Order::where('order_id', $data['order_id'])->find();
                if (!$order || $order['member_id'] != $data['member_id']) {
                    throw new \Exception('CONTRACT_ORDER_NOT_BELONG_TO_MEMBER');
                }
            } catch (\Exception $e) {
                // Skip validation
            }
        }
        
        $data['site_id'] = $this->site_id ?? 0;
        $data['create_time'] = time();
        
        return $this->model->save($data);
    }

    /**
     * Edit Contract
     */
    public function edit(int $id, array $data)
    {
        $site_id = $this->site_id ?? 0;
        $contract = $this->model->where([['id', '=', $id], ['site_id', '=', $site_id]])->find();
        
        if ($contract->isEmpty()) {
            throw new \Exception('CONTRACT_NOT_EXIST');
        }
        
        if ($contract['status'] == 1) {
            throw new \Exception('CONTRACT_SIGNED_CANNOT_EDIT');
        }
        
        $data = $this->autoParseFileExt($data);
        
        if (!empty($data['order_id'])) {
            try {
                $order = \addon\Contract\app\model\order\Order::where('order_id', $data['order_id'])->find();
                if (!$order || $order['member_id'] != $data['member_id']) {
                    throw new \Exception('CONTRACT_ORDER_NOT_BELONG_TO_MEMBER');
                }
            } catch (\Exception $e) {
                // Skip validation
            }
        }
        
        $data['update_time'] = time();
        return $contract->save($data);
    }

    /**
     * Delete Contract
     */
    public function delete(int $id)
    {
        $site_id = $this->site_id ?? 0;
        $contract = $this->model->where([['id', '=', $id], ['site_id', '=', $site_id]])->find();
        
        if ($contract->isEmpty()) {
            return false;
        }
        
        if ($contract['status'] == 1) {
            throw new \Exception('CONTRACT_SIGNED_CANNOT_DELETE');
        }
        
        return $contract->delete();
    }

    /**
     * Get Contract Info
     */
    public function getInfo(int $id)
    {
        $site_id = $this->site_id ?? 0;
        $contract = $this->model->with(['member', 'order'])->where([['id', '=', $id], ['site_id', '=', $site_id]])->findOrEmpty();
        
        $data = $contract->toArray();
        
        if (empty($data['file_ext']) && !empty($data['file_path'])) {
            $data['file_ext'] = $this->parseFileExt($data['file_path']);
        }
        
        return $data;
    }
}
