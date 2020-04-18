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
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = array();
        if ($params['cat'] > 0) {
            $where[] = array('P.category_id', $params['cat']);
        }
        $sql = static::createQuery()
            ->select('product_id', Sql::create('SUM(IF(`status`="IN", `quantity`, `quantity`*-1)) AS `quantity`'))
            ->from('stock')
            ->groupBy('product_id');

        return static::createQuery()
            ->select('P.product_no', 'P.topic', 'P.description', 'P.price', 'P.category_id', 'P.id', Sql::create('CASE WHEN P.`count_stock`=1 THEN S.`quantity` ELSE NULL END AS `quantity`'), 'P.unit')
            ->from('product P')
            ->join(array($sql, 'S'), 'LEFT', array('S.product_id', 'P.id'))
            ->where($where);
    }

    /**
     * รับค่าจาก action.
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
                    // ตาราง user
                    $table = $this->getTableName('product');
                    if ($action === 'delete') {
                        // ลบสินค้า ไม่สามารถลบรายการที่ขายไปแล้วได้
                        $query = $this->db()->createQuery()
                            ->select('P.id')
                            ->from('product P')
                            ->where(array(
                                array('P.id', $match[1]),
                            ))
                            ->notExists('stock', array(
                                array('product_id', 'P.id'),
                                array('status', 'OUT'),
                            ))
                            ->toArray();
                        $ids = array();
                        foreach ($query->execute() as $item) {
                            $ids[] = $item['id'];
                        }
                        if (!empty($ids)) {
                            // ลบสินค้า
                            $this->db()->delete($table, array('id', $ids), 0);
                            // ลบ inventory
                            $this->db()->delete($this->getTableName('stock'), array('product_id', $ids), 0);
                        }
                        $ret = array();
                        if (count($ids) != count($match[1])) {
                            // บางรายการลบไม่ได้
                            $ret['alert'] = Language::get('Some items can not be removed because it is in use');
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
