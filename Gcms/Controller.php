<?php
/**
 * @filesource Gcms/Controller.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Gcms;

/**
 * Controller base class.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * View.
     *
     * @var \Gcms\View
     */
    public static $view;

    /**
     * เก็บคลาสของเมนูที่เลือก
     *
     * @var string
     */
    protected $menu;

    /**
     * Menu Controller.
     *
     * @var \Index\Menu\Controller
     */
    protected static $menus;

    /**
     * ข้อความไตเติลบาร์.
     *
     * @var string
     */
    protected $title;

    /**
     * init Class.
     */
    public function __construct()
    {
        // ค่าเริ่มต้นของ Controller
        $this->title = strip_tags(self::$cfg->web_title);
        $this->menu = 'home';
    }

    /**
     * โหลด permissions ของโมดูลต่างๆ.
     *
     * @return array
     */
    public static function getPermissions()
    {
        // permissions เริ่มต้น
        $permissions = \Kotchasan\Language::get('PERMISSIONS');
        // โหลดค่าติดตั้งโมดูล
        $dir = ROOT_PATH.'modules/';
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($text = readdir($f))) {
                if ($text != '.' && $text != '..' && $text != 'index' && $text != 'css' && $text != 'js' && is_dir($dir.$text)) {
                    if (is_file($dir.$text.'/controllers/init.php')) {
                        require_once $dir.$text.'/controllers/init.php';
                        $className = '\\'.ucfirst($text).'\Init\Controller';
                        if (method_exists($className, 'updatePermissions')) {
                            $permissions = $className::updatePermissions($permissions);
                        }
                    }
                }
            }
            closedir($f);
        }

        return $permissions;
    }

    /**
     * ชื่อเมนูที่เลือก
     *
     * @return string
     */
    public function menu()
    {
        return $this->menu;
    }

    /**
     * ข้อความ title bar.
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }
}
