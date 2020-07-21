<?php
/**
 * @filesource modules/inventory/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Initmenu;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Init Menu
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล.
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        // สามารถดูรายชื่อลูกค้าได้
        if (Login::checkPermission($login, array('can_buy', 'can_sell', 'can_manage_inventory'))) {
            $menu->addTopLvlMenu('customer', '{LNG_Customer}/{LNG_Supplier}', 'index.php?module=inventory-customers', null, 'settings');
        }
        // สามารถบริหารคลังสินค้าได้
        if (Login::checkPermission($login, 'can_manage_inventory')) {
            foreach (Language::get('INVENTORY_CATEGORIES') as $type => $text) {
                $menu->add('settings', $text, 'index.php?module=inventory-categories&amp;type='.$type, null, 'category'.$type);
            }
            $menu->addTopLvlMenu('inventory', '{LNG_Inventory}', 'index.php?module=inventory-setup', null, 'settings');
            $submenus = array();
            foreach (Language::get('BUY_TYPIES') as $k => $v) {
                $submenus[$k] = array(
                    'text' => $v,
                    'url' => 'index.php?module=inventory-inward&amp;status='.$k,
                );
            }
            $menu->addTopLvlMenu('buy', '{LNG_Buy}', null, $submenus, 'inventory');
            $submenus = array();
            foreach (Language::get('SELL_TYPIES') as $k => $v) {
                $submenus[$k] = array(
                    'text' => $v,
                    'url' => 'index.php?module=inventory-outward&amp;status='.$k,
                );
            }
            $menu->addTopLvlMenu('sell', '{LNG_Sell}', null, $submenus, 'buy');
        }
    }
}
