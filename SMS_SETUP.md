# MSG91 SMS Setup Instructions

This guide explains how to set up MSG91 SMS gateway for sending attendance notifications to students.

## What is MSG91?

MSG91 is a popular Indian SMS gateway service that provides:
- Transactional SMS (Route 3) - for important alerts
- Promotional SMS (Route 4) - for marketing
- Competitive pricing (~₹0.15-0.25 per SMS)
- Easy API integration
- Reliable delivery

## Setup Steps

### 1. Create MSG91 Account

1. Visit [MSG91 Website](https://msg91.com)
2. Click "Sign Up" or "Register"
3. Fill in your details:
   - Name
   - Email
   - Phone number
   - Company name (PVPSIT)
4. Verify your email and phone number
5. Complete KYC (required for SMS sending in India):
   - Upload PAN card
   - Upload address proof
   - Provide company details

### 2. Get API Credentials

1. Login to MSG91 dashboard
2. Go to "API" section
3. Copy your **Auth Key** (also called API Key)
4. Create a **Sender ID**:
   - Go to "Sender ID" section
   - Request new sender ID (e.g., "PVPSIT")
   - Wait for approval (usually 1-2 hours)
   - Approved sender ID: 6 characters, alphanumeric

### 3. Add Credits

1. Go to "Billing" section
2. Add credits (minimum ₹500 usually)
3. Pricing:
   - Transactional SMS: ₹0.15-0.20 per SMS
   - Promotional SMS: ₹0.20-0.25 per SMS
4. For 495 students daily:
   - At ₹0.20/SMS = ₹99/day
   - Monthly cost: ~₹3,000

### 4. Configure Script

Edit `send_daily_attendance.php` and update these lines:

```php
// SMS configuration (MSG91)
define('MSG91_AUTH_KEY', 'YOUR_ACTUAL_AUTH_KEY'); // Replace with your MSG91 auth key
define('MSG91_SENDER_ID', 'PVPSIT'); // Your approved sender ID
define('MSG91_ROUTE', '4'); // Route 4 for promotional, Route 3 for transactional
define('MSG91_COUNTRY', '91'); // India country code
```

**Important:**
- Replace `YOUR_MSG91_AUTH_KEY` with your actual MSG91 auth key
- Use your approved sender ID (e.g., "PVPSIT")
- Choose Route 3 for transactional SMS (important alerts)
- Choose Route 4 for promotional SMS (general updates)

### 5. Test SMS Sending

Before running the full script, test with a single student:

```php
// Create test_sms.php
<?php
require_once 'config.php';
require_once 'send_daily_attendance.php';

$testPhone = '91XXXXXXXXXX'; // Your phone number
$testMessage = 'Test SMS from PVPSIT Attendance System';

$result = sendSMS($testPhone, $testMessage);
if ($result) {
    echo "SMS sent successfully!";
} else {
    echo "SMS failed. Check logs.";
}
?>
```

Run: `php test_sms.php`

### 6. Run Full Script

Once SMS is working:

```bash
php send_daily_attendance.php
```

The script will now:
1. Send email to all students
2. Send SMS to students with phone numbers
3. Log both email and SMS results

## SMS Message Format

The SMS message is kept short (under 160 characters):

```
PVPSIT Attendance Update: STUDENT_NAME (ROLL_NO). Attendance: XX%. Status: Safe/Condonation/Detention. Check email for details.
```

## Route Selection

**Route 3 (Transactional SMS):**
- For important alerts
- Delivered 24/7
- Higher priority
- Use for attendance alerts

**Route 4 (Promotional SMS):**
- For general updates
- Delivered only 9 AM to 9 PM
- Lower priority
- Use for general announcements

## Troubleshooting

### SMS not sending
- Check MSG91 auth key is correct
- Verify sender ID is approved
- Ensure sufficient credits in MSG91 account
- Check phone number format (should be 10 digits or 91XXXXXXXXXX)

### "SMS not sent - MSG91 not configured"
- Update MSG91_AUTH_KEY in the script
- Remove 'YOUR_MSG91_AUTH_KEY' placeholder

### "No phone number found"
- Student doesn't have phone number in database
- Update students table with phone numbers

### SMS delivery failed
- Check if phone number is DND (Do Not Disturb)
- Verify phone number is valid
- Check MSG91 dashboard for delivery reports

## Cost Estimation

**Current setup:**
- 495 students with attendance data
- Daily SMS count: ~495
- Cost per SMS: ₹0.20
- **Daily cost: ₹99**
- **Monthly cost: ₹2,970**

**To reduce costs:**
- Send SMS only to students with low attendance (<75%)
- Send SMS weekly instead of daily
- Use Route 4 (promotional) if appropriate

## Advanced Features

### Send SMS only to low attendance students

Edit the script to add this condition:

```php
// Send SMS only if attendance is below 75%
if (!empty($studentPhone) && MSG91_AUTH_KEY !== 'YOUR_MSG91_AUTH_KEY' && $percentage < 75) {
    $smsResult = sendSMS($studentPhone, $smsMessage);
    // ... rest of SMS logic
}
```

### Send SMS weekly instead of daily

Add a check in the script:

```php
// Send SMS only on Mondays
if (date('N') == 1) {
    // SMS sending logic
}
```

## Compliance Notes

**Important for Indian SMS regulations:**
1. Complete KYC with MSG91
2. Use approved sender ID only
3. Follow TRAI regulations
4. Maintain opt-out mechanism
5. Don't send to DND numbers without consent
6. Keep SMS content appropriate
7. Maintain delivery records

## Support

For MSG91 support:
- Email: support@msg91.com
- Phone: +91-9315969606
- Documentation: https://docs.msg91.com

## Security Notes

1. **Never commit MSG91 auth key to version control**
2. **Use environment variables for credentials** (recommended)
3. **Monitor SMS usage regularly**
4. **Set up alerts for low credit balance**
5. **Review delivery reports periodically**

## Alternative SMS Gateways

If MSG91 doesn't work for you, consider:

**Fast2SMS:**
- Free: 50 SMS daily for testing
- Paid: ₹0.10-0.30 per SMS
- Good for small scale

**Twilio:**
- International coverage
- $0.0075 per SMS
- Good for international students

**Exotel:**
- Indian focused
- ₹0.20-0.35 per SMS
- Good for enterprise

## Monitoring

Check MSG91 dashboard regularly for:
- Credit balance
- Delivery reports
- Failed SMS
- API usage statistics
- Sender ID status

## Next Steps

1. Create MSG91 account
2. Complete KYC
3. Get auth key and sender ID
4. Add credits
5. Update script configuration
6. Test SMS sending
7. Run full script
8. Monitor delivery and costs
