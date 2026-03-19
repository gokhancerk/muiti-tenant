<?php

namespace App\Services;

// Bu sınıflar rastgele bir kimlik (ID) üreterek RAM'de ne zaman yeniden oluşturulduklarını kanıtlayacak.

// 1. Her çağrıldığında yeni üretilecek servis (bind)
class TaxCalculatorService {

    public string $id;
    public function __construct()
    {
        $this->id = uniqid('tax_');
    }

}