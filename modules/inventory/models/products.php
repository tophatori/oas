<?php
/**
 * @filesource modules/inventory/models/products.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Products;

/**
 * model=inventory-products
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
            ->select('P.product_no', 'P.topic', 'P.category_id', 'P.price', 'P.id', 'P.stock')
            ->from('product P')
            ->where($where);
    }
}
