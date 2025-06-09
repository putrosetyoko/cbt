<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

function tampil_media($file, $width="", $height="") {
    $ret = '';

    if (empty($file)) {
        return '';
    }

    $pc_file = explode(".", $file);
    $eks = end($pc_file);

    $eks_video = array("mp4","flv","mpeg","webm");
    $eks_audio = array("mp3","aac","wav","ogg");
    $eks_image = array("jpeg","jpg","gif","bmp","png");

    $full_server_path = FCPATH . $file; 
    $file_exists_on_server = is_file($full_server_path); 

    // --- TAMBAH BARIS LOGGING INI ---
    log_message('debug', 'tampil_media DEBUG: File path from DB: "' . $file . '"');
    log_message('debug', 'tampil_media DEBUG: Constructed Full Server Path: "' . $full_server_path . '"');
    log_message('debug', 'tampil_media DEBUG: Does file exist on server? ' . ($file_exists_on_server ? 'YES' : 'NO'));
    // --- AKHIR BARIS LOGGING ---

    if (in_array(strtolower($eks), $eks_video)) {
        if ($file_exists_on_server) {
            $mime_type = 'video/' . strtolower($eks);
            if (strtolower($eks) === 'flv') {
                $mime_type = 'video/x-flv';
            } elseif (strtolower($eks) === 'mpeg') {
                $mime_type = 'video/mpeg';
            }
            
            $ret .= '<p><video width="'.($width ? $width : '100%').'" height="'.($height ? $height : 'auto').'" controls>
                        <source src="'.base_url($file).'" type="'.$mime_type.'">
                        Browser tidak support video ini.</video></p>';
        } else {
            $ret .= '<p class="text-danger">DEBUG: Video tidak ditemukan di server: ' . htmlspecialchars($full_server_path) . '</p>';
        }
    } 
    elseif (in_array(strtolower($eks), $eks_audio)) {
        if ($file_exists_on_server) {
            $mime_type = 'audio/' . strtolower($eks);
            if (strtolower($eks) === 'acc') {
                $mime_type = 'audio/aac';
            } elseif (strtolower($eks) === 'ogg') {
                $mime_type = 'audio/ogg';
            }
            
            $ret .= '<p><audio width="'.($width ? $width : '100%').'" height="'.($height ? $height : 'auto').'" controls>
                        <source src="'.base_url($file).'" type="'.$mime_type.'">
                        Browser tidak support audio ini.</audio></p>';
        } else {
            $ret .= '<p class="text-danger">DEBUG: Audio tidak ditemukan di server: ' . htmlspecialchars($full_server_path) . '</p>';
        }
    }
    elseif (in_array(strtolower($eks), $eks_image)) {
        if ($file_exists_on_server) {
            $style = '';
            if (!empty($width) && !empty($height)) {
                $style = 'width: '.$width.'; height: '.$height.'; object-fit: contain;';
            } elseif (!empty($width)) {
                $style = 'width: '.$width.'; height: auto;';
            } elseif (!empty($height)) {
                $style = 'height: '.$height.'; width: auto;';
            } else {
                $style = 'max-width: 400px; height: auto; display: block; margin: 0 auto;';
            }
            
            $ret .= '<img class="img-fluid" src="'.base_url($file).'" style="'.$style.'" alt="Media Soal" data-zoomable>'; 
        } else {
            // Ini akan muncul di halaman jika gambar tidak ditemukan
            $ret .= '<p class="text-danger">DEBUG: Gambar soal utama tidak ditemukan di server: ' . htmlspecialchars($full_server_path) . '</p>';
        }
    }
    
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