<!DOCTYPE html>
<html>
<head>
    <title>New Contact Us Request</title>
</head>
<body>
    <h2>New Contact Us Request</h2>
    <p>A new contact us request has been submitted with the following details:</p>
    
    <ul>
        <li><strong>Name:</strong> {{ $contactData['name'] }}</li>
        <li><strong>Email:</strong> {{ $contactData['email'] }}</li>
        <li><strong>Subject:</strong> {{ $contactData['subject'] }}</li>
        @if(!empty($contactData['business_name']))
            <li><strong>Bank / Tenant:</strong> {{ $contactData['business_name'] }} (Code: {{ $contactData['business_code'] ?? 'N/A' }})</li>
        @else
            <li><strong>Type:</strong> Central NegoMaster Support (General Inquiry)</li>
        @endif
    </ul>


    <h3>Message:</h3>
    <p>{{ $contactData['message'] }}</p>
    
    <br>
    <p>Thanks,</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
