<?php
namespace Sonic;

/**
 * extremely simple Google Authentication class
 *
 * @category Sonic
 * @package GoogleAuth
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class GoogleAuth
{
    /**
     * constants
     */
    const XRD_URI = 'https://www.google.com/accounts/o8/ud';
    const CLAIMED_ID = 'http://specs.openid.net/auth/2.0/identifier_select';
    const OPENID_NS = 'http://specs.openid.net/auth/2.0';
    const OPENID_NS_EXT1 = 'http://openid.net/srv/ax/1.0';
    const OPENID_EXT1_MODE = 'fetch_request';
    const OPENID_EXT1_TYPE_EMAIL = 'http://schema.openid.net/contact/email';
    const OPENID_EXT1_TYPE_FIRSTNAME = 'http://axschema.org/namePerson/first';
    const OPENID_EXT1_TYPE_LASTNAME = 'http://axschema.org/namePerson/last';
    const OPENID_EXT1_REQUIRED = 'email,firstname,lastname';

    /**
     * cached curl response
     *
     * @var string
     */
    protected static $_response;

    /**
     * gets the url to link to Google Authentication
     *
     * @param string $return_to url that Google will redirect back to once authentication is complete
     *                          this is the url where you will validate the signature
     * @return string
     */
    public function getUrl($return_to)
    {
        $params = array(
            'openid.ns' => self::OPENID_NS,
            'openid.claimed_id' => self::CLAIMED_ID,
            'openid.identity' => self::CLAIMED_ID,
            'openid.return_to' => $return_to,
            'openid.realm' => null,
            'openid.assoc_handle' => $this->_getParam('assoc_handle'),
            'openid.mode' => 'checkid_setup',
            'openid.ns.ext1' => self::OPENID_NS_EXT1,
            'openid.ext1.mode' => self::OPENID_EXT1_MODE,
            'openid.ext1.type.email' => self::OPENID_EXT1_TYPE_EMAIL,
            'openid.ext1.type.firstname' => self::OPENID_EXT1_TYPE_FIRSTNAME,
            'openid.ext1.type.lastname' => self::OPENID_EXT1_TYPE_LASTNAME,
            'openid.ext1.required' => self::OPENID_EXT1_REQUIRED
        );

        return self::XRD_URI . '?' . http_build_query($params);
    }

    /**
     * makes sure that Curl extension is installed and loaded
     *
     * @return void
     */
    protected function _requireCurl()
    {
        if (!class_exists('Sonic\Curl')) {
            throw new Exception('GoogleAuth class requires the Sonic Curl extension');
        }
    }

    /**
     * allows you to get a parameter related to the open id call
     *
     * @param string $param
     * @return string
     */
    protected function _getParam($param)
    {
        $this->_requireCurl();

        if (self::$_response === null) {
            $curl = new CurlCached(self::XRD_URI, __METHOD__, '2 hours');
            $params = array(
                'openid.ns' => self::OPENID_NS,
                'openid.mode' => 'associate',
                'openid.assoc_type' => 'HMAC-SHA1',
                'openid.session_type' => 'no-encryption'
            );
            $curl->addParams($params);

            self::$_response = $curl->getResponse();
        }

        preg_match('/' . $param . '\:(.*)/', self::$_response, $matches);

        if (count($matches) == 0) {
            return null;
        }

        return $matches[1];
    }

    /**
     * generates a signature from the params to check against the openid_sig
     *
     * @param string $openid_signed string returned from google of params used for signature
     * @return string
     */
    public function generateSignature($openid_signed)
    {
        $params = explode(',', $openid_signed);

        $new_params = array();
        foreach ($params as $param) {
            $key = 'openid_' . str_replace('.', '_', $param);
            $value = isset($_GET[$key]) ? $_GET[$key] : null;
            $new_params[$param] = $value;
        }

        if (isset($new_params['invalidate_handle'])) {
            // remove it?
        }

        if ($this->_getParam('assoc_handle') !== $new_params['assoc_handle']) {
            return null;
        }

        $string = '';
        foreach ($new_params as $key => $value) {
            $string .= "$key:$value\n";
        }

        $key = base64_decode($this->_getParam('mac_key'));
        $sig = base64_encode(hash_hmac('sha1', $string, $key, true));

        return $sig;
    }
}
