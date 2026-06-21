<?php
// JSON dosyasını okuma
$jsonData = file_get_contents('faturalar.json');
$faturalar = json_decode($jsonData, true);

// Eğer JSON verisi geçersizse
if ($faturalar === null) {
    die("JSON dosyası okunamadı veya geçersiz formatta.");
}

// Form gönderildiğinde veriyi işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fatura_adi = $_POST['fatura_adi'];
    $kesim_tarihi = $_POST['kesim_tarihi'];
    $son_odeme = $_POST['son_odeme'];
    $odeme_durumu = $_POST['odeme_durumu'];
    $odenen_tarih = $_POST['odenen_tarih'] ?: ''; // Boş gelirse boş string
    $odeme_turu = $_POST['odeme_turu']; // 'ödeme türü' for payment type
    $odenecek_tutar = $_POST['odenecek_tutar'];
    $yil = date('Y', strtotime($kesim_tarihi)); // Fatura kesim tarihinden yılı al
    $ay = date('F', strtotime($kesim_tarihi)); // Fatura kesim tarihinden ayı al

    // --- START: Optional: Convert month to Turkish if you want consistency with existing JSON ---
    $turkishMonths = [
        'January' => 'Ocak',
        'February' => 'Şubat',
        'March' => 'Mart',
        'April' => 'Nisan',
        'May' => 'Mayıs',
        'June' => 'Haziran',
        'July' => 'Temmuz',
        'August' => 'Ağustos',
        'September' => 'Eylül',
        'October' => 'Ekim',
        'November' => 'Kasım',
        'December' => 'Aralık'
    ];
    // Check if the English month exists in the mapping before converting
    if (isset($turkishMonths[$ay])) {
        $ay = $turkishMonths[$ay];
    } else {
        // Fallback or error handling if month name is not found (shouldn't happen with date('F'))
        error_log("Warning: Could not convert month '$ay' to Turkish.");
    }
    // --- END: Optional ---


    // Yeni fatura verisini oluştur
    $yeniFatura = [
        'faturaAdi' => $fatura_adi,
        'kesimTarihi' => $kesim_tarihi,
        'sonOdeme' => $son_odeme,
        'tutar' => number_format((float)$odenecek_tutar, 2, '.', '') . ' ₺', // Ensure consistent currency format
        'durum' => $odeme_durumu,
        'odemeTarihi' => $odenen_tarih,
        'odemeTuru' => $odeme_turu
    ];

    // Eğer yıl mevcutsa faturayı o yıla ekle, yoksa yeni bir yıl oluştur
    if (isset($faturalar[$yil])) {
        // Eğer ay mevcutsa faturayı o aya ekle, yoksa yeni bir ay oluştur
        if (isset($faturalar[$yil][$ay])) {
            $faturalar[$yil][$ay][] = $yeniFatura;
        } else {
            // Corrected variable name from $futralar to $faturalar
            $faturalar[$yil][$ay] = [$yeniFatura];
        }
    } else {
        $faturalar[$yil] = [
            $ay => [$yeniFatura]
        ];
    }

    // Güncellenmiş veriyi JSON dosyasına kaydet
    if (file_put_contents('faturalar.json', json_encode($faturalar, JSON_PRETTY_PRINT))) {
        $successMessage = "Fatura başarıyla eklendi!";
    } else {
        $errorMessage = "Fatura eklenirken bir hata oluştu.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura Ekle</title>
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
            padding: 30px;
            border-radius: 8px;
            width: 400px;
            text-align: center;
            margin-top: 50px;
        }
        h1 {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            text-align: left;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input, select {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        input[type="number"] {
            -webkit-appearance: none;
            -moz-appearance: textfield;
        }
        button {
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .success-message {
            color: green;
            font-weight: bold;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error-message {
            color: red;
            font-weight: bold;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .footer {
            text-align: center;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        #fatura_adi {
            visibility: visible;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fatura Ekle</h1>

        <?php if (isset($successMessage)): ?>
            <div class="success-message"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form action="fatura_ekle.php" method="POST">
            <label for="fatura_adi">Fatura Adı:</label>
            <input type="text" name="fatura_adi" id="fatura_adi" required>
            <label for="kesim_tarihi">Kesim Tarihi:</label>
            <input type="date" name="kesim_tarihi" id="kesim_tarihi" required>
            <label for="son_odeme">Son Ödeme Tarihi:</label>
            <input type="date" name="son_odeme" id="son_odeme" required>
            <label for="odeme_durumu">Ödeme Durumu:</label>
            <select name="odeme_durumu" required>
                <option value="Ödendi">Ödendi</option>
                <option selected value="Beklemede">Beklemede</option>
                <option value="Günü Geçti">Günü Geçti</option>
                <option value="Diğer">Diğer</option>
            </select>
            <label for="odenen_tarih">Ödenen Tarih:</label>
            <input type="date" name="odenen_tarih" id="odenen_tarih">
            <label for="odeme_turu">Ödeme Türü:</label>
            <select name="odeme_turu" id="odeme_turu" class="form-control">
                <option value="">Seçiniz</option>
                <option value="Nakit">Nakit</option>
                <option value="Kredi Kartı">Banka/Kredi Kartı</option>
                <option value="Otomatik Ödeme">Otomatik Ödeme</option>
                <option value="Diğer">Diğer</option>
            </select>
            <label for="odenecek_tutar">Ödenecek Tutar:</label>
            <input type="number" step="0.01" name="odenecek_tutar" id="odenecek_tutar" value="0" required>
            <button type="submit">Fatura Ekle</button>
        </form>
        <br><br>
        <a href="index.php">Listeye dön</a>

        <div class="footer">
            <p>Fatura eklemek için gerekli bilgileri doldurun.</p>
        </div>
    </div>
</body>
</html>