# Review Request Workflow - ASMR CCTV Trading

**Document Version:** 1.0  
**Last Updated:** 2026-10-28  
**Purpose:** Systematic process for requesting and managing customer reviews  

---

## Overview

This workflow ensures consistent, professional review requests that increase Google ratings and boost local SEO. The system sends automated emails 30 days after service completion, tracks requests, and manages customer feedback.

---

## Review System Architecture

### Components
1. **Automated Email System** (review-request.php)
2. **Database Tracking** (local_seo_review_requests table)
3. **QR Code Generator** (links to Google Reviews)
4. **Dashboard Monitoring** (local-seo-dashboard.php)
5. **Manual Follow-up** (for non-responsive customers)

### Timeline
- **Day 0:** Service completed, contact info captured
- **Day 30:** Automated email sent with review request
- **Day 35:** Dashboard shows pending requests
- **Day 45:** Manual follow-up if no response
- **Day 60:** Final attempt before marking complete

---

## Step-by-Step Workflow

### Step 1: Capture Customer Contact Information

**During Service or Lead Conversion:**

```
Required Information:
- Customer full name
- Email address
- Phone number
- Service type provided
- Service completion date
- Service satisfaction level (optional)
```

**Form Template:**
```html
<form method="POST" action="contact-handler.php">
	<input type="text" name="customer_name" required>
	<input type="email" name="customer_email" required>
	<select name="service_type">
		<option value="CCTV Systems">CCTV Systems</option>
		<option value="Network Solutions">Network Solutions</option>
		<option value="Computer Sales">Computer Sales</option>
		<option value="Computer Repair">Computer Repair</option>
		<option value="IT Maintenance">IT Maintenance</option>
	</select>
	<!-- Other fields -->
</form>
```

### Step 2: Trigger Review Request Email

**Automatically (30 days after service):**

The system automatically sends review requests based on scheduled_date in database.

**Manually (immediate):**

From dashboard or contact handler:

```php
require_once '/local-seo-system/review-request.php';
$review_system = new ReviewRequestSystem($pdo);

$result = $review_system->send_review_request_email(
	'customer@email.com',
	'John Smith',
	'CCTV Installation'
);

if ($result['success']) {
	echo 'Review request sent!';
}
```

### Step 3: Monitor Request Status

**In Dashboard:**
- URL: `/local-seo-system/local-seo-dashboard.php?key=YOUR_KEY`
- Check "Pending Requests" section
- See which customers haven't been sent requests yet
- Filter by service type or status

**Database Query:**
```sql
SELECT customer_name, customer_email, service_type, scheduled_date 
FROM local_seo_review_requests 
WHERE status = 'pending' AND scheduled_date <= NOW() 
ORDER BY scheduled_date ASC;
```

### Step 4: Track Email Delivery

**Automated Tracking:**
- System logs each email sent to database
- Records timestamp and attempt number
- Marks as 'sent' status
- Tracks delivery failures

**Manual Verification:**
- Check email server logs
- Monitor bounce-back emails
- Follow up on failed deliveries

### Step 5: Customer Receives Email

**Email Content:**
- Personalized greeting with customer name
- Thanks them for choosing ASMR CCTV
- Explains why reviews matter
- Provides clear call-to-action button
- Option to contact if issues occurred first

**Email Features:**
- HTML template with branding
- Direct link to Google Reviews (unique per customer)
- QR code for easy mobile scanning
- Contact info if customer has concerns
- Unsubscribe option (GDPR compliant)

### Step 6: Customer Clicks Review Link

**What Happens:**
1. Customer clicks review button in email
2. Lands on Google My Business Review page
3. Prompted to sign into Google account
4. Can then write review (1-5 stars + text)
5. Review posted to Google Business Profile

**QR Code Alternative:**
- Customer scans QR code with phone
- Directed to Google Reviews page
- No login required if already in Google
- Fast process on mobile

### Step 7: Review Appears on Profile

**Timeline:**
- Google review appears within 1-24 hours
- System checks daily for new reviews
- Updates in dashboard automatically
- Shows reviewer name and rating

**What to Do:**
- Monitor new reviews daily
- Respond to all reviews (positive and negative)
- Thank positive reviewers
- Address concerns in negative reviews professionally

### Step 8: Dashboard Updates

**Review Metrics Updated:**
- Total review count
- Average rating
- Recent review date
- Reviewer name and text
- Response status

**Example Dashboard View:**
```
Google Reviews Status
├─ Total Reviews: 15
├─ Average Rating: 4.8★
├─ New This Month: 2
├─ Pending Responses: 1
└─ Last Review: 2 days ago
```

---

## Email Template

### Subject Line
```
Share Your Experience with ASMR CCTV Trading
```

