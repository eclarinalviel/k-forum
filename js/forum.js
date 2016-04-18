var $ = jQuery;
$(function() {
    $('body').on('click', '.attach .delete', forum.delete);
    forum.el.postbox().find('form').submit(function(e){
        $(this).find('[type="submit"]').prop('disabled', true);
        return true;
    });
});
var forum = {
    el : {
        postbox : function () {
            return $("#post-new");
        },
        loader : function () {
            return forum.el.postbox().find(".loader");
        },
        photos : function() {
            return forum.el.postbox().find('.photos');
        },
        files : function() {
            return forum.el.postbox().find('.files');
        },
        fileIDs : function() {
            return forum.el.postbox().find('[name="file_ids"]');
        }
    },
    showLoader : function () {
        forum.el.loader().show();
    },
    hideLoader : function () {
        forum.el.loader().hide();
    },
    on_change_file_upload : function (filebox) {
        var filesize = filebox.files[0].size;
        if ( filesize >  max_upload_size ) {
            alert("File size is too big. Exceeded the limit.")
            return;
        }
        var $filebox = $(filebox);
        var $form = $filebox.parents("form");
        var $do = $form.find('[name="do"]');
        $do.val('file_upload');

        this.showLoader();

        $form.ajaxSubmit({
            error : function (xhr) {
                $do.val('post_create');
                forum.hideLoader();
                return alert(xhr.responseText);
            },
            complete: function (xhr) {
                $do.val('post_create');
                forum.hideLoader();
                var re;
                try {
                    re = JSON.parse(xhr.responseText);
                }
                catch (e) {
                    return alert(xhr.responseText);
                }
                console.log(re);
                //trace(re);
                if ( re['success'] == false ) return alert('upload failed.');
                forum.displayAttachment(re);
                forum.addFileID(re);
            }
        });
        $do.val('post_create');
        $filebox.val('');
    },
    displayAttachment : function ( re ) {
        var m;
        var data = re['data'];
        if ( data['file']['type'].indexOf('image') != -1 ) {
            forum.el.photos().append( forum.markup.upload( data ) );
            m = '<img id="id'+data['attach_id']+'" alt="'+data['file']['name']+'" src="'+data['url']+'"/>';
        }
        else {
            forum.el.files().append( forum.markup.upload( data ) );
            m = '<a id="id'+data['attach_id']+'" href="'+data['url']+'">'+data['file']['name']+'</a>';
        }
        tinymce.activeEditor.insertContent(m);
    },
    addFileID : function (re) {
        var val = forum.el.fileIDs().val();
        forum.el.fileIDs().val( val + ',' + re['data']['attach_id']);
    },
    removeFileID : function (re) {
        var id = re['data']['id'];
        var str = ',' + id;
        var ids = forum.el.fileIDs().val();
        var new_ids = ids.replace( str, '' );
        forum.el.fileIDs().val(new_ids);
    },
    markup : {
        upload : /**
         *
         *
         * @Attention This code must have same DOM structure as of forum()->markupAttachments()
         * @param data
         * @returns {string}
         */
            function ( data ) {
                var m = '<div class="attach" attach_id="'+data['attach_id']+'" type="'+data['type']+'">';
                if ( data['file']['type'].indexOf('image') != -1 ) { // image
                    m += '<img src="'+data['url']+'">' +
                        '<div class="delete"><span class="dashicons dashicons-trash"></span> Delete</div>';
                }
                else { // file
                    m += '<a href="'+data['url']+'">'+data['file']['name']+'</a>' +
                        '<span class="delete"><span class="dashicons dashicons-trash"></span> Delete</span>';
                }
                m += "</div>";
//                console.log(m);
                return m;
            }
    },
    delete : function () {

        var $delete = $(this);
        var $attach = $delete.parent('.attach');
        var id = $attach.attr('attach_id');

        console.log($delete);

        var url = url_endpoint + '?do=file_delete&id=' + id;
        console.log(url);

        $.get( url, function( re ) {
            console.log(re);
            if ( re['success'] == true ) {
                var editor = tinymce.activeEditor;
                var content = editor.getContent();
                var ex;
                if ( $attach.attr('type').indexOf('image') != -1 ) {
                    ex = new RegExp('<img[^>]+'+id+'[^>]+>', 'gi'); // patterns, modifiers
                }
                else {
                    ex = new RegExp('<a[^>]+'+id+'[^>]+>[^>]*</a>', 'gi'); // patterns, modifiers
                }
                console.log(ex);
                console.log(content);
                var html = content.replace(ex, '');
                console.log( html );
                editor.setContent(html);
                $attach.remove();
                forum.removeFileID(re);
            }
        });
    }
};
