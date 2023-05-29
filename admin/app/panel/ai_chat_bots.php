<?php
require_once '../../includes.php';

$info = array(
    'id' => '',
    'name' => '',
    'image' => '',
    'welcome_message' => '',
    'role' => '',
    'prompt' => '',
    'active' => '1',
);
if(!empty($_GET['id'])) {
    $info = ORM::for_table($config['db']['pre'] . 'ai_chat_bots')->find_one(validate_input($_GET['id']));
}

if(!empty($info['image']))
    $img_url = $config['site_url'].'storage/chat-bots/'.$info['image'];
else
    $img_url = get_avatar_url_by_name($info['name']);
?>
<div class="slidePanel-content">
    <header class="slidePanel-header">
        <div class="slidePanel-overlay-panel">
            <div class="slidePanel-heading">
                <h2><?php echo isset($_GET['id']) ? __('Edit Chat Bot') : __('Add Chat Bot'); ?></h2>
            </div>
            <div class="slidePanel-actions">
                <button id="post_sidePanel_data" class="btn-icon btn-primary" title="<?php _e('Save') ?>">
                    <i class="icon-feather-check"></i>
                </button>
                <button class="btn-icon slidePanel-close" title="<?php _e('Close') ?>">
                    <i class="icon-feather-x"></i>
                </button>
            </div>
        </div>
    </header>
    <div class="slidePanel-inner">
        <form method="post" data-ajax-action="editAIChatBot" id="sidePanel_form">
            <div class="form-body">
                <?php if(isset($_GET['id'])){ ?>
                    <input type="hidden" name="id" value="<?php _esc($_GET['id'])?>">
                <?php } ?>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-2">
                            <img src="<?php _esc($img_url) ?>" width="80" class="rounded" alt="">
                        </div>
                        <div class="col-md-10">
                            <label for="image"><?php _e("Profile Picture"); ?></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="image" name="image"
                                       accept="image/png, image/gif, image/jpeg">
                                <label class="custom-file-label" for="image"><?php _e("Choose file..."); ?></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name"><?php _e("Name"); ?></label>
                    <input id="name" type="text" class="form-control" name="name" value="<?php _esc($info['name']) ?>">
                </div>
                <div class="form-group">
                    <label for="role"><?php _e("Role"); ?></label>
                    <input id="role" type="text" class="form-control" name="role" value="<?php _esc($info['role']) ?>">
                </div>
                <div class="form-group">
                    <label for="welcome_message"><?php _e("Welcome Message"); ?></label>
                    <textarea name="welcome_message" rows="2" class="form-control" id="welcome_message"><?php _esc($info['welcome_message']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="prompt"><?php _e("Prompt"); ?></label>
                    <textarea name="prompt" rows="2" class="form-control" id="prompt"><?php _esc($info['prompt']) ?></textarea>
                </div>
                <?php
                quick_switch(__('Active'),'active', $info['active']); ?>
            </div>
        </form>
    </div>
</div>