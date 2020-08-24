<?php
/**
 * @filesource modules/inventory/models/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Setup;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * model=inventory-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = array();
        if (!empty($params['category_id'])) {
            $where[] = array('P.category_id', $params['category_id']);
        }

        return static::createQuery()
            ->select('P.product_no', 'P.topic', 'P.category_id', 'P.price', 'P.cost', 'P.id', 'P.stock')
            ->from('product P')
            ->where($where);
    }

    /**
     * รับค่าจาก action (setup.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, can_manage_inventory, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_manage_inventory') && Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    // ตาราง product
                    $table = $this->getTableName('product');
                    if ($action === 'delete') {
                        // ลบสินค้า
                        $this->db()->delete($table, array('id', $match[1]), 0);
                        // ลบไฟล์
                        foreach ($match[1] as $id) {
                            if (file_exists(ROOT_PATH.DATA_FOLDER.'inventory/'.$id.'.jpg')) {
                                unlink(ROOT_PATH.DATA_FOLDER.'inventory/'.$id.'.jpg');
                            }
                        }
                        // reload
                        $ret['location'] = 'reload';
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
