<?php

/**
 * Responsible for additional functions
 */

if (!function_exists('mdb_load_aggregation_data')) {

    function mdb_load_aggregation_data($rudID, $databaseName)
    {


        $dataApiKey = 'yTeeaNDJlh88IeDDBnGs1MW5knvVBfzUDayzVrs6iGBg6eAj5Y27ST0MUUz5QGnR';
        $endPointUrl = 'https://us-east-2.aws.data.mongodb-api.com/app/data-uaxenwn/endpoint/data/v1/action/aggregate';

        $database = $databaseName;
        $dataSource = 'Cluster0';
        $collection = 'rug';

        $pipeline = [
            [
                '$match' => ['ID' => $rudID]
            ],
            [
                '$lookup' => [
                    'from' => 'item_collection',
                    'localField' => 'collections',
                    'foreignField' => '_id',
                    'as' => 'collectionDocs'
                ]
            ],
            [
                '$lookup' => [
                    'from' => 'image',
                    'localField' => '_id',
                    'foreignField' => 'parentRef',
                    'as' => 'images'
                ]
            ]
        ];

        $data_p = array(
            'collection' => $collection,
            'database' => $database,
            'dataSource' => $dataSource,
            'pipeline' => $pipeline
        );

        $args = array(
            'headers' => array(
                'timeout' => 300,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'api-key' => $dataApiKey,
                'Access-Control-Request-Headers' => '*'
            ),
            'body' => json_encode($data_p)
        );

        $response = wp_remote_post($endPointUrl, $args);

        $return_data = [];

        if (!is_wp_error($response)) {

            $decoded_response = json_decode($response['body'], true);
            if ($decoded_response !== null) {
                $return_data = $decoded_response['documents'];
            }
        }



        return $return_data;
    }
}

/**
 * Load customer data
 */


if (!function_exists('mdb_load_customer_databases')) {
    function mdb_load_customer_databases($customerEmailAddress)
    {

        $dataApiKey = 'yTeeaNDJlh88IeDDBnGs1MW5knvVBfzUDayzVrs6iGBg6eAj5Y27ST0MUUz5QGnR';
        $endPointUrl = 'https://us-east-2.aws.data.mongodb-api.com/app/data-uaxenwn/endpoint/data/v1/action/find';

        $database = 'rugcopro-portals';
        $dataSource = 'Cluster0';
        $limit = 1;
        $collection = 'portal';

        // { email: "dandev@turcopersian.com" }, 
        //     { database: 1, url: 1, commerce_sites: 1 }

        $data_p = array(
            'collection' => $collection,
            'database' => $database,
            'dataSource' => $dataSource,
            'projection' => array(
                'email' => 1,
                'database' => 1,
                'url' => 1,
                'commerce_sites' => 1
            ),
            'limit' => 50000,
            'filter' => array(
                'email' => $customerEmailAddress
            )
        );


        $args = array(
            'headers' => array(
                'timeout' => 300,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'api-key' => $dataApiKey,
                'Access-Control-Request-Headers' => '*'
            ),
            'body' => json_encode($data_p)
        );

        $response = wp_remote_post($endPointUrl, $args);

        $return_data = [];

        if (!is_wp_error($response)) {

            $decoded_response = json_decode($response['body'], true);

            if ($decoded_response !== null) {
                $return_data = $decoded_response['documents'];
            }
        }

        return $return_data;
    }
}

function format_term_name($name)
{
    return ucwords(str_replace('-', ' ', strtolower($name)));
}


/**
 * Product Insersion
 */

