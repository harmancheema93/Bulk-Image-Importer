<?php

/**
 * Plugin Name: Bulk Image Importer
 * Description: Plugin used to bulk import Images in the WP using CSV file.
 * Plugin URI: https://github.com/harmancheema93/Bulk-Image-Importer
 * Author: Harmandeep Singh
 * Version: 1.0.0
 * Author URI: https://profiles.wordpress.org/harmancheema/
 *
 * Text Domain: hsc-importer
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('admin_menu', 'hsc_bulk_importer_menu_page');

if(!function_exists('hsc_bulk_importer_menu_page')){
    function hsc_bulk_importer_menu_page() {
        add_submenu_page( 
            'tools.php',
            'HSC Importer',
            'HSC Importer',
            'manage_options',
            'hsc-importer',
            'hsc_bulk_importer'
        );
    }
}

function hsc_bulk_importer(){
    echo '<div class="wrap">';
 
    echo '<h1>'.esc_html( get_admin_page_title() ) .'</h1>';
     
    echo '<div id="universal-message-container"><h2>Bulk Image Import</h2>';
 
    echo '<form enctype="multipart/form-data" id="hsc-img-importer" class="wp-upload-form" method="post" action="'. esc_html( admin_url( '/tools.php?page=hsc-importer' ) ) .'">';
    echo '<div class="options">';
    echo '<input type="hidden" value="'.date().'">';
    echo '<p><label>Choose CSV file. <!--<a href="#">Click Here</a> for demo file.</label>--><br />';
    echo '<input type="file" name="hsc-csv" value="" Required/></p>';
    echo '<p class="submit"><input type="submit" name="hsc-upload" id="submit" class="button button-primary" value="Upload file and import" disabled=""></p>';
    echo '</div><!-- options --></div><!-- #universal-message-container -->';
    echo '</form></div><!-- .wrap -->';
    
    if(isset($_POST['hsc-upload'])){
        echo 'Uploading....<br>';
  
        $filepath = $_FILES['hsc-csv']['tmp_name'];

        $file = fopen($filepath,"r");
        $header =fgetcsv($file);
        if (($handle = fopen($filepath, 'r')) !== FALSE){
            $count = 1;
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE){
                if($count > 1){
                $image = array_combine($header, $row);
                
                $image_url = $image['Image URL'];

                $upload_dir = wp_upload_dir();
                
                $image_data = file_get_contents( $image_url );
                
                $filename = basename( $image_url );
                
                if ( wp_mkdir_p( $upload_dir['path'] ) ) {
                  $file = $upload_dir['path'] . '/' . $filename;
                }
                else {
                  $file = $upload_dir['basedir'] . '/' . $filename;
                }
                
                file_put_contents( $file, $image_data );
                
                $wp_filetype = wp_check_filetype( $filename, null );
                
                $attachment = array(
                  'post_mime_type' => $wp_filetype['type'],
                  'post_title' => $image['Title'],
                  'post_content' => $image['Image Description'],
                  'post_excerpt' => $image['Image Caption'],
                  'post_status' => 'inherit'
                );
                
                global $wpdb;
                $query = "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/$filename'";
            
                if ( $wpdb->get_var($query) ){
                    echo '<strong>'. $image['Title'] .'</strong> image exists.<br>';
                }else{
                    $attach_id = wp_insert_attachment( $attachment, $file );
                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    if($attach_id){
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                        wp_update_attachment_metadata( $attach_id, $attach_data );
                        update_post_meta($attach_id, '_wp_attachment_image_alt', $image['Title']);
                        echo '<strong>'. $image['Title'].'</strong> image uploaded.<br>';
                    }else{
                        echo '<strong>'. $image['Title'].'</strong> image upload failed.<br>';
                    }
                }
                    
                }  
                
                $count++; 
            }
        }
        fclose($file);
        
        
        echo '<br>Uploaded, have fun!';
    }
}
