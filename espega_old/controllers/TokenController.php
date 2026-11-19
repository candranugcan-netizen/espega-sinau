<?php
class TokenController {
    public static function generateToken() {
        $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($char), 0, 6);
    }
}
?>