### Email Body Structure

**1. Header (Branded)**
- Company name and logo
- "We'd Love Your Feedback!" heading
- Professional color scheme

**2. Greeting**
```
Hi [Customer Name],

Thank you for choosing ASMR CCTV Trading for your [Service Type] needs! 
We truly appreciate your business.
```

**3. Main Content**
```
Your feedback is invaluable to us. If you're satisfied with our service, 
would you mind sharing your experience with others in the San Mateo community?

[CLICK HERE TO LEAVE A REVIEW] button

It only takes 2 minutes, and your review helps other customers make informed 
decisions while supporting our local business.
```

**4. Why Reviews Matter**
- Help other San Mateo families and businesses
- Build trust in the community
- Help us improve our services
- Support a local company

**5. Alternative Contact**
```
Have Questions or Issues?

If you had any concerns with our service, we'd prefer to hear about them 
directly before you post a review.

Phone: +63 976 382 3899
Email: inquiries@asmrcctvtechnologies.com
```

**6. Closing**
```
Thank you again for choosing ASMR CCTV Trading. 
We look forward to serving you again!

Best regards,
The ASMR CCTV Trading Team
```

**7. Footer**
- Copyright
- Unsubscribe link
- Company address

---

## QR Code Generation

### How QR Codes Work
1. Generate QR code linking to Google Reviews
2. Embed in email
3. Customer scans with phone camera
4. Redirects to review page
5. No login or clicks needed

### Generate QR Code

```php
$review_url = 'https://search.google.com/local/writereview?placeid=YOUR_PLACE_ID';
$qr_url = $review_system->generate_qr_code($review_url);

// Display in email
echo '<img src="' . $qr_url . '" alt="Leave a Review QR Code">';
```

### Get Your Place ID

