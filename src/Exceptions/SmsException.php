<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms\Exceptions;

use Exception;

class SmsException extends Exception
{
    protected $data;

    public function __construct(string $message = "", int $code = 0, $data = [])
    {
        $this->setData($data);
        parent::__construct($message, $code, null);
    }

    protected function setData($data)
    {
        $this->data = $data;
    }

    public function getData(string $key = '', $default = null)
    {
        if (empty($key)) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }
}
