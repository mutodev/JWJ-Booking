const memoryClaims = new Set();
const pendingEvents = new Map();

const CONSENT_STORAGE_KEY = 'jamwithjamie_cookies_preference';
const CONSENT_GRANTED = 'all';
const CONSENT_DENIED = 'necessary';

let vendorsInitialized = false;
let initializedWindow = null;
let consentState = readStoredConsent();

function config() {
  return window.MARKETING_TRACKING || { enabled: false };
}

function isConfiguredForCurrentHost() {
  const settings = config();
  return settings.enabled === true
    && Boolean(settings.allowedHost)
    && window.location.hostname.toLowerCase() === settings.allowedHost.toLowerCase();
}

function readStoredConsent() {
  try {
    const preference = window.localStorage.getItem(CONSENT_STORAGE_KEY);
    if (preference === CONSENT_GRANTED) return 'granted';
    if (preference === CONSENT_DENIED) return 'denied';
  } catch (_) {
    // Storage can be unavailable in private or hardened browser contexts.
  }

  return 'unknown';
}

function loadScript(src, id) {
  if (document.getElementById(id)) return;

  const script = document.createElement('script');
  script.id = id;
  script.async = true;
  script.src = src;
  document.head.appendChild(script);
}

function claimInBrowser(eventName, referenceId) {
  if (!referenceId) return false;

  const key = `jwj_conversion:${eventName}:${referenceId}`;
  if (memoryClaims.has(key)) return false;

  try {
    if (window.localStorage.getItem(key)) return false;
    window.localStorage.setItem(key, new Date().toISOString());
  } catch (_) {
    // Privacy settings may disable storage; memory still protects this page.
  }

  memoryClaims.add(key);
  return true;
}

