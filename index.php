<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <title>Fatura Takip Sistemi (json db)</title>

    <style>

        body { font-family: Arial, sans-serif; padding: 20px; }

        h1 { text-align: center; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }

        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }

        th { background-color: #f1f1f1; }

        .odendi { color: green; font-weight: bold; }

        .beklemede { color: orange; font-weight: bold; }

        .gunu_gecikti { color: red; font-weight: bold; }

        .diger { color: gray; font-weight: bold; }

        .add-button {

            display: inline-block;

            padding: 10px 15px;

            background-color: #4CAF50;

            color: white;

            text-decoration: none;

            border-radius: 5px;

            margin-bottom: 20px;

        }

        .add-button:hover {

            background-color: #45a049;

        }

        /* Yıllık Özet Stili */

        .yearly-summary {

            margin: 20px 0;

            padding: 15px;

            background: #f1f1f1;

            border-radius: 0 5px 5px 0;

        }

        .yearly-summary h3 {

            margin: 0 0 10px 0;

            color: black;

        }

        .yearly-summary div {

            font-size: 14px;

        }

        /* Yıllık Ayırıcı Çizgi */

        .year-divider {

            border: 0;

            height: 2px;

            background: #007BFF;

            margin: 40px 0;

        }

        /* Aylık Ayırıcı Çizgi */

        .month-divider {

            border: 0;

            height: 1px;

            background: #ccc;

            margin: 25px auto;

            width: 94%;

        }

    </style>

</head>

<body>

    <h1>Fatura Takip Sistemi (json db)</h1>

    <a href="fatura_ekle.php" class="add-button">Yeni Fatura Ekle +</a>
    <a href="ayi-kopyala.php" class="add-button" style="background-color:#007BFF; margin-left:10px;">Sonraki Ayı Hazırla ▶</a>

	<form method="GET" action="index.php" style="margin-top: 20px;">
        <input type="text" name="arama" placeholder="Fatura adı ara..." value="<?php echo htmlspecialchars($_GET['arama'] ?? ''); ?>" style="padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px;">
        <button type="submit" style="padding: 8px 15px; background-color: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer;">Ara</button>
        <?php if (isset($_GET['arama']) && $_GET['arama'] != ''): ?>
            <a href="index.php" style="padding: 8px 15px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; margin-left: 5px;">Temizle</a>
        <?php endif; ?>
    </form>	

    <?php

    // --- YARDIMCI FONKSİYONLAR ---

    function formatCurrency($amount) {

        $amount = floatval(str_replace(',', '.', str_replace(['₺', '?'], ['', ''], $amount)));

        return number_format($amount, 2, ',', '.') . ' ₺';

    }



    // faturalar.json dosyasını oku

    $json_data = file_get_contents('faturalar.json');

    $faturalar = json_decode($json_data, true);



    // Arama terimini al (varsa)
    $arama_terimi = $_GET['arama'] ?? '';

    $filtrelenmis_faturalar = [];

    if (!empty($arama_terimi) && !empty($faturalar)) {
        foreach ($faturalar as $yil => $donemler) {
            foreach ($donemler as $donem => $faturalarListesi) {
                foreach ($faturalarListesi as $index => $fatura) {
                    if (mb_stripos($fatura['faturaAdi'], $arama_terimi, 0, 'UTF-8') !== false) {
                        $filtrelenmis_faturalar[$yil][$donem][] = $fatura;
                    }
                }
            }
        }
        $faturalar = $filtrelenmis_faturalar;

        $yillikToplamlar = [];
        $yillikOdemeler = [];

        if (!empty($faturalar)) {
            foreach ($faturalar as $yil => $donemler) {
                $yillikToplamlar[$yil] = 0;
                $yillikOdemeler[$yil] = ['odenmis' => 0, 'odenmemis' => 0];

                foreach ($donemler as $donem => $faturalarListesi) {
                    foreach ($faturalarListesi as $fatura) {
                        $faturaTutari = floatval(str_replace(['₺', '?'], ['', ''], str_replace(',', '.', $fatura['tutar'])));
                        $yillikToplamlar[$yil] += $faturaTutari;

                        if (isset($fatura['durum'])) {
                            if (mb_strtolower($fatura['durum'], 'UTF-8') === 'ödendi') {
                                $yillikOdemeler[$yil]['odenmis'] += $faturaTutari;
                            } else {
                                $yillikOdemeler[$yil]['odenmemis'] += $faturaTutari;
                            }
                        } else {
                            $yillikOdemeler[$yil]['odenmemis'] += $faturaTutari;
                        }
                    }
                }
            }
        }
    }

    // Yıllık toplamları ve ödeme durumlarını depolamak için boş diziler

    $yillikToplamlar = [];

    $yillikOdemeler = [];



    if (!empty($faturalar)) {

        foreach ($faturalar as $yil => $donemler) {

            $yillikToplamlar[$yil] = 0;

            $yillikOdemeler[$yil] = ['odenmis' => 0, 'odenmemis' => 0];



            foreach ($donemler as $donem => $faturalarListesi) {

                foreach ($faturalarListesi as $fatura) {

                    $faturaTutari = floatval(str_replace(['₺', '?'], ['', ''], str_replace(',', '.', $fatura['tutar'])));

                    $yillikToplamlar[$yil] += $faturaTutari;



                    if (isset($fatura['durum'])) {

                        if (mb_strtolower($fatura['durum'], 'UTF-8') === 'ödendi') {

                            $yillikOdemeler[$yil]['odenmis'] += $faturaTutari;

                        } else {

                            $yillikOdemeler[$yil]['odenmemis'] += $faturaTutari;

                        }

                    } else {

                        $yillikOdemeler[$yil]['odenmemis'] += $faturaTutari;

                    }

                }

            }

        }

    }



    // Yıl ve dönemleri sırala

    krsort($faturalar);



    if (!empty($faturalar)) {

        $firstYear = true;

        foreach ($faturalar as $yil => $donemler) {

            if (!$firstYear) {

                echo '<hr class="year-divider">';

            } else {

                $firstYear = false;

            }



            uksort($donemler, function($a, $b) {

                $monthsOrder = [

                    'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',

                    'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'

                ];

                $aMonth = trim(preg_replace('/\s\d{4}$/', '', $a));

                $bMonth = trim(preg_replace('/\s\d{4}$/', '', $b));



                $posA = array_search($aMonth, $monthsOrder);

                $posB = array_search($bMonth, $monthsOrder);



                if ($posA === false || $posB === false) {

                    return strcmp($a, $b);

                }

                return $posA - $posB;

            });



            echo "<h1 align='center'> #  $yil # </h1><br><h4 align='center'> Aylık Detaylar: </h4>";



            $firstMonthInYear = true;

            foreach ($donemler as $donem => $faturalarListesi) {

                if (!$firstMonthInYear) {

                    echo '<hr class="month-divider">';

                } else {

                    $firstMonthInYear = false;

                }



                echo "<h1 align=center>$donem</h1>";

                echo "<table>";

                echo "<tr>

                    <th>Fatura Adı</th>

                    <th>Kesim Tarihi</th>

                    <th>Son Ödeme</th>

                    <th>Tutar</th>

                    <th>Durum</th>

                    <th>Ödeme Tarihi</th>

                    <th>Ödeme Türü</th>

                    <th>İşlemler</th>

                </tr>";



                $toplamTutar = 0;

                foreach ($faturalarListesi as $index => $fatura) {

                    $tutarValue = floatval(str_replace(['₺', '?', ','], ['', '', '.'], $fatura['tutar']));

                    $toplamTutar += $tutarValue;



                    echo "<tr>";

                    echo "<td>" . htmlspecialchars($fatura['faturaAdi']) . "</td>";

                    echo "<td>" . date('d.m.Y', strtotime($fatura['kesimTarihi'])) . "</td>";

                    echo "<td>" . date('d.m.Y', strtotime($fatura['sonOdeme'])) . "</td>";

                    echo "<td>" . formatCurrency($fatura['tutar']) . "</td>";

                    echo "<td class='" . htmlspecialchars($fatura['durum']) . "'>" . htmlspecialchars($fatura['durum']) . "</td>";

                    echo "<td>" . (isset($fatura['odemeTarihi']) && $fatura['odemeTarihi'] != '' ? date('d.m.Y', strtotime($fatura['odemeTarihi'])) : '-') . "</td>";

                    echo "<td>" . (isset($fatura['odemeTuru']) && $fatura['odemeTuru'] != '' ? htmlspecialchars($fatura['odemeTuru']) : '-') . "</td>";

                    echo "<td>

                        <a href='fatura_duzenle.php?edit=1&edit_year=" . urlencode($yil) . "&edit_month=" . urlencode($donem) . "&edit_index=" . $index . "'>Düzenle</a> |

                        <a href='fatura_sil.php?delete=1&delete_year=" . urlencode($yil) . "&delete_month=" . urlencode($donem) . "&delete_index=" . $index . "' onclick='return confirm(\"Silinsin mi?\")'>Sil</a>

                    </td>";

                    echo "</tr>";

                }



                echo "<tfoot>";

                echo "<tr>";

                echo "<th colspan='8' style='text-align: center;'> Toplam Tutar: " . number_format($toplamTutar, 2, ',', '.') . " ₺ </th>";

                echo "</tr>";

                echo "</tfoot>";

                echo "</table>";

                echo "<br>";

            }



            // Yıllık Özet

            ?>

            <div class="yearly-summary" align="center">

                <h3>

                    <?= htmlspecialchars("$yil Yılı Genel Toplamı: " . formatCurrency($yillikToplamlar[$yil])) ?>

                </h3>

                <div style="font-size:14px;">

                    <span style="color:green; font-weight:bold;">✓ Ödenen: <?= formatCurrency($yillikOdemeler[$yil]['odenmis']) ?></span> |

                    <span style="color:red; font-weight:bold;">✗ Ödenmeyen: <?= formatCurrency($yillikOdemeler[$yil]['odenmemis']) ?></span>

                </div>

            </div>

            <?php



        }

    } else {

        echo "<p style='text-align: center;'>Henüz hiç fatura eklenmedi. Yeni bir fatura eklemek için yukarıdaki 'Yeni Fatura Ekle +' butonunu kullanın.</p>";

    }

    ?>

    <?php
    // GENEL TOPLAM (tüm yıllar)
    if (!empty($yillikToplamlar)) {
        $genelToplam    = array_sum($yillikToplamlar);
        $genelOdenmis   = array_sum(array_column($yillikOdemeler, 'odenmis'));
        $genelOdenmemis = array_sum(array_column($yillikOdemeler, 'odenmemis'));
        ?>
        <hr style="border:0; height:3px; background:#333; margin:50px 0 20px 0;">
        <div class="yearly-summary" align="center" style="background:#e8e8e8;">
            <h3 style="font-size:20px;">
                🧾 Genel Toplam (Tüm Yıllar): <?= formatCurrency($genelToplam) ?>
            </h3>
            <div style="font-size:15px;">
                <span style="color:green; font-weight:bold;">✓ Toplam Ödenen: <?= formatCurrency($genelOdenmis) ?></span>
                &nbsp;|&nbsp;
                <span style="color:red; font-weight:bold;">✗ Toplam Ödenmeyen: <?= formatCurrency($genelOdenmemis) ?></span>
            </div>
        </div>
        <?php
    }
    ?>

</body>

</html>