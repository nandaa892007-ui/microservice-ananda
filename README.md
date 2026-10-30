# Microservices E-Commerce System

Sistem backend e-commerce berbasis arsitektur microservices yang terdiri dari empat layanan independen:
1. **Product Service** (Port 3000) - Katalog produk & informasi stok.
2. **Cart Service** (Port 3001) - Pengelolaan keranjang belanja pengguna.
3. **Review Service** (Port 3002) - Ulasan produk.
4. **Order Service** (Port 3003) - Pemrosesan transaksi checkout dan status pesanan.

---

## 1. Arsitektur Sistem & Alur Inter-Service

Setiap service berjalan di kontainer terpisah dengan basis datanya masing-masing (*Database-per-Service*):
* `product-service`: Express.js + MySQL 8.0 (Port 3306)
* `cart-service`: Laravel 11 + PostgreSQL 16 (Port 5432)
* `review-service`: Flask Python + MongoDB 7 (Port 27017)
* `order-service`: Laravel 11 + MySQL 8.0 (Port 3307)

```
[ Client / Postman ]
        │
        ├──> [ Order Service :3003 ]
        │          │
        │          ├── (1) GET /carts/{userId} ---------> [ Cart Service :3001 ]
        │          │                                             │
        │          ├── (2) GET /products/{id} ----------> [ Product Service :3000 ]
        │          │
        │          ├── (3) DB::transaction (Snapshot) ---> [ MySQL (order-db) :3307 ]
        │          │
        │          └── (4) DELETE /carts/{userId} ------> [ Cart Service :3001 ]
```

### Pembagian Lapisan di `order-service`:
* **Routes (`routes/api.php`)**: Menerima request HTTP, validasi format ID angka (`whereNumber`).
* **Form Request (`app/Http/Requests`)**: Validasi integritas body dan query parameter (format error 422).
* **Controller (`app/Http/Controllers/OrderController.php`)**: Layer HTTP murni, menangani response JSON dan mapping exception.
* **Service (`app/Services/OrderService.php`)**: Logika bisnis checkout, pengecekan stok, snapshot harga, dan orkestrasi `DB::transaction`.
* **Clients (`app/Clients`)**: Komunikasi HTTP antar-service dengan proteksi timeout maksimal 5 detik.
* **Models (`app/Models`)**: Relasi Eloquent `Order hasMany OrderItem` dan `OrderItem belongsTo Order`.

---

## 2. Cara Menjalankan Sistem

Seluruh sistem dapat dijalankan sekaligus menggunakan Docker Compose:

```bash
# 1. Pastikan docker engine aktif
docker compose up --build
```

Setelah proses build selesai, seluruh service dan database akan aktif:
* Product Service: `http://localhost:3000/products/1`
* Cart Service: `http://localhost:3001/up`
* Review Service: `http://localhost:3002/health`
* Order Service: `http://localhost:3003/health`

Untuk menghentikan sistem:
```bash
docker compose down
```

---

## 3. Daftar Endpoint Order Service (Port 3003)

| Method | Endpoint | Fungsi | Status Sukses | Status Gagal |
| :--- | :--- | :--- | :---: | :--- |
| `GET` | `/health` | Cek status hidup service | `200` | - |
| `POST` | `/orders` | Checkout pesanan dari keranjang | `201` | `422`, `409`, `503`, `500` |
| `GET` | `/orders/{id}` | Detail pesanan & items | `200` | `404` |
| `GET` | `/orders/user/{userId}` | Riwayat pesanan user (`?status=`) | `200` | `422`, `404` |
| `PATCH`| `/orders/{id}/pay` | Pembayaran pesanan (PENDING -> PAID)| `200` | `404`, `409` |

---

## 4. Contoh Request & Response

### POST `/orders` (Checkout)
**Request Body**:
```json
{
  "user_id": 7
}
```

