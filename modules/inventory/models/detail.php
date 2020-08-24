<?php
/**
 * @filesource modules/inventory/models/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Detail;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * ข้อมูล.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลสินค้าที่ $id
     * $id = 0 หมายถึงสินค้าใหม่
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        return static::createQuery()
            ->from('product')
            ->where(array('id', $id))
            ->first();
    }

    /**
     * บันทึกข้อมูล.
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, can_manage_inventory, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_manage_inventory') && Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $product = array(
                    'detail' => $request->post('write_detail')->textarea(),
                );
                // อ่านข้อมูลที่เลือก
                $index = self::get($request->post('write_id')->toInt());
                if (!$index) {
                    // ไม่พบข้อมูลที่แก้ไข
                    $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                } else {
                    $dir = ROOT_PATH.DATA_FOLDER.'inventory/';
                    // อัปโหลดไฟล์
                    foreach ($request->getUploadedFiles() as $item => $file) {
                        /* @var $file \Kotchasan\Http\UploadedFile */
                        if ($item == 'write_image') {
                            if ($file->hasUploadFile()) {
                                if (!File::makeDirectory($dir)) {
                                    // ไดเรคทอรี่ไม่สามารถสร้างได้
                                    $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'inventory/');
                                } else {
                                    try {
                                        $file->cropImage(array('jpg', 'jpeg', 'png'), $dir.$index->id.'.jpg', self::$cfg->inventory_w, self::$cfg->inventory_h);
                                    } catch (\Exception $exc) {
                                        // ไม่สามารถอัปโหลดได้
                                        $ret['ret_'.$item] = Language::get($exc->getMessage());
                                    }
                                }
                            } elseif ($file->hasError()) {
                                // ข้อผิดพลาดการอัปโหลด
                                $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                            }
                        }
                    }
                    if (empty($ret)) {
                        // แก้ไข
                        $this->db()->update($this->getTableName('product'), array('id', $index->id), $product);
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        // รีโหลด
                        $ret['location'] = 'reload';
                        // เคลียร์
                        $request->removeToken();
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
