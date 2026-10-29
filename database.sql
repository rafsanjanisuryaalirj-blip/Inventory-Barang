CREATE DATABASE IF NOT EXISTS db_inventory
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_inventory;

CREATE TABLE IF NOT EXISTS users (
    id_user INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kategori (
    id_kategori INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_kategori),
    UNIQUE KEY uq_kategori_nama (nama_kategori)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS barang (
    id_barang INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_barang VARCHAR(20) NOT NULL,
    nama_barang VARCHAR(150) NOT NULL,
    id_kategori INT UNSIGNED NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    satuan VARCHAR(20) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    deskripsi TEXT NULL,
    foto VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_barang),
    UNIQUE KEY uq_barang_kode (kode_barang),
    KEY idx_barang_nama (nama_barang),
    KEY idx_barang_kategori (id_kategori),
    CONSTRAINT fk_barang_kategori
        FOREIGN KEY (id_kategori) REFERENCES kategori (id_kategori)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_barang_stok_nonnegatif CHECK (stok >= 0),
    CONSTRAINT chk_barang_harga_nonnegatif CHECK (harga >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO kategori (nama_kategori) VALUES
    ('Elektronik'),
    ('Alat Tulis Kantor'),
    ('Perabot'),
    ('Peralatan'),
    ('Lain-lain')
ON DUPLICATE KEY UPDATE nama_kategori = VALUES(nama_kategori);

-- Akun pengguna dibuat melalui halaman registrasi, bukan disemai di SQL.
