<?php

namespace App\Http\Controllers;

use App\Mail\ContactEnquiryAcknowledgement;
use App\Mail\ContactEnquiryReceived;
use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactEnquiryController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required','string','max:120'],
            'business_name' => ['nullable','string','max:160'],
            'email' => ['required','email:rfc','max:254'],
            'phone_country_code' => ['required','regex:/^\+[1-9]\d{0,3}$/'],
            'phone_number' => ['required','regex:/^[0-9][0-9\s-]{5,18}[0-9]$/'],
            'country' => ['required','string','max:100'],
            'enquiry_type' => ['required','in:book_demo,sales,technical_support,partnership,billing,general'],
            'message' => ['required','string','min:10','max:5000'],
            'privacy_consent' => ['accepted'],
            'website' => ['nullable','max:0'],
        ], ['website.max' => 'Unable to submit this enquiry.']);

        $fingerprint = hash('sha256', strtolower($validated['email']).'|'.preg_replace('/\D/', '', $validated['phone_number']).'|'.mb_strtolower(trim($validated['message'])));
        if (ContactEnquiry::where('fingerprint', $fingerprint)->where('created_at', '>=', now()->subDay())->exists()) {
            return back()->withInput()->withErrors(['message' => 'We already received this enquiry. Please allow our team time to respond.']);
        }

        $enquiry = ContactEnquiry::create([
            ...collect($validated)->except(['privacy_consent','website'])->all(),
            'uuid' => (string) Str::uuid(), 'fingerprint' => $fingerprint,
            'source' => 'public_contact', 'status' => 'new', 'consented_at' => now(),
        ]);

        Mail::to($enquiry->email)->queue(new ContactEnquiryAcknowledgement($enquiry));
        if (filled(config('marketing.contact.recipient'))) {
            Mail::to(config('marketing.contact.recipient'))->queue(new ContactEnquiryReceived($enquiry));
        }

        return redirect()->route('marketing.contact')->with('success', 'Thank you. We’ve received your enquiry and will contact you soon.');
    }
}
