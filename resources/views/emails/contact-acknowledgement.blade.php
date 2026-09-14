<p>Hello {{ $enquiry->full_name }},</p>
<p>Thank you. We’ve received your LensPic enquiry and will contact you soon.</p>
<p>Your enquiry type: {{ ucwords(str_replace('_', ' ', $enquiry->enquiry_type)) }}</p>
<p>LensPic</p>
