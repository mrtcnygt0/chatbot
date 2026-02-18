# Alt Klasör Dağıtımı - Sorun Çözüldü! 🎉

## Problem
Projeyi `https://mertcanyigit.com/portfolio/chatbot/` adresine yüklediniz ancak:
- ❌ 403 Forbidden hatası alıyordunuz
- ❌ `/portfolio/chatbot/index.php` açıldığında `/login.php` adresine yönlendiriliyordu (doğru adres `/portfolio/chatbot/login.php` olmalıydı)
- ❌ CSS ve JavaScript dosyaları yüklenmiyordu

## Çözüm ✅

**Artık uygulama otomatik olarak hangi klasörde olduğunu algılıyor!** Hiçbir manuel ayar yapmanıza gerek yok.

### Yapılması Gerekenler

#### Seçenek 1: Yeniden Kurulum (Önerilen)

1. **Tüm dosyaları sunucunuza yükleyin**
   ```
   /home/your-user/public_html/portfolio/chatbot/
   ```

2. **Kurulum sihirbazını çalıştırın**
   ```
   https://mertcanyigit.com/portfolio/chatbot/install.php
   ```

3. **Kurulum formunu doldurun:**
   - Veritabanı bilgileri
   - Admin e-posta ve şifre
   - OpenAI API anahtarı

4. **Tamamlandı!**
   Kurulum otomatik olarak `/portfolio/chatbot` yolunu algılayacak ve tüm ayarları yapacak.

#### Seçenek 2: Mevcut Kurulumu Güncelleme

Eğer zaten kurulum yaptıysanız:

1. **config/config.php dosyasını açın**
2. **`base_path` ekleyin:**
   ```php
   return [
       'app' => [
           'name' => 'AI Chat',
           'url' => 'https://mertcanyigit.com',
           'base_path' => '/portfolio/chatbot',  // ← Bunu ekleyin
           ...
       ],
       ...
   ];
   ```
3. **Kaydedin ve tarayıcınızı yenileyin**

## Test Etme

### 1. Yol Algılamayı Test Edin
`test-path.php` dosyasını kullanarak yolun doğru algılanıp algılanmadığını kontrol edin:

```
https://mertcanyigit.com/portfolio/chatbot/test-path.php
```

Bu sayfa size:
- ✓ Algılanan temel yol
- ✓ Örnek URL'ler
- ✓ Yapılandırma durumu
- ⚠ Olası sorunlar

gösterecek.

**Önemli:** Test ettikten sonra güvenlik için `test-path.php` dosyasını silin!

### 2. Uygulamayı Test Edin

Şu URL'leri kontrol edin:

- [ ] Ana sayfa: `https://mertcanyigit.com/portfolio/chatbot/`
- [ ] Giriş sayfası: `https://mertcanyigit.com/portfolio/chatbot/login.php`
- [ ] CSS yükleniyor mu? (Tarayıcı geliştirici araçlarında kontrol edin)
- [ ] Giriş yaptıktan sonra doğru yere yönleniyor mu?
- [ ] Sohbet API'si çalışıyor mu?
- [ ] Admin paneli: `https://mertcanyigit.com/portfolio/chatbot/admin/`

## Teknik Detaylar

### Otomatik Yol Algılama Nasıl Çalışır?

Yeni `Url` yardımcı sınıfı `$_SERVER['SCRIPT_NAME']` değişkeninden yolu otomatik algılar:

```php
// Script: /portfolio/chatbot/public/index.php
// Algılanan yol: /portfolio/chatbot
```

### Tüm Yollar Güncellendi

1. **PHP Dosyaları:**
   - Yönlendirmeler: `Url::redirect('login.php')`
   - Varlıklar: `Url::asset('css/main.css')`
   - Bağlantılar: `Url::to('admin')`

2. **JavaScript Dosyaları:**
   - `window.BASE_PATH` değişkeni otomatik ekleniyor
   - API çağrıları: `this.url('/api/chat.php')`
   - Tüm yönlendirmeler doğru yolu kullanıyor

3. **.htaccess:**
   - Alt klasör desteği eklendi
   - `public/` klasörüne yönlendirme yapılandırıldı

## Sorun Giderme

### Hala 403 Forbidden Alıyorsanız

1. **Dosya izinlerini kontrol edin:**
   ```bash
   chmod 755 -R portfolio/chatbot
   chmod 777 portfolio/chatbot/config
   ```

2. **.htaccess çalışıyor mu?**
   - Apache'de `mod_rewrite` etkin olmalı
   - `AllowOverride All` ayarlanmış olmalı

3. **Manuel yol ayarlayın:**
   `config/config.php` içinde:
   ```php
   'base_path' => '/portfolio/chatbot',
   ```

### Varlıklar Yüklenmiyor (CSS/JS)

1. **Tarayıcı geliştirici araçlarını açın** (F12)
2. **Network sekmesinde** 404 hataları var mı kontrol edin
3. **Örnek CSS'ye direkt erişin:**
   ```
   https://mertcanyigit.com/portfolio/chatbot/public/assets/css/main.css
   ```
   Bu çalışıyorsa, `.htaccess` kurallarında sorun var demektir.

### Yönlendirmeler Yanlış

1. **Tarayıcı önbelleğini temizleyin** - Eski JavaScript önbellekte olabilir
2. **config.php'yi kontrol edin** - `base_path` doğru ayarlanmış mı?
3. **Kurulumu yeniden çalıştırın** - Otomatik algılayacak

## Ek Kaynaklar

- **Detaylı Kılavuz:** [SUBDIRECTORY_DEPLOYMENT.md](SUBDIRECTORY_DEPLOYMENT.md) (İngilizce)
- **Genel Kurulum:** [README.md](README.md)
- **Dağıtım Rehberi:** [DEPLOYMENT.md](DEPLOYMENT.md)

## Yardım İçin

Sorun devam ediyorsa:

1. `test-path.php` çıktısını kontrol edin
2. Apache/Nginx hata loglarına bakın:
   ```bash
   tail -f /var/log/apache2/error.log
   ```
3. Geçici olarak debug modunu açın (`config/config.php`):
   ```php
   'debug' => true,
   'environment' => 'development',
   ```

## Özet

✅ **Sorun çözüldü** - Otomatik yol algılama eklendi
✅ **Kolay kurulum** - Sadece dosyaları yükleyin ve installer'ı çalıştırın
✅ **Manuel ayar yok** - Sistem otomatik algılıyor
✅ **Test aracı** - `test-path.php` ile doğrulama yapın
✅ **Tam dokümantasyon** - Tüm senaryolar için kılavuzlar

**Artık projeniz `/portfolio/chatbot/` klasöründe sorunsuz çalışacak!** 🚀
