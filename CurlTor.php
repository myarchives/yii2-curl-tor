<?php
    /**
     * Curl with TOR support
     * User: n.zarubin
     * Date: 14.01.2019
     * Time: 16:48
     */

    namespace nikserg\yii2\CurlTor;

    use linslin\yii2\curl\Curl;

    class CurlTor extends Curl
    {

        private $host;
        private $port;
        private $socksType;
        private $isTorEnabled;
        private $authCode;
        private $controlPort;

        /**
         * Print log while getting new identity
         *
         *
         * @var bool
         */
        public $verbose = false;

        /**
         * CurlTor constructor.
         *
         * @param string $host Host of TOR client
         * @param int    $port Port of TOR client
         * @param int    $controlPort Port of TOR client controls
         * @param null   $authCode TOR auth code
         * @param int    $socksType
         */
        public function __construct(
            $host = 'localhost',
            $port = 9050,
            $controlPort = 9051,
            $authCode = null,
            $socksType = CURLPROXY_SOCKS5
        ) {
            $this->host = $host;
            $this->port = $port;
            $this->socksType = $socksType;
            $this->authCode = $authCode;
            $this->controlPort = $controlPort;
        }

        /**
         * Check if TOR is currently available
         *
         *
         * @return bool
         */
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
         * @return bool Was change successful
         */
        public function newIdentity()
        {
            //Cannot change IP with disabled TOR
            if (!$this->isTorEnabled) {
                if ($this->verbose) {
                    echo "Cannot change IP with disabled TOR\n";
                }
                return false;
            }
            //Old IP address
            $oldIp = $this->getMyIp();
            if ($this->verbose) {
                echo "Old TOR IP: $oldIp \n";
            }
            $fp = fsockopen($this->host, $this->controlPort, $errno, $errstr, 30);
            if (!$fp) {
                return false;
            } //can't connect to the control port

            fputs($fp, "AUTHENTICATE \"" . $this->authCode . "\"\r\n");
            $response = fread($fp, 1024);
            $explodedResponse = explode(' ', $response, 2);
            if (count($explodedResponse) < 2) {
                if ($this->verbose) {
                    echo "Unexpected TOR response on AUTHENTICATE " . $this->authCode . ' command: ' . $response . " host: " . $this->host . ", port: " . $this->controlPort . ". Make sure control port and auth token are right. Usually control port is 9051\n";
                    return false;
                }
            }
            list($code, $text) = $explodedResponse;
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

            while (($newIp = $this->getMyIp()) == $oldIp) {
                if ($this->verbose) {
                    echo "Wait for TOR IP to change...\n";
                }
                sleep(1);
            }
            if ($this->verbose) {
                echo "New TOR IP: $newIp \n";
            }
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
            return $this->get('http://ipinfo.io/ip');
        }
    }