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

class YuntongxunGateway extends \Overtrue\EasySms\Gateways\YuntongxunGateway
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        $phoneNumbers = [];
        foreach ($phoneContainer as $phone => $class) {
            $phoneNumbers[] = $class->getUniversalNumber();
        }
        $phoneNumbers = implode(',', $phoneNumbers);

        $datetime = date('YmdHis');

        $endpoint = $this->buildEndpoint('SMS', 'TemplateSMS', $datetime, $config);

        $result = $this->request('post', $endpoint, [
            'json' => [
                'to' => $phoneNumbers,
                'templateId' => (int)($this->config->get('debug') ? self::DEBUG_TEMPLATE_ID : $message->getTemplate(
                    $this
                )),
                'appId' => $config->get('app_id'),
                'datas' => $message->getData($this),
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8',
                'Authorization' => base64_encode($config->get('account_sid') . ':' . $datetime),
            ],
        ]);

        if (self::SUCCESS_CODE != $result['statusCode']) {
            throw new GatewayErrorException($result['statusCode'], $result['statusCode'], $result);
        }

        return $result;
    }
}
