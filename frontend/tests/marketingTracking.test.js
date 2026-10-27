import assert from 'node:assert/strict';
import test from 'node:test';

let moduleSequence = 0;

function createBrowser(preference = null) {
  const storage = new Map();
  if (preference) storage.set('jamwithjamie_cookies_preference', preference);

  const scripts = new Map();
  globalThis.window = {
    location: { hostname: 'bookingjamwithjamie.cloud' },
    MARKETING_TRACKING: {
      enabled: true,
      allowedHost: 'bookingjamwithjamie.cloud',
      googleTagId: 'G-TEST',
      googleReservationSendTo: 'AW-TEST/LEAD',
      googlePaymentSendTo: 'AW-TEST/PAYMENT',
      metaPixelId: 'META-TEST',
      openAiPixelId: 'OPENAI-TEST',
      debug: false,
    },
    localStorage: {
      getItem: (key) => storage.get(key) ?? null,
      setItem: (key, value) => storage.set(key, String(value)),
    },
  };
  globalThis.document = {
    head: {
      appendChild: (element) => scripts.set(element.id, element),
    },
    createElement: () => ({}),
    getElementById: (id) => scripts.get(id) ?? null,
  };

  return { scripts, storage };
}

async function loadTrackingModule() {
  moduleSequence += 1;
  const url = new URL(`../src/services/marketingTracking.js?test=${moduleSequence}`, import.meta.url);
  return import(url.href);
}

function calls(queue) {
  return (queue || []).map((entry) => Array.from(entry));
}

test('does not load marketing vendors before the visitor decides', async () => {
  const browser = createBrowser();
  const tracking = await loadTrackingModule();

  tracking.initializeMarketingTracking();

  assert.equal(browser.scripts.size, 0);
  assert.equal(window.gtag, undefined);
  assert.equal(window.fbq, undefined);
  assert.equal(window.oaiq, undefined);
});

test('does not load vendors on preview or unlisted hosts', async () => {
  const browser = createBrowser('all');
  window.location.hostname = 'preview.bookingjamwithjamie.cloud';
  const tracking = await loadTrackingModule();

  tracking.initializeMarketingTracking();
  tracking.trackReservationCreated('reservation-preview');

  assert.equal(browser.scripts.size, 0);
});

test('queues a paid conversion until consent and sends correct vendor payloads', async () => {
  const browser = createBrowser();
  const tracking = await loadTrackingModule();

  tracking.trackPaymentCompleted({
    transaction_id: 'pi_123',
    value: 42.13,
    amount_minor: 4213,
    currency: 'usd',
  });
  assert.equal(browser.scripts.size, 0);

  tracking.setMarketingConsent(true);

  assert.deepEqual([...browser.scripts.keys()].sort(), [
    'jwj-google-tag',
    'jwj-meta-pixel',
    'jwj-openai-pixel',
  ]);

  const googleConversion = calls(window.dataLayer).find((call) => call[0] === 'event');
  assert.deepEqual(googleConversion, [
    'event',
    'conversion',
    {
      send_to: 'AW-TEST/PAYMENT',
      value: 42.13,
      currency: 'USD',
      transaction_id: 'pi_123',
    },
  ]);

  const metaPurchase = calls(window.fbq.queue).find((call) => call[0] === 'track' && call[1] === 'Purchase');
  assert.deepEqual(metaPurchase, [
    'track',
    'Purchase',
    { value: 42.13, currency: 'USD' },
    { eventID: 'pi_123' },
  ]);

  const openAiPurchase = calls(window.oaiq.q).find((call) => call[0] === 'measure');
  assert.deepEqual(openAiPurchase, [
    'measure',
    'order_created',
    { type: 'contents', amount: 4213, currency: 'USD' },
    { event_id: 'pi_123' },
  ]);
});

test('necessary-only consent never loads vendors or replays rejected events', async () => {
  const browser = createBrowser('necessary');
  const tracking = await loadTrackingModule();

  tracking.initializeMarketingTracking();
  tracking.trackReservationCreated('reservation-1');
  assert.equal(browser.scripts.size, 0);

  tracking.setMarketingConsent(true);
  assert.equal(browser.scripts.size, 3);
  assert.equal(calls(window.dataLayer).some((call) => call[0] === 'event'), false);
  assert.equal(calls(window.fbq.queue).some((call) => call[0] === 'track' && call[1] === 'Lead'), false);
  assert.equal(calls(window.oaiq.q).some((call) => call[0] === 'measure'), false);
});

test('reservation conversion uses standard lead events and stable deduplication ids', async () => {
  createBrowser('all');
  const tracking = await loadTrackingModule();

  tracking.initializeMarketingTracking();
  tracking.trackReservationCreated('reservation-2');
  tracking.trackReservationCreated('reservation-2');

  const metaLeads = calls(window.fbq.queue).filter((call) => call[0] === 'track' && call[1] === 'Lead');
  const openAiLeads = calls(window.oaiq.q).filter((call) => call[0] === 'measure' && call[1] === 'lead_created');

  assert.equal(metaLeads.length, 1);
  assert.deepEqual(metaLeads[0][3], { eventID: 'reservation-2' });
  assert.equal(openAiLeads.length, 1);
  assert.deepEqual(openAiLeads[0][3], { event_id: 'reservation-2' });
});

test('rejects malformed payment conversions before any vendor receives them', async () => {
  createBrowser('all');
  const tracking = await loadTrackingModule();

  tracking.initializeMarketingTracking();
  tracking.trackPaymentCompleted({ transaction_id: 'pi_bad', value: 'not-a-number', currency: 'USD' });
  tracking.trackPaymentCompleted({ transaction_id: 'pi_bad_currency', value: 10, currency: 'US dollars' });

  assert.equal(calls(window.dataLayer).some((call) => call[0] === 'event'), false);
  assert.equal(calls(window.fbq.queue).some((call) => call[0] === 'track' && call[1] === 'Purchase'), false);
  assert.equal(calls(window.oaiq.q).some((call) => call[0] === 'measure'), false);
});
