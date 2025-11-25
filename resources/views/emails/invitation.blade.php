<!DOCTYPE html>
<html>
<head>
    <title>Undangan Penilaian</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
        <h2 style="color: #4F46E5;">Permohonan Penilaian 360°</h2>
        
        <p>Yth. Bapak/Ibu <strong>{{ $request->assessor_name }}</strong>,</p>
        
        <p>Anda telah dinominasikan sebagai <strong>{{ ucfirst($request->relationship) }}</strong> untuk memberikan penilaian kepemimpinan terhadap:</p>
        
        <p style="background-color: #f3f4f6; padding: 10px; border-radius: 5px;">
            <strong>Nama:</strong> {{ $request->user->name }}<br>
            <strong>Jabatan:</strong> {{ $request->user->jabatan ?? '-' }}
        </p>
        
        <p>Mohon kesediaan waktu Anda untuk mengisi kuesioner singkat (kurang dari 10 menit) melalui tautan berikut:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('assessment.show', $request->access_token) }}" style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Mulai Penilaian
            </a>
        </div>
        
        <p style="font-size: 12px; color: #777;">
            Jika tombol di atas tidak berfungsi, salin tautan ini ke browser Anda:<br>
            {{ route('assessment.show', $request->access_token) }}
        </p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin-top: 30px;">
        <p style="font-size: 12px; color: #999; text-align: center;">
            Sistem Penilaian Kepemimpinan 7 Bintang<br>
            Ini adalah email otomatis, mohon tidak membalas email ini.
        </p>
    </div>
</body>
</html>