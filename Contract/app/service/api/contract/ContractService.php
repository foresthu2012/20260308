<?php
namespace addon\Contract\app\service\api\contract;

use addon\Contract\app\model\Contract;
use addon\Contract\app\model\SignatureRecord;
use app\model\system\Config;
use core\base\BaseApiService;

/**
 * Contract Service for API
 * @package addon\Contract\app\service\api\contract
 */
class ContractService extends BaseApiService
{
    /**
     * Get My Contract Page List
     * @param array $where
     * @return array
     */
    public function getPage(array $where = [])
    {
        $field = 'id, title, file_path, status, sign_image, sign_time, create_time, order_id';
        $site_id = $this->site_id ?? 0;
        $member_id = $this->member_id ?? 0;
        $where[] = ['member_id', '=', $member_id];
        $where[] = ['site_id', '=', $site_id];
        $search_model = new Contract();
        return $this->getPageList($search_model, $where, $field, 'create_time desc');
    }

    /**
     * Get Contract Info
     * @param int $id
     * @return array
     */
    public function getInfo(int $id)
    {
        $site_id = $this->site_id ?? 0;
        $member_id = $this->member_id ?? 0;
        return (new Contract())
            ->where([['id', '=', $id], ['site_id', '=', $site_id], ['member_id', '=', $member_id]])
            ->findOrEmpty()->toArray();
    }



    /**
     * Confirm Sign with WeChat Auth
     * @param int $id
     * @param string $code
     * @return array
     * @throws \Exception
     */
    public function confirmSign(int $id, string $code)
    {
        $site_id = $this->site_id ?? 0;
        $member_id = $this->member_id ?? 0;
        
        // 1. Validate parameters
        if (empty($id)) {
            throw new \Exception('CONTRACT_ID_REQUIRED');
        }
        if (empty($code)) {
            throw new \Exception('WECHAT_CODE_REQUIRED');
        }
        
        // 2. Get and validate contract
        $contract = (new Contract())->where([['id', '=', $id], ['site_id', '=', $site_id], ['member_id', '=', $member_id]])->find();
        if ($contract->isEmpty()) {
            throw new \Exception('CONTRACT_NOT_EXIST');
        }
        if ($contract['status'] == 1) {
            throw new \Exception('CONTRACT_SIGNED');
        }

        // 3. Get WeChat MiniProgram Config
        $config_model = new Config();
        $config = $config_model->getConfig([
            ['site_id', '=', $site_id],
            ['app_module', '=', 'shop'],
            ['config_key', '=', 'WECHAT_MINIAPP_CONFIG']
        ]);
        $appId = $config['value']['appid'] ?? '';
        $appSecret = $config['value']['app_secret'] ?? '';

        if (empty($appId) || empty($appSecret)) {
             throw new \Exception('WECHAT_MINIAPP_NOT_CONFIGURED');
        }

        // 4. Exchange code for openid
        try {
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
            $response = @file_get_contents($url);
            if ($response === false) {
                throw new \Exception('WECHAT_API_REQUEST_FAILED');
            }
            
            $res = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('WECHAT_API_RESPONSE_INVALID');
            }
            
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $errMsg = $res['errmsg'] ?? 'Unknown error';
                throw new \Exception('WECHAT_LOGIN_FAIL: ' . $errMsg);
            }
            
            if (!isset($res['openid'])) {
                throw new \Exception('WECHAT_OPENID_MISSING');
            }
            
