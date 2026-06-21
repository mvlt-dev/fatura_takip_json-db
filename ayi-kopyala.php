<?php
// ayi_kopyala.php - Son ayın faturalarını bir sonraki aya kopyalar

$json_data = file_get_contents('faturalar.json');
$faturalar = json_decode($json_data, true);

if (empty($faturalar)) {
    die("Fatura bulunamadı.");
}

// Türkçe ay sıralaması
$monthsOrder = [
    'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
    'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
];

// En son yılı bul (en büyük yıl)
krsort($faturalar);
$sonYil = array_key_first($faturalar);
$donemler = $faturalar[$sonYil];

// O yılın en son ayını bul (ay sıralamasına göre)
uksort($donemler, function($a, $b) use ($monthsOrder) {
    $aMonth = trim(preg_replace('/\s\d{4}$/', '', $a));
    $bMonth = trim(preg_replace('/\s\d{4}$/', '', $b));
    $posA = array_search($aMonth, $monthsOrder);
    $posB = array_search($bMonth, $monthsOrder);
    if ($posA === false || $posB === false) return strcmp($a, $b);
    return $posA - $posB;
});

$sonDonemAdi = array_key_last($donemler);
$sonAyFaturalari = $donemler[$sonDonemAdi];

// Son dönem adından ay ve yılı çıkar (örn: "Ocak 2025")
$parcalar = explode(' ', trim($sonDonemAdi));
$sonAyAdi = $parcalar[0];
$sonDonemYil = isset($parcalar[1]) ? (int)$parcalar[1] : (int)$sonYil;

$sonAyIndex = array_search($sonAyAdi, $monthsOrder);
if ($sonAyIndex === false) {
    die("Ay adı tanınamadı: " . htmlspecialchars($sonAyAdi));
}

// Bir sonraki ayı hesapla
$yeniAyIndex = $sonAyIndex + 1;
$yeniYil = $sonDonemYil;
if ($yeniAyIndex >= 12) {
    $yeniAyIndex = 0;
    $yeniYil++;
}
$yeniAyAdi = $monthsOrder[$yeniAyIndex];
$yeniDonemAdi = $yeniAyAdi . ' ' . $yeniYil;

// Zaten var mı kontrol et
if (isset($faturalar[$yeniYil][$yeniDonemAdi])) {
    // Zaten var, kullanıcıya sor
    $zatenVar = true;
} else {
    $zatenVar = false;
}

// POST ile onay geldiyse kopyala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['onayla'])) {
    $yeniFaturalar = [];
    foreach ($sonAyFaturalari as $fatura) {
        // Tarihleri bir ay ileri al
        $kesimTarihiYeni = date('Y-m-d', strtotime($fatura['kesimTarihi'] . ' +1 month'));
        $sonOdemeYeni    = date('Y-m-d', strtotime($fatura['sonOdeme']    . ' +1 month'));

        $yeniFatura = [
            'faturaAdi'    => $fatura['faturaAdi'],
            'kesimTarihi'  => $kesimTarihiYeni,
            'sonOdeme'     => $sonOdemeYeni,
            'tutar'        => '0,00',          // Tutar sıfırlandı
            'durum'        => 'beklemede',     // Durum: beklemede
            'odemeTarihi'  => '',
            'odemeTuru'    => isset($fatura['odemeTuru']) ? $fatura['odemeTuru'] : '',
        ];
        $yeniFaturalar[] = $yeniFatura;
    }

    $faturalar[(string)$yeniYil][$yeniDonemAdi] = $yeniFaturalar;

    // JSON'a kaydet
    file_put_contents('faturalar.json', json_encode($faturalar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sonraki Ayı Hazırla</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { padding: 9px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f1f1f1; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 5px; text-decoration: none; color: white; cursor: pointer; border: none; font-size: 14px; }
        .btn-green  { background: #4CAF50; }
        .btn-green:hover { background: #45a049; }
        .btn-gray   { background: #888; }
        .btn-gray:hover { background: #666; }
        .info { background: #fff8dc; border-left: 4px solid #f0ad4e; padding: 12px 16px; margin: 15px 0; border-radius: 4px; }
        .warn { background: #fdecea; border-left: 4px solid #dc3545; padding: 12px 16px; margin: 15px 0; border-radius: 4px; }
    </style>
</head>
<body>

<h2>Sonraki Ayı Hazırla</h2>

<?php if ($zatenVar && !isset($_POST['onayla'])): ?>
    <div class="warn">
        <b>Uyarı:</b> <b><?= htmlspecialchars($yeniDonemAdi) ?></b> dönemi zaten mevcut. Üzerine yazmak istiyor musunuz?
    </div>
<?php else: ?>
    <div class="info">
        <b><?= htmlspecialchars($sonDonemAdi) ?></b> döneminin faturaları,
        <b><?= htmlspecialchars($yeniDonemAdi) ?></b> dönemine kopyalanacak.<br>
        Tutarlar <b>0,00 ₺</b> olarak sıfırlanacak, durum <b>beklemede</b> olarak ayarlanacak,
        tarihler <b>1 ay ileri</b> alınacak.
    </div>
<?php endif; ?>

<h3>Kopyalanacak Faturalar (<?= htmlspecialchars($sonDonemAdi) ?>):</h3>
<table>
    <tr>
        <th>Fatura Adı</th>
        <th>Yeni Kesim Tarihi</th>
        <th>Yeni Son Ödeme</th>
        <th>Tutar</th>
        <th>Durum</th>
    </tr>
    <?php foreach ($sonAyFaturalari as $fatura): ?>
    <tr>
        <td><?= htmlspecialchars($fatura['faturaAdi']) ?></td>
        <td><?= date('d.m.Y', strtotime($fatura['kesimTarihi'] . ' +1 month')) ?></td>
        <td><?= date('d.m.Y', strtotime($fatura['sonOdeme']    . ' +1 month')) ?></td>
        <td style="color:gray;">0,00 ₺</td>
        <td style="color:orange; font-weight:bold;">beklemede</td>
    </tr>
    <?php endforeach; ?>
</table>

<br>
<form method="POST">
    <input type="hidden" name="onayla" value="1">
    <button type="submit" class="btn btn-green">✓ Onayla ve Oluştur</button>
    &nbsp;
    <a href="index.php" class="btn btn-gray">İptal</a>
</form>

</body>
</html>