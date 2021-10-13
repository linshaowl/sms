<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms;

class ErrorCode
{
    public const INVALID_PHONE_FORMAT = 401;
    public const MESSAGE_MUST = 402;
    public const WAIT_INTERVAL = 403;
    public const INVALID_CODE = 411;
    public const CODE_NOT_CORRECT = 412;
    public const SEND_FREQUENTLY = 413;
}