            $openid = $res['openid'];
            $sessionKey = $res['session_key'] ?? '';
            
        } catch (\Exception $e) {
            throw new \Exception('WECHAT_AUTH_ERROR: ' . $e->getMessage());
        }

        // 5. Check duplicate signature record
        $exists = (new SignatureRecord())->where([
            ['site_id', '=', $site_id],
            ['contract_id', '=', $id],
            ['openid', '=', $openid]
        ])->findOrEmpty();
        
        if (!$exists->isEmpty()) {
            throw new \Exception('ALREADY_SIGNED_BY_THIS_USER');
        }

        // 6. Generate Stamp
        try {
            $stampPath = $this->generateStamp($openid);
        } catch (\Exception $e) {
            throw new \Exception('STAMP_GENERATION_FAILED: ' . $e->getMessage());
        }
        
        // 7. Composite Stamp to Contract Image
        try {
            $finalContractPath = $this->compositeStampToContract($contract, $stampPath);
        } catch (\Exception $e) {
            throw new \Exception('CONTRACT_COMPOSITE_FAILED: ' . $e->getMessage());
        }

        // 8. Save Signature Record
        try {
            $record = new SignatureRecord();
            $record->save([
                'site_id' => $site_id,
                'member_id' => $member_id,
                'contract_id' => $id,
                'openid' => $openid,
                'sign_time' => time(),
                'sign_image' => $finalContractPath // Store the final contract image path
            ]);
        } catch (\Exception $e) {
            throw new \Exception('SIGNATURE_RECORD_SAVE_FAILED');
        }
        
        // 9. Update Contract Status
        try {
            $contract->save([
                'status' => 1,
                'sign_image' => $finalContractPath,
                'sign_time' => time(),
                'update_time' => time()
            ]);
        } catch (\Exception $e) {
            throw new \Exception('CONTRACT_UPDATE_FAILED');
        }
        
        // 10. Return success response
        return [
            'contract_id' => $id,
            'sign_image' => $finalContractPath,
            'sign_time' => time(),
            'message' => '签署成功'
        ];
    }

    /**
     * Composite Stamp to Contract Image (Enhanced with better error handling)
     * @param mixed $contract
     * @param string $stampPath
     * @return string
     * @throws \Exception
     */
    private function compositeStampToContract($contract, $stampPath)
    {
        $rootPath = app()->getRootPath();
        $publicPath = $rootPath . 'public/';

        // 1. Determine Background Image (Contract File or Default Template)
        $bgPath = '';
        if (!empty($contract['file_path'])) {
            $realPath = $publicPath . $contract['file_path'];
            // Check if file exists
            if (!file_exists($realPath)) {
                throw new \Exception('CONTRACT_FILE_NOT_FOUND');
            }
            // Check if it's a valid image
            $imageInfo = @getimagesize($realPath);
            if ($imageInfo === false) {
                // Not an image, use default template
                $bgPath = '';
            } else {
                $bgPath = $realPath;
                $bgMime = $imageInfo['mime'];
            }
        }
        
        // Use default template if no valid contract image
        if (empty($bgPath)) {
            $templatePath = $rootPath . 'addon/Contract/resource/contract_template.png';
            if (!file_exists($templatePath)) {
                // Create a blank template on the fly if missing
                $bgPath = $this->createDefaultTemplate($publicPath);
            } else {
                $bgPath = $templatePath;
            }
            $bgMime = 'image/png';
        }

        // 2. Load Background Image
        $bgImg = $this->loadImage($bgPath, $bgMime);
        if ($bgImg === false) {
            throw new \Exception('FAILED_TO_LOAD_CONTRACT_IMAGE');
        }

        // 3. Load Stamp Image
        $stampRealPath = $publicPath . $stampPath;
        if (!file_exists($stampRealPath)) {
            imagedestroy($bgImg);
            throw new \Exception('STAMP_FILE_NOT_FOUND');
        }
        
        $stampImg = imagecreatefrompng($stampRealPath);
        if ($stampImg === false) {
            imagedestroy($bgImg);
            throw new \Exception('FAILED_TO_LOAD_STAMP_IMAGE');
        }

        // 4. Calculate dimensions and positioning
        $bgWidth = imagesx($bgImg);
        $bgHeight = imagesy($bgImg);
        $stampWidth = imagesx($stampImg);
        $stampHeight = imagesy($stampImg);
        
        if ($bgWidth === 0 || $bgHeight === 0 || $stampWidth === 0 || $stampHeight === 0) {
            imagedestroy($bgImg);
            imagedestroy($stampImg);
            throw new \Exception('INVALID_IMAGE_DIMENSIONS');
        }

        // Calculate stamp size (20-25% of document width, maintain aspect ratio)
        $targetStampWidth = $bgWidth * 0.22;
        $ratio = $targetStampWidth / $stampWidth;
        $targetStampHeight = $stampHeight * $ratio;

        // Calculate position (bottom right with padding)
        $padding = $bgWidth * 0.04;
        $destX = $bgWidth - $targetStampWidth - $padding;
        $destY = $bgHeight - $targetStampHeight - $padding;

        // 5. Merge (Composite) stamp onto contract
        // Enable alpha blending for transparent stamp
        imagealphablending($bgImg, true);
        imagesavealpha($bgImg, true);
        
        imagealphablending($stampImg, true);
        imagesavealpha($stampImg, true);
        
        $result = imagecopyresampled(
            $bgImg, $stampImg,
            $destX, $destY, 0, 0,
            $targetStampWidth, $targetStampHeight,
            $stampWidth, $stampHeight
        );
        
        if ($result === false) {
            imagedestroy($bgImg);
            imagedestroy($stampImg);
            throw new \Exception('FAILED_TO_COMPOSITE_IMAGES');
        }

        // 6. Save Final Image
        $fileName = 'signed_contract_' . time() . '_' . rand(1000, 9999) . '.jpg';
        $uploadDir = 'upload/contract_sign/';
        $saveDir = $publicPath . $uploadDir;
        
        if (!is_dir($saveDir)) {
            if (!mkdir($saveDir, 0755, true)) {
                imagedestroy($bgImg);
                imagedestroy($stampImg);
                throw new \Exception('FAILED_TO_CREATE_OUTPUT_DIRECTORY');
            }
        }
        
        // Save as JPG with quality 90
        $saveResult = imagejpeg($bgImg, $saveDir . $fileName, 90);
        
        // Cleanup
        imagedestroy($bgImg);
        imagedestroy($stampImg);
        
        if ($saveResult === false) {
            throw new \Exception('FAILED_TO_SAVE_FINAL_IMAGE');
        }

        return $uploadDir . $fileName;
    }
    
    /**
     * Load image from file based on MIME type
     * @param string $path
     * @param string $mime
     * @return resource|false
     */
    private function loadImage($path, $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($path);
                }
                return false;
            case 'image/bmp':
                if (function_exists('imagecreatefrombmp')) {
                    return imagecreatefrombmp($path);
                }
                return false;
            default:
                return false;
        }
    }



    /**
     * Generate Stamp Image (Optimized with better design)
     * @param string $openid
     * @return string
     * @throws \Exception
     */
    private function generateStamp($openid)
    {
        $width = 320;
        $height = 320;
        $img = imagecreatetruecolor($width, $height);
        
        if (!$img) {
            throw new \Exception('Failed to create image resource');
        }
        
        // Colors
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        $red = imagecolorallocate($img, 220, 20, 60);
        $darkRed = imagecolorallocate($img, 178, 34, 34);
        $white = imagecolorallocate($img, 255, 255, 255);
        
        imagefill($img, 0, 0, $transparent);
        imagesavealpha($img, true);
        
        $centerX = $width / 2;
        $centerY = $height / 2;
        
        // Draw outer circle border
        $radius = 150;
        imageellipse($img, $centerX, $centerY, $radius * 2, $radius * 2, $red);
        imageellipse($img, $centerX, $centerY, $radius * 2 - 4, $radius * 2 - 4, $red);
        imageellipse($img, $centerX, $centerY, $radius * 2 - 8, $radius * 2 - 8, $darkRed);
        
        // Draw inner circle
        $innerRadius = 140;
        imageellipse($img, $centerX, $centerY, $innerRadius * 2, $innerRadius * 2, $darkRed);
        
        // Draw dashed line
        for ($angle = 0; $angle < 360; $angle += 10) {
            if ($angle % 20 !== 0) {
                $rad = deg2rad($angle);
                $x1 = $centerX + cos($rad) * ($innerRadius - 8);
                $y1 = $centerY + sin($rad) * ($innerRadius - 8);
                $x2 = $centerX + cos($rad) * ($innerRadius - 4);
                $y2 = $centerY + sin($rad) * ($innerRadius - 4);
                imageline($img, $x1, $y1, $x2, $y2, $red);
            }
        }
        
        // Draw main text "已签署"
        $text = "已签署";
        $font = 5;
        $textWidth = imagefontwidth($font) * mb_strlen($text);
        imagestring($img, $font, ($width - $textWidth) / 2, $centerY - 45, $text, $red);
        
        // Draw "CONFIRMED"
        $text = "CONFIRMED";
        $textWidth = imagefontwidth($font) * strlen($text);
        imagestring($img, $font, ($width - $textWidth) / 2, $centerY - 15, $text, $darkRed);
        
        // Draw masked OpenID
        $maskedOpenid = 'ID: ' . substr($openid, -8);
        $font = 4;
        $textWidth = imagefontwidth($font) * strlen($maskedOpenid);
        imagestring($img, $font, ($width - $textWidth) / 2, $centerY + 15, $maskedOpenid, $darkRed);
        
        // Draw date (YYYY-MM-DD HH:MM)
        $date = date('Y-m-d H:i');
        $textWidth = imagefontwidth($font) * strlen($date);
        imagestring($img, $font, ($width - $textWidth) / 2, $centerY + 40, $date, $darkRed);
        
        // Save stamp image
        $fileName = 'stamp_' . time() . '_' . rand(1000, 9999) . '.png';
        $uploadDir = 'upload/contract_sign/';
        $savePath = app()->getRootPath() . 'public/' . $uploadDir;
        
        if (!is_dir($savePath)) {
            if (!mkdir($savePath, 0755, true)) {
                throw new \Exception('Failed to create upload directory');
            }
        }
        
        if (!imagepng($img, $savePath . $fileName)) {
            throw new \Exception('Failed to save stamp image');
        }
        imagedestroy($img);
        
        return $uploadDir . $fileName;
    }

    /**
     * Create a default white template if none exists
     */
    private function createDefaultTemplate($publicPath)
    {
        $width = 800;
        $height = 1131; // A4 ratio roughly
        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);
        
        // Add some dummy text
        $black = imagecolorallocate($img, 0, 0, 0);
        imagestring($img, 5, 50, 50, "Contract Agreement", $black);
        imagestring($img, 3, 50, 100, "This is a placeholder for the contract content.", $black);
        imagestring($img, 3, 50, 130, "Please upload a valid contract image.", $black);

        $dir = $publicPath . 'upload/temp/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $path = $dir . 'default_template.png';
        imagepng($img, $path);
        imagedestroy($img);
        return $path;
    }
}
