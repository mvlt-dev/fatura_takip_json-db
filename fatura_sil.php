<?php
// JSON dosyasını okuma
$jsonData = file_get_contents('faturalar.json');
$faturalar = json_decode($jsonData, true);

// Eğer JSON verisi geçersizse
if ($faturalar === null) {
    die("JSON dosyası okunamadı veya geçersiz formatta.");
}

// Silme isteği geldiğinde veriyi işleme
if (isset($_GET['delete']) && $_GET['delete'] == 1) {
    $yil = $_GET['delete_year'];
    $ay = $_GET['delete_month']; // Added: Get the month
    $index = $_GET['delete_index'];

    // Yıl, ay ve indeks mevcutsa faturayı sil
    if (isset($faturalar[$yil]) && isset($faturalar[$yil][$ay]) && isset($faturalar[$yil][$ay][$index])) {
        unset($faturalar[$yil][$ay][$index]);
        // Eğer ay boşsa sil
        if (empty($faturalar[$yil][$ay])) {
            unset($faturalar[$yil][$ay]);
        }
        // Eğer yıl boşsa sil
        if (empty($faturalar[$yil])) {
            unset($faturalar[$yil]);
        }
        // Güncellenmiş veriyi JSON dosyasına kaydet
        if (file_put_contents('faturalar.json', json_encode($faturalar, JSON_PRETTY_PRINT))) {
            $successMessage = "Fatura başarıyla silindi!";
        } else {
            $errorMessage = "Fatura silinirken bir hata oluştu.";
        }
    } else {
        $errorMessage = "Fatura bulunamadı veya geçersiz parametreler."; // More specific error
    }
} else {
    $errorMessage = "Geçersiz silme isteği.";
}

// Kullanıcıyı index.php'ye yönlendir
header('Location: index.php');
exit();
?>