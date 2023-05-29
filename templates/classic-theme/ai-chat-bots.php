<?php

overall_header(__("AI Chat Bots"));
?>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <?php
        include_once TEMPLATE_PATH . '/dashboard_sidebar.php';
        ?>
        <!-- Dashboard Content
        ================================================== -->
        <div class="dashboard-content-container" data-simplebar>
            <div class="dashboard-content-inner">
                <?php print_adsense_code('header_bottom'); ?>
                <!-- Dashboard Headline -->
                <div class="dashboard-headline">
                    <h3 class="d-flex align-items-center">
                        <?php _e("AI Chat Bots") ?>
                        <div class="word-used-wrapper margin-left-10">
                            <i class="icon-feather-bar-chart-2"></i>
                            <?php echo '<i id="quick-words-left">' .
                                _esc(number_format((float)$total_words_used), 0) . '</i> / ' .
                                ($words_limit == -1
                                    ? __('Unlimited')
                                    : _esc(number_format($words_limit), 0));
                            ?>
                            <strong><?php _e('Words Used'); ?></strong>
                        </div>
                    </h3>
                    <!-- Breadcrumbs -->
                    <nav id="breadcrumbs" class="dark">
                        <ul>
                            <li><a href="<?php url("INDEX") ?>"><?php _e("Home") ?></a></li>
                            <li><?php _e("AI Chat Bots") ?></li>
                        </ul>
                    </nav>
                </div>
                <?php if ($membership_ai_chat) { ?>
                    <div class="notification notice">
                        <?php _e("We provide a team of skilled AI experts who are prepared to assist you with a wide range of needs.") ?>
                    </div>
                <?php } else { ?>
                    <div class="notification small-notification error">
                        <?php _e("Upgrade your membership plan to use this feature.") ?>
                    </div>
                <?php } ?>

                <div class="row">
                    <?php
                    /* if default chat bot is enabled */
                    if (get_option("enable_default_chat_bot", 1)) { ?>
                        <div class="col-md-3">
                            <div class="dashboard-box margin-top-0 margin-bottom-30">
                                <div class="content text-center">
                                    <img src="<?php _esc($config['site_url']); ?>storage/profile/<?php _esc($ai_chat_bot_avatar) ?>"
                                         alt="<?php _esc($ai_chat_bot_name) ?>" class="rounded" width="100%">
                                    <div class="padding-top-20 padding-right-20 padding-left-20 padding-bottom-20">
                                        <h3><?php _esc($ai_chat_bot_name) ?></h3>
                                        <small><?php _e('Default Bot'); ?></small>
                                        <div class="margin-top-15">
                                            <a href="<?php url('AI_CHAT') ?>"
                                               class="button button-sliding-icon ripple-effect full-width"><?php _e('Chat Now') ?>
                                                <i class="icon-feather-arrow-right"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php foreach ($chat_bots as $chat_bot) {
                        if (!empty($chat_bot['image']))
                            $img_url = $config['site_url'] . 'storage/chat-bots/' . $chat_bot['image'];
                        else
                            $img_url = get_avatar_url_by_name($chat_bot['name']);
                        ?>
                        <div class="col-md-3">
                            <div class="dashboard-box margin-top-0 margin-bottom-30">
                                <div class="content text-center">
                                    <img src="<?php _esc($img_url); ?>" alt="<?php _esc($chat_bot['name']) ?>"
                                         class="rounded" width="100%">
                                    <div class="padding-top-20 padding-right-20 padding-left-20 padding-bottom-20">
                                        <h3><?php _esc($chat_bot['name']) ?></h3>
                                        <small class="margin-top-10"><?php _esc($chat_bot['role']) ?></small>
                                        <div class="margin-top-15">
                                            <a href="<?php url('AI_CHAT') ?>/<?php _esc($chat_bot['id']) ?>"
                                               class="button button-sliding-icon ripple-effect full-width"><?php _e('Chat Now') ?>
                                                <i class="icon-feather-arrow-right"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>


                <?php print_adsense_code('footer_top'); ?>
                <!-- Footer -->
                <div class="dashboard-footer-spacer"></div>
                <div class="small-footer margin-top-15">
                    <div class="footer-copyright">
                        <?php _esc($config['copyright_text']); ?>
                    </div>
                    <ul class="footer-social-links">
                        <?php
                        if ($config['facebook_link'] != "")
                            echo '<li><a href="' . _esc($config['facebook_link'], false) . '" target="_blank" rel="nofollow"><i class="fa fa-facebook"></i></a></li>';
                        if ($config['twitter_link'] != "")
                            echo '<li><a href="' . _esc($config['twitter_link'], false) . '" target="_blank" rel="nofollow"><i class="fa fa-twitter"></i></a></li>';
                        if ($config['instagram_link'] != "")
                            echo '<li><a href="' . _esc($config['instagram_link'], false) . '" target="_blank" rel="nofollow"><i class="fa fa-instagram"></i></a></li>';
                        if ($config['linkedin_link'] != "")
                            echo '<li><a href="' . _esc($config['linkedin_link'], false) . '" target="_blank" rel="nofollow"><i class="fa fa-linkedin"></i></a></li>';
                        if ($config['pinterest_link'] != "")
                            echo '<li><a href="' . _esc($config['pinterest_link'], false) . '" target="_blank" rel="nofollow"><i class="fa fa-pinterest"></i></a></li>';
                        if ($config['youtube_link'] != "")
                            echo '<li><a href="' . _esc($config['youtube_link'], false) . '" target="_blank" rel="nofollow"><i class="fa fa-youtube"></i></a></li>';
                        ?>
                    </ul>
                    <div class="clearfix"></div>
                </div>

            </div>
        </div>
    </div>
<?php ob_start() ?>
<?php
$footer_content = ob_get_clean();
include_once TEMPLATE_PATH . '/overall_footer_dashboard.php';