<?php
namespace xapi\core\v100;

require_once 'x-api/v1.0/CommonUtil.php';

class MySQLAES{
    protected $method;
    protected $key;
    /**
     * Crypter constructor.
     *
     * @param $seed
     * @param string $method default AES-128-ECB
     */
    public function __construct($seed, $method = 'AES-128-ECB')
    {
        $this->method = $method;
        $this->key = $this->generateKey($seed);
    }
    /**
     * Encrypts the data.
     *
     * @since  2.0
     *
     * @param string $data A string of data to encrypt.
     *
     * @return string (binary) The encrypted data
     */
    public function encrypt($data)
    {
        $chiperIvLength = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($chiperIvLength);
        $padValue = 16 - (strlen($data) % 16);
        $result = openssl_encrypt(
            str_pad($data, intval(16 * (floor(strlen($data) / 16) + 1)), chr($padValue)),
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
            );
        
        return CommonUtil::StringToHex($result);
    }
    /**
     * Decrypts the data.
     *
     * @since  2.0
     *
     * @param string $data A (binary) string of encrypted data
     *
     * @return string Decrypted data
     */
    public function decrypt($data)
    {
        $data = CommonUtil::HexToString($data);
        $data = openssl_decrypt(
            $data,
            $this->method,
            $this->key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
            );
        return rtrim($data, "\x00..\x10");
    }
    /**
     * Create and set the key used for encryption.
     *
     * @since  2.0
     *
     * @param string $seed The seed used to create the key.
     *
     * @return string (binary) the key to use in the encryption process.
     */
    protected function generateKey($seed)
    {
        $key = str_repeat(chr(0), 16);
        for ($i = 0, $len = strlen($seed); $i < $len; $i++) {
            $key[$i % 16] = $key[$i % 16] ^ $seed[$i];
        }
        return $key;
    }
}

?>