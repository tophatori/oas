<?php
/**
 * @filesource modules/index/models/id.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Index\Id;

/**
 * module=category.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * คืนค่าข้อมูล id ล่าสุด.
     *
     * @param int    $id         ID สำหรับตรวจสอบข้อมูลซ้ำ
     * @param string $name       ชื่อฟิลด์ที่ต้องการ
     * @param string $table_name ชื่อตาราง สำหรับตรวจสอบข้อมูลซ้ำ
     * @param string $field      ชื่อฟิลด์ สำหรับตรวจสอบข้อมูลซ้ำ
     *
     * @return string
     */
    public static function get($id, $name, $table_name, $field)
    {
        if (isset(self::$cfg->$name)) {
            $model = new static();
            $table_number = $model->getTableName('number');
            $number = $model->db()->first($table_number, 1);
            if ($number) {
                if (!isset($number->$name)) {
                    echo 'ไม่มีชื่อฟิลด์ที่ต้องการ';

                    return null;
                }
                $next_id = 1 + (int) $number->$name;
            } else {
                $next_id = 1;
            }
            // ตรวจสอบ order_no ซ้ำ
            while (true) {
                $result = sprintf(self::$cfg->$name, $next_id);
                $search = $model->db()->first($table_name, array(
                    array($field, $result),
                ));
                if (!$search || ($id > 0 && $search->id == $id)) {
                    break;
                } else {
                    ++$next_id;
                }
            }
            // อัพเดต running number
            if ($number) {
                $model->db()->update($table_number, $number->id, array($name => $next_id));
            } else {
                $model->db()->insert($table_number, array('id' => 1, $name => $next_id));
            }
            // คืนค่า

            return $result;
        } else {
            echo 'ไม่ได้กำหนดค่า $cfg->'.$name;
        }

        return null;
    }
}
