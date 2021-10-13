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

class YunpianGateway extends \Overtrue\EasySms\Gateways\YunpianGateway
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

        $endpoint = $this->buildEndpoint('sms', 'sms', count($phoneContainer) == 1 ? 'single_send' : 'batch_send');

        $signature = $config->get('signature', '');

        $content = $message->getContent($this);

        $result = $this->request('post', $endpoint, [
            'form_params' => [
                'apikey' => $config->get('api_key'),
                'mobile' => $phoneNumbers,
                'text' => 0 === \stripos($content, '【') ? $content : $signature . $content,
            ],
            'exceptions' => false,
        ]);

        if ($result['code']) {
            throw new GatewayErrorException($result['msg'], $result['code'], $result);
        }

        return $result;
    }
}
