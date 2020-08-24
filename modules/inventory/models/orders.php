<?php
/**
 * @filesource modules/inventory/models/orders.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Orders;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-orders
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
     * @param object $owner
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($owner)
    {
        return static::createQuery()
            ->select('O.order_date', 'O.order_no', 'C.company', Sql::create('(O.`total`+O.`vat`-O.`tax`) AS `total`'), 'O.id', 'O.customer_id')
            ->from('orders O')
            ->join('customer C', 'LEFT', array(
                array('C.id', 'O.customer_id'),
            ))
            ->where(array('O.status', $owner->status));
    }

    /**
     * รับค่าจาก action.
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, สามารถ ซื้อ/ขาย ได้, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_inventory_order') && Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    // ตาราง user
                    if ($action === 'delete') {
                        // ลบ order
                        $this->db()->delete($this->getTableName('orders'), array('id', $match[1]), 0);
                        // ลบ stock
                        $this->db()->delete($this->getTableName('stock'), array('order_id', $match[1]), 0);
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
