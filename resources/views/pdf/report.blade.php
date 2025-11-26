<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penilaian</title>
    <style>
        body { font-family: sans-serif; color: #333; margin: 0; padding: 0; }
        
        /* STYLE KOP SURAT BARU (MENGGUNAKAN TABEL) */
        .header-table {
            width: 100%;
            border-bottom: 3px solid #000; 
            padding-bottom: 10px; 
            margin-bottom: 25px;
        }
        .logo-cell {
            width: 100px; /* Lebar tetap untuk area logo */
            vertical-align: middle;
            text-align: left;
        }
        .logo-img {
            width: 90px; /* Ukuran gambar logo */
            height: auto;
        }
        .text-cell {
            vertical-align: middle;
            text-align: center;
            padding-right: 100px; /* Padding kanan buatan agar teks benar-benar di tengah kertas */
        }
        .uni-name {
            font-size: 16pt; /* Sedikit dikecilkan agar muat */
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }
        .uni-address {
            font-size: 10pt;
            margin: 5px 0;
            line-height: 1.4;
        }

        /* STYLE KONTEN LAIN */
        .title { text-align: center; font-size: 14pt; font-weight: bold; margin-bottom: 20px; text-decoration: underline; margin-top: 10px; }
        
        .profile-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .profile-table td { padding: 6px 0; font-size: 11pt; }
        .label { font-weight: bold; width: 140px; }

        .score-box { 
            text-align: center; 
            padding: 15px; 
            border: 1px solid #ddd; 
            background-color: #f9f9f9; 
            margin-bottom: 25px; 
        }
        .stars { color: #d4af37; font-size: 24pt; margin: 5px 0; } 
        
        .section-title { 
            font-size: 12pt; 
            font-weight: bold; 
            background-color: #eee; 
            padding: 8px; 
            margin-top: 25px; 
            border-left: 5px solid #4F46E5; 
            margin-bottom: 10px;
        }
        
        .detail-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .detail-table td { padding: 6px; border-bottom: 1px solid #eee; }
        .detail-table tr:last-child td { border-bottom: none; }

        .content-text { 
            font-size: 11pt; 
            line-height: 1.6; 
            text-align: justify; 
        }
        
        .footer { 
            position: fixed; 
            bottom: -30px; 
            left: 0; 
            right: 0; 
            font-size: 9pt; 
            text-align: center; 
            color: #777; 
            border-top: 1px solid #eee; 
            padding-top: 10px; 
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($setting->uni_logo_path)
                    <img src="{{ public_path('storage/' . $setting->uni_logo_path) }}" class="logo-img">
                @endif
            </td>
            <td class="text-cell">
                <h1 class="uni-name">{{ $setting->uni_name }}</h1>
                <div class="uni-address">{{ $setting->uni_address }}</div>
                <div class="uni-address" style="color: #555;">{{ $setting->uni_website }}</div>
            </td>
        </tr>
    </table>

    <div class="title">LAPORAN HASIL PENILAIAN KEPEMIMPINAN 7 BINTANG</div>

    <table class="profile-table">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td>: <strong>{{ $user->name }}</strong></td>
        </tr>
        <tr>
            <td class="label">Jabatan/Unit</td>
            <td>: {{ $user->jabatan ?? '-' }} / {{ $user->unit_kerja ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Cetak</td>
            <td>: {{ now()->format('d F Y') }}</td>
        </tr>
    </table>

    <div class="score-box">
        <div style="font-size: 10pt; text-transform: uppercase; letter-spacing: 1px; color: #555;">Hasil Klasifikasi</div>
        <div class="stars">
            {{ $analysis->star_rating }}
        </div>
        <div style="font-size: 12pt;">Total Skor: <strong>{{ $analysis->final_score }}</strong> / 100</div>
    </div>

    <div class="section-title">Rincian Skor 360° (Perilaku)</div>
    <table class="detail-table">
        @foreach($dimensionDetails as $dim => $score)
        <tr>
            <td>{{ $dim }}</td>
            <td style="text-align: right; font-weight: bold; width: 50px;">{{ number_format($score, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <div class="section-title">Analisis & Rekomendasi AI</div>
    <div class="content-text">
        {!! \Illuminate\Support\Str::markdown($analysis->ai_narrative) !!}
    </div>

    <div class="footer">
        Dokumen ini dicetak otomatis oleh Sistem Penilaian {{ $setting->uni_name }}.<br>
        Validitas data dijamin oleh sistem digital.
    </div>

</body>
</html>