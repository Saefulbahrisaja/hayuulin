# HAYUULIN – Hybrid Artificial Intelligence for Your Urban and Local Intelligent Navigation

Aplikasi Laravel untuk pencarian destinasi, rekomendasi perjalanan, optimasi urutan kunjungan, peta Leaflet, dan perencanaan itinerary. HAYUULIN adalah singkatan dari **Hybrid Artificial Intelligence for Your Urban and Local Intelligent Navigation**.

## Kebutuhan

- PHP 8.1+
- Composer
- MySQL/MariaDB

## Menjalankan aplikasi

1. Pastikan konfigurasi koneksi database pada file `.env` milik Anda sudah benar.
2. Jalankan:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve
   ```

3. Buka URL yang ditampilkan oleh `php artisan serve`.
4. Buat/promosikan administrator menggunakan perintah interaktif:

   ```powershell
   php artisan app:make-admin admin@example.com
   ```

   Perintah akan meminta nama dan kata sandi admin baru secara tersembunyi, atau hanya mempromosikan akun yang sudah ada. Tidak ada akun admin dan kata sandi bawaan.

## Fitur wisatawan

- Peta GIS Leaflet dengan marker kategori/wilayah, pencarian nama/alamat, filter kategori, rating, dan wilayah.
- Rekomendasi berdasarkan preferensi seimbang, rating, tiket termurah, atau jarak; pilihan anggaran, transportasi, jumlah orang, durasi, dan penginapan.
- ACO mengoptimalkan urutan kunjungan. Itinerary dibagi menurut durasi harian dan menampilkan jarak antartitik.
- Estimasi biaya transportasi, tiket, makan, penginapan, dan parkir; pilihan kendaraan dan tarif dikelola oleh admin.
- Informasi trayek dan instruksi perjalanan, perbandingan pilihan kendaraan, hotel/homestay/villa, tarif per malam, kapasitas, rating, serta fasilitas.
- Akun wisatawan untuk mengatur durasi kunjungan per destinasi, menyimpan, membuka, menghapus, dan mencetak itinerary atau menyimpannya sebagai PDF dari dialog cetak browser.
- Rekomendasi content-based + collaborative memakai kategori/minat, rating yang tersedia, harga tiket yang diketahui, dan pola itinerary tersimpan; ACO tetap mengoptimalkan urutan kunjungan, kemudian GIS Leaflet menampilkan hasilnya.
- Pengelompokan destinasi menggunakan K-Means; asisten wisata melakukan pencocokan bahasa sederhana pada katalog lokal.
- Wisatawan dapat mengirim ulasan yang dianalisis dengan Multinomial Naive Bayes korpus awal bahasa Indonesia. Prediksi hanya eksperimental dan belum tervalidasi pada ulasan lokal.
- Wisatawan dapat melaporkan biaya perjalanan aktual. Random Forest Regression hanya dilatih dari laporan aktual dan mulai digunakan setelah minimal 12 catatan; sebelum itu, rincian tarif yang ada ditampilkan sebagai estimasi aturan.

## Fitur administrator

- Dashboard ringkasan destinasi, kategori, transportasi, trayek, penginapan, pengguna, dan itinerary terbaru.
- CRUD kategori, destinasi dan koordinat GIS, pilihan transportasi, trayek, akomodasi, tarif, dan akun pengguna.
- Kelola peran administrator. Pendaftaran publik selalu membuat akun wisatawan, administrator terakhir tidak dapat dicabut atau dihapus.
- Pratinjau dan impor terbatas tempat OpenStreetMap dari panel admin; data impor tetap draf sampai diperiksa dan diverifikasi.
- Dashboard Machine Learning memungkinkan admin mencatat jumlah pengunjung aktual beserta sumber dan status hari libur untuk prediksi kunjungan (Random Forest setelah minimal 12 catatan per destinasi).

## API aplikasi mobile wisatawan

API JSON berversi tersedia di `/api/v1`. API ini ditujukan untuk aplikasi mobile wisatawan; akun admin tidak dapat masuk atau menggunakan endpoint privat. Endpoint katalog dapat dibaca tanpa token dan hanya menampilkan data destinasi/penginapan yang telah diverifikasi.

Autentikasi memakai Laravel Sanctum bearer token:

1. `POST /api/v1/auth/register` dengan `name`, `email`, `password`, `password_confirmation`, dan `device_name`, atau `POST /api/v1/auth/login` dengan `email`, `password`, dan `device_name`.
2. Kirim token yang diterima pada header `Authorization: Bearer <access_token>` dan header `Accept: application/json`.
3. Gunakan `GET /api/v1/auth/me` untuk profil dan `POST /api/v1/auth/logout` untuk mencabut token perangkat saat ini. Logout dari perangkat lain memerlukan token perangkat tersebut.

Endpoint katalog publik:

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/v1/categories` | Kategori dan jumlah destinasi terverifikasi |
| GET | `/api/v1/map` | Pengaturan peta dan marker GeoJSON destinasi/penginapan terverifikasi; filter `layer`, `category_id`, `region`, batas `south`, `west`, `north`, `east`, serta paginasi `limit`/`offset` |
| GET | `/api/v1/destinations` | Daftar destinasi; filter opsional `category_id`, `region`, `q`, `min_rating`, `per_page` |
| GET | `/api/v1/destinations/{id}` | Detail destinasi terverifikasi |
| GET | `/api/v1/accommodations` | Penginapan terverifikasi; filter `region`, `type`, `max_price`, `per_page` |
| GET | `/api/v1/transport-options` | Pilihan transportasi aktif |
| GET | `/api/v1/transport-routes` | Trayek aktif; filter wilayah dan pilihan transportasi |

