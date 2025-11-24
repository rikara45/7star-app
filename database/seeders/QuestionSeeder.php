<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel sebelum isi ulang
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('master_questions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $questions = [
            // 1. Intellectual Stimulation
            [
                'dimension' => 'Intellectual Stimulation',
                'statement_self' => 'Saya terbiasa mengajak rekan/mahasiswa untuk melihat masalah dari sudut pandang yang berbeda.',
                'statement_other' => 'Yang bersangkutan mendorong rekan/mahasiswa melihat persoalan dari sudut pandang yang berbeda.'
            ],
            [
                'dimension' => 'Intellectual Stimulation',
                'statement_self' => 'Saya merasa nyaman ketika harus mencari lebih dari satu cara untuk menyelesaikan persoalan.',
                'statement_other' => 'Yang bersangkutan mencari berbagai alternatif solusi dalam menyelesaikan masalah.'
            ],
            [
                'dimension' => 'Intellectual Stimulation',
                'statement_self' => 'Saya mendorong orang di sekitar saya untuk berani mencoba ide baru, walaupun ada risiko gagal.',
                'statement_other' => 'Yang bersangkutan memberi ruang bagi rekan/mahasiswa mencoba ide baru meskipun berisiko.'
            ],

            // 2. Inspirational Motivation
            [
                'dimension' => 'Inspirational Motivation',
                'statement_self' => 'Saya sering berbagi semangat agar rekan/mahasiswa tetap berfokus pada tujuan bersama.',
                'statement_other' => 'Yang bersangkutan menumbuhkan semangat kerja untuk mencapai tujuan bersama.'
            ],
            [
                'dimension' => 'Inspirational Motivation',
                'statement_self' => 'Saya berusaha menyampaikan harapan atau target dengan cara yang membuat orang lain termotivasi.',
                'statement_other' => 'Yang bersangkutan menyampaikan target atau harapan dengan cara yang memotivasi.'
            ],
            [
                'dimension' => 'Inspirational Motivation',
                'statement_self' => 'Saya merasa senang memberikan apresiasi (pujian, ucapan, simbol kecil) ketika orang lain berprestasi.',
                'statement_other' => 'Yang bersangkutan memberikan apresiasi nyata atas pencapaian orang lain.'
            ],

            // 3. Idealized Influence
            [
                'dimension' => 'Idealized Influence',
                'statement_self' => 'Saya berusaha menjaga integritas dan disiplin sehingga orang lain dapat menilai saya sebagai teladan.',
                'statement_other' => 'Yang bersangkutan menunjukkan integritas dan disiplin sebagai teladan.'
            ],
            [
                'dimension' => 'Idealized Influence',
                'statement_self' => 'Saya tetap konsisten dengan nilai yang saya yakini, meski dalam kondisi sulit.',
                'statement_other' => 'Yang bersangkutan konsisten memegang nilai meskipun dalam kondisi sulit.'
            ],
            [
                'dimension' => 'Idealized Influence',
                'statement_self' => 'Saya terbuka menyampaikan pencapaian atau kekurangan diri sendiri sebagai bentuk tanggung jawab.',
                'statement_other' => 'Yang bersangkutan terbuka menyampaikan pencapaian maupun kekurangannya.'
            ],

            // 4. Empowerment
            [
                'dimension' => 'Empowerment',
                'statement_self' => 'Saya sering melibatkan rekan/mahasiswa untuk ikut mengambil peran dalam kegiatan atau tugas.',
                'statement_other' => 'Yang bersangkutan melibatkan rekan/mahasiswa dalam proses peran atau keputusan.'
            ],
            [
                'dimension' => 'Empowerment',
                'statement_self' => 'Saya memberi ruang kepada orang lain untuk mengambil keputusan sesuai bidang atau keahliannya.',
                'statement_other' => 'Yang bersangkutan memberikan kepercayaan sesuai kompetensi masing-masing.'
            ],
            [
                'dimension' => 'Empowerment',
                'statement_self' => 'Saya merasa penting mendampingi orang lain agar lebih percaya diri menjalankan tanggung jawabnya.',
                'statement_other' => 'Yang bersangkutan membantu meningkatkan rasa percaya diri rekan dalam menjalankan tugas.'
            ],

            // 5. Innovation
            [
                'dimension' => 'Innovation',
                'statement_self' => 'Saya terbuka terhadap cara-cara baru dalam mengajar, meneliti, atau bekerja.',
                'statement_other' => 'Yang bersangkutan terbuka pada pendekatan baru dalam mengajar/meneliti/bekerja.'
            ],
            [
                'dimension' => 'Innovation',
                'statement_self' => 'Saya berusaha menyimpan atau membagikan ide/inovasi agar bisa dipelajari orang lain.',
                'statement_other' => 'Yang bersangkutan mendiseminasikan ide atau praktik inovatif.'
            ],
            [
                'dimension' => 'Innovation',
                'statement_self' => 'Saya mendorong diri sendiri dan orang lain untuk mengembangkan gagasan menjadi rencana nyata.',
                'statement_other' => 'Yang bersangkutan mendorong pengembangan gagasan menjadi tindakan nyata.'
            ],

            // 6. Individualized Consideration
            [
                'dimension' => 'Individualized Consideration',
                'statement_self' => 'Saya berusaha memahami perbedaan kebutuhan atau potensi tiap orang di sekitar saya.',
                'statement_other' => 'Yang bersangkutan memahami kebutuhan dan potensi individu.'
            ],
            [
                'dimension' => 'Individualized Consideration',
                'statement_self' => 'Saya memberi perhatian khusus pada perkembangan pribadi rekan/mahasiswa.',
                'statement_other' => 'Yang bersangkutan memberi perhatian terhadap perkembangan individu.'
            ],
            [
                'dimension' => 'Individualized Consideration',
                'statement_self' => 'Saya merasa penting mencatat atau mengingat aspirasi orang lain untuk mendukung kemajuan mereka.',
                'statement_other' => 'Yang bersangkutan mempertimbangkan aspirasi dalam pengambilan keputusan.'
            ],

            // 7. Transformational Traits
            [
                'dimension' => 'Transformational Traits',
                'statement_self' => 'Saya berusaha menularkan visi atau mimpi besar yang saya yakini kepada orang lain.',
                'statement_other' => 'Yang bersangkutan mengomunikasikan visi/gagasan besar secara inspiratif.'
            ],
            [
                'dimension' => 'Transformational Traits',
                'statement_self' => 'Saya dapat melihat hubungan antara pencapaian kecil dengan tujuan yang lebih besar.',
                'statement_other' => 'Yang bersangkutan menghubungkan pencapaian kecil dengan tujuan strategis.'
            ],
            [
                'dimension' => 'Transformational Traits',
                'statement_self' => 'Saya merasa penting menekankan nilai perubahan atau perbaikan dalam setiap kegiatan.',
                'statement_other' => 'Yang bersangkutan mendorong perubahan dan perbaikan berkelanjutan.'
            ],

            // 8. Digital Literacy
            [
                'dimension' => 'Digital Literacy',
                'statement_self' => 'Saya terbiasa menggunakan platform digital untuk mendukung komunikasi atau kerja sama.',
                'statement_other' => 'Yang bersangkutan menggunakan teknologi digital untuk mendukung koordinasi dan kolaborasi.'
            ],
            [
                'dimension' => 'Digital Literacy',
                'statement_self' => 'Saya berhati-hati menjaga keamanan data saat menggunakan teknologi.',
                'statement_other' => 'Yang bersangkutan menjaga keamanan dan etika penggunaan data digital.'
            ],
            [
                'dimension' => 'Digital Literacy',
                'statement_self' => 'Saya memanfaatkan aplikasi daring untuk meningkatkan efektivitas kerja tim.',
                'statement_other' => 'Yang bersangkutan memanfaatkan aplikasi/platform daring untuk meningkatkan efektivitas kerja tim.'
            ],

            // 9. Dua Arah Komunikasi
            [
                'dimension' => 'Dua Arah Komunikasi',
                'statement_self' => 'Saya terbuka menerima masukan langsung dari rekan/mahasiswa dalam berbagai kesempatan.',
                'statement_other' => 'Yang bersangkutan terbuka menerima umpan balik dari rekan/mahasiswa.'
            ],
            [
                'dimension' => 'Dua Arah Komunikasi',
                'statement_self' => 'Saya menyediakan ruang komunikasi (tatap muka, chat, atau email) untuk diskusi bersama.',
                'statement_other' => 'Yang bersangkutan menyediakan kanal komunikasi yang jelas untuk diskusi/koordinasi.'
            ],
            [
                'dimension' => 'Dua Arah Komunikasi',
                'statement_self' => 'Saya berusaha menindaklanjuti masukan orang lain agar terasa dihargai.',
                'statement_other' => 'Yang bersangkutan menindaklanjuti masukan dengan tindakan yang terukur.'
            ],
        ];

        DB::table('master_questions')->insert($questions);
        
        // Buat ulang periode penilaian karena tabel terhapus saat fresh migrate
        DB::table('assessment_periods')->insert([
            'name' => 'Periode Penilaian Awal',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Panggil AdminSeeder juga agar akun admin tidak hilang
        $this->call(AdminSeeder::class);
    }
}