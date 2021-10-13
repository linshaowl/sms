<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Support\Config;

class AvatardataGateway extends \Overtrue\EasySms\Gateways\AvatardataGateway
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        return parent::send(current($phoneContainer), $message, $config);
    }
}
