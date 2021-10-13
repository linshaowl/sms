<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms\Gateways;

use GuzzleHttp\Exception\RequestException;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Support\Config;

class HuaweiGateway extends \Overtrue\EasySms\Gateways\HuaweiGateway
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

        $appKey = $config->get('app_key');
        $appSecret = $config->get('app_secret');
        $channels = $config->get('from');
        $statusCallback = $config->get('callback', '');

        $endpoint = $this->getEndpoint($config);
        $headers = $this->getHeaders($appKey, $appSecret);

        $templateId = $message->getTemplate($this);
        $messageData = $message->getData($this);

        // 短信签名通道号码
        $from = 'default';
        if (isset($messageData['from'])) {
            $from = $messageData['from'];
            unset($messageData['from']);
        }
        $channel = isset($channels[$from]) ? $channels[$from] : '';

        if (empty($channel)) {
            throw new InvalidArgumentException("From Channel [{$from}] Not Exist");
        }

        $params = [
            'from' => $channel,
            'to' => $phoneNumbers,
            'templateId' => $templateId,
            'templateParas' => json_encode($messageData),
            'statusCallback' => $statusCallback,
        ];

        try {
            $result = $this->request('post', $endpoint, [
                'headers' => $headers,
                'form_params' => $params,
                //为防止因HTTPS证书认证失败造成API调用失败，需要先忽略证书信任问题
                'verify' => false,
            ]);
        } catch (RequestException $e) {
            $result = $this->unwrapResponse($e->getResponse());
        }

        if (self::SUCCESS_CODE != $result['code']) {
            throw new GatewayErrorException($result['description'], ltrim($result['code'], 'E'), $result);
        }

        return $result;
    }
}
