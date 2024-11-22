<?php
/*
Plugin Name: Skyfetch Bölüm Bildirim sistemi
plugin URL: https://skyfetch.cloud/
Description: Skyfetch Mangareader / Mangastream Bölüm Bildirim sistemi
Version: 1.5
Author: Skyfetch
Author URL: https://skyfetch.cloud/

*/

class DiscordNotificationManager {
    private const META_KEYS = [
        'webhook' => ['id' => 5600, 'key' => 'dc_webhook'],
        'webhook2' => ['id' => 5601, 'key' => 'dc_webhook2'],
        'webhook_name' => ['id' => 5602, 'key' => 'webhook_name'],  
        'webhook_avatar' => ['id' => 5603, 'key' => 'webhook_resim'],  
        'webhook_id1' => ['id' => 5604, 'key' => 'webhook_id1'], 
        'webhook_id2' => ['id' => 5605, 'key' => 'webhook_id2'] 
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'sendNotification']);
    }

    public function addAdminMenu() {
        add_menu_page(
            'Discord Bildirim',
            'Discord Ayarları',
            'manage_options',
            'discord-settings',
            [$this, 'renderSettingsPage'],
            'dashicons-admin-generic'
        );
    }

    public function renderSettingsPage() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['save_settings']) && check_admin_referer('discord_settings_nonce', 'discord_settings_nonce')) {
            $this->saveSettings();
        }

        $data = $this->getMetaData();
        ?>
        <div class="wrap discord-settings">
            <h1>Discord Bildirim Ayarları</h1>
            
            <div class="discord-settings-container">
                <form method="post" action="">
                    <?php wp_nonce_field('discord_settings_nonce', 'discord_settings_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="webhook">Discord Webhook URL</label>
                        <input type="url" 
                               id="webhook" 
                               name="webhook" 
                               value="<?php echo esc_attr($data['webhook'] ?? ''); ?>" 
                               class="regular-text">
                    </div>

                    <div class="form-group">
                        <label for="webhook_id1">Normal Rol ID</label>
                        <input type="text" 
                               id="webhook_id1" 
                               name="webhook_id1" 
                               value="<?php echo esc_attr($data['webhook_id1'] ?? ''); ?>" 
                               class="regular-text" 
                               pattern="[0-9]+" 
                               title="Sadece sayı giriniz">
                    </div>

                    <div class="form-group">
                        <label for="webhook2">2. Webhook URL</label>
                        <input type="url" 
                               id="webhook2" 
                               name="webhook2" 
                               value="<?php echo esc_attr($data['webhook2'] ?? ''); ?>" 
                               class="regular-text">
                    </div>

                    <div class="form-group">
                        <label for="webhook_id2">2. Rol ID</label>
                        <input type="text" 
                               id="webhook_id2" 
                               name="webhook_id2" 
                               value="<?php echo esc_attr($data['webhook_id2'] ?? ''); ?>" 
                               class="regular-text" 
                               pattern="[0-9]+" 
                               title="Sadece sayı giriniz">
                    </div>

                    <div class="form-group">
                        <label for="webhook_name">Webhook Adı</label>
                        <input type="text" 
                               id="webhook_name" 
                               name="webhook_name" 
                               value="<?php echo esc_attr($data['webhook_name'] ?? ''); ?>" 
                               class="regular-text">
                    </div>

                    <div class="form-group">
                        <label for="webhook_avatar">Webhook Avatar URL</label>
                        <input type="url" 
                               id="webhook_avatar" 
                               name="webhook_avatar" 
                               value="<?php echo esc_attr($data['webhook_avatar'] ?? ''); ?>" 
                               class="regular-text">
                    </div>

                    <button type="submit" 
                            class="button button-primary" 
                            name="save_settings">Kaydet</button>
                </form>
            </div>
        </div>
        <style>
        .discord-settings {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .discord-settings h1 {
            color: #23272A;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #7289DA;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2C2F33;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #7289DA;
            outline: none;
            box-shadow: 0 0 0 2px rgba(114,137,218,0.2);
        }

        .button-primary {
            background: #7289DA !important;
            border-color: #7289DA !important;
            padding: 0.75rem 2rem !important;
        }

        .button-primary:hover {
            background: #677BC4 !important;
        }
        </style>
        <?php
    }
    private function saveSettings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        foreach (self::META_KEYS as $field => $meta) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                $updated = update_post_meta($meta['id'], $meta['key'], $value);
            }
        }

        add_settings_error('discord_settings', 'settings_updated', 'Ayarlar başarıyla kaydedildi.', 'updated');
    }
    private function getMetaData() {
        $data = [];
        foreach (self::META_KEYS as $field => $meta) {
            $value = get_post_meta($meta['id'], $meta['key'], true);
            $data[$field] = $value;
        }
        return $data;
    }    
    public function addMetaBox() {
        add_meta_box(
            'discord_notification',
            'Discord Bildirim Ayarları',
            [$this, 'renderMetaBox'],
            'post',
            'normal',
            'high'
        );
    }
    public function renderMetaBox($post) {
        wp_nonce_field('discord_notification_nonce', 'discord_notification_nonce');
        ?>
        <div class="discord-meta-box">
            <div class="form-group">
                <label for="role_id">Bahsedilecek Rol ID:</label>
                <input type="text" 
                       id="role_id" 
                       name="role_id" 
                       value="<?php echo esc_attr(get_post_meta($post->ID, 'role_id', true)); ?>" 
                       class="widefat">
            </div>

            <div class="form-group">
                <label for="yetiskin">Hangi Webhook Kullanılsın:</label>
                <select name="yetiskin" id="yetiskin" class="widefat">
                    <option value="1" <?php selected(get_post_meta($post->ID, 'yetiskin', true), '1'); ?>>Ana Webhook</option>
                    <option value="2" <?php selected(get_post_meta($post->ID, 'yetiskin', true), '2'); ?>>2.Webhook</option>
                </select>
            </div>

            <p class="description">
                <strong style="color: red;">Dikkat!</strong> Rol ID boş bırakılırsa bildirim gönderilmez.
            </p>
        </div>
        <?php
    }

    public function sendNotification($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['discord_notification_nonce']) || !wp_verify_nonce($_POST['discord_notification_nonce'], 'discord_notification_nonce')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // aşağıdaki yerde post yerine manga yazılabilir bu size kalmış(mangareaderda manga kısmı seriler için post ise bölümler için kullanılıyor isterseniz burdan yeni seriler içinde bildirim sistemi yapabilirsiniz)
        if ('post' !== get_post_type($post_id)) return; 
        if (!isset($_POST['role_id']) || empty($_POST['role_id'])) return;
        $data = $this->getMetaData();
        $role_id = sanitize_text_field($_POST['role_id']);
        $is_adult = isset($_POST['yetiskin']) ? sanitize_text_field($_POST['yetiskin']) : '1';
        $webhook_url = ($is_adult === '2') ? $data['webhook2'] : $data['webhook'];
        $mention_role_id = ($is_adult === '2') ? $data['webhook_id2'] : $data['webhook_id1'];
        $post = get_post($post_id);
        $post_title = $post->post_title;
        $post_url = get_permalink($post_id);
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
        $timestamp = date("c", strtotime("now"));
        $json_data = [
            "content" => "<@&{$mention_role_id}> <@&{$role_id}>",
            "username" => $data['webhook_name'],
            "avatar_url" => $data['webhook_avatar'],
            "tts" => false,
            "embeds" => [
                [
                    "title" => $post_title . " Yayımlandı!",
                    "type" => "rich",
                    "description" => "Bölüm sitemize yüklenmiştir! Keyifli okumalar dileriz.",
                    "url" => $post_url,
                    "timestamp" => $timestamp,
                    "color" => hexdec("3366ff"),
                    "footer" => [
                        "text" => "Skyfetch Manga",
                        "icon_url" => $data['webhook_avatar']
                    ]
                ]
            ]
        ];
    
        if ($thumbnail_url) {
            $json_data['embeds'][0]['image'] = [
                "url" => $thumbnail_url
            ];
        }
    
        $ch = curl_init($webhook_url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($json_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
    
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        if ($http_code !== 204) {
            error_log('Discord Webhook Error: ' . $response);
            return false;
        }
            return true;
    }
}
new DiscordNotificationManager();
