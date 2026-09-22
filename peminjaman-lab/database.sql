-- =====================================================
-- DATABASE APLIKASI PEMINJAMAN BARANG LABORATORIUM
-- =====================================================
CREATE DATABASE IF NOT EXISTS peminjaman_lab;
USE peminjaman_lab;

CREATE TABLE admin (
  id_admin   INT AUTO_INCREMENT PRIMARY KEY,
  nama_admin VARCHAR(100) NOT NULL,
  username   VARCHAR(50) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL
);

CREATE TABLE siswa (
  id_siswa   INT AUTO_INCREMENT PRIMARY KEY,
  nis        VARCHAR(20) NOT NULL UNIQUE,
  nama_siswa VARCHAR(100) NOT NULL,
  kelas      VARCHAR(20) NOT NULL,
  jurusan    VARCHAR(50),
  no_telp    VARCHAR(15),
  alamat     TEXT
);

CREATE TABLE barang (
  id_barang    INT AUTO_INCREMENT PRIMARY KEY,
  kode_barang  VARCHAR(20) NOT NULL UNIQUE,
  nama_barang  VARCHAR(100) NOT NULL,
  jenis_barang VARCHAR(50) NOT NULL,
  jumlah       INT NOT NULL DEFAULT 0,
  kondisi      VARCHAR(50) DEFAULT 'Baik',
  keterangan   TEXT
);

CREATE TABLE peminjaman (
  id_peminjaman   INT AUTO_INCREMENT PRIMARY KEY,
  id_siswa        INT NOT NULL,
  id_admin        INT NOT NULL,
  tgl_pinjam      DATE NOT NULL,
  tgl_jatuh_tempo DATE NOT NULL,
  status          ENUM('Dipinjam','Dikembalikan') DEFAULT 'Dipinjam',
  FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE,
  FOREIGN KEY (id_admin) REFERENCES admin(id_admin)
);

CREATE TABLE detail (
  id_detail     INT AUTO_INCREMENT PRIMARY KEY,
  id_peminjaman INT NOT NULL,
  id_barang     INT NOT NULL,
  jumlah        INT NOT NULL,
  FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE,
  FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
);

CREATE TABLE pengembalian (
  id_pengembalian INT AUTO_INCREMENT PRIMARY KEY,
  id_peminjaman   INT NOT NULL UNIQUE,
  tgl_kembali     DATE NOT NULL,
  kondisi_barang  VARCHAR(50) DEFAULT 'Baik',
  denda           INT NOT NULL DEFAULT 0,
  FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE
);

-- Data awal (login: admin / admin123)
INSERT INTO admin (nama_admin, username, password) VALUES
('Admin Lab RPL', 'admin', MD5('admin123'));

INSERT INTO siswa (nis, nama_siswa, kelas, jurusan, no_telp, alamat) VALUES
('22001', 'Ahmad Fauzi',    'XII RPL 1', 'Rekayasa Perangkat Lunak',  '081234567801', 'Jl. Merdeka No. 10, Ponorogo'),
('22002', 'Siti Nurhaliza', 'XII RPL 1', 'Rekayasa Perangkat Lunak',  '081234567802', 'Jl. Sudirman No. 25, Ponorogo'),
('22003', 'Budi Santoso',   'XII TKJ 1', 'Teknik Komputer & Jaringan','081234567803', 'Jl. Diponegoro No. 7, Ponorogo'),
('22004', 'Dewi Lestari',   'XII TKJ 2', 'Teknik Komputer & Jaringan','081234567804', 'Jl. Kartini No. 3, Ponorogo'),
('22005', 'Rizky Pratama',  'XI RPL 1',  'Rekayasa Perangkat Lunak',  '081234567805', 'Jl. Cendrawasih No. 12, Ponorogo');

INSERT INTO barang (kode_barang, nama_barang, jenis_barang, jumlah, kondisi, keterangan) VALUES
('LP-001', 'Laptop ASUS A416',        'Laptop',    10, 'Baik', 'Core i3, RAM 8GB, SSD 256GB'),
('LP-002', 'Laptop Lenovo V14',       'Laptop',     8, 'Baik', 'Core i5, RAM 8GB, SSD 512GB'),
('PR-001', 'Proyektor Epson EB-X06',  'Proyektor',  3, 'Baik', 'XGA 3600 lumens'),
('RD-001', 'Router TP-Link Archer C6','Jaringan',   5, 'Baik', 'Dual band'),
('SW-001', 'Switch Cisco 2960',       'Jaringan',   2, 'Baik', '24 port'),
('KB-001', 'Keyboard Logitech K120',  'Aksesoris', 15, 'Baik', 'USB'),
('MS-001', 'Mouse Logitech B100',     'Aksesoris', 15, 'Baik', 'USB'),
('KD-001', 'Kabel UTP Cat6',          'Kabel',     20, 'Baik', 'Panjang 3 meter');