if (!function_exists('mdb_multiple_product_insertion')) {

    function mdb_multiple_product_insertion($products)
    {
        $product_ins_return = [
            'new_insert' => 0,
            'already_exists' => 0,
            'errors' => []
        ];

        foreach ($products as $index => $product_data) {

            // Check for mandatory fields
            if (empty($product_data['title']) || empty($product_data['regularPrice']) || empty($product_data['legacySKU'])) {
                $product_ins_return['errors'][] = "Product at index $index is missing mandatory fields.";
                continue;
            }

            if (!mdb_is_product_exists_by_title($product_data['title'])) {

                $wc_product = new WC_Product_Simple();
                $wc_product->set_name($product_data['title']);

                if (!empty($product_data['description'])) {
                    $wc_product->set_description($product_data['description']);
                }

                $wc_product->set_regular_price($product_data['regularPrice']);
                $wc_product->set_status('publish');

                $wc_product->set_sku($product_data['legacySKU']);
                $wc_product->set_tax_status(!empty($product_data['isTaxable']) && $product_data['isTaxable'] ? 'taxable' : 'none');

                // Setting dimensions
                if (!empty($product_data['dimension'])) {
                    $wc_product->set_length($product_data['dimension']['length'] ?? '');
                    $wc_product->set_width($product_data['dimension']['width'] ?? '');
                    $wc_product->set_height($product_data['dimension']['height'] ?? '');
                }

                // Setting sale price and sale date
                if (!empty($product_data['onSale'])) {
                    if (!empty($product_data['onSale']['salePrice'])) {
                        $wc_product->set_sale_price($product_data['onSale']['salePrice']);
                    }
                    if (!empty($product_data['onSale']['dateOnSaleFrom'])) {
                        $wc_product->set_date_on_sale_from($product_data['onSale']['dateOnSaleFrom']);
                    }
                    if (!empty($product_data['onSale']['dateOnSaleTo'])) {
                        $wc_product->set_date_on_sale_to($product_data['onSale']['dateOnSaleTo']);
                    }
                }

                // Adding custom meta fields
                $meta_fields = [
                    'costType', 'cost', 'condition', 'productType', 'rugType',
                    'constructionType', 'country', 'production', 'primaryMaterial',
                    'design', 'palette', 'pattern', 'pile', 'collection_name'
                ];

                foreach ($meta_fields as $field) {
                    if (isset($product_data[$field]) && !empty($product_data[$field])) {
                        $wc_product->update_meta_data($field, $product_data[$field]);
                    }
                }

                // Adding cost per square data
                if (!empty($product_data['costPerSquare'])) {
                    if (!empty($product_data['costPerSquare']['foot'])) {
                        $wc_product->update_meta_data('costPerSquareFoot', $product_data['costPerSquare']['foot']);
                    }
                    if (!empty($product_data['costPerSquare']['meter'])) {
                        $wc_product->update_meta_data('costPerSquareMeter', $product_data['costPerSquare']['meter']);
                    }
                }

                // Adding inventory data
                if (!empty($product_data['inventory'])) {
                    $wc_product->set_manage_stock($product_data['inventory']['manageStock'] ?? false);
                    $wc_product->set_stock_quantity($product_data['inventory']['quantityLevel'][0]['available'] ?? 0);
                    $wc_product->set_backorders(!empty($product_data['inventory']['isAllowBackOrder']) && $product_data['inventory']['isAllowBackOrder'] ? 'yes' : 'no');
                    $wc_product->set_sold_individually(!empty($product_data['inventory']['isSingleItem']) && $product_data['inventory']['isSingleItem']);
                }

                // Handle categories and subcategories
                $category_ids = [];
                if (!empty($product_data['category'])) {
                    $category_name = format_term_name($product_data['category']);
                    $category = get_term_by('name', $category_name, 'product_cat');
                    if (!$category) {
                        $category = wp_insert_term($category_name, 'product_cat');
                        if (!is_wp_error($category)) {
                            $category_ids[] = $category['term_id'];
                        }
                    } else {
                        $category_ids[] = $category->term_id;
                    }

                    if (!empty($product_data['subCategory'])) {
                        $subcategory_name = format_term_name($product_data['subCategory']);
                        $subcategory = get_term_by('name', $subcategory_name, 'product_cat');
                        if (!$subcategory) {
                            $subcategory = wp_insert_term($subcategory_name, 'product_cat', ['parent' => $category->term_id]);
                            if (!is_wp_error($subcategory)) {
                                $category_ids[] = $subcategory['term_id'];
                            }
                        } else {
                            // Ensure the subcategory's parent is correctly set
                            if ($subcategory->parent != $category->term_id) {
                                wp_update_term($subcategory->term_id, 'product_cat', ['parent' => $category->term_id]);
                            }
                            $category_ids[] = $subcategory->term_id;
                        }
                    }
                }

                if (!empty($category_ids)) {
                    $wc_product->set_category_ids($category_ids);
                }

                // Initial setup for image handling
                $image_ids = [];
                $featured_set = false;

                if (!empty($product_data['images'])) {
                    foreach ($product_data['images'] as $image_info) {
                        if (!empty($image_info['url'])) {
                            $attachment_id = mdb_upload_products_media($image_info['url']);
                            if ($attachment_id) {
                                if (!$featured_set) { // Set the first image processed as featured
                                    $wc_product->set_image_id($attachment_id);
                                    $featured_set = true;
                                } else {
                                    $image_ids[] = $attachment_id; // Add other images to the gallery
                                }
                            }
                        }
                    }
                }

                $wc_product->set_gallery_image_ids($image_ids);
                $product_id = $wc_product->save(); // Save product to database


                // Convert ISO 8601 date strings to MySQL-compatible datetime format
                $created_at = date('Y-m-d H:i:s', strtotime($product_data['created_at']));
                $updated_at = date('Y-m-d H:i:s', strtotime($product_data['updated_at']));

                // Update the post dates in the WordPress database
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    array(
                        'post_date' => $created_at,
                        'post_date_gmt' => get_gmt_from_date($created_at),
                        'post_modified' => $updated_at,
                        'post_modified_gmt' => get_gmt_from_date($updated_at),
                    ),
                    array('ID' => $product_id)
                );

                // Add design as product taxonomy
                if (!empty($product_data['design'])) {
                    $design_name = format_term_name($product_data['design']);
                    $design_term = get_term_by('name', $design_name, 'rug_design');
                    if (!$design_term) {
                        $design_term = wp_insert_term($design_name, 'rug_design');
                        if (!is_wp_error($design_term)) {
                            wp_set_object_terms($product_id, [$design_term['term_id']], 'rug_design');
                        }
                    } else {
                        wp_set_object_terms($product_id, [$design_term->term_id], 'rug_design');
                    }
                }


                // Add rugType as product taxonomy
                if (!empty($product_data['rugType'])) {
                    $rugType_name = format_term_name($product_data['rugType']);
                    $rugType_term = get_term_by('name', $rugType_name, 'rug_type');
                    if (!$rugType_term) {
                        $rugType_term = wp_insert_term($rugType_name, 'rug_type');
                        if (!is_wp_error($rugType_term)) {
                            wp_set_object_terms($product_id, [$rugType_term['term_id']], 'rug_type');
                        }
                    } else {
                        wp_set_object_terms($product_id, [$rugType_term->term_id], 'rug_type');
                    }
                }

                // Add size category tags as product attribute
                if (!empty($product_data['sizeCategoryTags'])) {
                    $size_term_ids = [];
                    foreach ($product_data['sizeCategoryTags'] as $size_tag) {
                        $size_name = format_term_name($size_tag);
                        $size_term = get_term_by('name', $size_name, 'rug_size');
                        if (!$size_term) {
                            $size_term = wp_insert_term($size_name, 'rug_size');
                            if (!is_wp_error($size_term)) {
                                $size_term_ids[] = $size_term['term_id'];
                            }
                        } else {
                            $size_term_ids[] = $size_term->term_id;
                        }
                    }
                    if (!empty($size_term_ids)) {
                        wp_set_object_terms($product_id, $size_term_ids, 'rug_size');
                    }
                }

                // Add shape category tags as product attribute
                if (!empty($product_data['shapeCategoryTags'])) {
                    $shape_term_ids = [];
                    foreach ($product_data['shapeCategoryTags'] as $shape_tag) {
                        $shape_name = format_term_name($shape_tag);
                        $shape_term = get_term_by('name', $shape_name, 'rug_shape');
                        if (!$shape_term) {
                            $shape_term = wp_insert_term($shape_name, 'rug_shape');
                            if (!is_wp_error($shape_term)) {
                                $shape_term_ids[] = $shape_term['term_id'];
                            }
                        } else {
                            $shape_term_ids[] = $shape_term->term_id;
                        }
                    }
                    if (!empty($shape_term_ids)) {
                        wp_set_object_terms($product_id, $shape_term_ids, 'rug_shape');
                    }
                }

                // Add pattern as product taxonomy
                if (!empty($product_data['pattern'])) {
                    $pattern_name = format_term_name($product_data['pattern']);
                    $pattern_term = get_term_by('name', $pattern_name, 'rug_pattern');
                    if (!$pattern_term) {
                        $pattern_term = wp_insert_term($pattern_name, 'rug_pattern');
                        if (!is_wp_error($pattern_term)) {
                            wp_set_object_terms($product_id, [$pattern_term['term_id']], 'rug_pattern');
                        }
                    } else {
                        wp_set_object_terms($product_id, [$pattern_term->term_id], 'rug_pattern');
                    }
                }

                // Add color tags as product attribute
                if (!empty($product_data['colourTags'])) {
                    $color_term_ids = [];
                    foreach ($product_data['colourTags'] as $color_tag) {
                        $color_name = format_term_name($color_tag['name']);
                        $color_term = get_term_by('name', $color_name, 'rug_color');
                        if (!$color_term) {
                            $color_term = wp_insert_term($color_name, 'rug_color');
                            if (!is_wp_error($color_term)) {
                                $color_term_ids[] = $color_term['term_id'];
                            }
                        } else {
                            $color_term_ids[] = $color_term->term_id;
                        }
                    }
                    if (!empty($color_term_ids)) {
                        wp_set_object_terms($product_id, $color_term_ids, 'rug_color');
                    }
                }

                // Add pattern as product taxonomy
                if (!empty($product_data['productType'])) {
                    $productType_name = format_term_name($product_data['productType']);
                    $productType_term = get_term_by('name', $productType_name, 'rug_product_type');
                    if (!$productType_term) {
                        $productType_term = wp_insert_term($productType_name, 'rug_product_type');
                        if (!is_wp_error($productType_term)) {
                            wp_set_object_terms($product_id, [$productType_term['term_id']], 'rug_product_type');
                        }
                    } else {
                        wp_set_object_terms($product_id, [$productType_term->term_id], 'rug_product_type');
                    }
                }

                $product_ins_return['new_insert']++;
            } else {
                $product_ins_return['already_exists']++;
            }
        }

        return $product_ins_return;
    }
}


