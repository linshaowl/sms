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

class LuosimaoGateway extends \Overtrue\EasySms\Gateways\LuosimaoGateway
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        $phoneNumbers = [];
        foreach ($phoneContainer as $phone => $class) {
            $phoneNumbers[] = $class->getNumber();
        }
        $phoneNumbers = implode(',', $phoneNumbers);

        $endpoint = $this->buildEndpoint('sms-api', count($phoneContainer) == 1 ? 'send' : 'send_batch');

        $result = $this->post(
            $endpoint,
            [
                'mobile' => $phoneNumbers,
                'message' => $message->getContent($this),
            ],
            [
                'Authorization' => 'Basic ' . base64_encode('api:key-' . $config->get('api_key')),
            ]
        );

        if ($result['error']) {
            throw new GatewayErrorException($result['msg'], $result['error'], $result);
        }

        return $result;
    }
}
