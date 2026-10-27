const loadedScripts = new Set();
const memoryClaims = new Set();

function config() {
  return window.MARKETING_TRACKING || { enabled: false };
}

function isEnabled() {
  const settings = config();
  return settings.enabled === true
    && Boolean(settings.allowedHost)
    && window.location.hostname.toLowerCase() === settings.allowedHost.toLowerCase();
}

function loadScript(src, id) {
  if (loadedScripts.has(id) || document.getElementById(id)) return;

  const script = document.createElement('script');
  script.id = id;
  script.async = true;
  script.src = src;
  document.head.appendChild(script);
  loadedScripts.add(id);
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
  window.oaiq('init', { pixelId: settings.openAiPixelId });
  loadScript('https://bzrcdn.openai.com/sdk/oaiq.min.js', 'jwj-openai-pixel');
}

export function initializeMarketingTracking() {
  if (!isEnabled()) return;

  try {
    const settings = config();
    initializeGoogle(settings);
    initializeMeta(settings);
    initializeOpenAi(settings);
  } catch (error) {
    console.warn('Marketing tracking initialization failed.', error);
  }
}

export function trackReservationCreated(reservationId) {
  if (!isEnabled() || !claimInBrowser('reservation_created', reservationId)) return;

  try {
    const settings = config();
    if (settings.googleReservationSendTo && window.gtag) {
      window.gtag('event', 'conversion', {
        send_to: settings.googleReservationSendTo,
        transaction_id: reservationId,
      });
    }
    if (settings.metaPixelId && window.fbq) {
      window.fbq('track', 'form_submit');
    }
    if (settings.openAiPixelId && window.oaiq) {
      window.oaiq('measure', 'lead_created', { type: 'customer_action' });
    }
  } catch (error) {
    console.warn('Reservation conversion tracking failed.', error);
  }
}

export function trackPaymentCompleted(conversion) {
  const transactionId = conversion?.transaction_id;
  if (!isEnabled() || !claimInBrowser('payment_completed', transactionId)) return;

  try {
    const settings = config();
    const value = Number(conversion.value);
    const currency = conversion.currency || 'USD';

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
      window.oaiq('measure', 'order_created', { type: 'contents' });
    }
  } catch (error) {
    console.warn('Payment conversion tracking failed.', error);
  }
}
