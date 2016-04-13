<?php

class forum
{
    public function __construct()
    {
    }

    public function setNone404() {
        global $wp_query;
        if ( $wp_query->is_404 ) {
            status_header( 200 );
            $wp_query->is_404=false;
        }
    }

    public function init()
    {
        $this->addAdminMenu();
        $this->addRoutes();
    }

    public function enqueue()
    {
        add_action( 'wp_enqueue_scripts', function() {
            //wp_enqueue_style( 'dashicons' );
            wp_enqueue_script( 'wp-util' );
            wp_enqueue_script( 'jquery-form' );
            wp_enqueue_script( 'forum', FORUM_URL . 'js/forum.js' );
            wp_enqueue_style( 'basic', FORUM_URL . 'css/basic.css' );
            wp_enqueue_script( 'basic', FORUM_URL . 'js/basic.js' );
        });
    }

    private function loadTemplate($file)
    {
        $new_template = locate_template( array( $file ) );
        if ( '' != $new_template ) {
            return $new_template ;
        }
        else {
            return FORUM_PATH . "template/$file";
        }
    }

    private function submit()
    {
        $this->$_REQUEST['do']();
        exit;
    }
    private function post_create() {
        $my_post = array(
            'post_title'    => $_REQUEST['title'],
            'post_content'  => $_REQUEST['content'],
            'post_status'   => 'publish',
            'post_author'   => wp_get_current_user()->ID,
            'post_category' => array( $_REQUEST['category_id'] )
        );
        // Insert the post into the database
        $post_ID = wp_insert_post( $my_post );
        if ( is_wp_error( $post_ID ) ) {
            echo $post_ID->get_error_message();
            exit;
        }
        $url = get_permalink( $post_ID );
        wp_redirect( $url ); // redirect to view the newly created post.
    }
    /**
     *
     *
     *
     * @WARNING
     *
     *      1. It uses md5() to avoid of replacing same file name.
     *          Since it does not add 'tag' like '(1)', '(2) for files which has same file name.
     *
     *      2. It uses md5() to avoid character set problems. like some server does not support utf-8 nor ... Most of servers do not support spanish chars. some servers do not support Korean characters.
     *
     *      3. It uses md5() to avoid possible matters due to lack of developmemnt time.
     *
     */
    private function file_upload() {
        $file = $_FILES["file"];

        dog($file);

        // Sanitize filename.
        $filename = $file["name"];
        $filetype = wp_check_filetype( basename( $filename ), null );
        $sanitized_filename = lib()->sanitize_special_chars( $filename );

        // Get WordPress upload folder.
        $wp_upload_dir = wp_upload_dir();

        // Get URL and Path of uploaded file.
        $path_upload = $wp_upload_dir['path'] . "/$sanitized_filename";
        $url_upload = $wp_upload_dir['url'] . "/$sanitized_filename";

        if ( $file['error'] ) wp_send_json_error( lib()->get_upload_error_message($file['error']) );

        // Move the uploaded file into WordPress uploaded path.
        if ( ! move_uploaded_file( $file['tmp_name'], $path_upload ) ) wp_send_json_error( "Failed on moving uploaded file." );

        // Create a post of attachment.
        $attachment = array(
            'guid'           => $url_upload,
            'post_mime_type' => $filetype['type'],
            'post_title'     => $filename,
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        /**
         * This does not upload a file but creates a 'attachment' post type in wp_posts.
         *
         */
        $attach_id = wp_insert_attachment( $attachment, $filename );
        dog("attach_id: $attach_id");

        // Update post_meta for the attachment.
        // You do it and you can use get_attached_file() and get_attachment_url()
        // update_attached_file will update the post meta of '_wp_attached_file' which is the source of "get_attached_file() and get_attachment_url()"
        update_attached_file( $attach_id, $path_upload );


        wp_send_json_success([
            'attach_id' => $attach_id,
            'url' => $url_upload,
            'type' => current(explode('/',$filetype['type'])),
            'file' => $file,
        ]);
    }

    private function file_delete() {
        $id = $_REQUEST['id'];
        $path = get_attached_file( $id );
        if ( ! file_exists( $path ) ) {
            wp_send_json_error( new WP_Error('file_not_found', "File of ID $id does not exists. path: $path") );
        }
        // wp_delete_attachment() 는 attachment post 와 업로드 된 파일을 같이 삭제한다.
        if ( wp_delete_attachment( $id ) === false ) {
            wp_send_json_error( new WP_Error('failed_on_delete', "File of ID $id does not exists. path: $path") );
        }
        else {
            wp_send_json_success();
        }
    }

    private function addAdminMenu()
    {
        add_action( 'wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            $wp_admin_bar->add_menu( array(
                'id' => 'forum_toolbar',
                'title' => __('K-Forum', 'k-forum'),
                'href' => home_url('wp-admin/admin.php?page=k-forum%2Ftemplate%2Fadmin.php')
            ) );
        });

        add_action('admin_menu', function () {
            add_menu_page(
                __('K-Forum', 'k-forum'), // page title. ( web browser title )
                __('K-Forum', 'k-forum'), // menu name on admin page.
                'manage_options', // permission
                'k-forum/template/admin.php', // slug id. what to open
                '',
                plugin_dir_url( __FILE__ ) . 'icon/siteapi.png', // @todo change icon.
                '23.45' // 표시 우선 순위.
            );
            add_submenu_page(
                'k-forum/template/admin.php', // parent slug id
                __('Forum List', 'k-forum'),
                __('K-Forum List', 'k-forum'),
                'manage_options',
                'k-forum/template/admin-forum-list.php',
                ''
            );
        } );

    }


    private function addRoutes()
    {


        /**
         *
         *
         * 아래의 rewrite_rule 를 사용하지 않고도 template_include 를 통해서 template 을 포함 할 수 있다.
         *
         * 하지만 Main Loop 를 사용 할 수 없다.
         *
         * ReWrite 하는 목적은 Main Loop 를 사용 할 수 있도록 하기 위한 것이다.
         *
         */
        add_action('init', function() {
            add_rewrite_rule(
                '^forum/([a-zA-Z0-9\-]+)/?$',
                'index.php?category_name=$matches[1]',
                'top'
            );
            add_rewrite_rule(
                '^forum/([a-zA-Z0-9\-]+)/([0-9]+)?$',
                'index.php?category_name=$matches[1]&p=$matches[2]',
                'top'
            );
            //add_rewrite_tag('%val%','([^/]*)');
            flush_rewrite_rules();
        });


        add_filter( 'template_include', function ( $template ) {
            $this->setNone404(); // @todo ??
            // http://abc.com/forum/submit
            if ( seg(0) == 'forum' && seg(1) == 'submit' ) {
                forum()->submit();
                exit;
            }
            else if ( seg(0) == 'forum' && seg(1) != null && seg(2) == null  ) {
                return $this->loadTemplate('forum-list-basic.php');
            }
            // http://abc.com/forum/xxxx/edit
            else if ( seg(0) == 'forum' && seg(1) != null && seg(2) == 'edit'  ) {
                return $this->loadTemplate('forum-edit-basic.php');
            }
            // https://abc.com/forum/xxxx/[0-9]+
            else if ( seg(0) == 'forum' && seg(1) != null && is_numeric(seg(2))  ) {
                return $this->loadTemplate('forum-view-basic.php');
            }
            // Matches if the post is under forum category.
            else if ( is_single() ) {
                $category_id = current( get_the_category( get_the_ID() ) )->term_id;
                $ex = explode('/', get_category_parents($category_id, false, '/', true));
                if ( $ex[0] == FORUM_CATEGORY_SLUG ) {
                    return $this->loadTemplate('forum-view-basic.php');
                }
            }
            return $template;
        } );
    }
}
function forum() {
    return new forum();
}
