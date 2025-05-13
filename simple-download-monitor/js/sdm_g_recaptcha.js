/**
 * sdm_reCaptcha
 * @type {{Object}}
 */
var sdm_reCaptcha = function () {
    var recaptcha = document.getElementsByClassName("g-recaptcha");
    for (var i = 0; i < recaptcha.length; i++) {
	grecaptcha.render(recaptcha.item(i), {"sitekey": sdm_recaptcha_opt.site_key});
    }
};

/**
 * for sdm recaptcha v3. This gets called when google reCaptcha cdn is loaded.
 */
function sdm_reCaptcha_v3(){
    grecaptcha.ready(function() {
        const v3recaptchaInputs = document.querySelectorAll('.sdm-g-recaptcha-v3-response');
        v3recaptchaInputs?.forEach(function(resp_input){
            grecaptcha.execute(
                sdm_recaptcha_opt.site_key,
                { action: 'sdm_download' }
            ).then(function(token) {
                resp_input.value = token
            });
        });
    });
}