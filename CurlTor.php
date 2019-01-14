<?php
    /**
     * Curl with TOR support
     * User: n.zarubin
     * Date: 14.01.2019
     * Time: 16:48
     */

    namespace nikserg\yii2\CurlTor;

    use linslin\yii2\curl;

    class CurlTor extends curl\Curl
    {

        private $host;
        private $port;
        private $socksType;
        private $isTorEnabled;
        private $authCode;
        private $lastTorIp;

        /**
         * CurlTor constructor.
         *
         * @param string $host Host of TOR client
         * @param int    $port Port of TOR client
         * @param null   $authCode TOR auth code
         * @param int    $socksType
         */
        public function __construct($host = 'localhost', $port = 9050, $authCode = null, $socksType = CURLPROXY_SOCKS5)
        {
            $this->host = $host;
            $this->port = $port;
            $this->socksType = $socksType;
            $this->authCode = $authCode;
        }

        public function getIsTorEnabled()
        {
            return $this->isTorEnabled;
        }

        /**
         * Enable TOR
         *
         *
         */
        public function enableTor()
        {
            if (!$this->isTorEnabled) {
                $this->isTorEnabled = true;
                $this->setOption(CURLOPT_PROXYTYPE, $this->socksType);
                $this->setOption(CURLOPT_PROXY, $this->host . ":" . $this->port);
            }
        }

        /**
         * Disable TOR
         *
         *
         */
        public function disableTor()
        {
            if ($this->isTorEnabled) {
                $this->isTorEnabled = false;
                $this->setOption(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                $this->setOption(CURLOPT_PROXY, null);
            }
        }

        /**
         * Change TOR IP address
         *
         *
         * @return bool
         */
        protected function newIdentity()
        {
            $fp = fsockopen($this->host, $this->port, $errno, $errstr, 30);
            if (!$fp) {
                return false;
            } //can't connect to the control port

            fputs($fp, "AUTHENTICATE \"" . $this->authCode . "\"\r\n");
            $response = fread($fp, 1024);
            list($code, $text) = explode(' ', $response, 2);
            if ($code != '250') {
                return false;
            } //authentication failed

            //send the request to for new identity
            fputs($fp, "signal NEWNYM\r\n");
            $response = fread($fp, 1024);
            list($code, $text) = explode(' ', $response, 2);
            if ($code != '250') {
                return false;
            } //signal failed

            fclose($fp);
            return true;
        }

        /**
         * My current IP address
         *
         *
         * @return string
         */
        public function getMyIp()
        {
            $ip = $this->get('http://ipinfo.io/ip');
            if ($this->lastTorIp === null || $this->lastTorIp != $ip) {
                $this->lastTorIp = $ip;
            }
            return $ip;
        }
    }