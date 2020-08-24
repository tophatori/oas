<?php
/**
 * @filesource modules/inventory/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Home;

/**
 * ค้นหาสินค้า.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านจำนวนใบสั่งซื้อที่ยังไม่ได้อนุมัติ.
     *
     * @param array $login
     *
     * @return object
     */
    public static function getCardData($login)
    {
        // Model
        $model = new static();
        // Database
        $db = $model->db();
        $sql1 = array($db->createQuery()->selectCount()->from('customer'), 'customers');
        $sql2 = array($db->createQuery()->selectCount()->from('product'), 'products');
        $sql3 = array($db->createQuery()->selectCount()->from('orders')->where(array(
            array('status', 'OUT'),
            array('order_date', date('Y-m-d')),
        )), 'sell');
        $sql4 = array($db->createQuery()->selectCount()->from('orders')->where(array(
            array('status', 'PO'),
            array('order_date', date('Y-m-d')),
        )), 'purcashe_order');

        return $db->createQuery()->first($sql1, $sql2, $sql3, $sql4);
    }
}
