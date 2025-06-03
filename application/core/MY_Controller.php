<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    // ...kode yang sudah ada...

    /**
     * Helper untuk mengenkripsi data untuk URL
     * @param mixed $data Data yang akan dienkripsi
     * @return string Data terenkripsi dalam format hex
     */
    protected function encrypt_url($data) 
    {
        return bin2hex($this->encryption->encrypt($data));
    }

    /**
     * Helper untuk mendekripsi data dari URL
     * @param string $encrypted Data terenkripsi dalam format hex
     * @return mixed Data yang sudah didekripsi
     */
    protected function decrypt_url($encrypted) 
    {
        return $this->encryption->decrypt(hex2bin($encrypted));
    }

}