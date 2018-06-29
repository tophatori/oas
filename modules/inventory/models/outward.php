<?php
/**
 * @filesource modules/inventory/models/outward.php
 *
 * @see http://www.kotchasan.com/
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 */

namespace Inventory\Outward;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับ (setup.php).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable.
     *
     * @param object $owner
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($owner)
    {
        $model = new static();

        return $model->db()->createQuery()
            ->select('O.order_date', 'O.order_no', 'C.company', Sql::create('(O.`total`+O.`vat`-O.`tax`) AS `total`'), 'O.id', 'O.customer_id')
            ->from('orders O')
            ->join('customer C', 'LEFT', array(
                array('C.id', 'O.customer_id'),
            ))
            ->where(array(
                array('O.status', $owner->status),
                array('O.stock_status', 'OUT'),
            ));
    }

    /**
     * รับค่าจาก action.
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, can_sell, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_sell') && Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    // ตาราง user
                    $table = $this->getTableName('product');
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
