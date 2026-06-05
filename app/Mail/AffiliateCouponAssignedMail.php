<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateCouponAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $coupon;

    public function __construct($user, $coupon)
    {
        $this->user = $user;
        $this->coupon = $coupon;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎁 Your Affiliate Coupon Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee_affiliate.coupon_assigned',
            with: [
                'user' => $this->user,
                'coupon' => $this->coupon,
            ],
        );
    }
}
