<script src="https://www.google.com/recaptcha/api.js?render={{ $key }}&onload=onloadCallback" defer></script>
<script>
    // Start Captchavel Script
    let onloadCallback = function () {
        Array.from(document.getElementsByTagName('form'))
            .filter((element) => element.dataset.recaptcha === 'true')
            .forEach(function (element) {
                grecaptcha.execute({{ $key }}, { action: element.action }).then((token) => {
                    if (token) {
                        let child = document.createElement('input');

                        child.setAttribute('type', 'hidden');
                        child.setAttribute('name', '_recaptcha');
                        child.setAttribute('value', token);

                        element.appendChild(child);
                    }
                });
            });
    };
    // End Captchavel Script
</script>