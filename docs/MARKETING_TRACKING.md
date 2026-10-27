# Marketing conversion tracking

This integration measures two business outcomes on the public booking site:

| Business outcome | Google Ads | Meta Pixel | OpenAI Measurement Pixel |
|---|---|---|---|
| Booking request created | configured reservation conversion | `Lead` | `lead_created` |
| Stripe payment completed | configured payment conversion | `Purchase` | `order_created` |

Tracking is opt-in. Google, Meta, and OpenAI scripts are not loaded until the
visitor chooses **Accept All**. Choosing **Accept Only Necessary** does not
affect booking or payment functionality. The public Cookie settings control can
be used to change the choice later.

## Production configuration

Set these values in the production `.env`. Pixel identifiers are public, but
the file must still remain outside version control because it also contains
application secrets.

```dotenv
CI_ENVIRONMENT = production
marketing.enabled = true
marketing.allowedHost = bookingjamwithjamie.cloud
marketing.googleTagId = G-21YKQ095DE
marketing.googleReservationSendTo = AW-16638861441/PIEACP_dgswaEIHBg_49
marketing.googlePaymentSendTo = AW-16638861441/6A9FCLX3zqIdEIHBg_49
marketing.metaPixelId = 1561789281445692
marketing.openAiPixelId = 8xFMwkD25wzWuqjVBxHzXn
marketing.debug = false
```

The host comparison is exact and case-insensitive. Tracking remains disabled on
localhost, preview domains, IP addresses, and unlisted subdomains.

## Deployment order

1. Back up the production database.
2. Deploy the backend and compiled `public/build` assets from the same commit.
3. Run `php spark migrate`. This creates `marketing_conversion_claims`, which
   prevents a Stripe payment success page from issuing a new conversion after a
   refresh.
4. Add the production environment values above, leaving
   `marketing.enabled = false` initially.
5. Clear any server/framework cache and perform the checks below.
6. Enable tracking and repeat the checks with a real low-value test transaction.

If the migration is missing or its database insert fails, the reservation and
payment flows continue normally and the failure is logged. The conversion is
not emitted, so database/log monitoring must be checked after deployment.

## Required account-side setup

Application code cannot validate vendor account configuration. Before launch:

- Google: confirm both `AW-...` conversion labels belong to the Google tag or a
  connected destination and are active.
- Meta: confirm Pixel `1561789281445692` accepts `Lead` and `Purchase` events.
- OpenAI Ads: confirm Pixel `8xFMwkD25wzWuqjVBxHzXn` has active conversion
  event settings for `lead_created` and `order_created` and that campaigns use
  those settings.

## Verification checklist

Use a fresh/private browser profile so a prior conversion claim cannot hide a
test result.

1. Before choosing a cookie option, confirm the Network panel does not load
   `googletagmanager.com`, `connect.facebook.net`, or `bzrcdn.openai.com`.
2. Choose **Accept Only Necessary** and confirm those requests remain absent.
3. Open Cookie settings, choose **Accept All**, and confirm all three SDKs load.
4. Submit one booking. Verify exactly one Google reservation conversion, one
   Meta `Lead`, and one OpenAI `lead_created` event.
5. Complete one Stripe test payment. Verify exactly one Google payment
   conversion, one Meta `Purchase`, and one OpenAI `order_created` event.
6. Confirm payment value and currency match Stripe. OpenAI receives the value
   in currency minor units (for example, USD 42.13 is `4213`).
7. Refresh the success page and confirm no duplicate conversion appears.
8. Review `writable/logs` for `Marketing conversion claim failed` entries.

Use Google Tag Assistant, Meta Events Manager/Test Events, and OpenAI Ads
Monitoring Events for the provider-side confirmation.

## Automated checks

```bash
cd frontend
npm test
npm run build

cd ..
vendor/bin/phpunit --no-coverage \
  tests/unit/MarketingTrackingConfigTest.php \
  tests/unit/MarketingConversionServiceTest.php
```

The browser tests protect consent gating, delayed-event delivery, vendor event
names, monetary units, and duplicate suppression.
