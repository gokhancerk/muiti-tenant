<?php

/**
 * bind() : Transient / Geçici
 * singleton() : Tekil Sınıf
 * scoped() : İstek Kapsamlı Tekil
 * 
 * Bu üç metodun farkını görmek için bir E-Ticaret Sepet ve Loglama simülasyonu kuracağız.
 * 
 * 
 */
namespace App\Services;

// 2. Uygulama boyunca tek olacak servis (singleton)
class ApplicationLoggerService 
{
    public string $id;
    public function __construct() {
        $this->id = uniqid('log_');
    }


    function test() {
        return 'test';
    }
}