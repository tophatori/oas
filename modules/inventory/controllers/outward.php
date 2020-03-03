<?php
/**
 * @filesource modules/inventory/controllers/outward.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Inventory\Outward;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-outward.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายงานการขายสินค้า.
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // เลือกเมนู
        $this->menu = 'sell';
        // member, can_sell
        if ($login = Login::checkPermission(Login::isMember(), 'can_sell')) {
            // ค่าที่ส่งมา
            $owner = (object) array(
                'typies' => Language::get('SELL_TYPIES'),
                'year' => $request->request('year', date('Y'))->toInt(),
                'month' => $request->request('month', date('m'))->toInt(),
                'status' => $request->request('status')->toInt(),
            );
            $owner->status = isset($owner->typies[$owner->status]) ? $owner->status : self::$cfg->outward_status;
            $title = '{LNG_Sales report} '.$owner->typies[$owner->status];
            if ($owner->month > 0) {
                $title .= ' {LNG_month}  '.Language::find('MONTH_LONG', null, $owner->month);
            }
            if ($owner->year > 0) {
                $title .= ' {LNG_year} '.($owner->year + Language::get('YEAR_OFFSET'));
            }
            $this->title = Language::trans($title);
            // แสดงผล
            $section = Html::create('section', array(
                'class' => 'content_bg',
            ));
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs',
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><a href="index.php" class="icon-home">{LNG_Home}</a></li>');
            $ul->appendChild('<li><span>{LNG_Sell}</span></li>');
            $ul->appendChild('<li><span>{LNG_Report}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-report">'.$this->title.'</h2>',
            ));
            // แสดงตาราง
            $section->appendChild(createClass('Inventory\Outward\View')->render($request, $owner));
            // คืนค่า HTML

            return $section->render();
        }
        // 404

        return \Index\Error\Controller::execute($this);
    }
}
