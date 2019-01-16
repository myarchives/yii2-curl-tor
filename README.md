# yii2-curl-tor
Support of TOR proxy in Curl for Yii2.

## Install:

`composer require nikserg/yii2-curl-tor`

## Usage:

`$curl = new CurlTor('localhost', 9050, 9051, 'OInf80v0cc83lascm9Jf');`

Where

`'localhost'` - host of TOR instance

`9050` - client port

`9051` - control port

`'OInf80v0cc83lascm9Jf'` - auth token

## About control port and auth token

This stuff is needed to change TOR identity on fly. How to setup:

1. On `/etc/torrc` uncomment:

`#ControlPort 9051`

`#HashedControlPassword 16:872860B76453A77D60CA2BB8C1A7042072093276A3D701AD684053EC4C`

2. Generate password with command `tor --hash-password mypass`, where `mypass` is desired password

3. Insert result into line `#HashedControlPassword`

4. Restart TOR using `sudo service tor restart`

You're good to go. Now you can use 

`$curl->newIdentity();`

And you'll get new IP in a few seconds.