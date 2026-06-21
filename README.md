# Fatura Takip Sistemi ( json db )

PHP ve JSON tabanlı basit ve etkili bir fatura yönetim uygulaması.

## Özellikler
- ✅ Fatura ekleme, düzenleme, silme
- 📊 Yıllık ve aylık gruplama
- 🔍 Arama ve filtreleme
- 📈 Sıralama özelliği
- 💰 Otomatik aylık toplam hesaplama
- 📱 Responsive tasarım
- 🎯 Mevcut aya otomatik odaklanma

  index.php :  Bu ilk sürüm bütün veriler tablo olarak yıllara aylara bölünerek tüm sayfada görüntülenir.
  index1v5-2gem.php : Bu dosya geliştirme aşamasında, sayfa ilk açıldığında mevcut tarihe odaklanır sayfayı kaydırmanıza gerek kalmaz, tablodaki tarih ve ödenme tarihine göre listeleme yaılabilir.

## Kullanım
1. Dosyaları web sunucunuza yükleyin
2. `faturalar.json` dosyası otomatik oluşacaktır
3. Tarayıcıdan index.php'yi açın

## Gereksinimler
- PHP 7.0+
- Web sunucusu (Apache/Nginx)
- JSON desteği