**Response Sukses (201)**:
```json
{
  "message": "Pesanan berhasil dibuat",
  "data": {
    "id": 1,
    "user_id": 7,
    "status": "PENDING",
    "total_amount": 1050000,
    "items": [
      {
        "product_id": 1,
        "product_name": "Keyboard Mekanikal",
        "unit_price": 450000,
        "quantity": 2,
        "subtotal": 900000
      }
    ],
    "created_at": "2026-10-30T00:00:00+00:00",
    "updated_at": "2026-10-30T00:00:00+00:00"
  }
}
```

**Response Gagal**:
* `422 Unprocessable Content`: Field `user_id` tidak dikirim.
* `409 Conflict`: Keranjang kosong atau stok produk di katalog tidak mencukupi.
* `503 Service Unavailable`: `product-service` atau `cart-service` tidak dapat dihubungi / timeout 5 detik.

### GET `/orders/{id}`
* `GET /orders/1` -> `200 OK` (Data pesanan beserta items).
* `GET /orders/999` -> `404 Not Found` (`"Pesanan dengan id 999 tidak ditemukan"`).

### GET `/orders/user/{userId}`
* `GET /orders/user/7?status=PENDING` -> `200 OK` (Daftar pesanan terurut dari yang terbaru).
* `GET /orders/user/7?status=SHIPPED` -> `422 Unprocessable Content` (`"Status harus PENDING atau PAID"`).

### PATCH `/orders/{id}/pay`
* `PATCH /orders/1/pay` -> `200 OK` (Status berubah menjadi `PAID`).
* `PATCH /orders/1/pay` (ulang) -> `409 Conflict` (`"Pesanan berstatus PAID, hanya pesanan PENDING yang dapat dibayar"`).

---

## 5. Environment Variables (`order-service`)

| Variable | Nilai Default | Penjelasan |
| :--- | :--- | :--- |
| `APP_DEBUG` | `true` | Menampilkan trace teknis error saat development |
| `DB_HOST` | `order-db` | Host container MySQL order-service |
| `DB_PORT` | `3306` | Port internal MySQL (`3307` pada host) |
| `DB_DATABASE` | `orderdb` | Nama database MySQL |
| `PRODUCT_SERVICE_URL` | `http://product-service:3000` | URL REST API product-service |
| `CART_SERVICE_URL` | `http://cart-service:3001` | URL REST API cart-service |
| `SERVICE_TIMEOUT` | `5` | Batas waktu timeout inter-service (detik) |

---

## 6. Keputusan Desain & Keterbatasan

1. **Snapshot Harga & Nama Produk**:
   * Nama dan harga produk disalin saat transaksi berlangsung ke tabel `order_items`. Hal ini menjamin integritas data historis pesanan agar tidak terpengaruh jika harga barang diubah di kemudian hari pada `product-service`.
2. **Transaksi Atomik**:
   * `Order` dan `OrderItem` disimpan menggunakan `DB::transaction`. Jika salah satu proses penyimpanan gagal, seluruh data di-rollback.
3. **Pengosongan Keranjang Non-Blocking**:
   * Jika pesanan berhasil disimpan tetapi cart-service gagal dikosongkan karena masalah jaringan, pesanan tidak dibatalkan (tetap 201) dan kendala dicatat ke log error sistem.
4. **Keterbatasan Sistem**:
   * Sistem belum menerapkan asynchronous message broker (seperti RabbitMQ) untuk transaksi terdistribusi, serta pengurangan stok di `product-service` masih di luar cakupan kebutuhan tugas.

---

## 7. Video Demo

* **Tautan Video**: [Tautan Video Demo Anda]
* **Timestamps**:
  * `00:00` - Pembukaan & Profil
  * `01:00` - Arsitektur & Struktur Lapisan Order Service
  * `03:00` - Menjalankan Docker Compose
  * `04:00` - Demonstrasi Endpoint & Skenario Error
  * `09:00` - Keputusan Desain & Keterbatasan
  * `10:30` - Penutup
