function sdm_on_intermediate_page_token_generation(token){
    console.log("Google reCaptcha challenge for SDM is successful.");

    const download_url = new URL(window.location.href); // This is the download request url
    download_url.searchParams.set('g-recaptcha-response', token); // append captcha token param

    sdm_execute_download_in_intermediate_page(download_url);
}

function sdm_execute_download_in_intermediate_page(download_url){
    // Downloads the file
    window.location.href = download_url;

    // Prepare the redirect url when leaving intermediate page.
    const redirect_url = document.getElementById('sdm_redirect_form_intermediate_page_url');
    let after_download_redirect_url = '';
    if( redirect_url?.value ){
        // Redirect url specified
        after_download_redirect_url = redirect_url.value;
    } else {
        // Redirect url not specified, redirect to previous url.
        // Make sure there is no download related query params.
        if(document.referrer){
            after_download_redirect_url = new URL(document.referrer);
            after_download_redirect_url.searchParams.delete('download_id');
            after_download_redirect_url.searchParams.delete('sdm_process_download');
            after_download_redirect_url.searchParams.delete('cf-turnstile-response');
        }else {
            after_download_redirect_url = window.origin;
        }
    }

    setTimeout(function (){
        // Remove widgets
        const sdm_captcha_verifying_content = document.getElementById('sdm_captcha_verifying_content');
        sdm_captcha_verifying_content?.remove();

        // Show manual redirect button.
        const sdm_after_captcha_verification_content = document.getElementById('sdm_after_captcha_verification_content');
        sdm_after_captcha_verification_content?.classList.remove('hidden');

        const redirect_btn = document.getElementById('sdm_intermediate_page_manual_redirection_btn');
        redirect_btn?.addEventListener('click', function(e){
            e.preventDefault();
            window.location.href = after_download_redirect_url;
        })
    }, 1000)

}

document.addEventListener('sdm_reCaptcha_v3_ready', function (){
    grecaptcha.execute(
        sdm_recaptcha_opt.site_key,
        { action: 'sdm_download' }
    ).then((token) => {
        sdm_on_intermediate_page_token_generation(token);
    });
})