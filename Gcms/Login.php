<?php
/**
 * @filesource Gcms/Login.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Gcms;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * คลาสสำหรับตรวจสอบการ Login
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Login extends \Kotchasan\Login
{
    /**
     * ฟังก์ชั่นตรวจสอบการ login และบันทึกการเข้าระบบ
     * เข้าระบบสำเร็จคืนค่าแอเรย์ข้อมูลสมาชิก, ไม่สำเร็จ คืนค่าข้อความผิดพลาด.
     *
     * @param array $params ข้อมูลการ login ที่ส่งมา $params = array('username' => '', 'password' => '');
     *
     * @return string|array
     */
    public function checkLogin($params)
    {
        // ตรวจสอบสมาชิกกับฐานข้อมูล
        $login_result = self::checkMember($params);
        if (is_array($login_result)) {
            // ip ที่ login
            $ip = self::$request->getClientIp();
            // current session
            $session_id = session_id();
            // ลบ password
            unset($login_result['password']);
            // เวลานี้
            $mktime = time();
            if (self::$cfg->member_only || empty($login_result['token']) || $mktime - $login_result['lastvisited'] > 86400) {
                // อัพเดต token
                $login_result['token'] = sha1(uniqid().$login_result['id'].$session_id);
                $save = array('token' => $login_result['token']);
            }
            if ($session_id != $login_result['session_id']) {
                // อัพเดตการเยี่ยมชม
                ++$login_result['visited'];
                $save = array(
                    'session_id' => $session_id,
                    'visited' => $login_result['visited'],
                    'lastvisited' => $mktime,
                    'ip' => $ip,
                    'token' => $login_result['token'],
                );
            }
            if (!empty($save)) {
                // บันทึกการเข้าระบบ
                \Kotchasan\Model::createQuery()
                    ->update('user')
                    ->set($save)
                    ->where((int) $login_result['id'])
                    ->execute();
            }
        }

        return $login_result;
    }

    /**
     * ฟังก์ชั่นตรวจสอบสมาชิกกับฐานข้อมูล
     * คืนค่าข้อมูลสมาชิก (array) ไม่พบคืนค่าข้อความผิดพลาด (string).
     *
     * @param array $params
     *
     * @return array|string
     */
    public static function checkMember($params)
    {
        // query Where
        $where = array();
        foreach (self::$cfg->login_fields as $field) {
            $where[] = array("U.{$field}", $params['username']);
        }
        $query = \Kotchasan\Model::createQuery()
            ->select('U.*')
            ->from('user U')
            ->where($where, 'OR')
            ->order('U.status DESC')
            ->toArray();
        $login_result = null;
        foreach ($query->execute() as $item) {
            if (isset($params['password']) && $item['password'] == sha1(self::$cfg->password_key.$params['password'].$item['salt'])) {
                // ตรวจสอบรหัสผ่าน
                $login_result = $item;
            } elseif (isset($params['token']) && $params['token'] == $item['token']) {
                // ตรวจสอบ token
                $login_result = $item;
            }
            if ($login_result && ($login_result['status'] == 1 || $login_result['active'] == 1)) {
                // permission
                $login_result['permission'] = empty($login_result['permission']) ? array() : explode(',', trim($login_result['permission'], " \t\n\r\0\x0B,"));
                break;
            } else {
                $login_result = null;
            }
        }
        if ($login_result === null) {
            // user หรือ password ไม่ถูกต้อง
            self::$login_input = isset($item) ? 'password' : 'username';
            $login_result = isset($item) ? Language::replace('Invalid :name', array(':name' => Language::get('Password'))) : Language::get('not a registered user');
        }

        return $login_result;
    }

    /**
     * ตรวจสอบความสามารถในการตั้งค่า
     * แอดมินสูงสุด (status=1) ทำได้ทุกอย่าง
     * คืนค่าข้อมูลสมาชิก (แอเรย์) ถ้าไม่สามารถทำรายการได้คืนค่า null.
     *
     * @param array        $login
     * @param array|string $permission
     *
     * @return array|null
     */
    public static function checkPermission($login, $permission)
    {
        if (!empty($login)) {
            if ($login['status'] == 1) {
                // แอดมิน
                return $login;
            } elseif (!empty($permission)) {
                if (is_array($permission)) {
                    foreach ($permission as $item) {
                        if (in_array($item, $login['permission'])) {
                            // มีสิทธิ์
                            return $login;
                        }
                    }
                } elseif (in_array($permission, $login['permission'])) {
                    // มีสิทธิ์
                    return $login;
                }
            }
        }
        // ไม่มีสิทธิ

        return null;
    }

    /**
     * ฟังก์ชั่นส่งอีเมลลืมรหัสผ่าน
     *
     * @param Request $request
     *
     * @return void
     */
    public function forgot(Request $request)
    {
        // ค่าที่ส่งมา
        $username = $request->post('login_username')->url();
        if (empty($username)) {
            if ($request->post('action')->toString() === 'forgot') {
                self::$login_message = Language::get('Please fill in');
            }
        } else {
            self::$login_params['username'] = $username;
            // ชื่อฟิลด์สำหรับตรวจสอบอีเมล ใช้ฟิลด์แรกจาก config
            $field = reset(self::$cfg->login_fields);
            // Model
            $model = new \Kotchasan\Model();
            // Database
            $db = $model->db();
            // ค้นหาอีเมล
            $search = $db->first($model->getTableName('user'), array(
                array($field, $username),
                array('social', 0),
            ));
            if ($search === false) {
                self::$login_message = Language::get('not a registered user');
            } else {
                // ขอรหัสผ่านใหม่
                $err = \Index\Forgot\Model::execute($search->id, $search->$field);
                if ($err == '') {
                    // คืนค่า
                    self::$login_message = Language::get('Your message was sent successfully');
                    self::$request = $request->withQueryParams(array('action' => 'login'));
                } else {
                    // ไม่สำเร็จ
                    self::$login_message = $err;
                }
            }
        }
    }

    /**
     * ฟังก์ชั่นตรวจสอบว่า เป็นสมาชิกตัวอย่างหรือไม่
     * คืนค่าข้อมูลสมาชิก (แอเรย์) ถ้าไม่ใช่สมาชิกตัวอย่าง, null ถ้าเป็นสมาชิกตัวอย่างและเปิดโหมดตัวอย่างไว้.
     *
     * @param array|null $login
     *
     * @return array|null
     */
    public static function notDemoMode($login)
    {
        return $login && !empty($login['social']) && self::$cfg->demo_mode ? null : $login;
    }
}
