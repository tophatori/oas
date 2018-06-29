<?php
/**
 * @filesource Kotchasan/KBase.php
 *
 * @see http://www.kotchasan.com/
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 */

namespace Kotchasan;

use Kotchasan\Http\Request;

/**
 * Kotchasan base class.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class KBase
{
    /**
     * Config class.
     *
     * @var Config
     */
    protected static $cfg;
    /**
     * Server request class.
     *
     * @var Request
     */
    protected static $request;
}
