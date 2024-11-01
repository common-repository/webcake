<?php
/**
 * @package Webcake
 * @author Pancake
 * @license GPL-2.0+
 * @link https://webcake.io
 * @copyright 2017 Webcake
 *
 *            @wordpress-plugin
 *            Plugin Name: Webcake - Builder landingpage
 *            Plugin URI: https://webcake.io
 *            Description: Connector to access content from Webcake service.
 *            Version: 1.1
 *            Author: Webcake
 *            Author URI: https://webcake.io
 */

    ini_set('display_startup_errors', 0);
 
    if (!class_exists("WebcakePageTemplater")) {
    	require plugin_dir_path(__FILE__) . 'add-template.php';
    }

    if (isset($_SERVER['HTTP_ORIGIN'])) {
    	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    	header('Access-Control-Allow-Credentials: true');
    	header('Access-Control-Max-Age: 86400'); // cache for 1 day
    }

	function webcake_create_page($data) {
    kses_remove_filters();

		$attrs = array(
			'post_content' => trim($data['html']),
			'post_title' => $data['title'],
			'post_status' => 'publish',
			'post_type' => 'page',
			'page_template' => 'null-template.php',
      'post_name' => $data['path'],
      'filter' => true
		);
		
		$page = wp_insert_post(add_magic_quotes($attrs));
		
		if($page) {
			return wp_send_json(array(
				'success' => true,
				'url' => get_permalink($page)
			));
		} else {
			return wp_send_json(array(
				'success' => false,
				'message' => 'Created failed, Pls try again.'
			));
		}
	}

	function webcake_create_new_page($data) {
		$page = get_page_by_path($data['path']);
		
		if( $page ) {
			$page_id = $page->ID;

			$res = wp_delete_post($page_id, false);

			if($res) {
				return webcake_create_page($data);
			} else {
				return wp_send_json(array('delete_page' => 'failed'));
			}
		} else {
      return webcake_create_page($data);
		}
	}

	function webcake_create_page_publish( $data ) {
		global $webcake_config;
		
		if ($webcake_config['api_key'] && $webcake_config['api_key'] == $data['api_key']) {
			switch($data['action']) {
				case "create_or_update_page":
					return webcake_create_new_page($data);
					break;
				default:
					return wp_send_json(array(
            'success' => false,
            'message' => 'Don"t match action'
					));
			}
		} else {
        return wp_send_json(array(
          'success' => false,
          'message' => 'Api key not match'
		     ));
      }
    }
    
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    header("Access-Control-Allow-Origin: *");
    //header('content-type: application/json; charset=utf-8');

    add_action( 'rest_api_init', function () {
      register_rest_route( 'webcake/v1', '/publish', array(
        'methods' => 'POST',
        'callback' => 'webcake_create_page_publish',
        ));
      });
    add_action('admin_menu', 'add_menu_webcake');
    add_action('wp_ajax_webcake_save_config', 'save_config');

    function webcake_publish($data) {
        global $webcake_config;
        $action  = $data['action'];
        
        if ($webcake_config['api_key'] && $data['api_key'] == $webcake_config['api_key']) {
            switch ($action) {
                case "create_or_update_page":
                    return create_or_update_page($data);
                    break;
                
                default:
                    $response['message'] = 'Action not found';
                    $res = new WP_REST_Response($response);
                    $res->set_status(404);
            
                    return ['req' => $res];
                    break;
            }
        } else {
            wp_send_json(array(
              'code'    => 403,
              'message' => 'Permistion denied: Api key not found'
            ));
        }
    };
    
    function create_or_update_page($data) {
        $api_key = $data['api_key'];
        $path = $data['path'];
        $title = $data['title'];
        $html = $data['html'];
        $page = get_page_by_slug($path);
        
        if ($page) {
            global $query;
            kses_remove_filters();
            $result_page =
                wp_update_post(
                    array(
                        'ID' => $page -> ID,
                        'post_title' => $title,
                        'post_content' => trim($html)
                    )
                );
                
            if ($result_page) {
                wp_send_json(array(
                  'code' => 200,
                  'url' => get_permalink($result_page),
                  'template' => $page -> page_template
                ));
            } else {
                wp_send_json(array(
                'code' => 400,
                'message' => 'Update page error, please try again.'
              ));
            }
        } else {
        	kses_remove_filters();
            $result_page =
                wp_insert_post(
            		array(
            			'post_title' => $title,
            			'post_name' => $path,
            			'post_type' => 'page',
            			'post_content' => trim($html),
            			'post_status' => 'publish',
            			'filter' => true,
            			'page_template'=> 'null-template.php'
            		)
            	);
            	
            	 
            if ($result_page) {
                wp_send_json(array(
                  'code' => 200,
                  'url' => get_permalink($result_page),
                  'template' => $result_page -> post_title
                ));
            } else {
                wp_send_json(array(
                  'code' => 400,
                  'message' => 'Create page error, please try again.'
                ));
            }
        }
    };
    
    function get_page_by_slug($slug) {
      $page = get_page_by_path($slug, 'OBJECT', ['post', 'page', 'product', 'property']);
      if ($page) {
        return $page;
      } else {
        return null;
      }
    };
  
    function add_menu_webcake(){
    	add_menu_page( 'Webcake plug settings', 'Webcake', 'manage_options', 'test-plugin', 'init_ui', 'https://cdn.pancake.vn/1/s20x20/21/3c/06/4a/632c70dea1ebd2687e0ae296cc73b4eedb24244cd3b2d7fc2a812f51.png', 29);
  	};
  	
  	function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		
		return $randomString;
	};

	function save_config() {
	    $data   = sanitize_text_field($_POST['data']);
    	$data   = json_decode(stripslashes($data));
    	$option = array();
    
    	foreach ($data as $key => $value) {
    		$option[$value->name] = $value->value;
    	}
    	update_option('webcake_config', $option);
    	die;
	};
	
	$webcake_config = get_option("webcake_config");
    
   
    function init_ui() {
        ?>
            <style>
                input:focus-visible {
                    outline: none;
                    border-color: #3996c3 !important;
                }
                
                .copied-text {
                    color: #30a089; display: flex; flex-direction: row; align-items: center; width: fit-content; margin-left: 10px; font-weight: 600; font-size: 11px; visibility: hidden;
                }
                
                .btn-copy {
                    flex-direction: row; width: fit-content; padding: 7px 16px; background: #fff; border-radius: 20px; box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;; cursor: pointer;
                }
                
                .btn-copy:hover { 
                    box-shadow: rgb(50 50 93 / 25%) 0px 13px 27px -5px, rgb(0 0 0 / 30%) 0px 8px 16px -8px;
                }
                
                .btn-save {
                    width: fit-content;
                    padding: 7px 16px;
                    background: #1ab855;
                    border-radius: 3px;
                    color: #fff;
                    font-weight: 600;
                    cursor: pointer;
                }
                
                .btn-save:hover { 
                    box-shadow: rgb(50 50 93 / 25%) 0px 13px 27px -5px, rgb(0 0 0 / 30%) 0px 8px 16px -8px;
                }
                
                .header {
                    height: 50px;
                }
                .is-fullheight {
                    height: 100%;
                }
                .is-fullwidth {
                    width: 100%;
                }
                .is-flex {
                    display: flex;
                }
                .is-flex--vcenter {
                    justify-content: center;
                }
                
                .is-flex--hcenter {
                    align-items: center;
                }
                
                .is-flex--center {
                    justify-content: center;
                    align-items: center;
                }
                
                .is-flex--end {
                    justify-content: flex-end;
                }
                
                .mt-0 {
        	        margin-top: 0px;
                }
                 .mt-8 {
                	 margin-top: 8px;
                }
                 .mt-16 {
                	 margin-top: 16px;
                }
                
                .mt-20 {
                	 margin-top: 20px;
                }
                 .mt-24 {
                	 margin-top: 24px;
                }
                 .mt-32 {
                	 margin-top: 32px;
                }
                 .mt-40 {
                	 margin-top: 40px;
                }
                 .mt-48 {
                	 margin-top: 48px;
                }
                 .mb-0 {
                	 margin-bottom: 0px !important;
                }
                 .mb-8 {
                	 margin-bottom: 8px;
                }
                 .mb-16 {
                	 margin-bottom: 16px;
                }
                 .mb-24 {
                	 margin-bottom: 24px;
                }
                 .mb-32 {
                	 margin-bottom: 32px;
                }
                 .mb-40 {
                	 margin-bottom: 40px;
                }
                 .mb-48 {
                	 margin-bottom: 48px;
                }
                 .ml-0 {
                	 margin-left: 0px;
                }
                 .ml-8 {
                	 margin-left: 8px;
                }
                 .ml-16 {
                	 margin-left: 16px;
                }
                 .ml-24 {
                	 margin-left: 24px;
                }
                 .ml-32 {
                	 margin-left: 32px;
                }
                 .ml-40 {
                	 margin-left: 40px;
                }
                 .ml-48 {
                	 margin-left: 48px;
                }
                 .mr-0 {
                	 margin-right: 0px;
                }
                 .mr-8 {
                	 margin-right: 8px;
                }
                 .mr-16 {
                	 margin-right: 16px;
                }
                 .mr-20 {
                	 margin-right: 20px;
                }
                 .mr-24 {
                	 margin-right: 24px;
                }
                 .mr-32 {
                	 margin-right: 32px;
                }
                 .mr-40 {
                	 margin-right: 40px;
                }
                 .mr-48 {
                	 margin-right: 48px;
                }
            </style>
            
            <div class='mt-20' style='width: fit-content;margin-left: auto;margin-right: auto;'>
    			<div class='header is-flex is-flex--vcenter'>
    				<img class='is-fullheight' src='https://statics.pancake.vn/web-media/c8/f7/9d/37/be0a477764df14b26bd3ba342907acae8d244ad49106fdb736f4be29.png'/>
    			</div>
    			
    			<div class='mt-20'>
    				<div style='width: 300px; padding: 20px; border-radius: 3px; box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px; background: #fff;'>
    					<div>
    						<div style='font-weight: 600; color: #4d585c;'>API KEY</div>
    						<div class='is-flex is-flex--hcenter' style='margin-top: 8px;'>
    							<div id='apiKey' class='is-fullwidth is-flex is-flex--center' style='background-color: #f0f7fb; border-radius: 3px; height: 24px;'>
                    <?php 
    								    global $webcake_config;
    								    if ($webcake_config['api_key']) {
                            echo esc_html($webcake_config['api_key']);
    								    } else {
    								        $webcake_config['api_key'] = generateRandomString(32);
                            echo esc_html($webcake_config['api_key']);
    								    }
    								?>
    							</div>
    							<div style='margin-left: 8px; color: #4d585c; color: #4d585c; cursor: pointer;' onclick='getNewApiKey()'><i class='fas fa-redo-alt'></i></div>
    						</div>
    					</div>
    					<div style='display: flex; flex-direction: row; margin-top: 16px;'>
    						<div onclick='copyApiKey()' class='is-flex btn-copy'>
    							<i class='far fa-copy' style='display: flex; align-items: center;'></i>
    							<div style='margin-left: 8px; font-weight: 600; font-size: 11px; color: #4d585c;'>COPY KEY</div>
    						</div>
    						
    						<div id='copiedText' class='copied-text'>
    							<i class='fas fa-check'></i>
    							<div style='margin-left: 6px;'>COPIED</div>
    						</div>
    					</div>
    
    					<div style='margin-top: 16px;'>
    						<div style='font-weight: 600; color: #4d585c;'>URL</div>
    						<div class='is-flex is-flex--hcenter' style='margin-top: 8px;'>
    						    <div id='apiUrl' class='is-fullwidth is-flex is-flex--center' style='background-color: #f0f7fb; border-radius: 3px; height: 24px; height: 30px; color: #3996c3; font-size: 15px; font-weight: 500;'>
    								<?php echo get_home_url(); ?>
    							</div>
    						</div>
    					</div>
    					
    					<div class='is-flex is-flex--end mt-20'>
    					    <div class='btn-save' onclick='saveChange()'>SAVE</div>
    					</div>
    				</div>
    			</div>
    		</div>
		
    		<script>
    		    let apiKey, apiUrl
    		    let apiKeyElement = document.getElementById('apiKey')
    		    let apiUrlElement = document.getElementById('apiUrl')
    		    if (apiKeyElement) {
    		      //  apiKeyElement.innerText = generateRandomString(32)
    		    }
    	
    		    function generateRandomString(length = 10) {
    			  	var text = '';
    			  	var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    			  	for (var i = 0; i < length; i++)
    			    text += possible.charAt(Math.floor(Math.random() * possible.length));
    			  	return text;
    			}
    		
    			function getNewApiKey() {
    			    let newApiKey = generateRandomString(32)
    			    let copiedText = document.getElementById('copiedText')
    			    if (apiKeyElement) {
    			        apiKeyElement.innerText = newApiKey
    			    }
    			    if (copiedText) {
    			        copiedText.style.visibility = 'hidden'
    			    }
    			}
    			
    			function copyApiKey() {
    			    let copiedText = document.getElementById('copiedText')
    			    if (apiKeyElement) {
    			        navigator.clipboard.writeText(apiKeyElement.innerText)
    			    }
    			    if (copiedText) {
    			        copiedText.style.visibility = 'visible'
    			    }
    			}
    			
    			function saveChange() {
    			    let data = [{'name': 'api_key','value': apiKeyElement.innerText},{'name': 'api_url','value': apiUrlElement.innerText}]
        			var xhr = new XMLHttpRequest();
                    xhr.open('POST', ajaxurl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    xhr.onreadystatechange = function() {
                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                            alert('Save success!')
                        } 
                        
                        if (this.readyState === XMLHttpRequest.DONE && this.status != 200) {
                            alert('Save error!')
                        }
                    }
                    data = "data=" + JSON.stringify(data)
                   
                    xhr.send("action=webcake_save_config" + "&" + data);               
                    
    			}
    		</script>
		
        <?php
    }
?>
