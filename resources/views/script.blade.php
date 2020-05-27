<script src="https://www.google.com/recaptcha/api.js?render={{ $key }}&onload=captchavelCallback" defer></script>
<script>
    // Start Captchavel Script
    const recaptchaSiteKey = "{{ $key }}";

    const recatpchaForms = Array.from(document.getElementsByTagName('form'))
        .filter(form => form.dataset.recaptcha === 'true');

    recatpchaForms.forEach((form) => {
        if (! form.dataset.recaptchaDontPrevent) {
            form.addEventListener('submit', event => {
                if (form.recaptcha_unresolved) {
                    event.preventDefault();
                }
            });
        }
    })

    window.captchavelCallback = () => {
        if (recaptchaSiteKey === '') {
            console.error("You haven't set your Site Key for reCAPTCHA v3. Get it on https://g.co/recaptcha/admin.");
            return;
        }

        function refreshToken(form) {
            form.recaptcha_unresolved = true;
            let action = form.action.includes('://') ? (new URL(form.action)).pathname : form.action;
            console.log('action: ' + action)

            grecaptcha.execute(recaptchaSiteKey, {
                action: action.substring(action.indexOf('?'), action.length).replace(/[^A-z\/_]/gi, '')
            }).then(token => {
                // Remove all inputs with an old reCAPTCHA token.
                form.removeChild(
                    Array.from(form.getElementsByTagName('input'))
                        .filter(input => input.name === '_recaptcha')
                );
                let child = document.createElement('input');
                child.setAttribute('type', 'hidden');
                child.setAttribute('name', '_recaptcha');
                child.setAttribute('value', token);
                form.appendChild(child);
                form.recaptcha_unresolved = false;
            });
        }

        recatpchaForms.forEach(form => {
            refreshToken(form);
            setInterval(() => refreshToken(form), 100 * 1000);
        });

        // If the user hits history back or forward, we will forcefully refresh the token.
        window.addEventListener('popstate', () => {
            recatpchaForms.forEach(form => {
                refreshToken(form);
            })
        })
    };
    // End Captchavel Script
</script>
