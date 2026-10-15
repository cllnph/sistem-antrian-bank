<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchContactSeeder extends Seeder
{
    // Branch addresses follow the contact-page screenshot provided by the user.
    // The head-office contact is retained because it is outside the screenshot.
    public const CONTACTS = [
        'pusat' => [
            'address' => 'Jl. Raya Pd. Gede No.9, Jatirahayu, Kec. Pondok Melati, Kota Bekasi, Jawa Barat',
            'phone' => '021-8467944',
        ],
        'bogor' => [
            'address' => 'Jl. Achmad Adnawijaya No.8, RT.07/RW.15, Bantarjati, Kec. Bogor Utara, Kota Bogor, Jawa Barat 16151',
            'phone' => null,
        ],
        'depok' => [
            'address' => 'Ruko ITC Depok, BLK. R No.19, Depok, Kec. Pancoran Mas, Kota Depok, Jawa Barat 16514',
            'phone' => null,
        ],
        'tangerang' => [
            'address' => 'Bpr Dp Taspen, Jl. KH Hasyim Ashari No.8, RT.004/RW.001, Poris Plawad Utara, Kec. Cipondoh, Kota Tangerang, Banten 15141',
            'phone' => null,
        ],
        'jaktim' => [
            'address' => 'Gedung Dana Pensiun Taspen, Jl. Raden Inten II No.1, RT.1/RW.14, Klender, Kec. Duren Sawit, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13470',
            'phone' => null,
        ],
        'karawang' => [
            'address' => 'Jl. Jenderal Ahmad Yani No.37, Tanjungpura, Kec. Karawang Bar., Kabupaten Karawang, Jawa Barat 41315',
            'phone' => null,
        ],
        'cikarang' => [
            'address' => 'Ruko El Premio Delta Mas, No.18, Sukamahi, Kec. Cikarang Pusat, Kabupaten Bekasi, Jawa Barat 17530',
            'phone' => null,
        ],
        'purwokerto' => [
            'address' => 'Jl. Prof. Dr. Suharso No.2, Glempang, Bancarkembar, Kec. Purwokerto Utara, Kabupaten Banyumas, Jawa Tengah 53114',
            'phone' => null,
        ],
        'bekasi' => [
            'address' => 'Ruko Mutiara Bekasi Center Blok B9, Jl. Rawa Tembaga No.5 RT.005/RW.002, Marga Jaya, Kec. Bekasi Sel., Kota Bekasi, Jawa Barat 17141',
            'phone' => null,
        ],
    ];

    public function run(): void
    {
        foreach (self::CONTACTS as $slug => $contact) {
            Branch::where('slug', $slug)->update($contact);
        }
    }
}
