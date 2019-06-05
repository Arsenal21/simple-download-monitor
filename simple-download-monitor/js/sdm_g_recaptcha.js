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