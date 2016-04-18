<?php

/**
 * Class forum
 * @file forum.php
 * @desc K forum.
 *
 *
 * @todo security issue. (1) check admin permission on admin action like post_create().
 */
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
        return $this;
    }

    public function loadText() {
        load_plugin_textdomain( 'k-forum', FALSE, basename( dirname( FORUM_FILE_PATH ) ) );
        return $this;
    }

    public function activate() {
        $category = get_category_by_slug(FORUM_CATEGORY_SLUG);
        if ( $category ) return;

        if ( ! function_exists('wp_insert_category') ) require_once (ABSPATH . "/wp-admin/includes/taxonomy.php");
        $catarr = array(
            'cat_name' => __('K-Forum', 'k-forum'),
            'category_description' => __("This is K forum.", 'k-forum'),
            'category_nicename' => FORUM_CATEGORY_SLUG,
        );
        $ID = wp_insert_category( $catarr, true );
        if ( is_wp_error( $ID ) ) wp_die($ID->get_error_message());

        $catarr = array(
            'cat_name' => __('Welcome', 'k-forum'),
            'category_description' => __("This is Welcome forum", 'k-forum'),
            'category_nicename' => 'welcome',
            'category_parent' => $ID,
        );
        $ID = wp_insert_category( $catarr, true );
        if ( is_wp_error( $ID ) ) wp_die($ID->get_error_message());

        forum()->post_create([
                'post_title'    => __('Welcome to K forum.', 'k-forum'),
                'post_content'  => __('This is a test post in welcome K forum.', 'k-forum'),
                'post_status'   => 'publish',
                'post_author'   => wp_get_current_user()->ID,
                'post_category' => array( $ID )
        ]);
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

            wp_enqueue_style( 'font-awesome', FORUM_URL . 'css/font-awesome/css/font-awesome.min.css' );
            wp_enqueue_style( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.2/css/bootstrap.min.css' );
            wp_enqueue_script( 'tether', FORUM_URL . 'js/tether.min.js' );
            wp_enqueue_script( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.2/js/bootstrap.min.js' );

        });
        return $this;
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


    /**
     *
     * This method is called by 'http://abc.com/forum/submit' with $_REQUEST['do']
     *
     * Use this function to do action like below that does not display data to web browser.
     *
     *  - ajax call
     *  - submission without display data and redirect to another page.
     *
     *
     * @Note This method can only call a method in 'forum' class.
     *
     *
     */
    private function submit()
    {

        $do_list = [
            'post_create', 'file_upload', 'file_delete',
            'forum_create', 'forum_delete',
            'post_delete',
        ];

        if ( in_array( $_REQUEST['do'], $do_list ) ) $this->$_REQUEST['do']();
        else echo "<h2>You cannot call the method - $_REQUEST[do] because the method is not listed on 'do-list'.</h2>";
        exit;
    }
    private function post_create( $post_arr = array() ) {
        if ( empty($post_arr) ) {
            $post_arr = array(
                'post_title'    => $_REQUEST['title'],
                'post_content'  => $_REQUEST['content'],
                'post_status'   => 'publish',
                'post_author'   => wp_get_current_user()->ID,
                'post_category' => array( $_REQUEST['category_id'] )
            );

            // @todo if it is update, then check the updator's ID.
            if ( $_REQUEST['id'] ) $post_arr['ID'] = $_REQUEST['id'];
        }


        // Insert the post into the database
        $post_ID = wp_insert_post( $post_arr );
        if ( is_wp_error( $post_ID ) ) {
            echo $post_ID->get_error_message();
            exit;
        }
        $url = get_permalink( $post_ID );


        $this->updateFileWithPost($post_ID);
        $this->deleteFileWithNoPost();

        wp_redirect( $url ); // redirect to view the newly created post.
    }

    public function deleteFileWithNoPost()
    {
        $args = array(
            'post_type' => 'attachment',
            'author' => FORUM_FILE_WITH_NO_POST,
            'date_query' => array(
                array(
                    'column' => 'post_date',
                    'before' => '1 day ago',
                )
            ),
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        );
        $files = new WP_Query( $args );
        if ( $files->have_posts() ) {
            while ( $files->have_posts() ) {
                $files->the_post();
                //di( get_post() );
                if ( wp_delete_attachment( get_the_ID() ) === false ) {
                    // error
                }
            }
        }
        // wp_delete_post(); // why this code is here?
    }

    /**
     * Returns the URL of forum submit with the $method.
     *
     * The returned URL will will call the method.
     *
     * @param $method
     * @return string|void
     * @code
     *      <form action="<?php echo forum()->doURL('forum_create')?>" method="post">
     * @encode
     */
    public function doURL($method)
    {
        return home_url("/forum/submit?do=$method");
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
            'post_author'   => FORUM_FILE_WITH_NO_POST,
            'post_mime_type' => $filetype['type'],
            'post_title'     => $filename,
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        /**
         * This does not upload a file but creates a 'attachment' post type in wp_posts.
         *
         */
        $attach_id = wp_insert_attachment( $attachment, $filename );
        add_post_meta( $attach_id, 'author', wp_get_current_user()->ID );
        dog("attach_id: $attach_id");


        // Update post_meta for the attachment.
        // You do it and you can use get_attached_file() and get_attachment_url()
        // update_attached_file will update the post meta of '_wp_attached_file' which is the source of "get_attached_file() and get_attachment_url()"
        update_attached_file( $attach_id, $path_upload );




        wp_send_json_success([
            'attach_id' => $attach_id,
            'url' => $url_upload,
            'type' => $filetype['type'],
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
            wp_send_json_success( array( 'id' => $id ) );
        }
    }

    private function addAdminMenu()
    {
        add_action( 'wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            $wp_admin_bar->add_menu( array(
                'id' => 'forum_toolbar',
                'title' => __('K-Forum', 'k-forum'),
                'href' => forum()->adminURL()
            ) );
        });

        add_action('admin_menu', function () {
            add_menu_page(
                __('K-Forum', 'k-forum'), // page title. ( web browser title )
                __('K-Forum', 'k-forum'), // menu name on admin page.
                'manage_options', // permission
                'k-forum/template/admin.php', // slug id. what to open
                '',
                'dashicons-text',
                '23.45' // list priority.
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


        /**
         *
         * Add routes for friendly URL.
         *
         * @Attention Do 'friendly URL routing ONLY IF it is necessary'.
         *
         *      - Don't do friendly URL routing on file upload submit, delete submit, vote submit, report submit.
         *      - Do friendly URL routing only if it is visiable to user and search engine robot.
         *
         */
        add_filter( 'template_include', function ( $template ) {
            $this->setNone404(); // @todo ??
            // http://abc.com/forum/submit will take all action that does not need to display HTML to web browser.
            //
            // http://abc.com/forum/submit?do=file_upload
            // http://abc.com/forum/submit?do=file_delete
            // http://abc.com/forum/submit?do=post_delete
            // http://abc.com/forum/submit?do=post_vote
            // http://abc.com/forum/submit?do=post_report
            // etc...
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
                dog("add_filter() : is_single()");
                $category_id = current( get_the_category( get_the_ID() ) )->term_id;
                dog("category_id: $category_id");
                $ex = explode('/', get_category_parents($category_id, false, '/', true));
                dog("category slug of the category id: $ex[0]");
                if ( $ex[0] == FORUM_CATEGORY_SLUG ) {
                    return $this->loadTemplate('forum-view-basic.php');
                }
            }
            return $template;
        } );
    }

    private function updateFileWithPost($post_ID)
    {
        $ids = $_REQUEST['file_ids'];
        $arr_ids = explode(',', $ids);
        if ( empty($arr_ids) ) return;
        foreach( $arr_ids as $id ) {
            if ( empty($id) ) continue;
            $author_id = get_post_meta($id, 'author', true);
            wp_update_post(['ID'=>$id, 'post_author' => $author_id, 'post_parent'=>$post_ID]);
            delete_post_meta( $id, 'author', $author_id);

        }
    }

    public function adminURL()
    {
        return home_url('wp-admin/admin.php?page=k-forum%2Ftemplate%2Fadmin.php');
    }


    private function forum_create() {

        if ( ! function_exists('wp_insert_category') ) require_once (ABSPATH . "/wp-admin/includes/taxonomy.php");

        $parent = $_REQUEST['parent'];
        if ( empty($parent) ) $parent = get_category_by_slug( FORUM_CATEGORY_SLUG )->term_id;


        $catarr = array(
            'cat_name' => $_REQUEST['name'],
            'category_description' => $_REQUEST['desc'],
            'category_nicename' => $_REQUEST['id'],
            'category_parent' => $parent,
        );

        $catarr['cat_ID'] = $_REQUEST['category_id'];

        $ID = wp_insert_category( $catarr, true );

        if ( is_wp_error( $ID ) ) wp_die($ID->get_error_message());
        wp_redirect( $this->adminURL() );
    }

    private function forum_delete() {
        if ( ! function_exists('wp_insert_category') ) require_once (ABSPATH . "/wp-admin/includes/taxonomy.php");
        //wp_delete_category();
        $category = get_category( $_REQUEST['category_id']);
        wp_insert_category([
            'cat_ID' => $category->term_id,
            'cat_name' => "Deleted : " . $category->name,
            'category_parent' => 0,
        ]);
        wp_redirect( $this->adminURL() );
    }

    /**
     * @todo permission check
     */
    private function post_delete() {

        $id = $_REQUEST['id'];
        $categories = get_the_category($id);


        // delete files
        $attachments = get_children( ['post_parent' => $id, 'post_type' => 'attachment'] );
        foreach ( $attachments  as $attachment ) {
            wp_delete_attachment( $attachment->ID, true );
        }
        wp_delete_post($id, true);


        // move to forum list.
        if ( ! $categories || is_wp_error( $categories ) ) {
            wp_redirect( home_url() );
        }
        else {
            $category = current($categories);
            wp_redirect( forum()->listURL($category->slug));
        }



    }

    /**
     * Returns HTML markup for display images and attachments.
     *
     * Use this method to display images and attachments.
     *
     * @param $post_ID
     *
     * @return string
     */
    public function markupAttachments( $post_ID ) {

        if ( empty( $post_ID ) ) return null;

        $files = get_children( ['post_parent' => $post_ID, 'post_type' => 'attachment'] );
        if ( ! $files || is_wp_error($files) ) return null;


        $images = $attachments = null;
        foreach ( $files as $file ) {
            $m = "<div class='attach' attach_id='{$file->ID}' type='{$file->post_mime_type}'>";
            if ( strpos( $file->post_mime_type, 'image' ) !== false ) { // image
                $m .= "<img src='{$file->guid}'>";
                $m .= "<div class='delete'><span class='dashicons dashicons-trash'></span> Delete</div>";
                $m .= '</div>';
                $images .= $m;
            }
            else { // attachment
                $m .= "<a href='{$file->guid}'>{$file->post_title}</a>";
                $m .= "<span class='delete'><span class='dashicons dashicons-trash'></span> Delete</span>";
                $m .= '</div>';
                $attachments .= $m;
            }


        }
        return [ 'images' => $images, 'attachments' => $attachments ];
    }

    public function listURL( $slug ) {
        return home_url() . "/forum/$slug";
    }

}

function forum() {
    return new forum();
}