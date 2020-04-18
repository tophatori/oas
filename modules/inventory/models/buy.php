<?php
/**
 * @filesource modules/inventory/models/buy.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Buy;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-buy
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึกข้อมูลการสั่งซื้อ
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, can_buy, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_buy') && Login::notDemoMode($login)) {
                $order = array(
                    'order_no' => $request->post('order_no')->topic(),
                    'customer_id' => $request->post('customer_id')->toInt(),
                    'comment' => $request->post('comment')->textarea(),
                    'order_date' => $request->post('order_date')->date(),
                    'due_date' => $request->post('due_date')->date(),
                    'discount_percent' => $request->post('discount_percent')->toDouble(),
                    'discount' => $request->post('total_discount')->toDouble(),
                    'tax' => $request->post('tax_total')->toDouble(),
                    'vat' => $request->post('vat_total')->toDouble(),
                    'total' => $request->post('amount')->toDouble(),
                    'vat_status' => $request->post('vat_status')->toInt(),
                    'tax_status' => $request->post('tax_status')->toInt(),
                    'status' => $request->post('status')->toInt(),
                );
                $order_id = $request->post('order_id')->toInt();
                // ชื่อตาราง
                $table_orders = $this->getTableName('orders');
                $table_stock = $this->getTableName('stock');
                // Database
                $db = $this->db();
                // ตรวจสอบรายการ order ที่เลือก
                $orders = \Inventory\Order\Model::get($order_id, 'IN', $order['status']);
                if (!$orders) {
                    // ไม่พบข้อมูลที่แก้ไข
                    $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                } elseif (empty($order['customer_id'])) {
                    // ไม่ได้เลือก Supplyer
                    $ret['ret_customer'] = 'this';
                } else {
                    // สินค้าที่เลือก
                    $datas = array(
                        'quantity' => $request->post('quantity', array())->toDouble(),
                        'topic' => $request->post('topic', array())->topic(),
                        'price' => $request->post('price', array())->toDouble(),
                        'discount' => $request->post('discount', array())->toDouble(),
                        'total' => $request->post('total', array())->toDouble(),
                        'vat' => $request->post('vat', array())->toDouble(),
                        'id' => $request->post('id', array())->toInt(),
                    );
                    $stock = array();
                    foreach ($datas['topic'] as $key => $value) {
                        if ($value == '') {
                            $ret['ret_topic_'.$key] = 'Please fill in';
                        } else {
                            $product_id = $datas['id'][$key];
                            $stock[$product_id] = array(
                                'quantity' => $datas['quantity'][$key],
                                'topic' => $datas['topic'][$key],
                                'price' => $datas['price'][$key],
                                'discount' => $datas['discount'][$key],
                                'total' => $datas['total'][$key],
                                'vat' => empty($datas['vat'][$key]) ? 0 : $datas['vat'][$key],
                                'product_id' => $product_id,
                                'member_id' => $login['id'],
                                'create_date' => $order['order_date'],
                                'status' => 'IN',
                            );
                        }
                    }
                    if (empty($ret)) {
                        if (empty($stock)) {
                            // ไม่ได้เลือกสินค้า
                            $ret['ret_product_no'] = 'this';
                        } else {
                            // save order
                            if ($order['order_no'] == '') {
                                // สร้างเลข running number
                                $order['order_no'] = \Inventory\Number\Model::get($order_id, 'order_no', $table_orders, 'order_no');
                            } else {
                                // ตรวจสอบ order_no ซ้ำ
                                $search = $db->first($table_orders, array('order_no', $order['order_no']));
                                if ($search !== false && $order_id != $search->id) {
                                    $ret['ret_order_no'] = Language::replace('This :name already exist', array(':name' => Language::get('Order No.')));
                                }
                            }
                        }
                    }
                    if (empty($ret)) {
                        if ($order_id > 0) {
                            // แก้ไข
                            $db->update($table_orders, array('id', $order_id), $order);
                        } else {
                            // ใหม่
                            $order['stock_status'] = 'IN';
                            $order['member_id'] = $login['id'];
                            $order_id = $db->insert($table_orders, $order);
                        }
                        // ตรวจสอบ stock เดิม
                        foreach ($db->select($table_stock, array('order_id', $order_id)) as $item) {
                            if (isset($stock[$item['product_id']])) {
                                $stock[$item['product_id']]['id'] = $item['id'];
                            }
                        }
                        // ลบ stock เก่า (ถ้ามี)
                        $db->delete($table_stock, array('order_id', $order_id), 0);
                        // save stock
                        foreach ($stock as $save) {
                            $save['order_id'] = $order_id;
                            $db->insert($table_stock, $save);
                        }
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $save_and_create = $request->post('save_and_create')->toInt();
                        if ($save_and_create == 1) {
                            $ret['location'] = 'reload';
                        } else {
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'inventory-inward', 'status' => $order['status'], 'id' => null));
                        }
                        // save cookie
                        setcookie('buy_save_and_create', $save_and_create, time() + 2592000, '/', HOST, HTTPS, true);
                        setcookie('buy_vat_status', $order['vat_status'], time() + 2592000, '/', HOST, HTTPS, true);
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
