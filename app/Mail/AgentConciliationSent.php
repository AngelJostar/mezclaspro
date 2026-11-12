<?php

namespace App\Mail;

use App\Models\HospitalConciliationSubmission;
use Illuminate\Mail\Mailable;

class AgentConciliationSent extends Mailable
{
    public function __construct(public HospitalConciliationSubmission $submission) {}

    public function build()
    {
        return $this->subject('Solicitud de conciliación '.$this->submission->folio())
            ->view('emails.agent-conciliation-sent');
    }
}
