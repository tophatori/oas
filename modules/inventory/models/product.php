<?php
/**
 * @filesource modules/inventory/models/product.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Product;

/**
 * เพิ่ม/แก้ไข ข้อมูล Inventory
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * เพิ่มสินค้าใหม่
     *
     * @param array $save
     *
     * @return int
     */
    public static function newProduct($save)
    {
        // product
        $product = array(
            'category_id' => empty($save['category']) ? 0 : \Inventory\Category\Model::newCategory($save['category']),
            'product_no' => $save['product_no'],
            'topic' => $save['topic'],
            'description' => isset($save['description']) ? $save['description'] : '',
            'cost' => isset($save['buy_price']) ? $save['buy_price'] : 0,
            'price' => $save['price'],
            'vat' => isset($save['vat']) ? $save['vat'] : 0,
            'stock' => empty($save['quantity']) ? 0 : $save['quantity'],
            'unit' => isset($save['unit']) ? $save['unit'] : '',
            'count_stock' => isset($save['count_stock']) ? $save['count_stock'] : 1,
            'last_update' => date('Y-m-d H:i:s'),
        );
        // หน่วยสินค้า
        \Inventory\Category\Model::newUnit($product['unit']);
        // Model
        $model = new \Kotchasan\Model;
        // save product
        $product_id = $model->db()->insert($model->getTableName('product'), $product);
        if ($product['stock'] > 0) {
            // stock
            $inventory = array(
                'order_id' => 0,
                'member_id' => $save['member_id'],
                'product_id' => $product_id,
                'status' => 'IN',
                'create_date' => isset($save['create_date']) ? $save['create_date'].date(' H:i:s') : $product['last_update'],
                'topic' => '',
                'quantity' => $product['stock'],
                'used' => 0,
                'price' => $product['cost'],
                'vat' => 0,
                'total' => $product['cost'] * $product['stock'],
            );
            if (!empty($save['buy_vat'])) {
                if ($save['buy_vat'] == 1) {
                    // ราคาสินค้าไม่รวม vat
                    $inventory['vat'] = (float) number_format(\Kotchasan\Currency::calcVat($inventory['total'], self::$cfg->vat, true), 2);
                } else {
                    // ราคาสินค้ารวม vat
                    $inventory['vat'] = (float) number_format(\Kotchasan\Currency::calcVat($inventory['total'], self::$cfg->vat, false), 2);
                    $inventory['total'] -= $inventory['vat'];
                }
            }
            // บันทึก
            $model->db()->insert($model->getTableName('stock'), $inventory);
        }

        return $product_id;
    }

    /**
     * อัพเดตข้อมูลสินค้าเท่านั้น
     *
     * @param array $save
     *
     * @return int
     */
    public static function updateProduct($id, $save)
    {
        // product
        $product = array(
            'category_id' => \Inventory\Category\Model::newCategory($save['category']),
            'product_no' => $save['product_no'],
            'topic' => $save['topic'],
            'description' => $save['description'],
            'cost' => $save['buy_price'],
            'price' => $save['price'],
            'vat' => $save['vat'],
            'unit' => $save['unit'],
            'count_stock' => $save['count_stock'],
            'last_update' => date('Y-m-d H:i:s'),
        );
        // หน่วยสินค้า
        \Inventory\Category\Model::newUnit($product['unit']);
        // Model
        $model = new \Kotchasan\Model;
        // save product
        $model->db()->update($model->getTableName('product'), $id, $product);

        return $id;
    }

    /**
     * อัพเดตข้อมูลสินค้าและ Stock
     *
     * @param array $save
     *
     * @return int
     */
    public static function updateStock($src, $save)
    {
        // product
        $product = array(
            'product_no' => $save['product_no'],
            'topic' => $save['topic'],
            'last_update' => date('Y-m-d H:i:s'),
        );
        $columns = array('price', 'description', 'vat', 'count_stock');
        foreach ($columns as $key) {
            if (isset($save[$key])) {
                $product[$key] = $save[$key];
            }
        }
        if (!empty($save['category'])) {
            // หมวดหมู่สินค้า
            $product['category_id'] = \Inventory\Category\Model::newCategory($save['category']);
        }
        if (!empty($save['unit'])) {
            // หน่วยสินค้า
            \Inventory\Category\Model::newUnit($save['unit']);
            $product['unit'] = $save['unit'];
        }
        if (isset($save['buy_price'])) {
            $product['cost'] = $save['buy_price'];
        }
        // Model
        $model = new \Kotchasan\Model;
        // save product
        $model->db()->update($model->getTableName('product'), $src->id, $product);
        // อัพเดต Stock
        if (isset($save['quantity']) && $src->stock != $save['quantity']) {
            $inventory = array(
                'order_id' => 0,
                'product_id' => $src->id,
                'create_date' => $product['last_update'],
                'member_id' => $save['member_id'],
                'topic' => '',
                'used' => 0,
                'vat' => 0,
            );
            if ($src->stock > $save['quantity']) {
                // ขาย
                $inventory['price'] = empty($product['price']) ? $src->price : $product['price'];
                $inventory['status'] = 'OUT';
                $inventory['quantity'] = $src->stock - $save['quantity'];
            } elseif ($src->stock < $save['quantity']) {
                // ซื้อ
                $inventory['price'] = empty($product['cost']) ? $src->cost : $product['cost'];
                $inventory['status'] = 'IN';
                $inventory['quantity'] = $save['quantity'] - $src->stock;
            }
            $inventory['total'] = $inventory['price'] * $inventory['quantity'];
            // save Order
            $model->db()->insert($model->getTableName('stock'), $inventory);
            // update Stock
            \Inventory\Fifo\Model::update($src->id);
        }
    }
}
