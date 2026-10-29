<?php
declare(strict_types=1);

if (!defined('INVENTORY_APP')) {
    http_response_code(404);
    exit;
}

function daftarKategori(PDO $pdo): array
{
    return $pdo->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();
}

function namaKategori(PDO $pdo, int $id): string
{
    $statement = $pdo->prepare('SELECT nama_kategori FROM kategori WHERE id_kategori = :id');
    $statement->execute([':id' => $id]);

    return (string) ($statement->fetchColumn() ?: '');
}

function ambilSemuaBarang(PDO $pdo, int $userId, ?int $idKategori = null): array
{
    $sql = 'SELECT b.kode_barang, b.nama_barang, b.stok, b.satuan, b.harga, k.nama_kategori
            FROM barang b INNER JOIN kategori k ON k.id_kategori = b.id_kategori
            WHERE b.id_user = :user';
    $parameters = [':user' => $userId];
    if ($idKategori !== null) {
        $sql .= ' AND b.id_kategori = :kategori';
        $parameters[':kategori'] = $idKategori;
    }
    $sql .= ' ORDER BY k.nama_kategori, b.nama_barang';

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

function cariBarang(PDO $pdo, int $id, int $userId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM barang WHERE id_barang = :id AND id_user = :user');
    $statement->execute([':id' => $id, ':user' => $userId]);

    return $statement->fetch() ?: null;
}

function detailBarang(PDO $pdo, int $id, int $userId): ?array
{
    $statement = $pdo->prepare(
        'SELECT b.*, k.nama_kategori FROM barang b
         INNER JOIN kategori k ON k.id_kategori = b.id_kategori
         WHERE b.id_barang = :id AND b.id_user = :user'
    );
    $statement->execute([':id' => $id, ':user' => $userId]);

    return $statement->fetch() ?: null;
}

function tambahBarang(PDO $pdo, int $userId, array $values, ?string $foto): void
{
    $statement = $pdo->prepare(
        'INSERT INTO barang (id_user, kode_barang, nama_barang, id_kategori, stok, satuan, harga, deskripsi, foto)
         VALUES (:user, :kode, :nama, :kategori, :stok, :satuan, :harga, :deskripsi, :foto)'
    );
    $statement->execute([
        ':user' => $userId,
        ':kode' => $values['kode_barang'],
        ':nama' => $values['nama_barang'],
        ':kategori' => (int) $values['id_kategori'],
        ':stok' => (int) $values['stok'],
        ':satuan' => $values['satuan'],
        ':harga' => $values['harga'],
        ':deskripsi' => $values['deskripsi'] !== '' ? $values['deskripsi'] : null,
        ':foto' => $foto,
    ]);
}

function ubahBarang(PDO $pdo, int $id, int $userId, array $values, ?string $foto): void
{
    $statement = $pdo->prepare(
        'UPDATE barang SET kode_barang = :kode, nama_barang = :nama, id_kategori = :kategori,
         stok = :stok, satuan = :satuan, harga = :harga, deskripsi = :deskripsi, foto = :foto
         WHERE id_barang = :id AND id_user = :user'
    );
    $statement->execute([
        ':kode' => $values['kode_barang'],
        ':nama' => $values['nama_barang'],
        ':kategori' => (int) $values['id_kategori'],
        ':stok' => (int) $values['stok'],
        ':satuan' => $values['satuan'],
        ':harga' => $values['harga'],
        ':deskripsi' => $values['deskripsi'] !== '' ? $values['deskripsi'] : null,
        ':foto' => $foto,
        ':id' => $id,
        ':user' => $userId,
    ]);
}

function hapusBarang(PDO $pdo, int $id, int $userId): ?string
{
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('SELECT foto FROM barang WHERE id_barang = :id AND id_user = :user FOR UPDATE');
        $statement->execute([':id' => $id, ':user' => $userId]);
        $barang = $statement->fetch();
        if (!$barang) {
            $pdo->rollBack();
            return null;
        }

        $statement = $pdo->prepare('DELETE FROM barang WHERE id_barang = :id AND id_user = :user');
        $statement->execute([':id' => $id, ':user' => $userId]);
        $pdo->commit();

        return (string) $barang['foto'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}