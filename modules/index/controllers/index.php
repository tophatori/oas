<?php
/**
 * @filesource modules/index/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Index\Index;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * Controller สำหรับแสดงหน้าเว็บ.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * หน้าหลักเว็บไซต์ (index.html)
     * ให้ผลลัพท์เป็น HTML.
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // ตัวแปรป้องกันการเรียกหน้าเพจโดยตรง
        define('MAIN_INIT', 'indexhtml');
        // session cookie
        $request->initSession();
        // ตรวจสอบการ login
        Login::create();
        // กำหนด skin ให้กับ template
        Template::init(self::$cfg->skin);
        // View
        self::$view = new \Gcms\View();
        // Javascript
        self::$view->addScript('var WEB_URL="'.WEB_URL.'";');
        if ($login = Login::isMember()) {
            // โหลดเมนู
            self::$menus = \Index\Menu\Controller::init($login);
            // Javascript
            self::$view->addScript('var FIRST_MODULE="'.self::$menus->home().'";');
            // โหลดค่าติดตั้งโมดูล
            $dir = ROOT_PATH.'modules/';
            $f = @opendir($dir);
            if ($f) {
                while (false !== ($text = readdir($f))) {
                    if ($text != '.' && $text != '..' && $text != 'index' && $text != 'css' && $text != 'js' && is_dir($dir.$text)) {
                        if (is_file($dir.$text.'/controllers/init.php')) {
                            require_once $dir.$text.'/controllers/init.php';
                            $className = '\\'.ucfirst($text).'\Init\Controller';
                            if (method_exists($className, 'execute')) {
                                $className::execute($request, self::$menus, $login);
                            }
                        }
                    }
                }
                closedir($f);
            }
            // Controller หลัก
            $main = new \Index\Main\Controller();
            $bodyclass = 'mainpage';
        } else {
            // forgot, login, register
            $main = new \Index\Welcome\Controller();
            $bodyclass = 'loginpage';
        }
        $languages = array();
        $uri = $request->getUri();
        foreach (Language::installedLanguage() as $item) {
            $languages[$item] = '<li><a id=lang_'.$item.' href="'.$uri->withParams(array('lang' => $item), true).'" title="{LNG_Language} '.strtoupper($item).'" style="background-image:url('.WEB_URL.'language/'.$item.'.gif)" tabindex=1>&nbsp;</a></li>';
        }
        if ($bodyclass == 'loginpage' && is_file(ROOT_PATH.self::$cfg->skin.'/bg_image.jpg')) {
            $bg_image = WEB_URL.self::$cfg->skin.'/bg_image.jpg';
        } else {
            $bg_image = '';
        }
        if (is_file(ROOT_PATH.self::$cfg->skin.'/logo.png')) {
            $logo = '<img src="'.WEB_URL.self::$cfg->skin.'/logo.png" alt="{WEBTITLE}">';
        } else {
            $logo = '<span class="icon-office">{WEBTITLE}</span>';
        }
        // เนื้อหา
        self::$view->setContents(array(
            // main template
            '/{MAIN}/' => $main->execute($request),
            // โลโก
            '/{LOGO}/' => $logo,
            // language menu
            '/{LANGUAGES}/' => implode('', $languages),
            // title
            '/{TITLE}/' => $main->title(),
            // class สำหรับ body
            '/{BODYCLASS}/' => $bodyclass,
            // รูปภาพพื้นหลัง
            '/{BGIMAGE}/' => $bg_image,
        ));
        if ($login) {
            self::$view->setContents(array(
                // เมนู
                '/{MENUS}/' => self::$menus->render($main->menu(), $login),
                // แสดงชื่อคน Login
                '/{LOGINNAME}/' => empty($login['name']) ? $login['username'] : $login['name'],
            ));
        }
        // ส่งออก เป็น HTML
        $response = new Response();
        $response->withContent(self::$view->renderHTML())->send();
    }
}
