<?php
namespace App\Services;
// 3. İstek boyunca tek olacak servis (scoped)
class CheckoutSessionService 
{
    public string $id;
    public function __construct() {
        $this->id = uniqid('session_');
    }
}