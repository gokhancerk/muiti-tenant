
---

## [TR] Vaka Çalışması: B2B SaaS Sistemlerinde Ölçeklenebilir Çoklu Kiracı (Multi-Tenant) Mimarisi

**Proje Özeti:** Bu proje, tek bir ilişkisel veritabanı altyapısı (Single Database, Multi-Schema) üzerinden birden fazla müşteriye (Tenant) sıfır veri sızıntısı (Zero Data Leak) garantisiyle hizmet sunan bir SaaS backend mimarisi prototipidir.

### Problem Tanımı ve Sistem Kısıtlamaları
Büyüme evresindeki B2B SaaS platformlarının karşılaştığı en büyük teknik darboğaz, müşteri verilerinin birbirine karışmasıdır. İzolasyonun Controller seviyesinde manuel `where('tenant_id', $id)` sorgularıyla yapılması, insan hatasına açık (error-prone) bir yapıdır ve katastrofik güvenlik ihlallerine zemin hazırlar. Ayrıca, uzun süre çalışan (long-running) sunucu mimarilerinde (örn: Laravel Octane), statik veya singleton nesnelerin kullanımı "State Pollution" (Veri Kirliliği) yaratır.

### Mimari Çözüm ve Birinci Prensipler
Bu proje, insan hatasını sistem tasarımı ile geçersiz kılmak (override) amacıyla üç katmanlı bir savunma hattı inşa etmiştir:

* **Fail-Fast (Erken Çıkış) ve Kesin Tespiti:** Kiracı tespiti `X-Tenant-ID` HTTP başlığı üzerinden yapılır. Uygulama, isteği iş mantığına ulaştırmadan önce özel bir Middleware katmanında keser. Geçersiz veya eksik başlıklar anında 400 Bad Request ile reddedilerek gereksiz I/O ve işlemci döngüsü (CPU cycle) israfı önlenir.
* **Bellek İzolasyonu ve RAM Optimizasyonu:** Tespit edilen kiracı durumu (state), Laravel IoC (Service Container) üzerinde `scoped` (istek bazlı tekil) nesne olarak örneklendirilir. Bu karar, uygulamanın geleneksel PHP-FPM dışında Swoole/RoadRunner gibi yüksek RPS'li ortamlarda çalıştırılması durumunda yaşanacak bellek sızıntılarını (memory leaks) mimari seviyede engeller.
* **Otonom Veri İzolasyonu (Defensive Programming):** Controller katmanı, çoklu kiracı yapısından tamamen habersiz (ignorant) bırakılmıştır. Veritabanı sorgularına kiracı filtresi enjekte etme işlemi, Eloquent ORM motoruna entegre edilen "Global Scopes" aracılığıyla otonom olarak gerçekleştirilir. 

### İş Değeri ve Kaldıraç (Business Impact)
* **Risk Yönetimi:** Çapraz kiracı veri sızıntısı (Cross-tenant data leak) riski matematiksel olarak sıfıra indirilmiştir.
* **Operasyonel Çeviklik:** İş mantığı nesneleri (Services) ile veri taşıma nesneleri (DTOs) arasındaki sıkı bağlılık (tight coupling), Dependency Injection ile çözülmüştür. Sisteme yeni bir müşteri ekleme (onboarding) maliyeti minimize edilmiştir.

---

## [EN] Case Study: Scalable Multi-Tenant Architecture for B2B SaaS Systems

**Project Overview:** This project is a foundational SaaS backend prototype that provides zero data leak isolation for multiple clients (Tenants) utilizing a Single Database, Multi-Schema relational infrastructure.

### Problem Statement and System Constraints
The most critical bottleneck for scaling B2B SaaS platforms is the risk of cross-tenant data contamination. Relying on manual `where('tenant_id', $id)` clauses at the Controller level introduces human error and creates severe security vulnerabilities. Furthermore, in long-running server architectures (e.g., Laravel Octane), improper state management via static variables or singletons inevitably leads to catastrophic State Pollution.

### Architectural Solution and First Principles
This project implements a three-layered defense mechanism to systematically override human error:

* **Fail-Fast Identification:** Tenant identification is strictly enforced via the `X-Tenant-ID` HTTP header. A dedicated Middleware intercepts the request before it hits the application router. Invalid or missing headers trigger an immediate 400 Bad Request, effectively preventing unnecessary I/O and CPU cycle waste.
* **Memory Isolation and RAM Optimization:** The resolved tenant state is bound to the Laravel IoC (Service Container) as a `scoped` instance. This specific architectural decision guarantees that the application is natively ready for high-RPS environments like Swoole/RoadRunner, completely eliminating memory leaks and state bleeding between requests.
* **Autonomous Data Isolation (Defensive Programming):** The Controller layer remains entirely ignorant of the multi-tenant context. The enforcement of tenant filters on database queries is handled autonomously by integrating "Global Scopes" directly into the Eloquent ORM engine.

### Business Impact and Leverage
* **Risk Management:** The probability of cross-tenant data leaks is structurally reduced to zero.
* **Operational Agility:** Tight coupling between business logic (Services) and data structures (DTOs) is eliminated via strict Dependency Injection. The engineering cost of onboarding new tenants or expanding the feature set is significantly minimized.

---
![alt text](image.png)

![alt text](image-1.png)

![alt text](image-2.png)