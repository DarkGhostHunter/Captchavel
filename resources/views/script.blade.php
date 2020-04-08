<script src="https://www.google.com/recaptcha/api.js?render={{ $key }}&onload=captchavelCallback" defer></script>
<script>
    // Start Captchavel Script
    window.captchavelCallback = function () {
        let site_key = "{{ $key }}";

        if (site_key === '') {
            console.error("You haven't set your Site Key for reCAPTCHA v3. Get it on https://g.co/recaptcha/admin.");
            return;
        }

        let elements = Array.from(document.getElementsByTagName('form'))
            .filter((form) => form.dataset.recaptcha === 'true');

        let renew = (form) => {
            let action = form.action.includes('://') ? (new URL(form.action)).pathname : form.action;
            grecaptcha.execute(site_key, {
                action: action
                    .substring(action.indexOf('?'), action.length)
                    .replace(/[^A-z\/_]/gi, '')
            }).then((token) => {
                if (token) {

                    let inputs = form.getElementsByClassName('recaptcha-token');

                    if (inputs.length) {
                        Array.from(inputs).forEach((input) => input.remove());
                    }

                    let child = document.createElement('input');
                    child.setAttribute('type', 'hidden');
                    child.setAttribute('name', '_recaptcha');
                    child.setAttribute('class', 'recaptcha-token');
                    child.setAttribute('value', token);
                    form.appendChild(child);
                }
            });
        };

        setTimeout(() => elements.forEach(renew), 1000 * 120);
    };
    // End Captchavel Script
</script>