1. Go to [Google My Business](https://business.google.com/)
2. Select your business
3. Click "Info" in left sidebar
4. Find "Place ID" in URL or Settings
5. Copy the ID (starts with ChIJ)

---

## Managing Negative Reviews

### Prevention Strategy

**Before Customer Leaves:**
- Confirm satisfaction during service
- Offer immediate fixes for issues
- Provide direct contact if problem occurs
- Ask dissatisfied customers to call before reviewing

### Response to Negative Review

**Within 24 Hours:**
1. Read review completely
2. Don't respond emotionally
3. Acknowledge customer's concern
4. Apologize sincerely (even if not fully your fault)
5. Offer solution (discount, redo service, refund)
6. Provide direct contact number

**Example Response:**
```
Thank you for bringing this to our attention, [Name]. We're sorry that 
[specific issue] occurred. We pride ourselves on quality service, and we 
fell short of that standard.

Here's what we can do: [specific solution]. We'd like the opportunity to 
make this right.

Please call us at +63 976 382 3899 or email inquiries@asmrcctvtechnologies.com 
so we can resolve this immediately.

Thank you for your patience.
```

**Follow-up:**
- Contact customer within 24 hours
- Genuinely resolve the issue
- Ask customer to update their review if resolved
- Don't pressure, but offer incentive ("If you're satisfied now, a review update 
would help us tremendously")

---

## Review Management Schedule

### Daily (5 min)
- [ ] Check for new reviews
- [ ] Respond to any pending reviews
- [ ] Monitor email bounces

### Weekly (30 min)
- [ ] Review dashboard metrics
- [ ] Check pending requests
- [ ] Send manual follow-ups (if needed)
- [ ] Monitor all platforms (Google, Facebook, etc.)

### Monthly (1 hour)
- [ ] Generate review performance report
- [ ] Analyze review content for trends
- [ ] Plan improvements based on feedback
- [ ] Update testimonials section with new reviews
- [ ] Review request email effectiveness

### Quarterly (2 hours)
- [ ] Audit review management process
- [ ] Test email delivery and QR codes
- [ ] Update review request templates
- [ ] Analyze competitor reviews
- [ ] Plan review generation campaigns

---

## Tracking Metrics

### Key Performance Indicators (KPIs)

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Total Google Reviews | 50+ | 15 | On Track |
| Average Rating | 4.5★+ | 4.8★ | Excellent |
| Reviews/Month | 3-5 | 2.5 | Needs Work |
| Response Rate | 100% | 100% | Excellent |
| Response Time | <24hrs | <12hrs | Excellent |
| Review Request Success Rate | 30%+ | 25% | Needs Work |

### Monthly Report Template

```
REVIEW MANAGEMENT REPORT - [Month] [Year]

New Reviews: [#]
Average Rating: [#]★
Total Reviews: [#]
Platforms:
- Google: [#]
- Facebook: [#]
- Other: [#]

Review Requests Sent: [#]
Responses Received: [#]
Success Rate: [%]

Recent Positive Feedback Themes:
- [Theme 1]
- [Theme 2]

Areas for Improvement:
- [Issue 1]
- [Issue 2]

Actions Taken:
- [Action 1]
- [Action 2]

Next Month Goals:
- [Goal 1]
- [Goal 2]
```

---

## Platform-Specific Review Management

### Google Reviews (Primary)
- **URL:** [Google Business Profile Reviews]
- **Update Frequency:** Daily
- **Response Requirements:** All reviews
- **Tools:** Google Business app, Search Console
- **Tips:** Quick, professional responses. Use business name in signature.

### Facebook Reviews
- **URL:** Facebook Business Page
- **Update Frequency:** 2-3x/week
- **Response Requirements:** All negative reviews
- **Tools:** Facebook Business Suite
- **Tips:** More conversational tone. Can use emoji. Friendly and warm.

### JustDial Reviews
- **URL:** JustDial.com business profile
- **Update Frequency:** Weekly
- **Response Requirements:** Respond to top reviews
- **Tools:** JustDial business dashboard
- **Tips:** Professional but approachable. Show you value the feedback.

### Yelp Reviews (if applicable)
- **URL:** Yelp.com business profile
- **Update Frequency:** Weekly
- **Response Requirements:** All reviews
- **Tools:** Yelp Business Account
- **Tips:** Formal and professional. Provide specific solutions in responses.

---

## Email Best Practices

### Subject Line Optimization
✓ "Share Your Experience with ASMR CCTV Trading"
✓ "We'd Love Your Feedback!"
✓ "Help Other San Mateo Families (2 Minutes)"
✗ "PLEASE LEAVE A REVIEW"
✗ "URGENT: Click here for review"

### Send Time Optimization
- Best time: Wednesday-Thursday
- Best hours: 10 AM - 2 PM
- Avoid: Early morning (6-8 AM), late night (after 8 PM)
- Avoid: Weekends for B2B services

### A/B Testing Ideas
- Subject line variants
- Email length (short vs. detailed)
- CTA button text
- QR code vs. link emphasis
- Personalization level

### Compliance
- Include unsubscribe option
- Don't mislead (subject line must match content)
- Identify yourself clearly
- Respect GDPR/CAN-SPAM laws
- Get consent before adding to email list

---

## Troubleshooting

### Problem: Low Review Request Success Rate
**Causes:**
- Email going to spam
- Long delay between service and request
- Unclear call-to-action
- Poor email design

**Solutions:**
- Check email server SPF/DKIM records
- Send request within 2 weeks of service
- Use clear, prominent CTA button
- Test email in different clients
- A/B test subject lines

### Problem: Customers Say They Didn't Receive Email
**Causes:**
- Email in spam folder
- Typo in email address
- Email filtering
- List unsubscribe

**Solutions:**
- Verify email addresses during capture
- Add sender to contacts instruction
- Test delivery with Gmail, Outlook
- Provide alternative (phone follow-up)
- Manual follow-up options

### Problem: Negative Reviews Appearing
**Causes:**
- Service quality issues
- Unmet expectations
- Customer miscommunication
- Timing issues

**Solutions:**
- Review service quality with team
- Set better expectations upfront
- Improve customer communication
- Address issues before negative review
- Respond professionally to all negative reviews

---

## Integration Checklist

- [ ] review-request.php installed in /local-seo-system/
- [ ] Database tables created (review-system-db.sql)
- [ ] Contact form captures required fields
- [ ] Email system integrated with contact handler
- [ ] Dashboard accessible with secure key
- [ ] QR code links working
- [ ] 30-day delay configured correctly
- [ ] Email templates tested
- [ ] SPF/DKIM records verified
- [ ] Team trained on review response protocol
- [ ] Monthly review process scheduled
- [ ] Reporting template prepared

---

## Success Criteria

**Month 1:**
- [ ] 100% of requests sent successfully
- [ ] 20%+ response rate
- [ ] 5+ new reviews received
- [ ] All reviews responded to

**Month 3:**
- [ ] 25%+ response rate
- [ ] 15+ new reviews
- [ ] Maintain 4.5★+ average rating
- [ ] Zero unresponded reviews

**Year 1:**
- [ ] 50+ total reviews
- [ ] 30%+ request response rate
- [ ] 4.5★+ average rating
- [ ] Consistent monthly reviews

---

## Additional Resources

- [Google Reviews Best Practices](https://support.google.com/business/answer/7435183)
- [Managing Your Online Reputation](https://trends.googleblog.com/)
- [Local SEO Review Strategy](https://moz.com/local/reviews)

---

**Next Step:** Set review request schedule and assign responsibility to team member.

