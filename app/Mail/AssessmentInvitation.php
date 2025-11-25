<?php

namespace App\Mail;

use App\Models\AssessmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AssessmentInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $request;

    // Terima data undangan saat class dipanggil
    public function __construct(AssessmentRequest $request)
    {
        $this->request = $request;
    }

    public function build()
    {
        return $this->subject('Undangan Penilaian Kepemimpinan 7 Bintang')
                    ->view('emails.invitation'); // Kita akan buat view ini di langkah 2
    }
}