function initializeGoogle(settings) {
  if (!settings.googleTagId) return;

  window.dataLayer = window.dataLayer || [];
  window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };
  window.gtag('consent', 'default', {
    ad_storage: 'granted',
    analytics_storage: 'granted',
    ad_user_data: 'granted',
    ad_personalization: 'granted',
  });
  window.gtag('js', new Date());
  window.gtag('config', settings.googleTagId);
  loadScript(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(settings.googleTagId)}`, 'jwj-google-tag');
}

function initializeMeta(settings) {
  if (!settings.metaPixelId) return;

  if (!window.fbq) {
    const fbq = function fbq() { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
    fbq.push = fbq;
    fbq.loaded = true;
    fbq.version = '2.0';
    fbq.queue = [];
    window.fbq = fbq;
    window._fbq = fbq;
  }

  window.fbq('consent', 'grant');
  window.fbq('init', settings.metaPixelId);
  window.fbq('track', 'PageView');
  loadScript('https://connect.facebook.net/en_US/fbevents.js', 'jwj-meta-pixel');
}

function initializeOpenAi(settings) {
  if (!settings.openAiPixelId) return;

  window.oaiq = window.oaiq || function oaiq() {
    window.oaiq.q = window.oaiq.q || [];
    window.oaiq.q.push(arguments);
  };
  window.oaiq('consent', true);
  window.oaiq('init', {
    pixelId: settings.openAiPixelId,
    ...(settings.debug === true ? { debug: true } : {}),
  });
  loadScript('https://bzrcdn.openai.com/sdk/oaiq.min.js', 'jwj-openai-pixel');
}

function initializeVendors() {
  if ((vendorsInitialized && initializedWindow === window)
    || consentState !== 'granted'
    || !isConfiguredForCurrentHost()) return;

  const settings = config();
  [
    ['Google', initializeGoogle],
    ['Meta', initializeMeta],
    ['OpenAI', initializeOpenAi],
  ].forEach(([vendor, initialize]) => {
    try {
      initialize(settings);
    } catch (error) {
      console.warn(`${vendor} marketing tracking initialization failed.`, error);
    }
  });
  vendorsInitialized = true;
  initializedWindow = window;
}

function revokeVendorConsent() {
  try {
    if (window.gtag) {
      window.gtag('consent', 'update', {
        ad_storage: 'denied',
        analytics_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
      });
    }
  } catch (error) {
    console.warn('Google consent revocation failed.', error);
  }
  try {
    if (window.fbq) window.fbq('consent', 'revoke');
  } catch (error) {
    console.warn('Meta consent revocation failed.', error);
  }
  try {
    if (window.oaiq) window.oaiq('consent', false);
  } catch (error) {
    console.warn('OpenAI consent revocation failed.', error);
  }
}

function runOrQueue(eventName, referenceId, callback) {
  const normalizedReferenceId = String(referenceId || '').trim();
  if (!isConfiguredForCurrentHost() || !normalizedReferenceId || consentState === 'denied') return;

  const key = `${eventName}:${normalizedReferenceId}`;
  if (consentState === 'unknown') {
    pendingEvents.set(key, { eventName, referenceId: normalizedReferenceId, callback });
    return;
  }

  initializeVendors();
  if (claimInBrowser(eventName, normalizedReferenceId)) callback();
}

function flushPendingEvents() {
  if (consentState !== 'granted') return;

  initializeVendors();
  const events = Array.from(pendingEvents.values());
  pendingEvents.clear();
  events.forEach(({ eventName, referenceId, callback }) => {
    if (claimInBrowser(eventName, referenceId)) callback();
  });
}

export function initializeMarketingTracking() {
  if (!isConfiguredForCurrentHost()) return;

  try {
    consentState = readStoredConsent();
    if (consentState === 'granted') initializeVendors();
  } catch (error) {
    console.warn('Marketing tracking initialization failed.', error);
  }
}

export function setMarketingConsent(granted) {
  consentState = granted === true ? 'granted' : 'denied';

  if (consentState === 'granted') {
    initializeVendors();
    flushPendingEvents();
    return;
  }

  pendingEvents.clear();
  revokeVendorConsent();
}

export function trackReservationCreated(reservationId) {
  runOrQueue('reservation_created', reservationId, () => {
    try {
      const settings = config();
      if (settings.googleReservationSendTo && window.gtag) {
        window.gtag('event', 'conversion', {
          send_to: settings.googleReservationSendTo,
          transaction_id: reservationId,
        });
      }
      if (settings.metaPixelId && window.fbq) {
        window.fbq('track', 'Lead', {}, { eventID: reservationId });
      }
      if (settings.openAiPixelId && window.oaiq) {
        window.oaiq(
          'measure',
          'lead_created',
          { type: 'customer_action' },
          { event_id: reservationId },
        );
      }
    } catch (error) {
      console.warn('Reservation conversion tracking failed.', error);
    }
  });
}

export function trackPaymentCompleted(conversion) {
  const transactionId = String(conversion?.transaction_id || '').trim();
  const value = Number(conversion?.value);
  const amountMinor = Number.isInteger(conversion?.amount_minor)
    ? conversion.amount_minor
    : Math.round(value * 100);
  const currency = String(conversion?.currency || 'USD').trim().toUpperCase();

  if (!transactionId || !Number.isFinite(value) || value <= 0 || amountMinor <= 0 || !/^[A-Z]{3}$/.test(currency)) {
    return;
  }

  runOrQueue('payment_completed', transactionId, () => {
    try {
      const settings = config();
      if (settings.googlePaymentSendTo && window.gtag) {
        window.gtag('event', 'conversion', {
          send_to: settings.googlePaymentSendTo,
          value,
          currency,
          transaction_id: transactionId,
        });
      }
      if (settings.metaPixelId && window.fbq) {
        window.fbq('track', 'Purchase', { value, currency }, { eventID: transactionId });
      }
      if (settings.openAiPixelId && window.oaiq) {
        window.oaiq(
          'measure',
          'order_created',
          { type: 'contents', amount: amountMinor, currency },
          { event_id: transactionId },
        );
      }
    } catch (error) {
      console.warn('Payment conversion tracking failed.', error);
    }
  });
}
