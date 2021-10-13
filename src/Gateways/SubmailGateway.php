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

class SubmailGateway extends \Overtrue\EasySms\Gateways\SubmailGateway
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        $data = $message->getData($this);

        $to = current($phoneContainer);
        $phoneNumbers = [];
        foreach ($phoneContainer as $phone => $class) {
            $phoneNumbers[] = [
                'to' => $class->getUniversalNumber(),
                'vars' => $data,
            ];
        }
        $phoneNumbers = json_encode($phoneNumbers, JSON_FORCE_OBJECT);

        $endpoint = $this->buildEndpoint(
            $this->inChineseMainland($to) ? 'message/multixsend' : 'internationalsms/multixsend'
        );

        $result = $this->post($endpoint, [
            'appid' => $config->get('app_id'),
            'signature' => $config->get('app_key'),
            'project' => !empty($data['project']) ? $data['project'] : $config->get('project'),
            'multi' => $phoneNumbers,
        ]);

        if ('success' != $result['status']) {
            throw new GatewayErrorException($result['msg'], $result['code'], $result);
        }

        return $result;
    }
}
