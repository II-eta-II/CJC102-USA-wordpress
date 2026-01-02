function cfturnstile_init_elementor_forms() {
  var settings = window.cfturnstileElementorSettings || {};
  var sitekey = settings.sitekey || '';
  var position = settings.position || 'before';
  var mode = settings.mode || 'turnstile';
  var recaptchaSiteKey = settings.recaptchaSiteKey || '';
  
  var elementorForms = document.querySelectorAll('.elementor-form:not(.cft-processed)');
  elementorForms.forEach(function(form, index) {
    if (form.querySelector('.cf-turnstile') || form.querySelector('.g-recaptcha') || form.querySelector('input[name="cfturnstile_failsafe"]')) {
      form.classList.add('cft-processed');
      return;
    }
    
    var submitButton = form.querySelector('button[type="submit"]');

    // Failsafe modes: inject marker and optionally reCAPTCHA widget.
    if (submitButton && (mode === 'allow' || mode === 'recaptcha')) {
      var marker = document.createElement('input');
      marker.type = 'hidden';
      marker.name = 'cfturnstile_failsafe';
      marker.value = mode;
      form.appendChild(marker);

      if (mode === 'recaptcha' && recaptchaSiteKey) {
        var recaptchaDiv = document.createElement('div');
        recaptchaDiv.className = 'g-recaptcha';
        recaptchaDiv.setAttribute('data-sitekey', recaptchaSiteKey);
        recaptchaDiv.style.cssText = 'display: block; margin: 10px 0 15px 0; width: 100%;';

        if (position === 'after') {
          submitButton.parentNode.insertBefore(recaptchaDiv, submitButton.nextSibling);
        } else if (position === 'afterform') {
          form.appendChild(recaptchaDiv);
        } else {
          submitButton.parentNode.insertBefore(recaptchaDiv, submitButton);
        }
      }

      form.classList.add('cft-processed');
      return;
    }

    if (submitButton && window.turnstile && sitekey) {
      var turnstileDiv = document.createElement('div');
      turnstileDiv.className = 'elementor-turnstile-field cf-turnstile';
      turnstileDiv.id = 'cf-turnstile-elementor-fallback-' + index;
      turnstileDiv.style.cssText = 'display: block; margin: 10px 0 15px 0; width: 100%;';
      
      if (position === 'after') {
        submitButton.parentNode.insertBefore(turnstileDiv, submitButton.nextSibling);
      } else if (position === 'afterform') {
        form.appendChild(turnstileDiv);
      } else {
        submitButton.parentNode.insertBefore(turnstileDiv, submitButton);
      }
      
      turnstile.render('#cf-turnstile-elementor-fallback-' + index, {
        sitekey: sitekey,
        theme: settings.theme || 'auto',
        callback: function(token) {
          if (typeof turnstileElementorCallback === 'function') {
            turnstileElementorCallback(token);
          }
        }
      });
      
      form.classList.add('cft-processed');
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  cfturnstile_init_elementor_forms();
});

// Listen to Elementor frontend init to handle cached elements
document.addEventListener('elementor/frontend/init', function() {
  cfturnstile_init_elementor_forms();
});

// Handle form submit button clicks for re-rendering
document.addEventListener('click', function(event) {
  if (event.target.matches('.elementor-form button[type="submit"]')) {
    var settings = window.cfturnstileElementorSettings || {};
    var mode = settings.mode || 'turnstile';
    if (mode !== 'turnstile' || !window.turnstile) {
      return;
    }
    var submittedForm = event.target.closest('.elementor-form');
    
    setTimeout(function() {
      if (!window.turnstile) {
        return;
      }
      turnstile.remove('.elementor-form .cf-turnstile');
      turnstile.render('.elementor-form .cf-turnstile', {
        sitekey: cfturnstileElementorSettings.sitekey,
        callback: 'turnstileCallback',
        theme: cfturnstileElementorSettings.theme || 'auto'
      });
    }, 2000);
  }
});

// Handle Elementor popup show events
document.addEventListener('elementor/popup/show', function(event) {
  setTimeout(function() {
    var settings = window.cfturnstileElementorSettings || {};
    var mode = settings.mode || 'turnstile';
    if (mode !== 'turnstile' || !window.turnstile) {
      return;
    }
    var popupTurnstile = document.querySelector('.elementor-popup-modal .cf-turnstile');
    if (!popupTurnstile) {
      return;
    }

    var failedText = document.querySelector('.cf-turnstile-failed-text');
    if (failedText) {
      failedText.style.display = 'none';
    }
    
    turnstile.remove('.elementor-popup-modal .cf-turnstile');
    
    turnstile.render('.elementor-popup-modal .cf-turnstile', {
      sitekey: cfturnstileElementorSettings.sitekey,
      callback: 'turnstileCallback',
      theme: cfturnstileElementorSettings.theme || 'auto'
    });

    popupTurnstile.style.marginTop = '-5px';
    popupTurnstile.style.marginBottom = '20px';

  }, 1000);
});