/**
 * Product Insersion
 */

if (!function_exists('mdb_multiple_product_updation')) {

    function mdb_multiple_product_updation($products)
    {

        foreach ($products as $index => $product_data) {

            // Check for mandatory fields
            if (empty($product_data['title']) || empty($product_data['regularPrice']) || empty($product_data['legacySKU'])) {
                continue;
            }

            $product_id = $product_data['product_id'];

            if ($product_id != '') {


                $wc_product = wc_get_product($product_id);

                if ($wc_product && $wc_product instanceof WC_Product_Simple) {

                    $wc_product->set_name($product_data['title']);

                    if (!empty($product_data['description'])) {
                        $wc_product->set_description($product_data['description']);
                    }

                    $wc_product->set_regular_price($product_data['regularPrice']);
                    $wc_product->set_status('publish');

                    $wc_product->set_sku($product_data['legacySKU']);
                    $wc_product->set_tax_status(!empty($product_data['isTaxable']) && $product_data['isTaxable'] ? 'taxable' : 'none');

                    // Setting dimensions
                    if (!empty($product_data['dimension'])) {
                        $wc_product->set_length($product_data['dimension']['length'] ?? '');
                        $wc_product->set_width($product_data['dimension']['width'] ?? '');
                        $wc_product->set_height($product_data['dimension']['height'] ?? '');
                    }

                    // Setting sale price and sale date
                    if (!empty($product_data['onSale'])) {
                        if (!empty($product_data['onSale']['salePrice'])) {
                            $wc_product->set_sale_price($product_data['onSale']['salePrice']);
                        }
                        if (!empty($product_data['onSale']['dateOnSaleFrom'])) {
                            $wc_product->set_date_on_sale_from($product_data['onSale']['dateOnSaleFrom']);
                        }
                        if (!empty($product_data['onSale']['dateOnSaleTo'])) {
                            $wc_product->set_date_on_sale_to($product_data['onSale']['dateOnSaleTo']);
                        }
                    }

                    // Adding custom meta fields
                    $meta_fields = [
                        'costType', 'cost', 'condition', 'productType', 'rugType',
                        'constructionType', 'country', 'production', 'primaryMaterial',
                        'design', 'palette', 'pattern', 'pile', 'collection_name'
                    ];

                    foreach ($meta_fields as $field) {
                        if (isset($product_data[$field]) && !empty($product_data[$field])) {
                            $wc_product->update_meta_data($field, $product_data[$field]);
                        }
                    }

                    // Adding cost per square data
                    if (!empty($product_data['costPerSquare'])) {
                        if (!empty($product_data['costPerSquare']['foot'])) {
                            $wc_product->update_meta_data('costPerSquareFoot', $product_data['costPerSquare']['foot']);
                        }
                        if (!empty($product_data['costPerSquare']['meter'])) {
                            $wc_product->update_meta_data('costPerSquareMeter', $product_data['costPerSquare']['meter']);
                        }
                    }

                    // Adding inventory data
                    if (!empty($product_data['inventory'])) {
                        $wc_product->set_manage_stock($product_data['inventory']['manageStock'] ?? false);
                        $wc_product->set_stock_quantity($product_data['inventory']['quantityLevel'][0]['available'] ?? 0);
                        $wc_product->set_backorders(!empty($product_data['inventory']['isAllowBackOrder']) && $product_data['inventory']['isAllowBackOrder'] ? 'yes' : 'no');
                        $wc_product->set_sold_individually(!empty($product_data['inventory']['isSingleItem']) && $product_data['inventory']['isSingleItem']);
                    }

                    // Handle categories and subcategories
                    $category_ids = [];
                    if (!empty($product_data['category'])) {
                        $category_name = format_term_name($product_data['category']);
                        $category = get_term_by('name', $category_name, 'product_cat');
                        if (!$category) {
                            $category = wp_insert_term($category_name, 'product_cat');
                            if (!is_wp_error($category)) {
                                $category_ids[] = $category['term_id'];
                            }
                        } else {
                            $category_ids[] = $category->term_id;
                        }

                        if (!empty($product_data['subCategory'])) {
                            $subcategory_name = format_term_name($product_data['subCategory']);
                            $subcategory = get_term_by('name', $subcategory_name, 'product_cat');
                            if (!$subcategory) {
                                $subcategory = wp_insert_term($subcategory_name, 'product_cat', ['parent' => $category->term_id]);
                                if (!is_wp_error($subcategory)) {
                                    $category_ids[] = $subcategory['term_id'];
                                }
                            } else {
                                // Ensure the subcategory's parent is correctly set
                                if ($subcategory->parent != $category->term_id) {
                                    wp_update_term($subcategory->term_id, 'product_cat', ['parent' => $category->term_id]);
                                }
                                $category_ids[] = $subcategory->term_id;
                            }
                        }
                    }

                    if (!empty($category_ids)) {
                        $wc_product->set_category_ids($category_ids);
                    }

                    $product_id = $wc_product->save(); // Save product to database

                    // Convert ISO 8601 date strings to MySQL-compatible datetime format
                    $created_at = date('Y-m-d H:i:s', strtotime($product_data['created_at']));
                    $updated_at = date('Y-m-d H:i:s', strtotime($product_data['updated_at']));

                    // Update the post dates in the WordPress database
                    global $wpdb;
                    $wpdb->update(
                        $wpdb->posts,
                        array(
                            'post_date' => $created_at,
                            'post_date_gmt' => get_gmt_from_date($created_at),
                            'post_modified' => $updated_at,
                            'post_modified_gmt' => get_gmt_from_date($updated_at),
                        ),
                        array('ID' => $product_id)
                    );

                    // Add design as product taxonomy
                    if (!empty($product_data['design'])) {
                        $design_name = format_term_name($product_data['design']);
                        $design_term = get_term_by('name', $design_name, 'rug_design');
                        if (!$design_term) {
                            $design_term = wp_insert_term($design_name, 'rug_design');
                            if (!is_wp_error($design_term)) {
                                wp_set_object_terms($product_id, [$design_term['term_id']], 'rug_design');
                            }
                        } else {
                            wp_set_object_terms($product_id, [$design_term->term_id], 'rug_design');
                        }
                    }


                    // Add rugType as product taxonomy
                    if (!empty($product_data['rugType'])) {
                        $rugType_name = format_term_name($product_data['rugType']);
                        $rugType_term = get_term_by('name', $rugType_name, 'rug_type');
                        if (!$rugType_term) {
                            $rugType_term = wp_insert_term($rugType_name, 'rug_type');
                            if (!is_wp_error($rugType_term)) {
                                wp_set_object_terms($product_id, [$rugType_term['term_id']], 'rug_type');
                            }
                        } else {
                            wp_set_object_terms($product_id, [$rugType_term->term_id], 'rug_type');
                        }
                    }

                    // Add size category tags as product attribute
                    if (!empty($product_data['sizeCategoryTags'])) {
                        $size_term_ids = [];
                        foreach ($product_data['sizeCategoryTags'] as $size_tag) {
                            $size_name = format_term_name($size_tag);
                            $size_term = get_term_by('name', $size_name, 'rug_size');
                            if (!$size_term) {
                                $size_term = wp_insert_term($size_name, 'rug_size');
                                if (!is_wp_error($size_term)) {
                                    $size_term_ids[] = $size_term['term_id'];
                                }
                            } else {
                                $size_term_ids[] = $size_term->term_id;
                            }
                        }
                        if (!empty($size_term_ids)) {
                            wp_set_object_terms($product_id, $size_term_ids, 'rug_size');
                        }
                    }

                    // Add shape category tags as product attribute
                    if (!empty($product_data['shapeCategoryTags'])) {
                        $shape_term_ids = [];
                        foreach ($product_data['shapeCategoryTags'] as $shape_tag) {
                            $shape_name = format_term_name($shape_tag);
                            $shape_term = get_term_by('name', $shape_name, 'rug_shape');
                            if (!$shape_term) {
                                $shape_term = wp_insert_term($shape_name, 'rug_shape');
                                if (!is_wp_error($shape_term)) {
                                    $shape_term_ids[] = $shape_term['term_id'];
                                }
                            } else {
                                $shape_term_ids[] = $shape_term->term_id;
                            }
                        }
                        if (!empty($shape_term_ids)) {
                            wp_set_object_terms($product_id, $shape_term_ids, 'rug_shape');
                        }
                    }

                    // Add pattern as product taxonomy
                    if (!empty($product_data['pattern'])) {
                        $pattern_name = format_term_name($product_data['pattern']);
                        $pattern_term = get_term_by('name', $pattern_name, 'rug_pattern');
                        if (!$pattern_term) {
                            $pattern_term = wp_insert_term($pattern_name, 'rug_pattern');
                            if (!is_wp_error($pattern_term)) {
                                wp_set_object_terms($product_id, [$pattern_term['term_id']], 'rug_pattern');
                            }
                        } else {
                            wp_set_object_terms($product_id, [$pattern_term->term_id], 'rug_pattern');
                        }
                    }

                    // Add color tags as product attribute
                    if (!empty($product_data['colourTags'])) {
                        $color_term_ids = [];
                        foreach ($product_data['colourTags'] as $color_tag) {
                            $color_name = format_term_name($color_tag['name']);
                            $color_term = get_term_by('name', $color_name, 'rug_color');
                            if (!$color_term) {
                                $color_term = wp_insert_term($color_name, 'rug_color');
                                if (!is_wp_error($color_term)) {
                                    $color_term_ids[] = $color_term['term_id'];
                                }
                            } else {
                                $color_term_ids[] = $color_term->term_id;
                            }
                        }
                        if (!empty($color_term_ids)) {
                            wp_set_object_terms($product_id, $color_term_ids, 'rug_color');
                        }
                    }

                    // Add pattern as product taxonomy
                    if (!empty($product_data['productType'])) {
                        $productType_name = format_term_name($product_data['productType']);
                        $productType_term = get_term_by('name', $productType_name, 'rug_product_type');
                        if (!$productType_term) {
                            $productType_term = wp_insert_term($productType_name, 'rug_product_type');
                            if (!is_wp_error($productType_term)) {
                                wp_set_object_terms($product_id, [$productType_term['term_id']], 'rug_product_type');
                            }
                        } else {
                            wp_set_object_terms($product_id, [$productType_term->term_id], 'rug_product_type');
                        }
                    }
                }
            }
        }
    }
}


