<?php

/**
 * Class Mongodb_Settings_Option
 *
 * Configure the plugin settings page.
 */
class Mongodb_Settings_Option
{

    /**
     * Capability required by the user to access the My Plugin menu entry.
     *
     * @var string $capability
     */
    private $capability = 'manage_options';

    /**
     * Array of fields that should be displayed in the settings page.
     *
     * @var array $fields
     */
    private $fields = [
        [
            'id' => 'customer-email-address',
            'label' => 'Customer Email Address',
            'description' => 'Please add the email address and then save to load the databases.',
            'type' => 'email',
        ],
        [
            'id' => 'load-databases',
            'label' => 'Database',
            'description' => '',
            'type' => 'table',
        ],
        [
            'id' => 'product-import-limit',
            'label' => 'Products Import Limit',
            'description' => 'Set the number of products you want to import. max limit is 50000',
            'type' => 'number',
        ],
        [
            'id' => 'mdb-load-products',
            'label' => '',
            'value' => 'Import Products',
            'description' => '',
            'type' => 'button',
            'link' => 'javascript:void(0)',
        ],
        [
            'id' => 'display_products_only',
            'label' => 'Display products only',
            'description' => 'Checked this box to disable selling',
            'type' => 'checkbox',
        ],

    ];

    /**
     * The Plugin Settings constructor.
     */
    function __construct()
    {
        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_menu', [$this, 'options_page']);
    }

    /**
     * Register the settings and all fields.
     */
    function settings_init(): void
    {

        // Register a new setting this page.
        register_setting('rug-settings', 'mongodb_options');


        // Register a new section.
        add_settings_section(
            'rug-settings-section',
            __('Please add your email and sync database and products accordingly.', 'rug-simple'),
            [$this, 'render_section'],
            'rug-settings'
        );


        /* Register All The Fields. */
        foreach ($this->fields as $field) {
            // Register a new field in the main section.
            add_settings_field(
                $field['id'], /* ID for the field. Only used internally. To set the HTML ID attribute, use $args['label_for']. */
                __($field['label'], 'rug-simple'), /* Label for the field. */
                [$this, 'render_field'], /* The name of the callback function. */
                'rug-settings', /* The menu page on which to display this field. */
                'rug-settings-section', /* The section of the settings page in which to show the box. */
                [
                    'label_for' => $field['id'], /* The ID of the field. */
                    'class' => 'wporg_row', /* The class of the field. */
                    'field' => $field, /* Custom data for the field. */
                ]
            );
        }
    }

    /**
     * Add a subpage to the WordPress Settings menu.
     */
    function options_page(): void
    {
        add_menu_page(
            'Rug Settings', /* Page Title */
            'Rug Settings', /* Menu Title */
            $this->capability, /* Capability */
            'rug-settings', /* Menu Slug */
            [$this, 'render_options_page'], /* Callback */
            'dashicons-admin-settings', /* Icon */
            '2', /* Position */
        );
    }

    /**
     * Render the settings page.
     */
    function render_options_page(): void
    {

        // check user capabilities
        if (!current_user_can($this->capability)) {
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // WordPress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error('wporg_messages', 'wporg_message', __('Settings Saved', 'rug-simple'), 'updated');
        }

        // show error/update messages
        settings_errors('wporg_messages');
?>
        <style>
            .mdb-form input[type="email"] {
                width: 50% !important;
            }
        </style>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <h2 class="description"></h2>
            <form action="options.php" method="post" class="mdb-form">
                <?php
                /* output security fields for the registered setting "wporg" */
                settings_fields('rug-settings');
                /* output setting sections and their fields */
                /* (sections are registered for "wporg", each field is registered to a specific section) */
                do_settings_sections('rug-settings');
                /* output save settings button */
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a settings field.
     *
     * @param array $args Args to configure the field.
     */
    function render_field(array $args): void
    {

        $field = $args['field'];

        // Get the value of the setting we've registered with register_setting()
        $options = get_option('mongodb_options');

        switch ($field['type']) {

            case "text": {
        ?>
                    <input type="text" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }
            case "number": {
                ?>
                    <input type="number" min="1" max="50000" step="1" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }


            case "checkbox": {
                ?>
                    <input type="checkbox" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="1" <?php echo isset($options[$field['id']]) ? (checked($options[$field['id']], 1, false)) : (''); ?>>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }

            case "textarea": {
                ?>
                    <textarea id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]"><?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?></textarea>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }

            case "select": {
                ?>
                    <select id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]">
                        <?php foreach ($field['options'] as $key => $option) { ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($options[$field['id']]) ? (selected($options[$field['id']], $key, false)) : (''); ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }

            case "password": {
                ?>
                    <input type="password" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }

            case "wysiwyg": {
                    wp_editor(
                        isset($options[$field['id']]) ? $options[$field['id']] : '',
                        $field['id'],
                        array(
                            'textarea_name' => 'mongodb_options[' . $field['id'] . ']',
                            'textarea_rows' => 5,
                        )
                    );
                    break;
                }

            case "email": {
                ?>
                    <input type="email" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }

            case "url": {
                ?>
                    <input type="url" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }

            case "color": {
                ?>
                    <input type="color" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }

            case "date": {
                ?>
                    <input type="date" id="<?php echo esc_attr($field['id']); ?>" name="mongodb_options[<?php echo esc_attr($field['id']); ?>]" value="<?php echo isset($options[$field['id']]) ? esc_attr($options[$field['id']]) : ''; ?>">
                    <p class="description">
                        <?php esc_html_e($field['description'], 'rug-simple'); ?>
                    </p>
                <?php
                    break;
                }
            case "button": {
                ?>
                    <div class="mdb-btn-wrap">
                        <a class="mdb-btn button button-primary" href="<?php esc_html_e($field['link']); ?>" id="<?php echo esc_attr($field['id']); ?>"><?php esc_html_e($field['value']); ?></a>
                        <p><?php isset($field['description']) ? esc_html_e($field['description']) : ''; ?></p>
                    </div>
                    <div id="progress-container" style="display: none;">
                        <div id="progress-bar"></div>
                    </div>

                    <?php
                    break;
                }
            case "table": {

                    $customerEmailAddress = isset($options['customer-email-address']) && $options['customer-email-address'] != '' ? $options['customer-email-address'] : '';
                    if ($customerEmailAddress != '') {
                        $databases_arr =  mdb_load_customer_databases($customerEmailAddress);
                        if (isset($databases_arr) && !empty($databases_arr) && sizeof($databases_arr) > 0) {
                    ?>
                            <select id="customer-database">
                                <?php
                                foreach ($databases_arr as $key => $each_customer) {
                                    if (isset($each_customer['database']) && $each_customer['database'] != '') {
                                ?>
                                        <option value="<?php echo $each_customer['database']; ?>">
                                            <?php echo $each_customer['database']; ?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                <?php }
                                ?>
                            </select>
                        <?php
                        } else {
                            echo "No database found with this email address : {$customerEmailAddress}";
                        }
                        ?>
                        <p class="description">
                            <?php esc_html_e($field['description'], 'rug-simple'); ?>
                        </p>
                    <?php
                    }

                    //mdb_load_aggregation_data();

                    ?>
<?php
                    break;
                }
        }
    }

    /**
     * Render a section on a page, with an ID and a text label.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     An array of parameters for the section.
     *
     *     @type string $id The ID of the section.
     * }
     */
    function render_section(array $args): void
    {
    }
}

new Mongodb_Settings_Option();
