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

class SendcloudGateway extends \Overtrue\EasySms\Gateways\SendcloudGateway
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        $to = current($phoneContainer);
        $phoneNumbers = [];
        foreach ($phoneContainer as $phone => $class) {
            $phoneNumbers[] = $class->getZeroPrefixedNumber();
        }
        $phoneNumbers = implode(',', $phoneNumbers);

        $params = [
            'smsUser' => $config->get('sms_user'),
            'templateId' => $message->getTemplate($this),
            'msgType' => $to->getIDDCode() != 86 ? 2 : 0,
            'phone' => $phoneNumbers,
            'vars' => $this->formatTemplateVars($message->getData($this)),
        ];

        if ($config->get('timestamp', false)) {
            $params['timestamp'] = time() * 1000;
        }

        $params['signature'] = $this->sign($params, $config->get('sms_key'));

        $result = $this->post(sprintf(self::ENDPOINT_TEMPLATE, 'send'), $params);

        if (!$result['result']) {
            throw new GatewayErrorException($result['message'], $result['statusCode'], $result);
        }

        return $result;
    }
}
