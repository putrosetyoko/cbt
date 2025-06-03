<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

function tampil_media($file, $width="", $height="") {
    $ret = '';

    // Pastikan file tidak kosong
    if (empty($file)) {
        return '';
    }

    $pc_file = explode(".", $file);
    $eks = end($pc_file);

    $eks_video = array("mp4","flv","mpeg","webm"); // Tambahkan webm jika digunakan
    $eks_audio = array("mp3","aac","wav","ogg"); // Tambahkan wav, ogg jika digunakan
    $eks_image = array("jpeg","jpg","gif","bmp","png");

    // Periksa keberadaan file di server
    $file_exists_on_server = is_file("./".$file); // Path relatif terhadap root CodeIgniter

    if (in_array(strtolower($eks), $eks_video)) {
        if ($file_exists_on_server) {
            // Tentukan tipe MIME yang lebih spesifik
            $mime_type = 'video/' . strtolower($eks);
            if (strtolower($eks) === 'flv') {
                $mime_type = 'video/x-flv'; // Perbaikan tipe MIME untuk FLV
            } elseif (strtolower($eks) === 'mpeg') {
                $mime_type = 'video/mpeg';
            }
            
            $ret .= '<p><video width="'.($width ? $width : '100%').'" height="'.($height ? $height : 'auto').'" controls>
                        <source src="'.base_url($file).'" type="'.$mime_type.'">
                        Browser tidak support video ini.</video></p>';
        }
    } 
    elseif (in_array(strtolower($eks), $eks_audio)) {
        if ($file_exists_on_server) {
            // Tentukan tipe MIME yang lebih spesifik
            $mime_type = 'audio/' . strtolower($eks);
            if (strtolower($eks) === 'acc') { // Jika acc adalah ekstensi Anda
                $mime_type = 'audio/aac';
            } elseif (strtolower($eks) === 'ogg') {
                $mime_type = 'audio/ogg';
            }
            
            $ret .= '<p><audio width="'.($width ? $width : '100%').'" height="'.($height ? $height : 'auto').'" controls>
                        <source src="'.base_url($file).'" type="'.$mime_type.'">
                        Browser tidak support audio ini.</audio></p>';
        }
    }
    elseif (in_array(strtolower($eks), $eks_image)) {
        if ($file_exists_on_server) {
            // Untuk gambar, kita bisa membuat lebih responsif dan opsional mengatur ukuran
            $style = '';
            if (!empty($width) && !empty($height)) {
                $style = 'width: '.$width.'; height: '.$height.'; object-fit: contain;'; // object-fit untuk menjaga rasio aspek
            } elseif (!empty($width)) {
                $style = 'width: '.$width.'; height: auto;';
            } elseif (!empty($height)) {
                $style = 'height: '.$height.'; width: auto;';
            } else {
                // Default responsive for images, especially for main questions
                $style = 'max-width: 400px; height: auto; display: block; margin: 0 auto;'; // Added to center and limit size
            }
            
            $ret .= '<img class="img-fluid" src="'.base_url($file).'" style="'.$style.'" alt="Media Soal">'; // Class img-fluid lebih baik dari thumbnail w-100
        }
    }
    // Jika tidak ada ekstensi yang cocok atau file tidak ditemukan, $ret akan tetap kosong
    
    return $ret;
}

if (!function_exists('encrypt_url')) {
    function encrypt_url($str) {
        $CI =& get_instance();
        return rawurlencode($CI->encryption->encrypt($str));
    }
}

if (!function_exists('decrypt_url')) {
    function decrypt_url($str) {
        $CI =& get_instance();
        return $CI->encryption->decrypt(rawurldecode($str));
    }
}

if (!function_exists('get_csrf')) {
    function get_csrf()
    {
        $ci =& get_instance();
        return '<input type="hidden" name="'.$ci->security->get_csrf_token_name().'" value="'.$ci->security->get_csrf_hash().'" />';
    }
}

