<?php
require_once '../../includes.php';

$info = array(
    'id' => '',
    'title' => '',
    'chat_bots' => '',
    'description' => '',
    'prompt' => '',
    'active' => '1',
);
if (!empty($_GET['id'])) {
    $info = ORM::for_table($config['db']['pre'] . 'ai_chat_prompts')->find_one(validate_input($_GET['id']));
}

$chat_bots = ORM::for_table($config['db']['pre'] . 'ai_chat_bots')
    ->where('active', 1)
    ->order_by_asc('position')
    ->find_array();

?>
<div class="slidePanel-content">
    <header class="slidePanel-header">
        <div class="slidePanel-overlay-panel">
            <div class="slidePanel-heading">
                <h2><?php echo isset($_GET['id']) ? __('Edit Chat Prompts') : __('Add Chat Prompts'); ?></h2>
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
        <form method="post" data-ajax-action="editAIChatPrompts" id="sidePanel_form">
            <div class="form-body">
                <?php if (isset($_GET['id'])) { ?>
                    <input type="hidden" name="id" value="<?php _esc($_GET['id']) ?>">
                <?php } ?>

                <div class="form-group">
                    <label for="title"><?php _e("Title"); ?></label>
                    <input id="title" type="text" class="form-control" name="title"
                           value="<?php _esc($info['title']) ?>">
                </div>
                <div class="form-group">
                    <label for="description"><?php _e("Description"); ?></label>
                    <textarea name="description" rows="2" class="form-control"
                              id="description"><?php _esc($info['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="chat_bots"><?php _e("Chat Bots"); ?></label>
                    <select id="chat_bots" class="form-control quick-select2" name="chat_bots[]" multiple>
                        <?php
                        $bots = explode(',', $info['chat_bots']);
                        if (get_option("enable_default_chat_bot", 1)) { ?>
                            <option value="default" <?php echo in_array('default', $bots) ? 'selected' : '' ?>><?php _e('Default Bot'); ?></option>
                        <?php } ?>
                        <?php
                        foreach ($chat_bots as $chat_bot) {
                            echo '<option value="' . $chat_bot['id'] . '" ' . (in_array($chat_bot['id'], $bots) ? 'selected' : '') . '>' . $chat_bot['name'] . '</option>';
                        }
                        ?>
                    </select>
                    <span class="form-text text-muted"><?php _e('Leave empty for all bots.') ?></span>
                </div>
                <div class="form-group">
                    <label for="prompt"><?php _e("Prompt"); ?></label>
                    <textarea name="prompt" rows="2" class="form-control"
                              id="prompt"><?php _esc($info['prompt']) ?></textarea>
                </div>
                <?php
                quick_switch(__('Active'), 'active', $info['active']); ?>
            </div>
        </form>
    </div>
</div>
<script>
    $('.quick-select2').select2();
</script>