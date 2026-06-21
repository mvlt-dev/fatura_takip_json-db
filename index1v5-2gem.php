<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Fatura Takip Sistemi</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9; }
        h1 { text-align: center; color: #333; }
        
        .header-buttons { text-align: center; margin-bottom: 20px; }
        .add-button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .search-container { text-align: center; margin-bottom: 30px; }
        #faturaSearch {
            width: 80%; max-width: 600px; padding: 15px; font-size: 20px;
            border: 2px solid #007BFF; border-radius: 25px; outline: none;
        }

        .table-responsive {
            width: 100%; overflow-x: auto; border: 1px solid #ccc;
            border-radius: 8px; margin-top: 10px;
        }

        table { width: 100%; border-collapse: collapse; background: white; white-space: nowrap; }
        th, td { padding: 15px 20px; border: 1px solid #ddd; font-size: 24px; }
        
        /* Başlıklar Ortalı */
        th { background-color: #f1f1f1; font-weight: bold; padding: 0; text-align: center; vertical-align: middle; }
        
        /* Başlık Buton Stili */
        .header-sort-btn {
            width: 100%; height: 100%; padding: 15px 20px;
            background: #f1f1f1; border: none; font-size: 24px;
            font-weight: bold; cursor: pointer; display: flex;
            justify-content: space-between; align-items: center; transition: background 0.2s;
        }
        .header-sort-btn:hover { background: #e2e6ea; }
        .sort-icon { font-size: 18px; color: #007BFF; }

        .month-header { 
            text-align: center; color: #444; font-size: 26px; 
            margin-top: 40px; font-weight: bold; padding: 10px; background: #eee; border-radius: 5px;
        }

        .text-right { text-align: right; }
        .odendi { color: green; font-weight: bold; }
        .Beklemede, .beklemede { color: orange; font-weight: bold; }
        
        .action-cell { display: flex; gap: 10px; justify-content: center; }
        .btn-action { padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 18px; color: white; font-weight: bold; }
        .btn-edit { background-color: #007BFF; }
        .btn-delete { background-color: #dc3545; }

        .year-wrapper { margin-bottom: 25px; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; }
        .year-header { background-color: #007BFF; color: white; padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .year-content { display: none; padding: 15px; background: #fff; }
        .year-content.active { display: block; }

        .monthly-total-row { 
            text-align: center; background-color: #f8f9fa; 
            font-size: 30px; font-weight: bold; padding: 20px; color: #222;
        }
    </style>
</head>
<body>

    <h1>Fatura Takip Sistemi</h1>
    
    <div class="header-buttons">
        <a href="fatura_ekle.php" class="add-button">Yeni Fatura Ekle +</a>
        <a href="ayi-kopyala.php" class="add-button" style="background-color:#007BFF; margin-left:10px;">Sonraki Ayı Hazırla ▶</a>
    </div>

    <div class="search-container">
        <input type="text" id="faturaSearch" placeholder="Fatura ara (İsim, Durum, Tür)..." onkeyup="filterBills()">
    </div>

    <?php
    function formatCurrency($amount) {
        $amount = floatval(str_replace(',', '.', str_replace(['₺', '?'], ['', ''], $amount)));
        return number_format($amount, 2, ',', '.') . ' ₺';
    }

    $json_data = @file_get_contents('faturalar.json');
    $faturalar = json_decode($json_data, true) ?: [];
    
    // Güncel Ay-Yıl Bilgisi (Scroll odağı için)
    $aylar = [ 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık' ];
    $buAyYil = $aylar[date('n') - 1] . " " . date('Y');

    if (!empty($faturalar)) {
        krsort($faturalar); // Yılları yeniden eskiye sırala
        foreach ($faturalar as $yil => $donemler) {
            $isFirstYear = ($yil == date('Y'));
            echo '<div class="year-wrapper">';
            echo '<div class="year-header" onclick="toggleYear(this)">';
            echo '<h2># ' . $yil . ' Yılı Faturaları</h2><div class="icon" style="font-size:24px;">' . ($isFirstYear ? '▲' : '▼') . '</div></div>';
            echo '<div class="year-content' . ($isFirstYear ? ' active' : '') . '">';
            
            // Ayları kronolojik sırala
            uksort($donemler, function($a, $b) use ($aylar) {
                return array_search(trim(explode(" ", $a)[0]), $aylar) - array_search(trim(explode(" ", $b)[0]), $aylar);
            });

            foreach ($donemler as $donem => $faturalarListesi) {
                $ayId = "ay-" . md5($donem);
                echo "<div class='month-container'>";
                // EĞER BU AY İSE ID EKLE
                echo "<div class='month-header' " . ($donem == $buAyYil ? 'id="current-month-target"' : '') . ">$donem</div>";
                echo "<div class='table-responsive'><table id='$ayId'><thead><tr>
                        <th style='text-align:center;'>Fatura Adı</th>
                        <th style='text-align:center;'>Son Ödeme</th>
                        <th>
                            <button class='header-sort-btn' onclick='sortTable(\"$ayId\", 2, \"numeric\")'>
                                Tutar <span class='sort-icon'>⇅</span>
                            </button>
                        </th>
                        <th style='text-align:center;'>Durum</th>
                        <th>
                            <button class='header-sort-btn' onclick='sortTable(\"$ayId\", 4, \"date\")'>
                                Ödeme Tarihi <span class='sort-icon'>⇅</span>
                            </button>
                        </th>
                        <th style='text-align:center;'>Ödeme Türü</th>
                        <th style='text-align:center;'>İşlemler</th>
                      </tr></thead><tbody class='fatura-body'>";

                $aylikToplam = 0;
                foreach ($faturalarListesi as $index => $fatura) {
                    $rawTutar = str_replace(['₺', '?', '.', ' '], ['', '', '', ''], $fatura['tutar']);
                    $tutarVal = floatval(str_replace(',', '.', $rawTutar));
                    $aylikToplam += $tutarVal;

                    $odemeTarihi = (!empty($fatura['odemeTarihi'])) ? date('d.m.Y', strtotime($fatura['odemeTarihi'])) : '-';
                    echo "<tr class='fatura-row' data-tutar='$tutarVal' data-date='".($fatura['odemeTarihi'] ?? '9999-12-31')."'>
                            <td>" . htmlspecialchars($fatura['faturaAdi']) . "</td>
                            <td style='text-align:center;'>" . date('d.m.Y', strtotime($fatura['sonOdeme'])) . "</td>
                            <td class='text-right' style='font-weight:bold;'>" . formatCurrency($fatura['tutar']) . "</td>
                            <td class='" . htmlspecialchars($fatura['durum']) . "' style='text-align:center;'>" . htmlspecialchars($fatura['durum']) . "</td>
                            <td style='text-align:center;'>$odemeTarihi</td>
                            <td style='text-align:center;'>" . ($fatura['odemeTuru'] ?? '-') . "</td>
                            <td><div class='action-cell'>
                                <a href='fatura_duzenle.php?edit=1&edit_year=$yil&edit_month=".urlencode($donem)."&edit_index=$index' class='btn-action btn-edit'>Düzelt</a>
                                <a href='fatura_sil.php?delete=1&delete_year=".urlencode($yil)."&delete_month=".urlencode($donem)."&delete_index=$index' class='btn-action btn-delete' onclick='return confirm(\"Silinsin mi?\")'>Sil</a>
                            </div></td></tr>";
                }
                echo "</tbody><tfoot><tr><th colspan='7' class='monthly-total-row'>Aylık Toplam: " . number_format($aylikToplam, 2, ',', '.') . " ₺</th></tr></tfoot></table></div></div>";
            }
            echo "</div></div>";
        }
    }
    ?>

    <script>
        // Sayfa yüklendiğinde mevcut aya odaklan (Scroll Focus)
        window.onload = function() {
            const target = document.getElementById('current-month-target');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        };

        // Arama Fonksiyonu
        function filterBills() {
            let input = document.getElementById('faturaSearch').value.toLowerCase();
            let rows = document.querySelectorAll('.fatura-row');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
            });
        }

        // Sıralama Fonksiyonu (Sayısal ve Tarih bazlı)
        function sortTable(tableId, colIndex, type) {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('.fatura-body');
            const rows = Array.from(tbody.querySelectorAll('.fatura-row'));
            const isAsc = table.getAttribute('data-order-' + colIndex) === 'asc';
            
            rows.sort((a, b) => {
                let valA, valB;
                if(type === 'numeric') {
                    valA = parseFloat(a.getAttribute('data-tutar'));
                    valB = parseFloat(b.getAttribute('data-tutar'));
                    return isAsc ? valB - valA : valA - valB;
                } else if(type === 'date') {
                    valA = a.getAttribute('data-date');
                    valB = b.getAttribute('data-date');
                    return isAsc ? valB.localeCompare(valA) : valA.localeCompare(valB);
                }
            });

            table.setAttribute('data-order-' + colIndex, isAsc ? 'desc' : 'asc');
            tbody.innerHTML = "";
            rows.forEach(row => tbody.appendChild(row));
        }

        // Yıl daraltma/genişletme
        function toggleYear(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.icon');
            const isVisible = (content.style.display === "block" || content.classList.contains('active'));
            
            content.style.display = isVisible ? "none" : "block";
            content.classList.toggle('active', !isVisible);
            icon.innerText = isVisible ? '▼' : '▲';
        }
    </script>
</body>
</html>
