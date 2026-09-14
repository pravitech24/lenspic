<p>A new LensPic public enquiry was received.</p>
<p><strong>Reference:</strong> {{ $enquiry->uuid }}</p>
<p><strong>Name:</strong> {{ $enquiry->full_name }}</p>
<p><strong>Business:</strong> {{ $enquiry->business_name ?: 'Not provided' }}</p>
<p><strong>Email:</strong> {{ $enquiry->email }}</p>
<p><strong>Phone:</strong> {{ $enquiry->phone_country_code }} {{ $enquiry->phone_number }}</p>
<p><strong>Country:</strong> {{ $enquiry->country }}</p>
<p><strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $enquiry->enquiry_type)) }}</p>
<p>Open the authorized administration workflow to review the complete message. It is intentionally omitted from email.</p>
