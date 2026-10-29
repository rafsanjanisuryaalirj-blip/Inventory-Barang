<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/barang.php';
requireLogin();

$categoryId = inputText($_GET['kategori'] ?? null);
$kategoriNama = '';

try {
    require __DIR__ . '/config/database.php';

    $idKategori = $categoryId !== '' && ctype_digit($categoryId) && (int) $categoryId > 0 ? (int) $categoryId : null;
    $barangList = ambilSemuaBarang($pdo, $idKategori);
    if ($idKategori !== null) {
        $kategoriNama = namaKategori($pdo, $idKategori);
    }
} catch (PDOException $exception) {
    error_log('Gagal menyiapkan cetak laporan: ' . $exception->getMessage());
    http_response_code(500);
    exit('Laporan gagal dibuat karena kesalahan database.');
}

$totals = hitungTotalBarang($barangList);

require __DIR__ . '/lib/fpdf/fpdf.php';

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->SetMargins(10, 12, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetTitle(teksLatin('Laporan Inventaris Barang'));
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(27, 42, 73);
$pdf->Cell(0, 10, teksLatin('Laporan Inventaris Barang'), 0, 1, 'C');

$subJudul = 'Dicetak: ' . date('d-m-Y H:i');
if ($kategoriNama !== '') {
    $subJudul .= ' | Kategori: ' . $kategoriNama;
}
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 6, teksLatin($subJudul), 0, 1, 'C');
$pdf->Ln(3);

$headers = [
    'No' => 10,
    'Kode' => 30,
    'Nama Barang' => 78,
    'Kategori' => 45,
    'Stok' => 15,
    'Satuan' => 22,
    'Harga' => 30,
    'Subtotal' => 37,
];

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(31, 58, 99);
$pdf->SetTextColor(255, 255, 255);
foreach ($headers as $label => $width) {
    $align = in_array($label, ['Stok', 'Harga', 'Subtotal'], true) ? 'R' : 'C';
    $pdf->Cell($width, 8, teksLatin($label), 1, 0, $align, true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(30, 41, 59);
if ($barangList === []) {
    $pdf->Cell(267, 8, teksLatin('Belum ada data barang.'), 1, 1, 'C');
} else {
    foreach ($barangList as $index => $barang) {
        $subtotal = (int) $barang['stok'] * (float) $barang['harga'];
        $pdf->Cell(10, 7, (string) ($index + 1), 1, 0, 'C');
        $pdf->Cell(30, 7, teksLatin($barang['kode_barang']), 1);
        $pdf->Cell(78, 7, teksLatin(potongTeks($barang['nama_barang'], 46)), 1);
        $pdf->Cell(45, 7, teksLatin(potongTeks($barang['nama_kategori'], 26)), 1);
        $pdf->Cell(15, 7, (string) (int) $barang['stok'], 1, 0, 'R');
        $pdf->Cell(22, 7, teksLatin($barang['satuan']), 1, 0, 'C');
        $pdf->Cell(30, 7, teksLatin(formatRupiah($barang['harga'])), 1, 0, 'R');
        $pdf->Cell(37, 7, teksLatin(formatRupiah($subtotal)), 1, 0, 'R');
        $pdf->Ln();
    }
}

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(240, 244, 250);
$pdf->Cell(163, 8, teksLatin('Total'), 1, 0, 'R', true);
$pdf->Cell(15, 8, (string) $totals['total_stok'], 1, 0, 'R', true);
$pdf->Cell(22, 8, '', 1, 0, 'C', true);
$pdf->Cell(30, 8, '', 1, 0, 'R', true);
$pdf->Cell(37, 8, teksLatin(formatRupiah($totals['total_nilai'])), 1, 0, 'R', true);
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 6, teksLatin('Total nilai inventaris: ' . formatRupiah($totals['total_nilai'])), 0, 1, 'L');

$pdf->Output('I', 'laporan-inventaris-' . date('Ymd') . '.pdf');
