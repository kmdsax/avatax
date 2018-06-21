<?php
/**
 * OnePica_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   OnePica
 * @package    OnePica_AvaTax
 * @author     OnePica Codemaster <codemaster@onepica.com>
 * @copyright  Copyright (c) 2015 One Pica, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Avalara\AvaTaxEcomSDK;

/**
 * Class Client
 *
 * @package Avalara\AvaTaxEcomSDK
 */
class Client extends ClientBase
{
    /**
     * Generate bearer token by given credentials
     * @return mixed|string
     */
    public function getToken()
    {
        $path = "/v2/auth/get-token";
        $params = [
            'query' => [],
            'body'  => null
        ];

        return $this->restCall($path, 'POST', $params);
    }
}
