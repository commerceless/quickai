<?php

/**
 * Print live chat js code
 */
function print_live_chat_code()
{
    if (get_option('enable_live_chat')) {

        if (get_option('tawkto_membership')) {
            // check membership
            $settings = get_user_membership_settings();
            if (!$settings['live_chat'])
                return;
        }

        $chat_link = get_option('tawkto_chat_link');
        $chat_link = str_replace('https://tawk.to/chat/', '', $chat_link);
        ?>
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
            <?php if(checkloggedin()){
            // add logged in user's data
            $user_data = get_user_data($_SESSION['user']['username']);
            ?>
            Tawk_API.visitor = {
                name: <?php _esc(json_encode($user_data['name'])) ?>,
                email: <?php _esc(json_encode($user_data['email'])) ?>
            };
            <?php } ?>
            (function () {
                var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/<?php _esc($chat_link) ?>';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
        <?php
    }
}

/**
 * Get the ai templates
 *
 * @param null $category
 * @return array|array[]|null
 */
function get_ai_templates($category = null)
{
    global $config;

    $orm = ORM::for_table($config['db']['pre'] . 'ai_template_categories')
        ->where('active', '1')
        ->order_by_asc('position');

    if ($category) {
        $orm->where('id', $category);
    }
    $cats = $orm->find_array();

    foreach ($cats as $key => $cat) {
        $templates = ORM::for_table($config['db']['pre'] . 'ai_templates')
            ->where('active', '1')
            ->where('category_id', $cat['id'])
            ->order_by_asc('position')
            ->find_array();

        $custom_templates = ORM::for_table($config['db']['pre'] . 'ai_custom_templates')
            ->where('active', '1')
            ->where('category_id', $cat['id'])
            ->order_by_asc('position')
            ->find_array();

        $cats[$key]['templates'] = array_merge($templates, $custom_templates);
    }

    return $cats;
}

/**
 * Get api key
 *
 * @return array|mixed|null
 */
function get_api_key()
{
    global $config;

    $id = get_option('open_ai_api_key');
    $orm = ORM::for_table($config['db']['pre'] . 'api_keys')
        ->where('type', 'openai')
        ->where('active', '1');
    if ($id == 'random') {
        $data = $orm->find_array();
        $result = $data[array_rand($data)];
    } else {
        $result = $orm->find_one($id);
    }
    return !empty($result['api_key']) ? $result['api_key'] : null;

}

/**
 * Get api key for image generation
 *
 * @return array|mixed|null
 */
function get_image_api_key($type)
{
    global $config;

    $id = get_option('ai_image_api_key');
    $orm = ORM::for_table($config['db']['pre'] . 'api_keys')
        ->where('active', '1');
    if ($id == 'random') {
        $orm->where('type', $type);

        $data = $orm->find_array();
        $result = $data[array_rand($data)];
    } else {
        $result = $orm->find_one($id);
    }
    return !empty($result['api_key']) ? $result['api_key'] : null;
}

/**
 * Get api proxy
 *
 * @return string|null
 */
function get_api_proxy()
{

    $proxies = get_option('ai_proxies');
    if (!empty($proxies)) {
        $proxies = explode(',', $proxies);
        return $proxies[array_rand($proxies)];
    }
    return null;
}

/**
 * Get AI language
 *
 * @return array|string
 */
function get_ai_languages($key = null)
{

    $languages = get_option("ai_languages");
    $languages = explode(',', $languages);
    $languages = array_map('trim', $languages);

    if (!is_null($key))
        return $languages[$key];
    else
        return $languages;
}


/**
 * Get a list of OpenAI models
 *
 * @return array
 */
function get_opeai_models()
{
    return array(
        'text-ada-001' => __('Ada (Simple & Fastest)'),
        'text-babbage-001' => __('Babbage (Moderate)'),
        'text-curie-001' => __('Curie (Good)'),
        'text-davinci-003' => __('Davinci (Most Expensive & Powerful)'),
        'gpt-3.5-turbo' => __('ChatGPT 3.5'),
        'gpt-4' => __('ChatGPT 4 (Beta)'),
    );
}

/**
 * Check bad words
 *
 * @param $text
 * @return bool
 */
function check_bad_words($text)
{
    $bad_words = get_option("bad_words");
    $bad_words = explode(',', $bad_words);
    $bad_words = array_map('trim', $bad_words);

    foreach ($bad_words as $word) {
        /* Search for the word in string */
        $regex_start = '/(^|\b|\s)';
        $regex_end = '(\b|\s|$)/i';
        if (preg_match($regex_start . '(' . preg_quote(mb_strtolower($word), '/') . ')' . $regex_end, mb_strtolower($text))) {
            return $word;
        }
    }

    return false;
}

/**
 * Get custom api error messages
 *
 * @param $http_response
 * @param $api
 * @return string
 */
function get_api_error_message($http_response)
{
    switch ($http_response) {
        case 400:
            return __('API Error: The requested data is not valid for the API request.');
        case 401:
            return __('API Error: The API key is missing or invalid.');
        case 403:
            return __('API Error: You lack the necessary permissions to perform this action.');
        case 404:
            return __('API Error: The requested resource was not found.');
        case 429:
            return __('API Error: You are sending requests too quickly or you exceeded your current quota.');
        case 500:
            return __('API Error: The server had an error while processing your request, please try again.');
        default:
            return __('Unexpected error, please try again.');

    }
}

/**
 * @param $ai_message
 * @return array|string|string[]|null
 */
function filter_ai_response($ai_message)
{
    if (strpos($ai_message, '<pre><code>') === false) {
        $ai_message = escape($ai_message);
        if (preg_match_all('/```([\s\S]+?)```/', $ai_message, $parts)) {
            foreach ($parts[1] as $key => $part) {
                $ai_message = str_replace($parts[0][$key], '<pre><code>' . $part . '</code><button class="copy-ai-code" onclick="copyAICode(this)"><svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg> <span class="label-copy-code">' . __('Copy') . '</span></button></pre>', $ai_message);
            }
        }
        $ai_message = nl2br($ai_message);
    }

    $ai_message = preg_replace('/(\d+\.\s)/', '<strong>$1</strong>', $ai_message);
    $ai_message = preg_replace('/(^[A-Za-z\s]+:)/m', '<strong>$1</strong>', $ai_message);

    return $ai_message;
}

/**
 * @return array[]
 */
function get_ai_voices()
{
    return array(
        'ar-XA' =>
            array(
                'language_code' => 'ar-XA',
                'language' => 'Arabic',
                'voices' =>
                    array(
                        'ar-aws-std-zeina' =>
                            array(
                                'voice' => 'Zeina',
                                'voice_id' => 'ar-aws-std-zeina',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'cmn-CN' =>
            array(
                'language_code' => 'cmn-CN',
                'language' => 'Chinese (Mandarin)',
                'voices' =>
                    array(
                        'cn-aws-std-zhiyu' =>
                            array(
                                'voice' => 'Zhiyu',
                                'voice_id' => 'cn-aws-std-zhiyu',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'da-DK' =>
            array(
                'language_code' => 'da-DK',
                'language' => 'Danish (Denmark)',
                'voices' =>
                    array(
                        'dk-aws-std-naja' =>
                            array(
                                'voice' => 'Naja',
                                'voice_id' => 'dk-aws-std-naja',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'dk-aws-std-mads' =>
                            array(
                                'voice' => 'Mads',
                                'voice_id' => 'dk-aws-std-mads',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'nl-NL' =>
            array(
                'language_code' => 'nl-NL',
                'language' => 'Dutch (Netherlands)',
                'voices' =>
                    array(
                        'nl-aws-std-lotte' =>
                            array(
                                'voice' => 'Lotte',
                                'voice_id' => 'nl-aws-std-lotte',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'nl-aws-std-ruben' =>
                            array(
                                'voice' => 'Ruben',
                                'voice_id' => 'nl-aws-std-ruben',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'en-AU' =>
            array(
                'language_code' => 'en-AU',
                'language' => 'English (Australia)',
                'voices' =>
                    array(
                        'au-aws-std-nicole' =>
                            array(
                                'voice' => 'Nicole',
                                'voice_id' => 'au-aws-std-nicole',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'au-aws-std-russell' =>
                            array(
                                'voice' => 'Russell',
                                'voice_id' => 'au-aws-std-russell',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'au-aws-nrl-olivia' =>
                            array(
                                'voice' => 'Olivia',
                                'voice_id' => 'au-aws-nrl-olivia',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                    ),
            ),
        'en-GB' =>
            array(
                'language_code' => 'en-GB',
                'language' => 'English (UK)',
                'voices' =>
                    array(
                        'gb-aws-std-amy' =>
                            array(
                                'voice' => 'Amy',
                                'voice_id' => 'gb-aws-std-amy',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'gb-aws-std-emma' =>
                            array(
                                'voice' => 'Emma',
                                'voice_id' => 'gb-aws-std-emma',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'gb-aws-std-brian' =>
                            array(
                                'voice' => 'Brian',
                                'voice_id' => 'gb-aws-std-brian',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'gb-aws-nrl-amy' =>
                            array(
                                'voice' => 'Amy',
                                'voice_id' => 'gb-aws-nrl-amy',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'gb-aws-nrl-emma' =>
                            array(
                                'voice' => 'Emma',
                                'voice_id' => 'gb-aws-nrl-emma',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'gb-aws-nrl-brian' =>
                            array(
                                'voice' => 'Brian',
                                'voice_id' => 'gb-aws-nrl-brian',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                    ),
            ),
        'en-IN' =>
            array(
                'language_code' => 'en-IN',
                'language' => 'English (India)',
                'voices' =>
                    array(
                        'in-aws-std-aditi' =>
                            array(
                                'voice' => 'Aditi',
                                'voice_id' => 'in-aws-std-aditi',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'in-aws-std-raveena' =>
                            array(
                                'voice' => 'Raveena',
                                'voice_id' => 'in-aws-std-raveena',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'en-US' =>
            array(
                'language_code' => 'en-US',
                'language' => 'English (USA)',
                'voices' =>
                    array(
                        'us-aws-std-ivy' =>
                            array(
                                'voice' => 'Ivy',
                                'voice_id' => 'us-aws-std-ivy',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-joanna' =>
                            array(
                                'voice' => 'Joanna',
                                'voice_id' => 'us-aws-std-joanna',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-kendra' =>
                            array(
                                'voice' => 'Kendra',
                                'voice_id' => 'us-aws-std-kendra',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-kimberly' =>
                            array(
                                'voice' => 'Kimberly',
                                'voice_id' => 'us-aws-std-kimberly',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-salli' =>
                            array(
                                'voice' => 'Salli',
                                'voice_id' => 'us-aws-std-salli',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-joey' =>
                            array(
                                'voice' => 'Joey',
                                'voice_id' => 'us-aws-std-joey',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-justin' =>
                            array(
                                'voice' => 'Justin',
                                'voice_id' => 'us-aws-std-justin',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-matthew' =>
                            array(
                                'voice' => 'Matthew',
                                'voice_id' => 'us-aws-std-matthew',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-nrl-ivy' =>
                            array(
                                'voice' => 'Ivy',
                                'voice_id' => 'us-aws-nrl-ivy',
                                'gender' => 'Female(child)',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-joanna' =>
                            array(
                                'voice' => 'Joanna',
                                'voice_id' => 'us-aws-nrl-joanna',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-kendra' =>
                            array(
                                'voice' => 'Kendra',
                                'voice_id' => 'us-aws-nrl-kendra',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-kimberly' =>
                            array(
                                'voice' => 'Kimberly',
                                'voice_id' => 'us-aws-nrl-kimberly',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-salli' =>
                            array(
                                'voice' => 'Salli',
                                'voice_id' => 'us-aws-nrl-salli',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-joey' =>
                            array(
                                'voice' => 'Joey',
                                'voice_id' => 'us-aws-nrl-joey',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-justin' =>
                            array(
                                'voice' => 'Justin',
                                'voice_id' => 'us-aws-nrl-justin',
                                'gender' => 'Male(child)',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-kevin' =>
                            array(
                                'voice' => 'Kevin',
                                'voice_id' => 'us-aws-nrl-kevin',
                                'gender' => 'Male(child)',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                        'us-aws-nrl-matthew' =>
                            array(
                                'voice' => 'Matthew',
                                'voice_id' => 'us-aws-nrl-matthew',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                    ),
            ),
        'en-GB-WLS' =>
            array(
                'language_code' => 'en-GB-WLS',
                'language' => 'English (Wales)',
                'voices' =>
                    array(
                        'wls-aws-std-matthew' =>
                            array(
                                'voice' => 'Geraint',
                                'voice_id' => 'wls-aws-std-matthew',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'fr-FR' =>
            array(
                'language_code' => 'fr-FR',
                'language' => 'French (France)',
                'voices' =>
                    array(
                        'fr-aws-std-celine' =>
                            array(
                                'voice' => 'Celine',
                                'voice_id' => 'fr-aws-std-celine',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'fr-aws-std-lea' =>
                            array(
                                'voice' => 'Lea',
                                'voice_id' => 'fr-aws-std-lea',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'fr-aws-std-mathieu' =>
                            array(
                                'voice' => 'Mathieu',
                                'voice_id' => 'fr-aws-std-mathieu',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'fr-CA' =>
            array(
                'language_code' => 'fr-CA',
                'language' => 'French (Canada)',
                'voices' =>
                    array(
                        'ca-aws-std-chantal' =>
                            array(
                                'voice' => 'Chantal',
                                'voice_id' => 'ca-aws-std-chantal',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'ca-aws-nrl-gabrielle' =>
                            array(
                                'voice' => 'Gabrielle',
                                'voice_id' => 'ca-aws-nrl-gabrielle',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                    ),
            ),
        'de-DE' =>
            array(
                'language_code' => 'de-DE',
                'language' => 'German (Germany)',
                'voices' =>
                    array(
                        'de-aws-std-marlene' =>
                            array(
                                'voice' => 'Marlene',
                                'voice_id' => 'de-aws-std-marlene',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'de-aws-std-vicki' =>
                            array(
                                'voice' => 'Vicki',
                                'voice_id' => 'de-aws-std-vicki',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'de-aws-std-hans' =>
                            array(
                                'voice' => 'Hans',
                                'voice_id' => 'de-aws-std-hans',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'hi-IN' =>
            array(
                'language_code' => 'hi-IN',
                'language' => 'Hindi (India)',
                'voices' =>
                    array(
                        'hi-aws-std-aditi' =>
                            array(
                                'voice' => 'Aditi',
                                'voice_id' => 'hi-aws-std-aditi',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'is-IS' =>
            array(
                'language_code' => 'is-IS',
                'language' => 'Icelandic (Iceland)',
                'voices' =>
                    array(
                        'is-aws-std-dora' =>
                            array(
                                'voice' => 'Dora',
                                'voice_id' => 'is-aws-std-dora',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'is-aws-std-karl' =>
                            array(
                                'voice' => 'Karl',
                                'voice_id' => 'is-aws-std-karl',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'it-IT' =>
            array(
                'language_code' => 'it-IT',
                'language' => 'Italian (Italy)',
                'voices' =>
                    array(
                        'it-aws-std-carla' =>
                            array(
                                'voice' => 'Carla',
                                'voice_id' => 'it-aws-std-carla',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'it-aws-std-bianca' =>
                            array(
                                'voice' => 'Bianca',
                                'voice_id' => 'it-aws-std-bianca',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'it-aws-std-giorgio' =>
                            array(
                                'voice' => 'Giorgio',
                                'voice_id' => 'it-aws-std-giorgio',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'ja-JP' =>
            array(
                'language_code' => 'ja-JP',
                'language' => 'Japanese (Japan)',
                'voices' =>
                    array(
                        'jp-aws-std-mizuki' =>
                            array(
                                'voice' => 'Mizuki',
                                'voice_id' => 'jp-aws-std-mizuki',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'jp-aws-std-takumi' =>
                            array(
                                'voice' => 'Takumi',
                                'voice_id' => 'jp-aws-std-takumi',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'ko-KR' =>
            array(
                'language_code' => 'ko-KR',
                'language' => 'Korean (South Korea)',
                'voices' =>
                    array(
                        'kr-aws-std-seoyeon' =>
                            array(
                                'voice' => 'Seoyeon',
                                'voice_id' => 'kr-aws-std-seoyeon',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'kr-aws-nrl-seoyeon' =>
                            array(
                                'voice' => 'Seoyeon',
                                'voice_id' => 'kr-aws-nrl-seoyeon',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                    ),
            ),
        'nb-NO' =>
            array(
                'language_code' => 'nb-NO',
                'language' => 'Norwegian (Norway)',
                'voices' =>
                    array(
                        'no-aws-std-liv' =>
                            array(
                                'voice' => 'Liv',
                                'voice_id' => 'no-aws-std-liv',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'pl-PL' =>
            array(
                'language_code' => 'pl-PL',
                'language' => 'Polish (Poland)',
                'voices' =>
                    array(
                        'pl-aws-std-ewa' =>
                            array(
                                'voice' => 'Ewa',
                                'voice_id' => 'pl-aws-std-ewa',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'pl-aws-std-maja' =>
                            array(
                                'voice' => 'Maja',
                                'voice_id' => 'pl-aws-std-maja',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'pl-aws-std-jacek' =>
                            array(
                                'voice' => 'Jacek',
                                'voice_id' => 'pl-aws-std-jacek',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'pl-aws-std-jan' =>
                            array(
                                'voice' => 'Jan',
                                'voice_id' => 'pl-aws-std-jan',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'pt-BR' =>
            array(
                'language_code' => 'pt-BR',
                'language' => 'Portuguese (Brazil)',
                'voices' =>
                    array(
                        'br-aws-std-camila' =>
                            array(
                                'voice' => 'Camila',
                                'voice_id' => 'br-aws-std-camila',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'br-aws-std-vitoria' =>
                            array(
                                'voice' => 'Vitoria',
                                'voice_id' => 'br-aws-std-vitoria',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'br-aws-std-ricardo' =>
                            array(
                                'voice' => 'Ricardo',
                                'voice_id' => 'br-aws-std-ricardo',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'br-aws-nrl-camila' =>
                            array(
                                'voice' => 'Camila',
                                'voice_id' => 'br-aws-nrl-camila',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                    ),
            ),
        'pt-PT' =>
            array(
                'language_code' => 'pt-PT',
                'language' => 'Portuguese (Portugal)',
                'voices' =>
                    array(
                        'pt-aws-std-ines' =>
                            array(
                                'voice' => 'Ines',
                                'voice_id' => 'pt-aws-std-ines',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'pt-aws-std-cristiano' =>
                            array(
                                'voice' => 'Cristiano',
                                'voice_id' => 'pt-aws-std-cristiano',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'ro-RO' =>
            array(
                'language_code' => 'ro-RO',
                'language' => 'Romanian (Romania)',
                'voices' =>
                    array(
                        'ro-aws-std-carment' =>
                            array(
                                'voice' => 'Carmen',
                                'voice_id' => 'ro-aws-std-carment',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'ru-RU' =>
            array(
                'language_code' => 'ru-RU',
                'language' => 'Russian (Russia)',
                'voices' =>
                    array(
                        'ru-aws-std-tatyana' =>
                            array(
                                'voice' => 'Tatyana',
                                'voice_id' => 'ru-aws-std-tatyana',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'ru-aws-std-maxim' =>
                            array(
                                'voice' => 'Maxim',
                                'voice_id' => 'ru-aws-std-maxim',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'es-ES' =>
            array(
                'language_code' => 'es-ES',
                'language' => 'Spanish (Spain)',
                'voices' =>
                    array(
                        'es-aws-std-conchita' =>
                            array(
                                'voice' => 'Conchita',
                                'voice_id' => 'es-aws-std-conchita',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'es-aws-std-lucia' =>
                            array(
                                'voice' => 'Lucia',
                                'voice_id' => 'es-aws-std-lucia',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'es-aws-std-enrique' =>
                            array(
                                'voice' => 'Enrique',
                                'voice_id' => 'es-aws-std-enrique',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'es-MX' =>
            array(
                'language_code' => 'es-MX',
                'language' => 'Spanish (Mexico)',
                'voices' =>
                    array(
                        'mx-aws-std-mia' =>
                            array(
                                'voice' => 'Mia',
                                'voice_id' => 'mx-aws-std-mia',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'es-US' =>
            array(
                'language_code' => 'es-US',
                'language' => 'Spanish (USA)',
                'voices' =>
                    array(
                        'us-aws-std-lupe' =>
                            array(
                                'voice' => 'Lupe',
                                'voice_id' => 'us-aws-std-lupe',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-penelope' =>
                            array(
                                'voice' => 'Penelope',
                                'voice_id' => 'us-aws-std-penelope',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-std-miguel' =>
                            array(
                                'voice' => 'Miguel',
                                'voice_id' => 'us-aws-std-miguel',
                                'gender' => 'Male',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                        'us-aws-nrl-lupe' =>
                            array(
                                'voice' => 'Lupe',
                                'voice_id' => 'us-aws-nrl-lupe',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_nrl',
                                'voice_type' => 'neural',
                            ),
                    ),
            ),
        'sv-SE' =>
            array(
                'language_code' => 'sv-SE',
                'language' => 'Swedish (Sweden)',
                'voices' =>
                    array(
                        'se-aws-std-astrid' =>
                            array(
                                'voice' => 'Astrid',
                                'voice_id' => 'se-aws-std-astrid',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'tr-TR' =>
            array(
                'language_code' => 'tr-TR',
                'language' => 'Turkish (Turkey)',
                'voices' =>
                    array(
                        'tr-aws-std-filiz' =>
                            array(
                                'voice' => 'Filiz',
                                'voice_id' => 'tr-aws-std-filiz',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
        'cy-GB' =>
            array(
                'language_code' => 'cy-GB',
                'language' => 'Welsh (Wales)',
                'voices' =>
                    array(
                        'cy-aws-std-gwyneth' =>
                            array(
                                'voice' => 'Gwyneth',
                                'voice_id' => 'cy-aws-std-gwyneth',
                                'gender' => 'Female',
                                'vendor_id' => 'aws_std',
                                'voice_type' => 'standard',
                            ),
                    ),
            ),
    );
}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_blog_idea_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the ideas must be:\n" . $tone . "\n\n";
    }

    return $lang_text . "Write interesting blog ideas about:\n\n" . $description . "\n\n" . $tone_text;

}


/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_blog_intros_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the intro must be:\n" . $tone . "\n\n";
    }

    return $lang_text . "Write an interesting blog post intro about:\n\n" . $description . "\n\n Blog post title:\n" . $title . "\n\n" . $tone_text;
}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_blog_titles_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the titles must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Generate 10 catchy blog titles for:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_blog_section_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the paragraphs must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a full blog section with at least 5 large paragraphs about:\n\n" . $title . "\n\n Split by subheadings:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_blog_conclusion_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the conclusion must be:\n" . $tone . "\n\n";
    }

    return $lang_text . "Write a blog article conclusion for:\n\n" . $description . "\n\n Blog article title:\n" . $title . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_article_writer_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the article must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a complete 3000+ words long article with your maximum capacity with introduction and conclusion. The article should be nice looking with headings, sub headings and lists. Use this topic:\n\n" . $title . "\n\nUse following keywords in the article:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_article_rewriter_prompt($description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the article must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Improve and rewrite this article in a creative and smart way:\n\n" . $description . "\n\nUse following keywords in the article:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_article_outlines_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the outlines must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 short and simple article outlines about:\n\n" . $description . "\n\nBlog article title:\n" . $title . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_talking_points_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the points must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write short, simple and informative talking points for:\n\n" . $title . "\n\nAnd also similar talking points for subheadings:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_paragraph_writer_prompt($description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the paragraphs must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 3 perfectly structured and meaningful paragraphs about:\n\n" . $description . "\n\nUse following keywords in the paragraphs:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_content_rephrase_prompt($description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the content must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Rephrase this content in a smart way:\n\n" . $description . "\n\nUse following keywords in the content:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_facebook_ads_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the ad must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a creative ad for the following product to run on Facebook aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_facebook_ads_headlines_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the ad must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 catchy and convincing headlines for the following product to run on Facebook ad aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_google_ads_titles_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the titles must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 interesting titles for Google ads of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text . "\n\n Title's length must be 30 characters\n\n";

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_google_ads_descriptions_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the description must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write an interesting description for the Google ad of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;
}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_linkedin_ads_headlines_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the ads must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 catchy headlines for the LinkedIn ads of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;
}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_linkedin_ads_descriptions_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the description must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a professional and eye-catching description for the LinkedIn ads of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_app_sms_notifications_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the messages must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Generate 10 eye catching notification messages about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_text_extender_prompt($description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the content must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Improve and extend this content:\n\n" . $description . "\n\nUse following keywords in the content:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_content_shorten_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the summery must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Summarize this text in a short concise way:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_quora_answers_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the answer must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a detailed answer for Quora of this question:\n\n" . $title . "\n\nUse this content for more information:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_summarize_2nd_grader_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the summery must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Summarize this for a second-grade student:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $audience
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_stories_prompt($audience, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the story must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write an engaging and interesting story about:\n\n" . $description . "\n\n Audience of the story must be:\n" . $audience . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_bullet_point_answers_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the answer must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a detailed answer with bullet points:\n\n" . $description . "\n\n" . $tone_text;
}

/**
 * Create prompt
 *
 * @param $keyword
 * @param $language
 * @param $tone
 * @return string
 */
function create_definition_prompt($keyword, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the meaning must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "What is the meaning of:\n\n" . $keyword . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_answers_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the answer must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a long and detailed answer of:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_questions_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the questions must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Create 10 engaging questions from this paragraph:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_passive_active_voice_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the sentence must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Convert this passive voice sentence into active voice:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_pros_cons_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the pros and cons must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write pros and cons based on the following description:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_rewrite_with_keywords_prompt($description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the content must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Improve and rewrite this content in a smart way:\n\n" . $description . "\n\nMust use following keywords in the content:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_emails_prompt($recipient, $recipient_position, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the email must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write an engaging email about:\n\n" . $description . "\n\n Recipient:\n" . $recipient . "\n\n Recipient Position:\n" . $recipient_position . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_emails_v2_prompt($from, $to, $goal, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the email must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write an engaging email about:\n\n" . $description . "\n\n From:\n" . $from . "\n\n To:\n" . $to . "\n\n Main Goal of this email:\n" . $goal . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $language
 * @param $tone
 * @return string
 */
function create_email_subject_lines_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the subject must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 catchy email subject lines for this product:\n\n" . $title . "\n\nProduct Description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $language
 * @param $tone
 * @return string
 */
function create_startup_name_generator_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the names must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Generate 10 creative, and catchy company names about:\n\n" . $description . "\n\nUse the following keywords:\n" . $title . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $platform
 * @param $language
 * @param $tone
 * @return string
 */
function create_company_bios_prompt($title, $description, $platform, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the bio must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a short and cool bio for " . ucfirst($platform) . "\n\nCompany Name:\n" . $title . "\n\nCompany Information:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $language
 * @param $tone
 * @return string
 */
function create_company_mission_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the statement must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a clear and concise statement of the company's goals and purpose, Company Name:\n" . $title . "\n\nCompany Information:\n" . $description . "\n\n" . $tone_text;
}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $language
 * @param $tone
 * @return string
 */
function create_company_vision_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the vision must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a vision that attracts the right people and customers. \n\nCompany Name:\n" . $title . "\n\nCompany Information:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_product_name_generator_prompt($description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the names must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Create 10 creative product names about:\n" . $description . "\n\nUse the following keywords:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_product_descriptions_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the description must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a detailed description of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_amazon_product_titles_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the titles must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 unique product titles to gain more sells on Amazon of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_amazon_product_descriptions_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the description must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a interesting product description to gain more sells on Amazon of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $audience
 * @param $language
 * @param $tone
 * @return string
 */
function create_amazon_product_features_prompt($title, $description, $audience, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the features must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 advantages and features to gain more sells on Amazon of the following product aimed at:\n\n" . $audience . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_social_post_personal_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the post must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a social media post for personal account about:\n\n" . $description . "\n\n" . $tone_text;

}


/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $information
 * @param $language
 * @param $tone
 * @return string
 */
function create_social_post_business_prompt($title, $information, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the post must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a social media post for company account about:\n\n" . $description . "\n\n Company name:\n" . $title . "\n\n Company Information:\n" . $information . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_instagram_captions_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the caption must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write an interesting caption for an Instagram post about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_instagram_hashtags_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the hashtags must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write trendy hashtags for an Instagram post about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_twitter_tweets_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the tweet must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write a trending tweet for a Twitter post about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_youtube_titles_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the titles must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 catchy titles for a Youtube video about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_youtube_descriptions_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the description must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write an interesting description for a Youtube video about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_youtube_outlines_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the outlines must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write interesting outlines for a Youtube video about:\n\n" . $description . "\n\n" . $tone_text;

}


/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_linkedin_posts_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the posts must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write inspiring posts for LinkedIn about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_tiktok_video_scripts_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the ideas must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write 10 viral ideas for a short video about:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_meta_tags_blog_prompt($title, $description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the generated text must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write SEO meta title and description for a blog post about:\n\n" . $description . "\n\n Blog title:\n" . $title . "\n\n Seed Words:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_meta_tags_homepage_prompt($title, $description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the generated text must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write SEO meta title and description for a website about:\n" . $description . "\n\nWebsite Name:\n" . $title . "\n\nSeed Words:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $company_name
 * @param $title
 * @param $description
 * @param $keywords
 * @param $language
 * @param $tone
 * @return string
 */
function create_meta_tags_product_prompt($company_name, $title, $description, $keywords, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the generated text must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write SEO meta title and description for a product page about:\n\n" . $description . "\n\n Product Title:\n" . $title . "\n\n Company Name:\n" . $company_name . "\n\n Seed Words:\n" . $keywords . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_tone_changer_prompt($description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone :\n" . $tone . "\n\n";
    }
    return $lang_text . "Change the tone of voice of this text:\n\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $title
 * @param $genre
 * @param $language
 * @param $tone
 * @return string
 */
function create_song_lyrics_prompt($title, $genre, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the lyrics must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Write unique song lyrics about:\n" . $title . "\n\nGenre of the song must be:\n" . $genre . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $language
 * @param $tone
 * @return string
 */
function create_translate_prompt($description, $language, $tone)
{

    return "Translate this into " . get_ai_languages($language) . " with the $tone tone of voice:\n\n" . $description . "\n\n";
}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $language
 * @param $tone
 * @return string
 */
function create_faqs_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the questions must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Generate list of 10 frequently asked questions based on description:\n\n" . $description . "\n\n Product name:\n" . $title . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $question
 * @param $language
 * @param $tone
 * @return string
 */
function create_faq_answers_prompt($title, $description, $question, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the answers must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Generate creative 5 answers to question:\n\n" . $question . "\n\n Product name:\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;

}

/**
 * Create prompt
 *
 * @param $description
 * @param $title
 * @param $language
 * @param $tone
 * @return string
 */
function create_testimonials_reviews_prompt($title, $description, $language, $tone)
{
    $lang_text = '';
    $tone_text = '';
    if (!is_null($language)) {
        $lang_text = "Provide response in " . get_ai_languages($language) . " language.\n\n ";
    }
    if ($tone) {
        $tone_text = "Tone of voice of the customer review must be:\n" . $tone . "\n\n";
    }
    return $lang_text . "Create 5 creative customer reviews for a product. Product name:\n\n" . $title . "\n\n Product description:\n" . $description . "\n\n" . $tone_text;
}


/**
 * Cron Jobs
 *
 * @return false|void
 */
function run_cron_job()
{

    global $config, $lang, $link;
    $pdo = ORM::get_db();
    $cron_time = isset($config['cron_time']) ? $config['cron_time'] : 0;
    $cron_exec_time = isset($config['cron_exec_time']) ? $config['cron_exec_time'] : "300";

    if ((time() - $cron_exec_time) > $cron_time) {

        ignore_user_abort(1);
        @set_time_limit(0);

        $start_time = time();
        update_option('cron_time', time());

        /**
         * START REMOVE OLD PENDING TRANSACTIONS IN 3 Days
         *
         */
        $expiry_time = time() - 259200;
        ORM::for_table($config['db']['pre'] . 'transaction')
            ->where_any_is(array(
                array('status' => 'pending', 'transaction_time' => $expiry_time)), array('transaction_time' => '<'))
            ->delete_many();
        // END REMOVE OLD PENDING TRANSACTIONS

        /**
         * START REMOVE EXPIRED UPGRADES IN 24 Hours
         *
         */
        $expire_membership = 0;
        $expiry_time = time() - 86400;


        $result = ORM::for_table($config['db']['pre'] . 'upgrades')
            ->select_many('upgrade_id', 'user_id')
            ->where_lt('upgrade_expires', $expiry_time)
            ->find_many();
        foreach ($result as $info) {
            $person_count = ORM::for_table($config['db']['pre'] . 'user')
                ->where('id', $info['user_id'])
                ->count();
            if ($person_count) {
                $person = ORM::for_table($config['db']['pre'] . 'user')->find_one($info['user_id']);
                $person->group_id = 'free';
                $person->save();
            }
            ORM::for_table($config['db']['pre'] . 'upgrades')
                ->where_equal('upgrade_id', $info['upgrade_id'])
                ->delete_many();

            $expire_membership++;
        }
        // END REMOVE EXPIRED UPGRADES

        $end_time = (time() - $start_time);

        $cron_details = "Expire membership: " . $expire_membership . "<br>";
        $cron_details .= "<br>";
        $cron_details .= "Cron Took: " . $end_time . " seconds";

        log_adm_action('Cron Run', $cron_details);
    } else {
        return false;
    }
}

run_cron_job();