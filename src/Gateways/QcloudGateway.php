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

class QcloudGateway extends \Overtrue\EasySms\Gateways\QcloudGateway
{
    public const ENDPOINT_BATCH_METHOD = 'tlssmssvr/sendmultisms2';

    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        $phoneNumbers = [];
        foreach ($phoneContainer as $phone => $class) {
            $phoneNumbers[] = [
                'nationcode' => $class->getIDDCode() ?: 86,
                'mobile' => $class->getNumber(),
            ];
        }

        $data = $message->getData($this);

        $signName = !empty($data['sign_name']) ? $data['sign_name'] : $config->get('sign_name', '');

        unset($data['sign_name']);

        $msg = $message->getContent($this);
        if (!empty($msg) && '【' != mb_substr($msg, 0, 1) && !empty($signName)) {
            $msg = '【' . $signName . '】' . $msg;
        }

        $type = !empty($data['type']) ? $data['type'] : 0;
        $params = [
            'tel' => $phoneNumbers,
            'type' => $type,
            'msg' => $msg,
            'time' => time(),
            'extend' => '',
            'ext' => '',
        ];
        if (!is_null($message->getTemplate($this)) && is_array($data)) {
            unset($params['msg']);
            $params['params'] = array_values($data);
            $params['tpl_id'] = $message->getTemplate($this);
            $params['sign'] = $signName;
        }
        $random = substr(uniqid(), -10);

        $params['sig'] = $this->generateSign($params, $random);

        $url = sprintf(
            '%s%s?sdkappid=%s&random=%s',
            self::ENDPOINT_URL,
            count($phoneContainer) == 1 ? self::ENDPOINT_METHOD : self::ENDPOINT_BATCH_METHOD,
            $config->get('sdk_app_id'),
            $random
        );

        $result = $this->request('post', $url, [
            'headers' => ['Accept' => 'application/json'],
            'json' => $params,
        ]);

        if (0 != $result['result']) {
            throw new GatewayErrorException($result['errmsg'], $result['result'], $result);
        }

        return $result;
    }
}
