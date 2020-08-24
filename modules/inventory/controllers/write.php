<?php
/**
 * @filesource modules/inventory/controllers/write.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Write;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * เพิ่ม-แก้ไข สินค้า
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Product');
        // เลือกเมนู
        $this->menu = 'inventory';
        // สามารถทำรายการสินค้าได้
        if (Login::checkPermission(Login::isMember(), 'can_manage_inventory')) {
            // อ่านข้อมูลที่เลือก
            $product = \Inventory\Write\Model::get($request->request('id')->toInt());
            if ($product) {
                // ข้อความ title bar
                if ($product['id'] == 0) {
                    $title = '{LNG_Add New}';
                    $this->title = Language::get('Add New').' '.$this->title;
                } else {
                    $title = '{LNG_Detail}';
                    $this->title = Language::get('Details of').' '.$product['product_no'];
                }
                // แสดงผล
                $section = Html::create('section', array(
                    'class' => 'content_bg',
                ));
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs',
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-product">{LNG_Inventory}</span></li>');
                $ul->appendChild('<li><a href="index.php?module=inventory-setup">{LNG_Product}</a></li>');
                $ul->appendChild('<li><span>'.$title.'</span></li>');
                $header = $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>',
                ));
                $inline = $header->add('div', array(
                    'class' => 'inline',
                ));
                $writetab = $inline->add('div', array(
                    'class' => 'writetab',
                ));
                $ul = $writetab->add('ul', array(
                    'id' => 'accordient_menu',
                ));
                // tab ที่เลือก
                $tab = $request->request('tab')->filter('a-z');
                if ($tab == '') {
                    $tab = $product['id'] == 0 ? 'product' : 'overview';
                }
                if ($product['id'] > 0) {
                    $ul->add('li', array(
                        'innerHTML' => '<a'.($tab == 'overview' ? ' class=select' : '').' href="index.php?module=inventory-write&amp;id='.$product['id'].'&amp;tab=overview">{LNG_Overview}</a>',
                    ));
                }
                $ul->add('li', array(
                    'innerHTML' => '<a'.($tab == 'product' ? ' class=select' : '').' href="index.php?module=inventory-write&amp;id='.$product['id'].'&amp;tab=product">{LNG_Details of} {LNG_Product}</a>',
                ));
                if ($product['id'] > 0) {
                    $ul->add('li', array(
                        'innerHTML' => '<a'.($tab == 'detail' ? ' class=select' : '').' href="index.php?module=inventory-write&amp;id='.$product['id'].'&amp;tab=detail">{LNG_Other details}</a>',
                    ));
                    $ul->add('li', array(
                        'innerHTML' => '<a'.($tab == 'inventory' ? ' class=select' : '').' href="index.php?module=inventory-write&amp;id='.$product['id'].'&amp;tab=inventory">{LNG_Inventory}</a>',
                    ));
                }
                if ($tab == 'overview' && $product['id'] > 0) {
                    // แสดงภาพรวมของสินค้า รูปแบบกราฟ
                    $section->appendChild(createClass('Inventory\Overview\View')->render($request, $product));
                } elseif ($tab == 'detail' && $product['id'] > 0) {
                    // รายละเอียดสินค้า
                    $section->appendChild(createClass('Inventory\Detail\View')->render($request, $product));
                } elseif ($tab == 'inventory' && $product['id'] > 0) {
                    // ตารางสต๊อกสินค้า
                    $section->appendChild(createClass('Inventory\Inventory\View')->render($request, $product));
                } else {
                    // แสดงฟอร์ม write
                    $section->appendChild(createClass('Inventory\Write\View')->render($request, $product));
                }
                // คืนค่า HTML

                return $section->render();
            }
        }
        // 404

        return \Index\Error\Controller::execute($this);
    }

    /**
     * แสดงฟอร์มสำหรับเพิ่มพัสดุ (modal)
     *
     * @param Request $request
     */
    public function showModal(Request $request)
    {
        // สมาชิก
        if ($login = Login::isMember()) {
            // View
            $view = new \Gcms\View();
            // เพิ่ม
            $product = \Inventory\Write\Model::get(0);
            // แสดงผลฟอร์ม
            echo $view->renderHTML(createClass('Inventory\Write\View')->render($request, $product, $login));
        }
    }
}
