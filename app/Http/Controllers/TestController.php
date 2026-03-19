<?php

namespace App\Http\Controllers;

use App\Services\ApplicationLoggerService as ServicesApplicationLoggerService;
use App\Services\CheckoutSessionService as ServicesCheckoutSessionService;
use App\Services\TaxCalculatorService;
use ApplicationLoggerService;
use CheckoutSessionService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function __construct(private TaxCalculatorService $taxService1, private ServicesApplicationLoggerService $loggerService, private ServicesCheckoutSessionService $sessionService)
    {
    }

    public function testDependencies() {
        // servisleri container'dan ikinci kez manuel talep ediyoruz
        $taxService2 = app(TaxCalculatorService::class);
        $loggerService2 = app(ServicesApplicationLoggerService::class);
        $sessionService2 = app(ServicesCheckoutSessionService::class);
       
        return response()->json([
            'bind_test_tax' => [
                'instance_1_id' => $this->taxService1->id,
                'instance_2_id' => $taxService2->id,
                'is_same_ram_object' => spl_object_id($this->taxService1) === spl_object_id($taxService2) // FALSE döner
            ],
            'singleton_test_logger' => [
                'instance_1_id' => $this->loggerService->id,
                'instance_2_id' => $loggerService2->id,
                'is_same_ram_object' => spl_object_id($this->loggerService) === spl_object_id($loggerService2) // TRUE döner
            ],
            'scoped_test_session' => [
                'instance_1_id' => $this->sessionService->id,
                'instance_2_id' => $sessionService2->id,
                'is_same_ram_object' => spl_object_id($this->sessionService) === spl_object_id($sessionService2) // TRUE döner (FPM ortamında)
            ]
        ]);
    
    
        }

}