/**
 * Check product existence
 */

if (!function_exists('mdb_is_product_exists_by_title')) {
    function mdb_is_product_exists_by_title($title)
    {
        global $wpdb;
        $post_title = wp_strip_all_tags($title);
        $query = "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'product' AND post_status = 'publish'";
        return $wpdb->get_var($wpdb->prepare($query, $post_title));
    }
}


/**
 * Upload Product Images
 */

if (!function_exists('mdb_upload_products_media')) {

    function mdb_upload_products_media($image_url)
    {
        $upload = wp_upload_bits(basename($image_url), null, file_get_contents($image_url));
        if (!$upload['error']) {
            $wp_filetype = wp_check_filetype($upload['file'], null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_parent' => 0,
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment($attachment, $upload['file']);
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            return $attachment_id;
        }
        return null;
    }
}

/**
 * Ajax Request to load and import products
 */


if (!function_exists('mdb_load_customer_products_callback')) {


    add_action('wp_ajax_mdb_load_customer_products', 'mdb_load_customer_products_callback');

    function mdb_load_customer_products_callback()
    {
        global $mongodb_options;

        $mongodb_options = get_option('mongodb_options');

        $customerEmailAddress = isset($mongodb_options['customer-email-address']) && $mongodb_options['customer-email-address'] != '' ? $mongodb_options['customer-email-address'] : '';

        $databaseData = isset($_POST['database']) && $_POST['database'] != '' ? $_POST['database'] : '';
        $emailData = isset($_POST['email']) && $_POST['email'] != '' ? $_POST['email'] : '';
        $limit = isset($_POST['limit']) && $_POST['limit'] != '' ? $_POST['limit'] : 10;

        $url = 'https://us-east-2.aws.data.mongodb-api.com/app/data-uaxenwn/endpoint/data/v1/action/find';
        $api_key = 'yTeeaNDJlh88IeDDBnGs1MW5knvVBfzUDayzVrs6iGBg6eAj5Y27ST0MUUz5QGnR';

        $collectionName = 'rug';
        $database = $databaseData;
        $dataSource = 'Cluster0';

        $data_p = array(
            'collection' => $collectionName,
            'database' => $database,
            'dataSource' => $dataSource,
            'limit' => (int)$limit,
        );

        $args = array(
            'headers' => array(
                'timeout' => 300,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'api-key' => $api_key,
                'Access-Control-Request-Headers' => '*'
            ),
            'body' => json_encode($data_p)
        );

        $response = wp_remote_post($url, $args);

        $arr = [];
        if (is_wp_error($response)) {
            // Handle the error gracefully
            $error_message = $response->get_error_message();

            $arr['status'] = 'error';
            $arr['message'] = "Error: $error_message";
            echo json_encode($arr);
            wp_die();
        }

        // Check if the response is valid
        if (isset($response['body'])) {

            $decoded_response = json_decode($response['body'], true);

            if ($decoded_response === null) {
                // Failed to decode JSON response
                $arr['status'] = 'error';
                $arr['message'] = "Error: Failed to decode JSON response";

                echo json_encode($arr);
                wp_die();
            } else {


                $customerRugData = $decoded_response;


                $woo_insersion_data = [];

                if (isset($customerRugData['documents']) && !empty($customerRugData['documents'])) {

                    foreach ($customerRugData['documents'] as $key => $each_doc_value) {

                        $rugID = isset($each_doc_value['ID']) ? $each_doc_value['ID'] : '';
                        $rugSubCategory = isset($each_doc_value['subCategory']['name']) ? $each_doc_value['subCategory']['name'] : '';

                        if ($rugID != '' && $rugSubCategory != '') {

                            $customerRegredadeData = mdb_load_aggregation_data($rugID, $databaseData);

                            if (isset($customerRegredadeData) && !empty($customerRegredadeData)) {

                                foreach ($customerRegredadeData as $key => $eachCustomerData) {

                                    $wooSubItem = [];

                                    // product title
                                    //if ($rugSubCategory == 'owned') {
                                    $wooSubItem['title'] = isset($eachCustomerData['owned']['title']) ? $eachCustomerData['owned']['title'] : '';
                                    //}
                                    // product description
                                    $wooSubItem['description'] = isset($eachCustomerData['description']) ? $eachCustomerData['description'] : '';
                                    // product description
                                    $wooSubItem['collection_name'] = isset($databaseData) ? $databaseData : '';

                                    //price
                                    $wooSubItem['regularPrice'] = isset($eachCustomerData['price']['regularPrice']) ? $eachCustomerData['price']['regularPrice'] : 0;
                                    $wooSubItem['sellingPrice'] = isset($eachCustomerData['price']['sellingPrice']) ? $eachCustomerData['price']['sellingPrice'] : 0;
                                    // product dimension
                                    $wooSubItem['dimension'] = $eachCustomerData['dimension'];
                                    // on sale or not
                                    $wooSubItem['isOnSale'] = isset($eachCustomerData['price']['isOnSale']) ? $eachCustomerData['price']['isOnSale'] : false;
                                    $wooSubItem['onSale'] = isset($eachCustomerData['price']['onSale']) ? $eachCustomerData['price']['onSale'] : [];
                                    //cost type and cost
                                    $wooSubItem['costType'] = isset($eachCustomerData['price']['costType']) ? $eachCustomerData['price']['costType'] : '';
                                    $wooSubItem['cost'] = isset($eachCustomerData['price']['cost']) ? $eachCustomerData['price']['cost'] : '';
                                    $wooSubItem['costPerSquare'] = isset($eachCustomerData['price']['costPerSquare']) ? $eachCustomerData['price']['costPerSquare'] : [];
                                    // media
                                    $wooSubItem['images'] = isset($eachCustomerData['images']) ? $eachCustomerData['images'] : [];
                                    // tax-field
                                    $wooSubItem['isTaxable'] = isset($eachCustomerData['price']['isTaxable']) ? $eachCustomerData['price']['isTaxable'] : false;
                                    // product condition
                                    $wooSubItem['condition'] = isset($eachCustomerData['condition']) ? $eachCustomerData['condition'] : '';
                                    // product data
                                    $wooSubItem['productData'] = isset($eachCustomerData['productData']) ? $eachCustomerData['productData'] : '';
                                    $wooSubItem['status'] = isset($eachCustomerData['status']) ? $eachCustomerData['status'] : '';
                                    $wooSubItem['legacySKU'] = isset($eachCustomerData['legacySKU']) ? $eachCustomerData['legacySKU'] : '';
                                    $wooSubItem['ID'] = isset($eachCustomerData['ID']) ? $eachCustomerData['ID'] : 0;
                                    // product type rugs or not and rug type
                                    $wooSubItem['productType'] = isset($eachCustomerData['productType']) ? $eachCustomerData['productType'] : '';
                                    $wooSubItem['rugType'] = isset($eachCustomerData['rugType']) ? $eachCustomerData['rugType'] : '';
                                    //Categories
                                    $wooSubItem['category'] = isset($eachCustomerData['category']['name']) ? $eachCustomerData['category']['name'] : '';
                                    $wooSubItem['subCategory'] = isset($eachCustomerData['subCategory']['name']) ? $eachCustomerData['subCategory']['name'] : '';
                                    // colorTags
                                    $wooSubItem['colourTags'] = isset($eachCustomerData['colourTags']) ? $eachCustomerData['colourTags'] : [];
                                    $wooSubItem['attributes'] = isset($eachCustomerData['attributes']) ? $eachCustomerData['attributes'] : [];
                                    // construct type
                                    $wooSubItem['constructionType'] = isset($eachCustomerData['constructionType']) ? $eachCustomerData['constructionType'] : '';
                                    // location
                                    $wooSubItem['country'] = isset($eachCustomerData['country']) ? $eachCustomerData['country'] : '';
                                    // extra data
                                    $wooSubItem['collections'] = isset($eachCustomerData['collections']) ? $eachCustomerData['collections'] : [];
                                    $wooSubItem['customFields'] = isset($eachCustomerData['customFields']) ? $eachCustomerData['customFields'] : [];
                                    $wooSubItem['production'] = isset($eachCustomerData['production']) ? $eachCustomerData['production'] : '';
                                    $wooSubItem['primaryMaterial'] = isset($eachCustomerData['primaryMaterial']) ? $eachCustomerData['primaryMaterial'] : '';
                                    $wooSubItem['design'] = isset($eachCustomerData['design']) ? $eachCustomerData['design'] : '';
                                    $wooSubItem['palette'] = isset($eachCustomerData['palette']) ? $eachCustomerData['palette'] : '';
                                    $wooSubItem['pattern'] = isset($eachCustomerData['pattern']) ? $eachCustomerData['pattern'] : '';
                                    $wooSubItem['pile'] = isset($eachCustomerData['pile']) ? $eachCustomerData['pile'] : '';
                                    $wooSubItem['period'] = isset($eachCustomerData['period']) ? $eachCustomerData['period'] : '';

                                    // publish and updated time
                                    $wooSubItem['created_at'] = isset($eachCustomerData['created_at']) ? $eachCustomerData['created_at'] : '';
                                    $wooSubItem['updated_at'] = isset($eachCustomerData['updated_at']) ? $eachCustomerData['updated_at'] : '';

                                    //inventory
                                    $wooSubItem['inventory'] = isset($eachCustomerData['inventory']) ? $eachCustomerData['inventory'] : [];
                                    $wooSubItem['shipping'] = isset($eachCustomerData['shipping']) ? $eachCustomerData['shipping'] : [];

                                    $wooSubItem['otherTags'] = isset($eachCustomerData['otherTags']) ? $eachCustomerData['otherTags'] : [];
                                    $wooSubItem['sizeCategoryTags'] = isset($eachCustomerData['sizeCategoryTags']) ? $eachCustomerData['sizeCategoryTags'] : [];
                                    $wooSubItem['styleTags'] = isset($eachCustomerData['styleTags']) ? $eachCustomerData['styleTags'] : [];
                                    $wooSubItem['shapeCategoryTags'] = isset($eachCustomerData['shapeCategoryTags']) ? $eachCustomerData['shapeCategoryTags'] : [];


                                    $woo_insersion_data[] = $wooSubItem;
                                }
                            } else {

                                $arr['status'] = 'error';
                                $arr['message'] = "Connected: No Record Found";

                                echo json_encode($arr);
                                wp_die();
                            }
                        }
                    }
                } else {

                    $arr['status'] = 'error';
                    $arr['message'] = "Connected: No Record Found";

                    echo json_encode($arr);
                    wp_die();
                }

                $insertionStatusData = [];


                if (!empty($woo_insersion_data) && sizeof($woo_insersion_data) > 0) {

                    $insertionStatusData =  mdb_multiple_product_insertion($woo_insersion_data);
                } else {

                    $arr['status'] = 'error';
                    $arr['message'] = "Connected: No Record Found";

                    echo json_encode($arr);
                    wp_die();
                }

                $arr['status'] = 'success';
                $arr['message'] = " {$insertionStatusData['new_insert']} New Product Inserted <br /> {$insertionStatusData['already_exists']} Products already exists";
                $arr['import_status'] = $insertionStatusData;

                echo json_encode($arr);
                wp_die();
            }
        } else {
            $arr['status'] = 'error';
            $arr['message'] = "Error: Empty response body";
            echo json_encode($arr);
            wp_die();
        }

        echo json_encode($arr);
        wp_die();
    }
}

// Disable purchasing if "Display Products Only" option is enabled
add_filter('pre_option_woocommerce_enable_ajax_add_to_cart', 'rugs_disable_ajax_add_to_cart');
function rugs_disable_ajax_add_to_cart($value)
{

    $mongo_options = get_option('mongodb_options');
    if (isset($mongo_options['display_products_only']) && $mongo_options['display_products_only']) {
        return 'no';
    }
    return $value;
}

// Hide add to cart button on single product page if "Display Products Only" option is enabled
add_action('woocommerce_single_product_summary', 'rugs_hide_add_to_cart_button', 1);
function rugs_hide_add_to_cart_button()
{
    $mongo_options = get_option('mongodb_options');
    if (isset($mongo_options['display_products_only']) && $mongo_options['display_products_only']) {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    }
}

// Hide add to cart button on product archives (shop, category, etc.) if "Display Products Only" option is enabled
add_action('woocommerce_loop_add_to_cart_link', 'rugs_remove_loop_add_to_cart_button', 10, 2);
function rugs_remove_loop_add_to_cart_button($button, $product)
{
    $mongo_options = get_option('mongodb_options');
    if (isset($mongo_options['display_products_only']) && $mongo_options['display_products_only']) {
        return '';
    }
    return $button;
}

// Redirect to shop page if "Display Products Only" option is enabled
add_action('template_redirect', 'rugs_redirect_cart_checkout');
function rugs_redirect_cart_checkout()
{
    if (is_cart() || is_checkout()) {
        $mongo_options = get_option('mongodb_options');
        if (isset($mongo_options['display_products_only']) && $mongo_options['display_products_only']) {
            wp_redirect(get_permalink(wc_get_page_id('shop')));
            exit;
        }
    }
}