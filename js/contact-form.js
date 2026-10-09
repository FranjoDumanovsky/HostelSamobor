// Submits the contact form with fetch so the page never reloads and the visitor
// never gets thrown back to the top and scrolled down again. The result lands in
// #form-status: success fades away on its own, an error stays until the next try.
//
// send.php still redirects back with ?sent=1/0 when JavaScript is off, so that
// flag is read here too and the banner behaves the same way.

(function () {
  var HIDE_AFTER = 6000;
  var FADE_MS = 250; // keep in sync with .form-status transition in style.css

  var el = document.getElementById('form-status');
  if (!el) return;

  var isEn = document.documentElement.lang === 'en';
  var messages = isEn
    ? {
        ok: 'Thank you! Your inquiry has been sent — we will get back to you shortly.',
        err: 'Sending failed. Please try again, or call us / send an e-mail instead.',
        sending: 'Sending…'
      }
    : {
        ok: 'Hvala! Vaš upit je poslan — javit ćemo se u najkraćem roku.',
        err: 'Slanje nije uspjelo. Pokušajte ponovno ili nas nazovite / pošaljite e-mail.',
        sending: 'Šaljem…'
      };

  var hideTimer, fadeTimer;

  function show(ok) {
    clearTimeout(hideTimer);
    clearTimeout(fadeTimer);

    el.textContent = ok ? messages.ok : messages.err;
    el.classList.toggle('is-ok', ok);
    el.classList.toggle('is-err', !ok);
    el.hidden = false;

    // Force a reflow: without it the class lands in the same frame as the
    // unhide, the element goes straight to its end state and nothing animates.
    void el.offsetWidth;
    el.classList.add('is-visible');

    // Errors stay put — the visitor needs them to decide what to do next.
    if (ok) hideTimer = setTimeout(hide, HIDE_AFTER);
  }

  function hide() {
    el.classList.remove('is-visible');
    fadeTimer = setTimeout(function () { el.hidden = true; }, FADE_MS);
  }

  // No-JS fallback path: send.php redirected back with the flag in the query.
  var sent = new URLSearchParams(location.search).get('sent');
  if (sent !== null) {
    show(sent === '1');
    // Strip the query so a refresh does not re-display a stale message.
    history.replaceState(null, '', location.pathname + location.hash);
  }

  var form = document.querySelector('form.contact-form');
  if (!form) return;

  var button = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var label = button ? button.textContent : '';
    if (button) {
      button.disabled = true;
      button.textContent = messages.sending;
    }

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { Accept: 'application/json' }
    })
      .then(function (response) {
        return response.json().then(
          function (data) { return response.ok && data && data.ok === true; },
          function () { return false; }
        );
      })
      .catch(function () { return false; })
      .then(function (ok) {
        show(ok);
        if (ok) form.reset();
        if (button) {
          button.disabled = false;
          button.textContent = label;
        }
      });
  });
})();
