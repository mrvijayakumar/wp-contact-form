<?php
//Create a page and add short code [cf] to show the form
//Form Validation handled using HTML5. If required, i will write in JavaScript

//Hooks
add_shortcode('cf', 'contact_form');
add_action( 'admin_post_nopriv_cf_action', 'cf_cb' );
add_action( 'admin_post_cf_action', 'cf_cb' );
add_action( 'admin_menu', 'cf_menu');
add_action( 'admin_enqueue_scripts', 'cf_enqueue_scripts' );

//Contact Form
function contact_form() {    
    if (isset($_GET['saved'])) { 
        $msg_to_display = $_GET['saved'] == 1 ? _e('Success! Message sent sucessfully.') : _e('Something went wrong. Please try again');      
        echo '<div class="updated"><p>' . $msg_to_display . '</p></div>';
    }
?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST" class="contact-form">
    <p>
        <label for="cf_name"><?php _e( 'Name' ); ?></label>
        <input id="cf_name" autocomplete="off" required type="text" name="cf_name" />
    </p>
    <p>
        <label for="cf_email"><?php _e( 'Email' ); ?></label>
        <input id="cf_email" autocomplete="off" required type="email" name="cf_email"  />
    </p>
    <p>
        <label for="cf_phone"><?php _e( 'Phone Number' ); ?></label>
        <input id="cf_phone" autocomplete="off" required type="tel" name="cf_phone" pattern="[0-9]{10}" />
    </p>
    <p>
        <input id="cf_submit" type="submit" name="cf_submit" value="<?php _e( 'Submit' ); ?>" />
    </p>
    <input type="hidden" name="action" value="cf_action">
    <input type="hidden" name="curent_page" value="<?php echo get_permalink();?>">
</form>
<?php
}

//Perform action on submission
function cf_cb() {
    $name = sanitize_text_field($_POST['cf_name']);
    $email = sanitize_email($_POST['cf_email']);
    $phone = absint($_POST['cf_phone']); 
    $curent_page = esc_url_raw($_POST['curent_page']);
    if(empty($name) || empty($email) || empty($phone)) {
        wp_redirect($curent_page . '?saved=2');
        return;
    } 
    perform_mailer($name, $email, $phone);
    perform_save($name, $email, $phone);
    wp_redirect($curent_page . '?saved=1');
}

//Send mail to administrator
function perform_mailer($name, $email, $phone) {    
    $isMailSent = false;
    $subject = "Mail through Contact Form";    
    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: '.$name.' <'.$email.'>');
    $to = get_option('admin_email');
    $body = "Received mail from <strong>Name: </strong>".$name."<br/><strong>Phone: </strong>".$phone."<br/><strong>Email: </strong>".$email;
    $isMailSent = wp_mail( $to, $subject, $body, $headers ); //Works with SMTP(recommended) and also default PHP mail() function.
    return $isMailSent;
} 

//Save to DB
function perform_save($name, $email, $phone) {
    global $wpdb;
    $table_name = $wpdb->prefix . "cf";
    $charset_collate = $wpdb->get_charset_collate();
    //Create Table if not exists
    $create_table = "CREATE TABLE $table_name (
        `cf_id` int(11) NOT NULL AUTO_INCREMENT,
        `cf_name` varchar(256) NOT NULL,
        `cf_email` varchar(256) NOT NULL,
        `cf_phone` varchar(256) NOT NULL,
        `cf_date` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY  (cf_id) 
    ) $charset_collate;";   
    echo $create_table;   
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    maybe_create_table( $table_name, $create_table );

    //insert the record into table
    $wpdb->insert($table_name, array('cf_name' => $name, 'cf_email' => $email, 'cf_phone' => $phone) );
}

//Create Menu in Dashboard
function cf_menu() {
    add_menu_page(_e('Contact Form', 'cf_form' ), _e( 'Contact Form', 'cf_form' ), 'manage_options', 'cf_form', 'cf_content', 'dashicons-hourglass', 3);
}

//Show Content form entries in dashboard
function cf_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . "cf";
?>
    <script>
    (function($) {$(document).ready( function () {$('#cf_table').DataTable({"columnDefs": [{"className": "dt-center", "targets": "_all"}]});} );})(jQuery);
    </script>
    <div style="background: #fff; width: 80%; margin: 0 auto; border: 1px solid #ccc; padding: 0 3% 3%; margin-top: 5%;">
    <h1>Contact Form Entries</h1>
    <table id="cf_table" class="display">
        <thead>
            <tr>
                <th>Reference ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Received Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $results = $wpdb->get_results( "SELECT * FROM $table_name");
                foreach($results as $row){ 
            ?>
            <tr>
                <td><?php echo $row->cf_id;?></td>
                <td><?php echo $row->cf_name;?></td>
                <td><?php echo $row->cf_email;?></td>
                <td><?php echo $row->cf_phone;?></td>
                <td><?php echo $row->cf_date;?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
<?php
}

//Adding JS/CSS dependencies required for sorting/filtering
function cf_enqueue_scripts() {
    wp_enqueue_style('cf_form', 'http://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css', false, '1.0' );
    wp_enqueue_script('cf_form', 'http://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js', array('jquery'), '1.0', true );
}