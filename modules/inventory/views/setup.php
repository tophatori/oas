<?php
/**
 * @filesource modules/inventory/views/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Setup;

use Kotchasan\Currency;
use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-setup.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var mixed
     */
    private $tax_status;
    /**
     * @var mixed
     */
    private $categories;

    /**
     * ตารางรายการ สินค้า.
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->tax_status = Language::get('TAX_STATUS');
        $this->categories = \Inventory\Category\Model::categories();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Inventory\setup\Model::toDataTable(),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('setup_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('setup_sort', 'id desc')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('product_no', 'topic'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/inventory/model/setup/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}',
                    ),
                ),
                array(
                    'class' => 'button pink icon-plus',
                    'href' => $uri->createBackUri(array('module' => 'inventory-write', 'id' => '0')),
                    'text' => '{LNG_Add New} {LNG_Product}',
                ),
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                'category_id' => array(
                    'name' => 'cat',
                    'default' => 0,
                    'text' => '{LNG_Category}',
                    'options' => array(0 => '{LNG_all items}') + $this->categories->toSelect(),
                    'value' => $request->request('cat', 0)->toInt(),
                ),
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'product_no' => array(
                    'text' => '{LNG_Product Code}',
                    'sort' => 'product_no',
                ),
                'topic' => array(
                    'text' => '{LNG_Topic}',
                    'sort' => 'topic',
                ),
                'description' => array(
                    'text' => '{LNG_Description}',
                    'class' => 'tablet',
                ),
                'price' => array(
                    'text' => '{LNG_Unit Price}',
                    'class' => 'center',
                    'sort' => 'price',
                ),
                'category_id' => array(
                    'text' => '{LNG_Category}',
                    'class' => 'center',
                ),
                'quantity' => array(
                    'text' => '{LNG_Stock}',
                    'class' => 'center',
                    'sort' => 'quantity',
                ),
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'description' => array(
                    'class' => 'tablet',
                ),
                'price' => array(
                    'class' => 'right',
                ),
                'category_id' => array(
                    'class' => 'center',
                ),
                'quantity' => array(
                    'class' => 'center',
                ),
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                array(
                    'class' => 'icon-report button orange',
                    'href' => $uri->createBackUri(array('module' => 'inventory-write', 'tab' => 'overview', 'id' => ':id')),
                    'text' => '{LNG_Detail}',
                ),
                array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'inventory-write', 'tab' => 'product', 'id' => ':id')),
                    'text' => '{LNG_Edit}',
                ),
            ),
        ));
        // save cookie
        setcookie('setup_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('setup_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML

        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $item['price'] = Currency::format($item['price']);
        $item['category_id'] = $this->categories->get($item['category_id']);

        return $item;
    }
}
