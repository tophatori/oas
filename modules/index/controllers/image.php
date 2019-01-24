<?php
/**
 * @filesource modules/index/controllers/image.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Index\Image;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=image.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{

  /**
   * ตั้งค่าบริษัท.
   *
   * @param Request $request
   *
   * @return string
   */
  public function render(Request $request)
  {
    // ข้อความ title bar
    $this->title = Language::get('Image settings');
    // เลือกเมนู
    $this->menu = 'settings';
    // สามารถตั้งค่าระบบได้
    if ($login = Login::checkPermission(Login::isMember(), 'can_config')) {
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
      $ul->appendChild('<li><span>{LNG_Settings}</span></li>');
      $ul->appendChild('<li><span>{LNG_Image}</span></li>');
      $section->add('header', array(
        'innerHTML' => '<h2 class="icon-config">'.$this->title.'</h2>',
      ));
      // แสดงฟอร์ม
      $section->appendChild(createClass('Index\Image\View')->render());
      // คืนค่า HTML
      return $section->render();
    }
    // 404
    return \Index\Error\Controller::execute($this);
  }
}