(function ($) {
    "use strict";

    const $form = $('#ai-chat-form');
    const $error = $form.find('.form-error')
    const $msgInput = $('#ai-chat-textarea');
    const $bot_id = $('#bot_id');
    const $msgChat = $("#dynamic-messages");
    const $msgChatInner = $(".message-content-inner");
    const $msgSendBtn = $("#chat-send-button");
    const $msgLoader = $("#conversation-loader");

    $(document).ready(function () {
        $(".conversation").first().trigger('click');
    });

    // load chats
    $(document).on('click', ".conversation", function (e) {
        e.preventDefault();
        e.stopPropagation();
        let $this = $(this);
        let $conv_id = $this.data('id');

        $('.messages-inbox').removeClass('open');

        if($this.closest('li').hasClass('active-message'))
            return;

        $msgChat.html('');

        // set active
        $(".conversation").closest('li').removeClass('active-message');
        $this.closest('li').addClass('active-message');
        $(".conversation").removeClass('active');
        $this.addClass('active');

        $error.slideUp();

        if($conv_id === ''){
            $msgLoader.fadeOut('fast');
            return;
        }

        var formData = new FormData();
        formData.append('conv_id', $conv_id);

        if($conv_id === 'default')
            formData.append('bot_id', $bot_id.val());

        $msgLoader.fadeIn('fast');
        $.ajax({
            type: "POST",
            url: ajaxurl + '?action=load_ai_chats',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function (response) {
                $msgLoader.fadeOut('fast');
                if (response.success) {

                    let last_time = null;
                    for (const chat of response.chats) {

                        /* add date */
                        if(last_time){
                            var end = new Date(last_time);
                        }else{
                            var end = new Date();
                        }
                        var start = new Date(chat.date),
                            diff  = new Date(end - start),
                            days  = Math.round(Math.abs(diff/1000/60/60/24));
                        if(days){
                            $msgChat.append('<div class="message-time-sign"><span>'+chat.date_formatted+'</span></div>');
                        }
                        last_time = chat.date;

                        appendMessage(PERSON_NAME, PERSON_IMG, "right", chat.user_message);
                        appendMessage(BOT_NAME, BOT_IMG, "left", chat.ai_message, "");
                    }

                    hljs.highlightAll();

                    $msgChatInner.animate({
                        scrollTop: $msgChatInner.prop("scrollHeight")
                    }, 1);
                } else {
                    $error.html(response.error).slideDown().focus();
                }
            }
        });
    });

    $("#new-conversation").on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $('#conversations-wrapper').prepend(`<li>
                                        <a href="javascript:void(0)"
                                           class="conversation"
                                           data-id="">
                                            <div class="message-by margin-left-0">
                                                <div class="message-by-headline">
                                                    <h5>${LANG_NEW_CONVERSATION}</h5>
                                                    <span class="conversation-time">${LANG_JUST_NOW}</span>
                                                </div>
                                                <p class="conversation-msg">...</p>
                                            </div>
                                        </a>
                                    </li>`);
        $(".conversation").first().focus().trigger('click');
    });

    // edit conversation title
    $(document).on('click', ".conversation-edit", function (e) {
        e.preventDefault();
        e.stopPropagation();

        let $conversation = $(this).closest('li');

        $conversation.find('.conversation-title').show().focus();
        $conversation.find('h5').hide();

    })
        // On blur
        .on('blur', '.conversation-title', update_title)
        // On enter
        .on('keypress', '.conversation-title', function (e) {
            var code = e.keyCode || e.which;
            if (code == 13) {
                update_title.apply(this);
            }
        })

    function update_title() {
        var $this = $(this),
            $item = $this.closest('li'),
            $edit_icon = $item.find('.conversation-edit'),
            $name = $item.find('h5'),
            value = $this.val(),
            id = $item.find('a').data('id'),
            data = {id: id, title: value};

        if($name.text() != value) {
            $edit_icon.addClass('button-loader button-loader-dark').prop('disabled',true);
            $.post(ajaxurl + '?action=edit_conversation_title', data, function () {
                $edit_icon.removeClass('button-loader button-loader-dark').prop('disabled', false);
            });
        }
        // Show modified category name.
        $name.text(value);
        // Hide input field.
        $this.hide();
        $name.show();
    }

    // delete chats
    $('#delete-chats').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);

        let $conv_id = $(".conversation.active").data('id');

        if($conv_id === ''){
            delete_conversation();
            return;
        }

        if (confirm(LANG_ARE_YOU_SURE)) {
            $btn.addClass('button-progress').prop('disabled', true);

            $.ajax({
                type: "POST",
                url: ajaxurl + '?action=delete_ai_chats&bot_id=' + $bot_id.val() + '&conv_id=' + $conv_id,
                dataType: 'json',
                success: function (response) {
                    $btn.removeClass('button-progress').prop('disabled', false);
                    if (response.success) {
                        delete_conversation();
                    }
                }
            });
        }
    });
    function delete_conversation(){
        $(".conversation.active").closest('li').slideUp("fast",
            function() {
                $(this).remove();

                /* Check if conversations exist */
                if($(".conversation").length){
                    $(".conversation").first().focus().trigger('click');
                } else {
                    $("#new-conversation").trigger('click');
                }
            }
        );
    }

    // export chat
    $('#export-chats').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        $btn.addClass('button-progress').prop('disabled', true);
        $error.slideUp();
        $.ajax({
            type: "POST",
            url: ajaxurl + '?action=export_ai_chats&bot_id=' + $bot_id.val() + '&conv_id=' + $(".conversation.active").data('id'),
            dataType: 'json',
            success: function (response) {
                $btn.removeClass('button-progress').prop('disabled', false);
                if (response.success) {

                    var downloadableLink = document.createElement('a');
                    downloadableLink.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(response.text));
                    downloadableLink.download = "Chats" + ".txt";
                    document.body.appendChild(downloadableLink);
                    downloadableLink.click();
                    document.body.removeChild(downloadableLink);
                } else {
                    $error.html(response.error).slideDown().focus();
                }
            }
        });
    });

    // template search
    $('#conversation-search').on('keyup', function () {
        var searchTerm = $(this).val().toLowerCase();
        $('#conversations-wrapper').find('li').each(function () {
            if ($(this).filter(function() {
                return $(this).find('h5').text().toLowerCase().indexOf(searchTerm) > -1;
            }).length > 0 || searchTerm.length < 1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    /* show conversations on mobile */
    $("#show-conversations").on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $('.messages-inbox').toggleClass('open');
    });

    // on send
    $form.on('submit', function (e) {
        e.preventDefault();

        const msgText = $msgInput.val();
        if (!msgText) return;

        // append user message
        appendMessage(PERSON_NAME, PERSON_IMG, "right", msgText);
        $msgInput.val('');

        // append bot message
        let id = $msgChat.find('.message-bubble').length;
        appendMessage(BOT_NAME, BOT_IMG, "left", "", id);

        // scroll
        $msgChatInner.animate({
            scrollTop: $msgChatInner.prop("scrollHeight")
        }, 500);

        let $div = $("#msg-" + id);
        let $msg_txt = $div.find('.markdown-body');
        let $typing = $div.find('.typing-indicator');

        $msgSendBtn.addClass('button-progress').prop('disabled', true);
        var formData = new FormData();
        formData.append('msg', msgText);
        formData.append('bot_id', $bot_id.val());
        formData.append('conv_id', $(".conversation.active").data('id'));

        $error.slideUp();
        $.ajax({
            type: "POST",
            url: ajaxurl + '?action=send_ai_message',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function (response) {
                $msgSendBtn.removeClass('button-progress').prop('disabled', false);

                if (response.success) {
                    $typing.hide();
                    $msg_txt.show();

                    let $active_conversation = $('.conversation.active');
                    $active_conversation.data('id', response.conversation_id);
                    $active_conversation.find('.conversation-time').html(LANG_JUST_NOW);
                    $active_conversation.find('.conversation-msg').html(response.last_message);

                    /* move the conversation to top */
                    var $myLi = $active_conversation.closest('li');
                    if(!$myLi.is(':first-child'))
                    {
                        var $myUl = $active_conversation.closest('ul');
                        var listHeight = $myUl.innerHeight();
                        var elemHeight = $myLi.height();
                        var elemTop = $myLi.position().top;
                        var moveUp = listHeight - (listHeight - elemTop);
                        var moveDown = elemHeight;

                        $("#conversations-wrapper li").each(function() {
                            if ($(this).find('.conversation').hasClass('active')) {
                                return false;
                            }
                            $(this).animate({
                                "top": '+=' + moveDown
                            }, 400);
                        });

                        $myLi.animate({
                            "top": '-=' + moveUp
                        }, 400, function() {
                            $myLi.prependTo('#conversations-wrapper')
                            $myUl.children("li").attr("style", "");
                        });
                    }

                    if(!ENABLE_TYPING_EFFECT) {
                        response.message = escape_html(response.message);
                        $msg_txt.html(response.message);
                        hljs.highlightAll();
                    } else {
                        var str = escape_html( response.message),
                            i = 0,
                            isTag,
                            text;
                        (function type() {
                            if (i < str.length) {
                                text = str.slice(0, ++i);
                                if (text === str) return;

                                $msg_txt.html(text);
                                hljs.highlightAll();

                                $msgChatInner.animate({
                                    scrollTop: $msgChatInner.prop("scrollHeight")
                                }, 0);

                                var char = text.slice(-1);
                                if (char === '<') isTag = true;
                                if (char === '>') isTag = false;


                                if (isTag) return type();
                                setTimeout(type, 10);
                            } else {
                                $msgChatInner.animate({
                                    scrollTop: $msgChatInner.prop("scrollHeight")
                                }, 500);
                            }
                        }());
                    }

                    $msgChatInner.animate({
                        scrollTop: $msgChatInner.prop("scrollHeight")
                    }, 500);

                    animate_value('quick-words-left', response.old_used_words, response.current_used_words, 4000);
                } else {
                    $error.html(response.error).slideDown().focus();
                }
            },
            error: function (){
                $msgSendBtn.removeClass('button-progress').prop('disabled', false);
            }
        });
    });
    $msgInput.on('keypress', function (e) {
        if(ENABLE_ENTER_TO_SEND){
            if (e.keyCode == 13 && !e.shiftKey) {
                $form.trigger('submit');
                return false;
            }
        } else {
            if (e.keyCode == 13 && e.shiftKey) {
                $form.trigger('submit');
                return false;
            }
        }
    });

    /* Prompt library */
    $('#chat-prompts').on('click', function(e){
        e.preventDefault();

        $.magnificPopup.open({
            items: {
                src: '#prompt-library-popup',
                type: 'inline',
                fixedContentPos: false,
                fixedBgPos: true,
                overflowY: 'auto',
                closeBtnInside: true,
                preloader: false,
                midClick: true,
                removalDelay: 300,
                mainClass: 'my-mfp-zoom-in'
            }
        });
    });

    $('#prompt-search').on('keyup', function () {
        var searchTerm = $(this).val().toLowerCase();
        $('#chat-prompts-list').find('a').each(function () {
            if ($(this).filter(function() {
                return $(this).data('search-key').toLowerCase().indexOf(searchTerm) > -1;
            }).length > 0 || searchTerm.length < 1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('#chat-prompts-list').find('a').on('click', function (e) {
        e.preventDefault();

        $msgInput.val($(this).data('prompt'));
        $.magnificPopup.close();
        $msgInput.focus();
    });

    /* microphone (speech to text) */
    const microphoneButton = document.querySelector('#chat-microphone');
    let isTranscribing = false; // Initially not transcribing

    if(microphoneButton) {
        if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
            const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();

            recognition.continuous = true;

            recognition.addEventListener('start', () => {
                $msgSendBtn.attr("disabled", true);
                $("#chat-microphone").find('i').removeClass('fa-microphone').addClass('fa-stop-circle');
            });

            recognition.addEventListener('result', (event) => {
                const transcript = event.results[0][0].transcript;
                $msgInput.val($msgInput.val() + transcript + ' ');

                microphoneButton.click();
            });

            recognition.addEventListener('end', () => {
                $msgSendBtn.attr("disabled", false);
                $("#chat-microphone").find('i').addClass('fa-microphone').removeClass('fa-stop-circle');
                isTranscribing = false;
            });

            microphoneButton.addEventListener('click', () => {
                if (!isTranscribing) {
                    // Start transcription if not transcribing
                    recognition.start();
                    isTranscribing = true;
                } else {
                    // Stop transcription if already transcribing
                    recognition.stop();
                    isTranscribing = false;
                }
            });
        } else {
            console.log('Web Speech Recognition API not supported by this browser');
            $("#chat-microphone").hide()
        }
    }

    function appendMessage(name, img, side, text, id) {

        let typing = (text.length === 0 && side === 'left')
            ? `<div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>`
            : '';
        let msg_show = (text.length === 0 && side === 'left') ? 'style="display:none"' : '';

        side = (side === 'right') ? 'me' : '';

        text = escape_html(text);

        const msgHTML = `
            <div class="message-bubble ${side}">
                <div class="message-bubble-inner">
                    <div class="message-avatar"><img src="${img}" alt="${name}" /></div>
                    <div class="message-text" id="msg-${id}">
                        ${typing}
                        <div class="markdown-body" ${msg_show}>${text}</div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
          `;

        $msgChat.append(msgHTML);
    }

    function animate_value(id, start, end, duration) {
        if (start === end) return;
        var range = end - start;
        var current = start;
        var increment = end > start ? 1 : -1;
        var stepTime = Math.abs(Math.floor(duration / range));
        var obj = document.getElementById(id);
        var timer = setInterval(function () {
            current += increment;
            obj.innerHTML = current;
            if (current == end) {
                clearInterval(timer);
            }
        }, stepTime);
    }

    /* convert the api response */
    function escape_html (str) {
        let converter = new showdown.Converter({openLinksInNewWindow: true});
        converter.setFlavor('github');
        str = converter.makeHtml(str);

        /* add copy button */
        str = str.replace('</code></pre>', '</code><button class="copy-ai-code" onclick="copyAICode(this)"><svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg> <span class="label-copy-code">' + LANG_COPY + '</span></button></pre>');

        return str;
    }
})(jQuery);