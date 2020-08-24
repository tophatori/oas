<?php

/* config.php */

return array(
    'version' => '3.0.0',
    'web_title' => 'OAS',
    'web_description' => 'Online Accounting System',
    'timezone' => 'Asia/Bangkok',
    'product_no' => 'P%05d',
    'billing_no' => 'inv%05d',
    'order_no' => 'ord%05d',
    'currency_unit' => 'THB',
    'member_status' => array(
        0 => 'พนักงาน',
        1 => 'ผู้ดูแลระบบ',
    ),
    'default_icon' => 'icon-billing',
    'inventory_w' => 500,
    'inventory_h' => 500,
);
