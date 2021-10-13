<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms\Utils;

use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\PhoneNumber;

class FormatPhone
{
    /**
     * 运行
     * @param string|array|PhoneNumberInterface $phone
     * @param string $delimiter
     * @return array
     */
    public static function run($phone, string $delimiter = ','): array
    {
        if ($phone instanceof PhoneNumberInterface) {
            return [
                $phone->getNumber() => $phone,
            ];
        }

        $res = [];

        if (is_string($phone) || is_int($phone)) {
            $phone = explode($delimiter, $phone);
        }

        foreach ($phone as $v) {
            $v = \trim($v);
            if (empty($v)) {
                continue;
            }
            $res[$v] = $v instanceof PhoneNumberInterface ? $v : new PhoneNumber($v);
        }

        return $res;
    }
}
