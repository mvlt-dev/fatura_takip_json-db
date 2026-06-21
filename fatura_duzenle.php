<?php
// JSON dosyasını okuma
$jsonData = file_get_contents('faturalar.json');
$faturalar = json_decode($jsonData, true);

// Form gönderildiğinde veriyi güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $yil = $_POST['yil'];
    $donem = $_POST['donem'];
    $index = $_POST['index'];
    
    $fatura_adi = $_POST['fatura_adi'];
    $kesim_tarihi = $_POST['kesim_tarihi'];
    $son_odeme = $_POST['son_odeme'];
    $odeme_durumu = $_POST['odeme_durumu'];
    $odenen_tarih = $_POST['odenen_tarih'] ?: '';
    $odeme_turu = $_POST['odeme_turu'];
    $odenecek_tutar = $_POST['odenecek_tutar'];

    // Faturayı güncelle
    $faturalar[$yil][$donem][$index] = [
        'faturaAdi' => $fatura_adi,
        'kesimTarihi' => $kesim_tarihi,
        'sonOdeme' => $son_odeme,
        'tutar' => number_format((float)$odenecek_tutar, 2, '.', '') . ' ₺',
        'durum' => $odeme_durumu,
        'odemeTarihi' => $odenen_tarih,
        'odemeTuru' => $odeme_turu
    ];

    // Güncellenmiş veriyi JSON dosyasına kaydet
    if (file_put_contents('faturalar.json', json_encode($faturalar, JSON_PRETTY_PRINT))) {
        header('Location: index.php');
        exit;
    } else {
        $errorMessage = "Fatura güncellenirken bir hata oluştu.";
    }
}

// Düzenlenecek faturayı bul
if (isset($_GET['edit']) && $_GET['edit'] == 1) {
    $edit_year = $_GET['edit_year'];
    $edit_month = $_GET['edit_month'];
    $edit_index = $_GET['edit_index'];

    if (isset($faturalar[$edit_year][$edit_month][$edit_index])) {
        $fatura = $faturalar[$edit_year][$edit_month][$edit_index];
    } else {
        die("Fatura bulunamadı.");
    }
} else {
    die("Geçersiz düzenleme işlemi.");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura Düzenle</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            margin: 20px 0;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Fatura Düzenle</h2>
        <form action="fatura_duzenle.php" method="POST">
            <input type="hidden" name="yil" value="<?php echo htmlspecialchars($edit_year); ?>">
            <input type="hidden" name="donem" value="<?php echo htmlspecialchars($edit_month); ?>">
            <input type="hidden" name="index" value="<?php echo htmlspecialchars($edit_index); ?>">

            <label for="fatura_adi">Fatura Adı:</label>
            <input type="text" name="fatura_adi" id="fatura_adi" required value="<?php echo htmlspecialchars($fatura['faturaAdi']); ?>">

            <label for="kesim_tarihi">Kesim Tarihi:</label>
            <input type="date" name="kesim_tarihi" id="kesim_tarihi" required value="<?php echo htmlspecialchars($fatura['kesimTarihi']); ?>">

            <label for="son_odeme">Son Ödeme Tarihi:</label>
            <input type="date" name="son_odeme" id="son_odeme" required value="<?php echo htmlspecialchars($fatura['sonOdeme']); ?>">

            <label for="odeme_durumu">Ödeme Durumu:</label>
            <select name="odeme_durumu" required>
                <option value="Ödendi" <?php echo $fatura['durum'] == 'Ödendi' ? 'selected' : ''; ?>>Ödendi</option>
                <option value="Beklemede" <?php echo $fatura['durum'] == 'Beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                <option value="Günü Geçti" <?php echo $fatura['durum'] == 'Günü Geçti' ? 'selected' : ''; ?>>Günü Geçti</option>
                <option value="Diğer" <?php echo $fatura['durum'] == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
            </select>

            <label for="odenen_tarih">Ödenen Tarih:</label>
            <input type="date" name="odenen_tarih" id="odenen_tarih" value="<?php echo htmlspecialchars($fatura['odemeTarihi']); ?>">

            <label for="odeme_turu">Ödeme Türü:</label>
                <select name="odeme_turu" required>
                <option value="Nakit" <?php echo $fatura['odemeTuru'] == 'Nakit' ? 'selected' : ''; ?>>Nakit</option>
                <option value="Banka/Kredi Kartı" <?php echo $fatura['odemeTuru'] == 'Banka/Kredi Kartı' ? 'selected' : ''; ?>>Banka/Kredi Kartı</option>
                <option value="Otomatik Ödeme" <?php echo $fatura['odemeTuru'] == 'Otomatik Ödeme' ? 'selected' : ''; ?>>Otomatik Ödeme</option>
                <option value="Diğer" <?php echo $fatura['odemeTuru'] == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
            </select>

          <!--  <select name="odeme_turu" required>
                <option value="Nakit" <?php echo $fatura['odemeTuru'] == 'Nakit' ? 'selected' : ''; ?>>Nakit</option>
                <option value="Kredi Kartı" <?php echo $fatura['odemeTuru'] == 'Banka/Kredi Kartı' ? 'selected' : ''; ?>>Banka/Kredi Kartı</option>
                <option value="Banka Havalesi" <?php echo $fatura['odemeTuru'] == 'Otomatik Ödeme' ? 'selected' : ''; ?>>Otomatik Ödeme</option>
                <option value="Diğer" <?php echo $fatura['odemeTuru'] == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                <option value="Diğer" <?php echo $fatura['odemeTuru'] == '' ? 'selected' : ''; ?>>Boş</option>
            </select> -->

            <label for="odenecek_tutar">Ödenecek Tutar:</label>
            <input type="number" step="0.01" name="odenecek_tutar" id="odenecek_tutar" required value="<?php echo str_replace(['₺', ' ', ','], ['', '', '.'], $fatura['tutar']); ?>">

            <button type="submit">Faturayı Güncelle</button>
            <button type="button" onclick="window.history.back();" style="background-color: #f44336; margin-top: 10px;">İptal</button>
            <a href="index.php" style="text-decoration: none; display: inline-block; margin-top: 10px;">
                <button style="background-color: #2196F3;">Geri Dön</button>
            </a>
        </form>
    </div>
</body>
</html>