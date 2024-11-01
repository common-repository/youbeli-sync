<?php

class Youbeli_Admin
{
    private static $APIGetCategory = 'https://api.youbeli.com/3.0/get_category.php';
    private static $APIUpdateProduct = 'https://api.youbeli.com/3.0/update_product.php';
    private static $APIProductDelete = 'https://api.youbeli.com/3.0/delete_product.php';
    private static $APIUpdate = 'https://api.youbeli.com/3.0/api_update.php?sID=';

    public static function init()
    {
        add_action('admin_head', array( 'Youbeli_Admin', 'custom_head' ));
    }

    public static function setting()
    {
        $saved = false;
        if (isset($_POST['youbeli_store_id'])) {
            update_option('youbeli_store_id', $_POST['youbeli_store_id']);
            $youbeli_store_id = $_POST['youbeli_store_id'];
            $saved            = true;
        } else {
            $youbeli_store_id = get_option('youbeli_store_id');
        }
        if (isset($_POST['youbeli_api_key'])) {
            update_option('youbeli_api_key', $_POST['youbeli_api_key']);
            $youbeli_api_key = $_POST['youbeli_api_key'];
            $saved           = true;
        } else {
            $youbeli_api_key = get_option('youbeli_api_key');
        }
        if (isset($_POST['youbeli_delivery_days'])) {
            update_option('youbeli_delivery_days', $_POST['youbeli_delivery_days']);
            $youbeli_delivery_days = $_POST['youbeli_delivery_days'];
            $saved                 = true;
        } else {
            $youbeli_delivery_days = get_option('youbeli_delivery_days');
        }

        wp_enqueue_style('magnific', plugins_url('assets/css/admin.css', __FILE__));
        $file = YOUBELI__PLUGIN_DIR . 'views/setting.php';
        include $file;
    }

    public static function category_setting()
    {
        global $wpdb;
        $synced = '';
        if (isset($_POST['do_action']) && $_POST['do_action'] == 'get_youbeli_category') {
            $synced = self::sync_category();
        }

        $number   = 10;
        $page_num = 1;
        if (isset($_GET['page_num']) && $_GET['page_num']) {
            $page_num = $_GET['page_num'];
        }

        $terms = get_terms(
            'product_cat',
            array(
                'hide_empty' => false,
                'number'     => $number,
                'offset'     => ($page_num - 1) * $number,
            )
        );
        $total = wp_count_terms('product_cat');

        $categories = array();
        foreach ($terms as $term) {
            $object = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}youbeli_category_path WHERE term_id = " . $term->term_id);
            if($object) {
                $categories[] = (object) array_merge((array) $object[0], (array) $term);
            } else {
                $categories[] = $term;
            }

        }

        $pagination = array(
            'base'    => get_admin_url() . 'admin.php%_%',
            'total'   => ceil($total / $number),
            'current' => $page_num,
            'format'  => '?page_num=%#%',
        );

        $results = $wpdb->get_results("SELECT a.id,a.name,count(b.id) as child FROM {$wpdb->prefix}youbeli_category a left join {$wpdb->prefix}youbeli_category b ON a.id=b.parent_id WHERE a.parent_id = 0 GROUP BY a.id");

        $youbeli = array();
        foreach ($results as $result) {
            $youbeli[] = array(
                'id'    => $result->id,
                'name'  => $result->name,
                'child' => $result->child,
            );
        }

        wp_enqueue_script('category', plugins_url('assets/js/category.js', __FILE__), array( 'jquery' ), null, true);
        wp_enqueue_style('category', plugins_url('assets/css/custom.css', __FILE__));
        wp_enqueue_script('magnific', plugins_url('assets/js/magnific.js', __FILE__), array( 'jquery' ), null, true);
        wp_enqueue_style('magnific', plugins_url('assets/css/magnific.css', __FILE__));

