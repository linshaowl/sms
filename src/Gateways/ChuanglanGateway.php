<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;

class ChuanglanGateway extends \Overtrue\EasySms\Gateways\ChuanglanGateway
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        $to = current($phoneContainer);
        $phoneNumbers = implode(',', array_keys($phoneContainer));

        $IDDCode = !empty($to->getIDDCode()) ? $to->getIDDCode() : 86;

        $params = [
            'account' => $config->get('account'),
            'password' => $config->get('password'),
            'phone' => $phoneNumbers,
            'msg' => $this->wrapChannelContent($message->getContent($this), $config, $IDDCode),
        ];

        if (86 != $IDDCode) {
            $params['mobile'] = $to->getIDDCode() . $to->getNumber();
            $params['account'] = $config->get('intel_account') ?: $config->get('account');
            $params['password'] = $config->get('intel_password') ?: $config->get('password');
        }

        $result = $this->postJson($this->buildEndpoint($config, $IDDCode), $params);

        if (!isset($result['code']) || '0' != $result['code']) {
            throw new GatewayErrorException(
                json_encode($result, JSON_UNESCAPED_UNICODE),
                isset($result['code']) ? $result['code'] : 0,
                $result
            );
        }

        return $result;
    }
}