Endpoint yang memerlukan bearer token wisatawan:

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/v1/trips/recommend` | Rekomendasi personal, optimasi urutan rute, dan estimasi biaya |
| POST | `/api/v1/trips` | Hitung ulang pilihan perjalanan di server dan simpan itinerary |
| GET | `/api/v1/trips` | Daftar itinerary milik wisatawan yang masuk |
| GET | `/api/v1/trips/{id}` | Detail itinerary milik sendiri |
| PATCH | `/api/v1/trips/{id}/actual-cost` | Simpan biaya aktual untuk perjalanan sendiri |
| DELETE | `/api/v1/trips/{id}` | Hapus itinerary sendiri |
| POST | `/api/v1/destinations/{id}/reviews` | Tulis atau perbarui ulasan destinasi |
| POST | `/api/v1/assistant` | Tanya asisten berdasarkan katalog lokal |

Contoh body rekomendasi (gunakan juga untuk membuat itinerary, dengan tambahan `title`):

```json
{
  "origin_name": "Kota Sukabumi",
  "origin_latitude": -6.9193,
  "origin_longitude": 106.9267,
  "budget": 1500000,
  "days": 2,
  "travel_date": "2026-12-20",
  "travelers": 4,
  "preference": "balanced",
  "include_accommodation": true,
  "is_holiday": false
}
```

`category_id`, `region`, `min_rating`, `max_distance`, `transport_option_id`, dan `q` bersifat opsional. `preference` menerima `balanced`, `rating`, `budget`, atau `distance`. API mengembalikan JSON dengan objek `data`; respons daftar juga menyertakan `meta` paginasi. Kesalahan validasi mengembalikan HTTP 422. Pembuatan itinerary selalu dihitung ulang oleh server—payload rute atau biaya dari aplikasi mobile tidak dipercaya. Prediksi, jarak, dan tarif tetap berupa perkiraan sesuai ketersediaan data aplikasi.

`GET /api/v1/map` mengembalikan konfigurasi peta OpenStreetMap dan marker berformat GeoJSON. Koordinat GeoJSON memakai urutan `[longitude, latitude]`. Parameter `layer` menerima `all`, `destinations`, atau `accommodations`; batas viewport harus dikirim berpasangan sebagai `south`, `west`, `north`, dan `east`. Respons dibatasi 500 marker per permintaan; gunakan `limit` (maksimum 500) dan `offset` untuk memuat halaman selanjutnya. Respons rekomendasi dan itinerary tersimpan juga berisi `map.routes`: garis GeoJSON `LineString` per hari yang menghubungkan titik awal, destinasi sesuai urutan, lalu kembali ke titik awal. Garis ini hanya visualisasi urutan dan jarak perkiraan, bukan navigasi jalan atau rute lalu lintas. Aplikasi mobile dapat menggambar peta dengan `tile_url_template` dan wajib menampilkan `attribution`. Server ubin publik OpenStreetMap disediakan tanpa billing tetapi memiliki kebijakan penggunaan dan bukan layanan ubin/offline tanpa batas; untuk pemakaian tinggi gunakan penyedia ubin yang sesuai atau host sendiri.

## Data dan asumsi

Seeder menyediakan contoh nama/lokasi destinasi dan penginapan serta tarif transportasi indikatif untuk membantu uji coba. Destinasi dan penginapan contoh tidak ditampilkan untuk umum sebelum admin memverifikasi data. Tiket dan harga kamar yang belum diketahui tidak ditandai gratis atau digunakan dalam rekomendasi; harga kamar hanya digunakan setelah admin mengonfirmasikannya. Tarif transportasi/trayek dan biaya tambahan ditampilkan sebagai simulasi indikatif, bukan harga real-time, dan tetap harus dikonfirmasi kepada operator/admin.

Admin dapat mencari data lokasi OpenStreetMap dengan memasukkan nama kabupaten/kota; nama tanpa awalan juga dicoba dengan awalan “Kabupaten” dan “Kota”. Jika pencarian peta tidak tersedia, destinasi dan penginapan tetap dapat dibuat manual dari katalog admin dengan mengisi wilayah dan informasi lokasi yang diketahui. Data lokasi terbuka diambil atas permintaan admin melalui Overpass API. Jika layanan utama sedang sibuk, aplikasi mencoba hingga dua layanan Overpass publik lain secara bergantian. Setiap layanan dibatasi 12 detik, dengan waktu kueri 10 detik, agar proses tidak melewati batas waktu PHP. URL dapat diatur melalui `OPENSTREETMAP_OVERPASS_URL` dan `OPENSTREETMAP_OVERPASS_FALLBACK_URLS` di `.env`; setelah mengubahnya, jalankan `php artisan config:clear` bila konfigurasi telah di-cache. Tidak menggunakan Google Places/Routes maupun Nominatim untuk unduhan massal. Overpass publik dapat membatasi permintaan dan kelengkapan data; impor dibatasi 120 objek dan hasil pratinjau disimpan 20 menit. Setiap data perlu ditinjau terhadap sumber operasional resmi sebelum ditayangkan. Data OpenStreetMap © kontributor OpenStreetMap, lisensi ODbL; pertahankan atribusi dan patuhi ketentuan lisensi.

Rute yang digambar pada peta dan jarak/waktu dari koordinat adalah estimasi, bukan navigasi jalan real-time. Tarif transportasi dihitung dari konfigurasi admin dan kapasitas kendaraan; operator serta biaya aktual harus dikonfirmasi. Penginapan hanya digunakan dalam estimasi bila lokasi, status, dan harga per malam sudah dikonfirmasi.

## Machine Learning: kualitas data

Model berjalan lokal di Laravel tanpa layanan AI berbayar. Rekomendasi collaborative filtering memakai **itinerary tersimpan sebagai sinyal minat**, bukan bukti perjalanan benar-benar terjadi. Biaya aktual dan jumlah kunjungan perlu dilaporkan/dicatat dari sumber yang dapat dipercaya; model regresi tidak menampilkan prediksi saat jumlah sampel belum memadai. Korpus Naive Bayes dan K-Means merupakan baseline awal, bukan model produksi tervalidasi. Hasil ML bersifat pendukung dan tidak menggantikan konfirmasi pengelola, operator, atau kondisi lapangan.

## Pengujian

```powershell
php artisan test
php vendor/bin/pint --test app\Http\Controllers app\Models app\Services app\Console\Commands database\migrations database\seeders routes\web.php tests
```