        $file = YOUBELI__PLUGIN_DIR . 'views/category_setting.php';
        include $file;
    }

    public static function product_sync()
    {
        global $wpdb;
        global $woocommerce;

        add_filter('posts_where', array( 'Youbeli_Admin', 'posts_where' ));
        add_filter('posts_join', array( 'Youbeli_Admin', 'posts_join' ));

        $log = new \Log('youbeli.log');

        if (isset($_POST['do_action']) && $_POST['do_action']) {
            if ($_POST['do_action'] == 'sync_selected') {
                $sync_status = self::sync_product();
            } elseif ($_POST['do_action'] == 'sync_all') {
                $sync_status = self::sync_product();
            } elseif ($_POST['do_action'] == 'sync_continue') {
                $sync_status = self::sync_product();
            } elseif ($_POST['do_action'] == 'unsync_continue') {
                $sync_status = self::unsync_product();
            } elseif ($_POST['do_action'] == 'unsync_selected') {
                $sync_status = self::unsync_product();
            }
        }

        $number   = 20;
        $page_num = 1;
        if (isset($_GET['page_num']) && $_GET['page_num']) {
            $page_num = $_GET['page_num'];
        }

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $args     = array(
                'type'   => array( 'simple', 'external', 'variable' ),
                'status' => 'any',
                'limit'  => $number,
                'offset' => ($page_num - 1) * $number,
                'page'   => $page_num,
            );
            $products = wc_get_products($args);

            $total = count(
                wc_get_products(
                    array(
                        'type'   => array( 'simple', 'external', 'variable' ),
                        'status' => 'any',
                        'limit'  => '10000000',
                    )
                )
            );
        } else {
            $args     = array(
                'post_type'      => 'product',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => array( 'simple', 'external', 'variable' ),
                    ),
                ),
                'posts_per_page' => $number,
                'offset'         => ($page_num - 1) * $number,
                'post_status'    => 'any',
            );
            $products = get_posts($args);
            $total    = count(
                get_posts(
                    array(
                        'post_type'      => 'product',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'product_type',
                                'field'    => 'slug',
                                'terms'    => array( 'simple', 'external', 'variable' ),
                            ),
                        ),
                        'posts_per_page' => 10000000,
                    )
                )
            );
        }

        $total_products = $total;
        $total_sync = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}youbeli_sync_product WHERE in_youbeli = 1");
        if (!$total_sync) {
            $total_sync = 0;
        }

        $total_unsync = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}youbeli_sync_product WHERE in_youbeli = 0");
        if (!$total_unsync) {
            $total_unsync = 0;
        }

        $total_error = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}youbeli_sync_product WHERE error != '' AND error IS NOT NULL");
        if (!$total_error) {
            $total_error = 0;
        }

        $pagination = array(
            'base'    => get_admin_url() . 'admin.php%_%',
            'total'   => ceil($total / $number),
            'current' => $page_num,
            'format'  => '?page_num=%#%',
        );

        self::repairProduct();

        wp_enqueue_script('sync', plugins_url('assets/js/sync.js', __FILE__), array( 'jquery' ), null, true);
        $file = YOUBELI__PLUGIN_DIR . 'views/product_sync.php';
        include $file;
    }

    public static function log()
    {
        $log = new \Log('youbeli.log');
        $filepath = YOUBELI__PLUGIN_DIR . 'youbeli.log';
        $filesize = filesize($filepath);

        if (isset($_GET['action']) && $_GET['action'] == 'download') {
            header('X-Robots-Tag: noindex, nofollow', true);
            header('Content-Type: application/octet-stream');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="youbeli.log";');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . $filesize);
            ob_clean();
            flush();
            readfile($filepath);
            exit;
        } elseif (isset($_GET['action']) && $_GET['action'] == 'clear') {
            if (file_exists($filepath) && is_file($filepath)) {
                unlink($filepath);
                header('Location: ' . Youbeli::get_page_url('log'));
            }
        }

        $size = $filesize / 1024;
        $unit = 'kb';
        if (floor($size) > 1) {
            $size = $size / 1024;
            $unit = 'mb';
        }
        $size = round($size, 2);

        $deleted = false;

        wp_enqueue_style('magnific', plugins_url('assets/css/admin.css', __FILE__));
        $file = YOUBELI__PLUGIN_DIR . 'views/log.php';
        include $file;
    }

    public static function repairProduct()
    {
        global $wpdb;

        $sql = "SELECT {$wpdb->prefix}youbeli_sync_product.* from {$wpdb->prefix}youbeli_sync_product LEFT JOIN {$wpdb->posts} ON({$wpdb->posts}.ID = {$wpdb->prefix}youbeli_sync_product.product_id) WHERE {$wpdb->posts}.ID IS NULL";

        $rows = $wpdb->get_results($sql);
        foreach ($rows as $row) {
            self::delete_product($row->product_id);
        }
    }

    public static function sync_product()
    {
        $log            = new \Log('youbeli.log');
        $product_ids    = array();
        $count          = 0;
        $success_count  = 0;
        $fail_count  = 0;
        $start          = microtime(true);
        $sync_running   = false;
        $api_update = false;
        $log->write('sync_product()_' . $_POST['do_action']);
        if ($_POST['do_action'] == 'sync_selected' || $_POST['do_action'] == 'sync_all') {
            if ($_POST['do_action'] == 'sync_selected') {
                if ($_POST['check']) {
                    foreach ($_POST['check'] as $id) {
                        self::set_sync_action($id, 1);
                        $product_ids[] = $id;
                        $count++;
                    }
                }
            } elseif ($_POST['do_action'] == 'sync_all') {
                $products = get_posts(
                    array(
                        'post_type'      => 'product',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'product_type',
                                'field'    => 'slug',
                                'terms'    => array( 'simple', 'external', 'variable' ),
                            ),
                        ),
                        'posts_per_page' => 10000000,
                    )
                );

                foreach ($products as $product) {
                    self::set_sync_action($product->ID, 1);
                    $product_ids[] = $product->ID;
                    $count++;
                }
            }
        } elseif ($_POST['do_action'] == 'sync_continue') {
            $results = self::get_sync_ids();
            foreach ($results as $result) {
                $product_ids[] = $result->product_id;
                $count++;
            }
        }
        foreach ($product_ids as $id) {
            $result = self::do_sync($id);
            $sync_running   = true;
            if (isset($result['status']) && $result['status'] == 1) {
                $api_update = true;
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        $sync_running = false;
        if ($api_update) {
            self::api_update();
            $log->write('sync_product()_total:' . $count . '_success:' . $success_count);
        }
        $execution_time = round((microtime(true) - $start), 2);
        $log->write('sync_product()_time_used:' . $execution_time);
        $res = array(
            'total'         => $count,
            'success_total' => $success_count,
            'failed_total'  => $fail_count,
            'time_used'     => $execution_time,
            'method'        => 'sync',
            'sync_running'  => $sync_running,
        );
        return $res;
    }

    public static function unsync_product()
    {
        $product_synced = false;
        $log           = new \Log('youbeli.log');
        $product_ids   = array();
        $count         = 0;
        $success_count = 0;
        $fail_count    = 0;
        $start         = microtime(true);
        $sync_running  = false;
        if ($_POST['do_action'] == 'unsync_selected') {
            if ($_POST['check']) {
                $ids = $_POST['check'];
                foreach ($ids as $id) {
                    self::set_sync_action($id, 0);
                    $product_ids[] = $id;
                }
            }
        } elseif ($_POST['do_action'] == 'unsync_continue') {
            $results = self::get_unsync_ids();
            foreach ($results as $result) {
                $product_ids[] = $result->product_id;
            }
        }

        $api_update = false;
        foreach ($product_ids as $id) {
            if (self::is_in_youbeli($id)) {
                $count++;
                $result = self::do_unsync($id);
                $sync_running   = true;
                if (isset($result['status']) && $result['status'] == 1) {
                    $success_count++;
                    $api_update = true;
                } else {
                    $fail_count++;
                }
            }
        }

        $sync_running = false;
        if ($api_update) {
            self::api_update();
            $log->write('unsync_product()_total:' . $count . '_success:' . $success_count);
        }
        $execution_time = round((microtime(true) - $start), 2);
        $log->write('unsync_product()_time_used:' . $execution_time);
        $res = array(
            'total'         => $count,
            'success_total' => $success_count,
            'failed_total'  => $fail_count,
            'time_used'     => $execution_time,
            'method'        => 'unsync',
            'sync_running'  => $sync_running,
        );
        return $res;
    }

    public static function do_sync($id)
    {
        global $wpdb;
        global $woocommerce;
        $log = new \Log('youbeli.log');
        $res_status = 0;
        $msg = '';

        if(version_compare($woocommerce->version, '2.2', '<')) {
            $product = get_product($id);
        } else {
            $product = wc_get_product($id);
        }

        $product_tag = wp_get_post_terms($product->id, 'product_tag', array( 'fields' => 'names' ));

        $tax = 0;
        $wc_tax_enabled = false;
        $woocommerce_prices_include_tax = false;
        if(version_compare($woocommerce->version, '2.2', '<=')) {
            $wc_tax_enabled = get_option('woocommerce_calc_taxes') === 'yes';
            $woocommerce_prices_include_tax = get_option('woocommerce_prices_include_tax') === 'yes';
        } else {
            $wc_tax_enabled = wc_tax_enabled();
            $woocommerce_prices_include_tax = wc_prices_include_tax();
        }

        if ($wc_tax_enabled) {
            if ($product->tax_status == 'taxable') {
                if ($product->tax_class == 'zero-rate') {
                    $tax = 'Z';
                } else {
                    $rates = WC_Tax::get_rates($product->tax_class);
                    if ($rates) {
                        foreach ($rates as $rate) {
                            if (isset($rate['rate']) && $rate['rate'] == 6) {
                                $tax = 1;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $price         = '';
        $special_price = '';
        if ($product->is_type('variable')) {
            $price = $product->get_variation_regular_price();
            if ($product->get_variation_sale_price() < $price) {
                $special_price = $product->get_variation_sale_price();
            }
            $qty_by_option = 1;
        } else {
            $price         = $product->get_regular_price();
            $special_price = $product->get_sale_price();
            $qty_by_option = 0;
        }

        if ($wc_tax_enabled && !$woocommerce_prices_include_tax && $tax == 1) {
            if ($price) {
                $price = $price + ($price * 0.06);
            }
            if ($special_price) {
                $special_price = $special_price + ($special_price * 0.06);
            }
        }
        $special_date_start = '';
        $special_date_end   = '';
        if (version_compare($woocommerce->version, '3.0', '>=')) {
            if ($product->date_on_sale_from) {
                $special_date_start = $product->date_on_sale_from->date('Y-m-d');
            }

            if ($product->date_on_sale_to) {
                $special_date_end = $product->date_on_sale_to->date('Y-m-d');
            }
        } else {
            $date_start = get_post_meta($product->id, '_sale_price_dates_from', true);
            if ($date_start) {
                $special_date_start = date_i18n('Y-m-d', $date_start);
            }

            $date_end = get_post_meta($product->id, '_sale_price_dates_to', true);
            if ($date_end) {
                $special_date_end = date_i18n('Y-m-d', $date_end);
            }
        }

        $status = 1;
        if ($product->stock_status == 'instock') {
            $status = 1;
        } elseif ($product->stock_status == 'outofstock') {
            $status = 5;
        }

        $display = 1;
        if ($product->status = 'publish') {
            $display = 1;
        } elseif ($product->status = 'pending') {
            $display = 0;
        } elseif ($product->status = 'draft') {
            $display = 0;
        }

        // wc_get_weight
        $youbeli_id = '';
        $categories = wp_get_post_terms($product->id, 'product_cat', array( 'fields' => 'ids' ));

        if ($categories) {
            foreach ($categories as $category_id) {
                $youbeli_id = $wpdb->get_var("SELECT youbeli_id FROM {$wpdb->prefix}youbeli_category_path WHERE term_id = " . $category_id);
                if ($youbeli_id) {
                    break;
                }
            }
        }

        $highlight = '';
        if (version_compare($woocommerce->version, '3.0', '>=') && false) {
            $title       = strip_tags($product->get_name());
            $description = html_entity_decode($product->get_description(), ENT_QUOTES, 'UTF-8');
            if (empty($description)) {
                $description = html_entity_decode($product->get_short_description(), ENT_QUOTES, 'UTF-8');
            } else {
                $highlight = html_entity_decode($product->get_short_description(), ENT_QUOTES, 'UTF-8');
            }
        } else {
            $title       = strip_tags($product->get_title());
            $description = html_entity_decode($product->get_post_data()->post_content, ENT_QUOTES, 'UTF-8');
            if (empty($description)) {
                $description = html_entity_decode($product->get_post_data()->post_excerpt, ENT_QUOTES, 'UTF-8');
            } else {
                $highlight = html_entity_decode($product->get_post_data()->post_excerpt, ENT_QUOTES, 'UTF-8');
            }
        }
        $delivery_days = (get_option('youbeli_delivery_days') ? get_option('youbeli_delivery_days') : 7);
        $options       = array();
        if (in_array($product->product_type, array( 'variable', 'external' ))) {
            $option_name = '';

            foreach ($product->get_available_variations() as $variant) {
                $attributes       = array();
                $attributes_value = array();
                foreach ($variant['attributes'] as $key => $value) {
                    $attributes[]       = wc_attribute_label(str_replace('attribute_', '', $key), $product);
                    $option_name        = implode('/', $attributes);
                    $attributes_value[] = $value;
                }
                if (version_compare($woocommerce->version, '2.3', '<')) {
                    $var = get_product($variant['variation_id']);
                    $variant_price = $var->get_price();
                } else {
                    $variant_price = $variant['display_price'];
                }

                if ($tax == 1) {
                    $variant_price = $variant_price + ($variant_price * 0.06);
                }
                $price_prefix = '';
                $price_gap    = 0;
                if ($special_price) {
                    if ($special_price > $variant_price) {
                        $price_prefix = '-';
                        $price_gap    = $special_price - $variant_price;
                    } else {
                        $price_prefix = '+';
                        $price_gap    = $variant_price - $special_price;
                    }
                } elseif ($price) {
                    if ($price > $variant_price) {
                        $price_prefix = '-';
                        $price_gap    = $price - $variant_price;
                    } else {
                        $price_prefix = '+';
                        $price_gap    = $variant_price - $price;
                    }
                }

                $option_value[] = array(
                    'name'         => implode(', ', $attributes_value),
                    'qty'          => $variant['max_qty'],
                    'price'        => $price_gap,
                    'price_prefix' => $price_prefix,
                );
            }
            $options[] = array(
                'name'  => $option_name,
                'value' => $option_value,
            );
        }

        if ($product->is_type('simple')) {
            if ($product->get_attributes()) {
                $specification = '';
                if (version_compare($woocommerce->version, '2.5', '<')) {
                    $product_id = $product->id;
                } else {
                    $product_id = $product->get_id();
                }

                foreach ($product->get_attributes() as $attribute) {

                    if ($attribute['visible'] || $attribute['is_visible']) {
                        $values          = array();
                        $attribute_value = array();
                        if (isset($attribute['is_taxonomy']) && $attribute['is_taxonomy']) {
                            $attribute_value = wc_get_product_terms($product_id, $attribute['name'], array( 'fields' => 'names' ));

                        } elseif (isset($attribute['value'])) {
                            $attribute_value = array_map('trim', explode('|', $attribute['value']));
                        }
                        foreach ($attribute_value as $value) {
                            $values[] = $value;
                        }
                        $specification .= '<tr><td nowrap>' . wc_attribute_label($attribute['name'], $product) . ': </td><td>' . implode(', ', $values) . '</td></tr>';
                    }
                }

                if ($specification) {
                    $description .= '<p>Specification:</p><table border="0">';
                    $description .= $specification;
                    $description .= '</table>';
                }
            }
        }

        $images        = $attachment_ids = array();
        if (version_compare($woocommerce->version, '2.2', '<')) {
            $product_image = get_post_thumbnail_id($product->id);
        } else {
            $product_image = $product->get_image_id();
        }
        if (! empty($product_image)) {
            $attachment_ids[] = $product_image;
        }

        if (version_compare($woocommerce->version, '3.0', '>=')) {
            $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());
        } else {
            $attachment_ids = array_merge($attachment_ids, $product->get_gallery_attachment_ids());
        }

        foreach ($attachment_ids as $position => $attachment_id) {
            $attachment = wp_get_attachment_image_src($attachment_id, 'full');
            $images[]   = current($attachment);
        }

        $product_arr = array(
            'sku'                => $product->id,
            'model'              => $product->sku,
            'title'              => $title,
            'description'        => $description,
            'highlight'          => $highlight,
            'keywords'           => implode(',', $product_tag),
            'price'              => $price,
            'tax'                => $tax,
            'special_price'      => $special_price,
            'special_exp_date'   => $special_date_end,
            'special_start_date' => $special_date_start,
            'status'             => $status,
            'display'            => $display,
            'weight'             => wc_get_weight($product->weight, 'kg'),
            'length'             => wc_get_dimension($product->get_length(), 'cm'),
            'width'              => wc_get_dimension($product->get_width(), 'cm'),
            'height'             => wc_get_dimension($product->get_height(), 'cm'),
            'product_category'   => $youbeli_id,
            'qty'                => $product->get_stock_quantity(),
            'qty_by_option'      => $qty_by_option,
            'options'            => $options,
            'images'             => $images,
            'delivery_days'      => $delivery_days,
        );
        $postData = self::getPostParameter(array('product' => $product_arr));
        $result = self::curlPost(self::$APIUpdateProduct, $postData);
        $response       = json_decode($result, true);

        if (isset($response['status'])) {
            $msg = $response['message'];
            if ($response['status'] == -1) {
                $log->write('do_sync()_ERR_' . $response['message']);
                self::set_sync_error($id, $response['message']);
            } elseif ($response['status'] == 0) {
                $log->write('do_sync()_ERR_Product ' . $id . ' have error - ' . $response['message']);
                self::set_sync_error($id, $response['message']);
            } elseif ($response['status'] == 1) {
                self::set_sync_success($id);
                $log->write('do_sync()_Product ' . $id . ' synced successfully (' . $response['message'] . ')');
                $res_status = 1;
            }
        } else {
            $log->write('do_sync() API call error');
            $msg = 'API Call Error';
        }

        return array(
            'status' => $res_status,
            'msg' => $msg,
        );
    }

    public static function do_unsync($id)
    {
        $status = 0;
        $msg = '';
        $log          = new \Log('youbeli.log');
        $product_arr  = array(
            'sku' => $id,
        );

        $postData = self::getPostParameter($product_arr);
        $result = self::curlPost(self::$APIProductDelete, $postData);
        $response = json_decode($result, true);

        $product_synced = false;
        if (isset($response['status'])) {
            $status = $response['status'];
            $msg = $response['message'];
            if ($response['status'] == -1) {
                $log->write('do_unsync()_ERR_' . $response['message']);
                self::set_sync_error($id, $response['message']);
            } elseif ($response['status'] == 0) {
                $log->write('do_unsync()_ERR_Product ' . $id . ' have error - ' . $response['message']);
                if ($response['message'] == 'Product sku does not exists') {
                    self::set_product_removed($id);
                } else {
                    self::set_sync_error($id, $response['message']);
                }
            } elseif ($response['status'] == 1) {
                self::set_unsync_success($id);
                $log->write('do_unsync()_Product ' . $id . ' unsync successfully (' . $response['message'] . ')');
                $product_synced = true;
            }
        } else {
            $log->write('do_unsync() API call Error');
            $msg = 'API Call Error';
        }

        return array(
            'status' => $status,
            'msg' => $msg
        );
    }

    public static function api_update()
    {
        $log = new \Log('youbeli.log');
        $log->write('api_update()');
        $result = self::curlGet(self::$APIUpdate . get_option('youbeli_store_id'));
        $log->write('api_update()_' . $result);
        $last_sync = date('Y-M-d H:i:s');
        update_option('youbeli_config_last_sync_product', $last_sync);
    }

    public static function custom_head()
    {
        $css = untrailingslashit(plugins_url('/', __FILE__)) . '/assets/css/admin.css';
        echo '<link rel="stylesheet" href="' . $css . '" type="text/css" />';
    }

    public static function posts_where($where)
    {
        global $wpdb;

        $post_where = '';
        if (isset($_GET['filter_synced'])) {
            if ($_GET['filter_synced'] != '') {
                $post_where = " AND {$wpdb->prefix}youbeli_sync_product.in_youbeli = " . $_GET['filter_synced'];
            }
        }

        if (isset($_GET['filter_error'])) {
            if (!empty($_GET['filter_error'])) {
                $post_where = " AND {$wpdb->prefix}youbeli_sync_product.error != '' AND {$wpdb->prefix}youbeli_sync_product.error IS NOT NULL";
            }
        }

        $where = $where . $post_where;
        return $where;
    }

    public static function posts_join($join)
    {
        global $wpdb;

        $post_join = '';
        if (isset($_GET['filter_synced']) || isset($_GET['filter_error'])) {
            $post_join = " LEFT JOIN {$wpdb->prefix}youbeli_sync_product ON({$wpdb->posts}.ID = {$wpdb->prefix}youbeli_sync_product.product_id)";
        }
        $join = $join . $post_join;
        return $join;
    }

    public static function sync_category()
    {
        global $wpdb;
        set_time_limit(0);

        $data = self::getPostParameter();
        $result   = self::curlPost(self::$APIGetCategory, $data);
        $response = json_decode($result, true);

        if (isset($response['status']) && ($response['status'] == 0)) {
            return false;
        } elseif (isset($response['status']) && ($response['status'] == 1)) {

            $data = array();

            foreach ($response['category'] as $category) {
                $data[] = array(
                    'id'        => $category['id'],
                    'parent_id' => $category['parent_id'],
                    'level'     => $category['level'],
                    'name'      => $category['name'],
                );
            }

            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}youbeli_category");
            foreach ($data as $value) {
                $wpdb->query("INSERT INTO {$wpdb->prefix}youbeli_category SET id = '" . (int) $value['id'] . "', `parent_id` = '" . (int) $value['parent_id'] . "', `level` = '" . (int) $value['level'] . "', `name` = '" . $wpdb->escape($value['name']) . "'");
            }
            $last_sync = date('Y-M-d H:i:s');
            update_option('youbeli_config_last_sync_cat', $last_sync);

            return true;
        }
    }

    public static function select()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT a.id,a.name,count(b.id) as child FROM {$wpdb->prefix}youbeli_category a left join {$wpdb->prefix}youbeli_category b ON a.id=b.parent_id WHERE a.parent_id = " . $_POST['param'] . ' GROUP BY a.id');
        $youbeli = array();
        foreach ($results as $result) {
            $youbeli[] = array(
                'id'    => $result->id,
                'name'  => $result->name,
                'child' => $result->child,
            );
        }

        echo json_encode($youbeli);
        die();
    }

    public static function match_category()
    {
        global $wpdb;
        $woo = $_POST['woo_id'];
        $yb  = $_POST['yb_id'];
        $wpdb->query("DELETE FROM {$wpdb->prefix}youbeli_category_path WHERE term_id = '" . (int) $woo . "'");

        $wpdb->query("INSERT INTO {$wpdb->prefix}youbeli_category_path SET term_id = '" . (int) $woo . "', youbeli_id = '" . (int) $yb . "'");

        $query = $wpdb->get_results("SELECT level FROM {$wpdb->prefix}youbeli_category WHERE id = '" . (int) $yb . "'");

        (int) $level = $query[0]->level;

        $query_build = "SELECT CONCAT_WS(',',";

        $names = array();

        for ($i = 0; $i < $level + 1; $i++) {
            array_push($names, 't' . ($i + 1) . '.name');
        }

        $query_build .= implode(',', $names) . ") as name FROM {$wpdb->prefix}youbeli_category AS t1 ";

        for ($i = 0; $i < $level; $i++) {
            $query_build .= "LEFT JOIN {$wpdb->prefix}youbeli_category AS t" . ($i + 2) . ' ON t' . ($i + 2) . '.parent_id = t' . ($i + 1) . '.id ';
        }

        $query_build .= 'WHERE t' . ($level + 1) . '.id = ' . (int) $yb;

        $query = $wpdb->get_results($query_build);

        $name = $query[0]->name;
        $name = str_replace(',', ' > ', $name);
        $wpdb->query("UPDATE {$wpdb->prefix}youbeli_category_path SET youbeli_name = '" . $wpdb->escape($name) . "' WHERE term_id = '" . (int) $woo . "'");

        $json = array(
            'status' => 1,
            'yb_cat' => $name,
        );

        echo json_encode($json);
        die();
    }

    public static function getPostParameter($additional = array())
    {
        $data = array(
            'store_id' => (int)get_option('youbeli_store_id'),
            'timestamp' => time(),
        );
        return array_merge($data, $additional);
    }

    public static function curlGet($url)
    {
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public static function curlPost($url, $data = array())
    {
        $headers = array('Content-Type: application/json; charset=UTF-8');

        $sign = hash_hmac('sha256', json_encode($data), get_option('youbeli_api_key'));
        $data['sign'] = $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);

        curl_close($ch);
        return $result;
    }

    public static function product_page_youbeli_custom_tabs_panel()
    {
        global $post;
        echo '<div id="youbeli_sync" class="panel wc-metaboxes-wrapper woocommerce_options_panel">';

        if (Youbeli::is_api_set()) {
            $in_youbeli = self::is_in_youbeli($post->ID);
            echo '<div class="options_group">';
            echo '<p class="form-field comment_status_field ">';
            echo '<label for="comment_status">Sync to Youbeli.com</label>';
            echo '<input type="hidden" name="has_sync_to_youbeli" value="1">';
            echo '<input type="checkbox" class="checkbox" style="" name="sync_to_youbeli" id="sync_to_youbeli"';
            if ($_GET['action'] == 'edit') {
                if (isset($in_youbeli) && $in_youbeli) {
                    echo 'checked';
                }
            } else {
                echo 'checked';
            }
            echo '><span class="description">If the category is not match, the product will be assigned to "New Arrival" category in Youbeli.com. You can match category in <a href="' . Youbeli::get_page_url('youbeli_category_setting') . '">Category Setting</a>.</span></p>';
            echo '</div>';
        } else {
            echo '<p>Youbeli Sync setting not found. Please set your store ID and API key in <a href="' . Youbeli::get_page_url('youbeli_setting') . '">Youbeli Sync Setting</a> to sync.</p>';
        }

        echo '</div>';

    }

    public static function set_sync_action($id, $action)
    {
        global $wpdb;
        if ($action == 0) {
            $status = $wpdb->get_var("SELECT in_youbeli FROM {$wpdb->prefix}youbeli_sync_product WHERE product_id=" . (int) $id);
            if ($status) {
                $wpdb->query("INSERT INTO {$wpdb->prefix}youbeli_sync_product (product_id,action,do_sync) VALUES (" . (int) $id . ',' . (int) $action . ',1) ON DUPLICATE KEY UPDATE action=' . (int) $action . ',do_sync=1 ');
            }
        } else {
            $wpdb->query("INSERT INTO {$wpdb->prefix}youbeli_sync_product (product_id,action,do_sync) VALUES (" . (int) $id . ',' . (int) $action . ',1) ON DUPLICATE KEY UPDATE action=' . (int) $action . ',do_sync=1 ');
        }
    }

    public static function get_sync_ids()
    {
        global $wpdb;
        $ids = $wpdb->get_results("SELECT product_id FROM {$wpdb->prefix}youbeli_sync_product WHERE action=1 AND do_sync=1");
        return $ids;
    }

    public static function get_unsync_ids()
    {
        global $wpdb;
        $ids = $wpdb->get_results("SELECT product_id FROM {$wpdb->prefix}youbeli_sync_product WHERE action=0 AND in_youbeli=1 AND do_sync=1");
        return $ids;
    }

    public static function is_in_youbeli($id)
    {
        global $wpdb;
        $status = $wpdb->get_var("SELECT in_youbeli FROM {$wpdb->prefix}youbeli_sync_product WHERE product_id=" . (int) $id);
        return $status;
    }

    public static function get_sync_action($id)
    {
        global $wpdb;
        $action = $wpdb->get_var("SELECT action FROM {$wpdb->prefix}youbeli_sync_product WHERE product_id=" . (int) $id . ' AND do_sync=1');
        return $action;
    }

    public static function set_sync_success($id)
    {
        global $wpdb;
        $wpdb->query("UPDATE {$wpdb->prefix}youbeli_sync_product SET in_youbeli=1, do_sync=0, date_sync = NOW(), error = '' WHERE product_id=" . (int) $id);
    }

    public static function set_sync_error($id, $error)
    {
        global $wpdb;
        $wpdb->query("UPDATE {$wpdb->prefix}youbeli_sync_product SET do_sync = 0, error = '" . $wpdb->escape($error) . "' WHERE product_id=" . (int) $id);
    }

    public static function set_unsync_success($id)
    {
        global $wpdb;
        $wpdb->query("UPDATE {$wpdb->prefix}youbeli_sync_product SET in_youbeli=0, do_sync=0, date_sync = NOW(), error = '' WHERE product_id=" . (int) $id);
    }

    public static function set_product_removed($id)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}youbeli_sync_product WHERE product_id = " . (int) $id);
    }

    public static function get_sync_status_html($id)
    {
        if (self::is_in_youbeli($id)) {
            echo '<a class="label label-success">Sync</a>';
        } else {
            echo '<a class="label label-danger">Unsync</a>';
        }
    }

    public static function get_sync_error_message($id)
    {
        global $wpdb;
        $error = $wpdb->get_col("SELECT error FROM {$wpdb->prefix}youbeli_sync_product WHERE product_id=" . (int) $id);
        echo $error[0];
    }

    public static function get_last_sync_time($id)
    {
        global $wpdb;
        $error = $wpdb->get_col("SELECT date_sync FROM {$wpdb->prefix}youbeli_sync_product WHERE product_id=" . (int) $id);
        echo $error[0];
    }

    public static function bulk_quick_edit_save($product)
    {
        global $woocommerce;
        if (version_compare($woocommerce->version, '2.4', '<=')) {
            $id = $product->id;
        } else {
            $id = $product->get_id();
        }
        $log = new \Log('youbeli.log');

        if (self::is_in_youbeli($id)) {
            $log->write('bulk_quick_edit_save()_' . $id);
            self::set_sync_action($id, 1);
            $product_synced = self::do_sync($id);
            if ($product_synced) {
                self::api_update();
            }
        }
    }

    public static function product_save_data($id, $post)
    {
        if (empty($id)) {
            return;
        }
        $log = new \Log('youbeli.log');
        $log->write('product_save_data()');
        if (in_array($_POST['product-type'], array( 'simple', 'external', 'variable' ))) {
            if (isset($_POST['has_sync_to_youbeli'])) {
                if (isset($_POST['sync_to_youbeli'])) {
                    if ($_POST['sync_to_youbeli'] == 'on') {
                        self::set_sync_action($id, 1);
                    }
                } else {
                    if (self::is_in_youbeli($id)) {
                        self::set_sync_action($id, 0);
                    }
                }
            }
        }
    }

    public static function edit_product($id, $product)
    {
        $log = new \Log('youbeli.log');

        if ($product->post_type == 'product') {
            $log->write('edit_product()_' . $id);
            $action         = self::get_sync_action($id);
            if (isset($action)) {
                if ($action == 1) {
                    $result = self::do_sync($id);
                } elseif ($action == 0) {
                    $result = self::do_unsync($id);
                }
            }
            if (isset($result['status']) && $result['status'] == 1) {
                self::api_update();
            }
        }
    }

    public static function delete_product($id)
    {
        $log = new \Log('youbeli.log');

        if (self::is_in_youbeli($id)) {
            $log->write('delete_product()_' . $id);
            self::set_sync_action($id, 0);
            $result = self::do_unsync($id);
            ;
            if (isset($result['status']) && $result['status'] == 1) {
                self::set_product_removed($id);
                self::api_update();
            }
        } else {
            $log->write('call_set_product_removed()');
            self::set_product_removed($id);
        }
    }
}
