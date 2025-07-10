<?php
/**
 * Plugin Name: Promocodes by Pavlenkodim
 * Plugin URI: https://github.com/pavlenkodim/promocodes-by-pavlenkodim
 * Description: Плагин для создания промокодов и квизов с генерацией наград
 * Version: 1.0.0
 * Author: Dmitriy Pavlenko
 * License: GPL v2 or later
 * Text Domain: promocodes-by-pavlenkodim
 */

// Предотвращение прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('PROMOCODES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PROMOCODES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PROMOCODES_PLUGIN_VERSION', '1.0.0');

class PromocodesByPavlenkodim {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Загрузка перевода
        load_plugin_textdomain('promocodes-by-pavlenkodim', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Инициализация компонентов
        $this->init_database();
        $this->init_admin();
        $this->init_shortcodes();
        $this->init_ajax();
        
        // Подключение стилей и скриптов
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function activate() {
        $this->create_database_tables();
        // Добавляем права доступа
        $this->add_capabilities();
    }
    
    public function deactivate() {
        // Очистка при деактивации если нужно
    }
    
    public function init_database() {
        // Инициализация работы с базой данных
    }
    
    public function init_admin() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }
    
    public function init_shortcodes() {
        add_shortcode('promocode_form', array($this, 'promocode_form_shortcode'));
        add_shortcode('quiz_form', array($this, 'quiz_form_shortcode'));
    }
    
    public function init_ajax() {
        add_action('wp_ajax_check_promocode', array($this, 'check_promocode_ajax'));
        add_action('wp_ajax_nopriv_check_promocode', array($this, 'check_promocode_ajax'));
        add_action('wp_ajax_submit_quiz', array($this, 'submit_quiz_ajax'));
        add_action('wp_ajax_nopriv_submit_quiz', array($this, 'submit_quiz_ajax'));
    }
    
    public function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица промокодов
        $table_promocodes = $wpdb->prefix . 'promocodes_pavlenkodim';
        $sql_promocodes = "CREATE TABLE $table_promocodes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(12) NOT NULL,
            prize text,
            is_used tinyint(1) DEFAULT 0,
            used_by bigint(20),
            used_date datetime,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code)
        ) $charset_collate;";
        
        // Таблица квизов
        $table_quizzes = $wpdb->prefix . 'quizzes_pavlenkodim';
        $sql_quizzes = "CREATE TABLE $table_quizzes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Таблица вопросов квиза
        $table_quiz_questions = $wpdb->prefix . 'quiz_questions_pavlenkodim';
        $sql_questions = "CREATE TABLE $table_quiz_questions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            question text NOT NULL,
            question_type varchar(20) NOT NULL,
            is_multiple tinyint(1) DEFAULT 0,
            order_num int DEFAULT 0,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id)
        ) $charset_collate;";
        
        // Таблица вариантов ответов
        $table_quiz_answers = $wpdb->prefix . 'quiz_answers_pavlenkodim';
        $sql_answers = "CREATE TABLE $table_quiz_answers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question_id mediumint(9) NOT NULL,
            answer_text text,
            answer_image varchar(255),
            is_correct tinyint(1) DEFAULT 0,
            order_num int DEFAULT 0,
            PRIMARY KEY (id),
            KEY question_id (question_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_promocodes);
        dbDelta($sql_quizzes);
        dbDelta($sql_questions);
        dbDelta($sql_answers);
    }
    
    public function add_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_promocodes');
            $role->add_cap('manage_quizzes');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Промокоды и Квизы',
            'Промокоды',
            'manage_promocodes',
            'promocode-settings',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
        
        add_submenu_page(
            'promocode-settings',
            'Управление промокодами',
            'Промокоды',
            'manage_promocodes',
            'promocode-settings',
            array($this, 'promocodes_admin_page')
        );
        
        add_submenu_page(
            'promocode-settings',
            'Управление квизами',
            'Квизы',
            'manage_quizzes',
            'quiz-settings',
            array($this, 'quiz_admin_page')
        );
    }
    
    public function admin_init() {
        // Регистрация настроек
        register_setting('promocode_settings', 'promocode_prize_text');
    }
    
    public function admin_page() {
        $this->promocodes_admin_page();
    }
    
    public function promocodes_admin_page() {
        include_once PROMOCODES_PLUGIN_PATH . 'admin/promocodes-admin.php';
    }
    
    public function quiz_admin_page() {
        include_once PROMOCODES_PLUGIN_PATH . 'admin/quiz-admin.php';
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('promocode-frontend', PROMOCODES_PLUGIN_URL . 'assets/frontend.css', array(), PROMOCODES_PLUGIN_VERSION);
        wp_enqueue_script('promocode-frontend', PROMOCODES_PLUGIN_URL . 'assets/frontend.js', array('jquery'), PROMOCODES_PLUGIN_VERSION, true);
        
        wp_localize_script('promocode-frontend', 'promocode_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('promocode_nonce')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'promocode') !== false || strpos($hook, 'quiz') !== false) {
            wp_enqueue_style('promocode-admin', PROMOCODES_PLUGIN_URL . 'assets/admin.css', array(), PROMOCODES_PLUGIN_VERSION);
            wp_enqueue_script('promocode-admin', PROMOCODES_PLUGIN_URL . 'assets/admin.js', array('jquery'), PROMOCODES_PLUGIN_VERSION, true);
            wp_enqueue_media();
        }
    }
    
    // Шорткод формы промокода
    public function promocode_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'button_text' => 'Проверить промокод',
            'placeholder' => 'Введите промокод'
        ), $atts, 'promocode_form');
        
        ob_start();
        ?>
        <div class="promocode-form-container">
            <form class="promocode-form" id="promocode-form">
                <div class="promocode-input-group">
                    <input type="text" id="promocode-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" maxlength="12" />
                    <button type="submit"><?php echo esc_html($atts['button_text']); ?></button>
                </div>
                <div class="promocode-result" id="promocode-result"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Шорткод формы квиза
    public function quiz_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'quiz_id' => 1
        ), $atts, 'quiz_form');
        
        $quiz_id = intval($atts['quiz_id']);
        $quiz_data = $this->get_quiz_data($quiz_id);
        
        if (!$quiz_data) {
            return '<p>Квиз не найден</p>';
        }
        
        ob_start();
        ?>
        <div class="quiz-container" data-quiz-id="<?php echo $quiz_id; ?>">
            <h3><?php echo esc_html($quiz_data['title']); ?></h3>
            <form class="quiz-form" id="quiz-form-<?php echo $quiz_id; ?>">
                <?php $this->render_quiz_questions($quiz_data['questions']); ?>
                <button type="submit" class="quiz-submit-btn">Завершить квиз</button>
            </form>
            <div class="quiz-result" id="quiz-result-<?php echo $quiz_id; ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // AJAX обработчик проверки промокода
    public function check_promocode_ajax() {
        check_ajax_referer('promocode_nonce', 'nonce');
        
        $code = sanitize_text_field($_POST['code']);
        $result = $this->check_promocode($code);
        
        wp_send_json($result);
    }
    
    // AJAX обработчик отправки квиза
    public function submit_quiz_ajax() {
        check_ajax_referer('promocode_nonce', 'nonce');
        
        $quiz_id = intval($_POST['quiz_id']);
        $answers = $_POST['answers'];
        
        $result = $this->process_quiz($quiz_id, $answers);
        
        wp_send_json($result);
    }
    
    // Методы для работы с промокодами
    public function generate_promocode() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }
    
    public function check_promocode($code) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'promocodes_pavlenkodim';
        $promocode = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE code = %s AND is_used = 0",
            $code
        ));
        
        if ($promocode) {
            // Помечаем промокод как использованный
            $wpdb->update(
                $table,
                array(
                    'is_used' => 1,
                    'used_by' => get_current_user_id(),
                    'used_date' => current_time('mysql')
                ),
                array('id' => $promocode->id)
            );
            
            return array(
                'success' => true,
                'message' => 'Поздравляем! Ваш приз: ' . $promocode->prize
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Промокод не найден или уже использован'
            );
        }
    }
    
    // Методы для работы с квизами
    public function get_quiz_data($quiz_id) {
        global $wpdb;
        
        $quiz_table = $wpdb->prefix . 'quizzes_pavlenkodim';
        $questions_table = $wpdb->prefix . 'quiz_questions_pavlenkodim';
        $answers_table = $wpdb->prefix . 'quiz_answers_pavlenkodim';
        
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $quiz_table WHERE id = %d AND is_active = 1",
            $quiz_id
        ));
        
        if (!$quiz) {
            return false;
        }
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $questions_table WHERE quiz_id = %d ORDER BY order_num",
            $quiz_id
        ));
        
        foreach ($questions as &$question) {
            $question->answers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $answers_table WHERE question_id = %d ORDER BY order_num",
                $question->id
            ));
        }
        
        return array(
            'id' => $quiz->id,
            'title' => $quiz->title,
            'questions' => $questions
        );
    }
    
    public function render_quiz_questions($questions) {
        foreach ($questions as $question) {
            echo '<div class="quiz-question" data-question-id="' . $question->id . '">';
            echo '<h4>' . esc_html($question->question) . '</h4>';
            echo '<div class="quiz-answers">';
            
            $input_type = $question->is_multiple ? 'checkbox' : 'radio';
            $input_name = 'question_' . $question->id . ($question->is_multiple ? '[]' : '');
            
            foreach ($question->answers as $answer) {
                echo '<label class="quiz-answer ' . $question->question_type . '">';
                echo '<input type="' . $input_type . '" name="' . $input_name . '" value="' . $answer->id . '">';
                
                if ($question->question_type === 'image' && $answer->answer_image) {
                    echo '<img src="' . esc_url($answer->answer_image) . '" alt="' . esc_attr($answer->answer_text) . '">';
                } else {
                    echo '<span>' . esc_html($answer->answer_text) . '</span>';
                }
                echo '</label>';
            }
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    public function process_quiz($quiz_id, $answers) {
        global $wpdb;
        
        $questions_table = $wpdb->prefix . 'quiz_questions_pavlenkodim';
        $answers_table = $wpdb->prefix . 'quiz_answers_pavlenkodim';
        
        // Получаем правильные ответы
        $correct_answers = $wpdb->get_results($wpdb->prepare(
            "SELECT qa.question_id, qa.id as answer_id 
             FROM $answers_table qa 
             JOIN $questions_table qq ON qa.question_id = qq.id 
             WHERE qq.quiz_id = %d AND qa.is_correct = 1",
            $quiz_id
        ));
        
        $correct_count = 0;
        $total_questions = count($answers);
        
        // Подсчет правильных ответов
        foreach ($correct_answers as $correct) {
            $question_id = $correct->question_id;
            $answer_id = $correct->answer_id;
            
            if (isset($answers['question_' . $question_id])) {
                $user_answers = (array) $answers['question_' . $question_id];
                if (in_array($answer_id, $user_answers)) {
                    $correct_count++;
                }
            }
        }
        
        // Если квиз пройден успешно (более 70% правильных ответов)
        if ($correct_count / $total_questions >= 0.7) {
            $promocode = $this->generate_and_save_promocode('Приз за прохождение квиза!');
            
            return array(
                'success' => true,
                'message' => 'Поздравляем! Вы успешно прошли квиз!',
                'promocode' => $promocode,
                'correct' => $correct_count,
                'total' => $total_questions
            );
        } else {
            return array(
                'success' => false,
                'message' => 'К сожалению, вы ответили неправильно на слишком много вопросов.',
                'correct' => $correct_count,
                'total' => $total_questions
            );
        }
    }
    
    public function generate_and_save_promocode($prize = 'Специальный приз!') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'promocodes_pavlenkodim';
        
        // Генерируем уникальный промокод
        do {
            $code = $this->generate_promocode();
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE code = %s",
                $code
            ));
        } while ($exists);
        
        // Сохраняем промокод
        $result = $wpdb->insert(
            $table,
            array(
                'code' => $code,
                'prize' => $prize,
                'created_date' => current_time('mysql')
            )
        );
        
        return $result ? $code : false;
    }
    
    // Методы для админ панели
    public function handle_generate_promocodes() {
        if (!wp_verify_nonce($_POST['promocode_nonce'], 'promocode_admin_action')) {
            wp_die('Ошибка безопасности');
        }
        
        $count = intval($_POST['count']);
        $prize = sanitize_text_field($_POST['prize']);
        
        if ($count < 1 || $count > 100) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Некорректное количество промокодов (1-100)</p></div>';
            });
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'promocodes_pavlenkodim';
        
        $generated = 0;
        $failed = 0;
        
        for ($i = 0; $i < $count; $i++) {
            // Генерируем уникальный промокод
            $attempts = 0;
            do {
                $code = $this->generate_promocode();
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table WHERE code = %s",
                    $code
                ));
                $attempts++;
            } while ($exists && $attempts < 10);
            
            if ($attempts >= 10) {
                $failed++;
                continue;
            }
            
            // Сохраняем промокод
            $result = $wpdb->insert(
                $table,
                array(
                    'code' => $code,
                    'prize' => $prize,
                    'created_date' => current_time('mysql')
                )
            );
            
            if ($result) {
                $generated++;
            } else {
                $failed++;
            }
        }
        
        if ($generated > 0) {
            add_action('admin_notices', function() use ($generated) {
                echo '<div class="notice notice-success"><p>Успешно сгенерировано промокодов: ' . $generated . '</p></div>';
            });
        }
        
        if ($failed > 0) {
            add_action('admin_notices', function() use ($failed) {
                echo '<div class="notice notice-warning"><p>Не удалось сгенерировать: ' . $failed . '</p></div>';
            });
        }
    }
    
    public function handle_add_promocode() {
        if (!wp_verify_nonce($_POST['promocode_nonce'], 'promocode_admin_action')) {
            wp_die('Ошибка безопасности');
        }
        
        $code = strtoupper(sanitize_text_field($_POST['code']));
        $prize = sanitize_text_field($_POST['prize']);
        
        // Валидация
        if (empty($code) || empty($prize)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Заполните все поля</p></div>';
            });
            return;
        }
        
        if (strlen($code) !== 12) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Промокод должен содержать 12 символов</p></div>';
            });
            return;
        }
        
        if (!preg_match('/^[A-Z0-9]+$/', $code)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Промокод может содержать только латинские буквы и цифры</p></div>';
            });
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'promocodes_pavlenkodim';
        
        // Проверяем уникальность
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE code = %s",
            $code
        ));
        
        if ($exists) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Промокод уже существует</p></div>';
            });
            return;
        }
        
        // Сохраняем
        $result = $wpdb->insert(
            $table,
            array(
                'code' => $code,
                'prize' => $prize,
                'created_date' => current_time('mysql')
            )
        );
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Промокод успешно добавлен</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Ошибка при добавлении промокода</p></div>';
            });
        }
    }
    
    public function handle_delete_promocode() {
        if (!wp_verify_nonce($_POST['promocode_nonce'], 'promocode_admin_action')) {
            wp_die('Ошибка безопасности');
        }
        
        $promocode_id = intval($_POST['promocode_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'promocodes_pavlenkodim';
        
        $result = $wpdb->delete($table, array('id' => $promocode_id));
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Промокод удален</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Ошибка при удалении промокода</p></div>';
            });
        }
    }
    
    public function get_all_promocodes() {
        global $wpdb;
        $table = $wpdb->prefix . 'promocodes_pavlenkodim';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_date DESC");
    }
    
    public function get_all_quizzes() {
        global $wpdb;
        $table = $wpdb->prefix . 'quizzes_pavlenkodim';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_date DESC");
    }
    
    public function get_quiz_questions_count($quiz_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'quiz_questions_pavlenkodim';
        
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE quiz_id = %d", $quiz_id));
    }
    
    public function handle_create_quiz() {
        if (!wp_verify_nonce($_POST['quiz_nonce'], 'quiz_admin_action')) {
            wp_die('Ошибка безопасности');
        }
        
        $title = sanitize_text_field($_POST['quiz_title']);
        
        if (empty($title)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Название квиза не может быть пустым</p></div>';
            });
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'quizzes_pavlenkodim';
        
        $result = $wpdb->insert(
            $table,
            array(
                'title' => $title,
                'created_date' => current_time('mysql')
            )
        );
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Квиз успешно создан</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Ошибка при создании квиза</p></div>';
            });
        }
    }
    
    public function handle_delete_quiz() {
        if (!wp_verify_nonce($_POST['quiz_nonce'], 'quiz_admin_action')) {
            wp_die('Ошибка безопасности');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        
        global $wpdb;
        $quiz_table = $wpdb->prefix . 'quizzes_pavlenkodim';
        $questions_table = $wpdb->prefix . 'quiz_questions_pavlenkodim';
        $answers_table = $wpdb->prefix . 'quiz_answers_pavlenkodim';
        
        // Удаляем ответы
        $wpdb->delete($answers_table, array('question_id' => array(
            'IN' => $wpdb->get_col($wpdb->prepare("SELECT id FROM $questions_table WHERE quiz_id = %d", $quiz_id))
        )));
        
        // Удаляем вопросы
        $wpdb->delete($questions_table, array('quiz_id' => $quiz_id));
        
        // Удаляем квиз
        $result = $wpdb->delete($quiz_table, array('id' => $quiz_id));
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Квиз удален</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Ошибка при удалении квиза</p></div>';
            });
        }
    }
    
    public function handle_save_question() {
        if (!wp_verify_nonce($_POST['quiz_nonce'], 'quiz_admin_action')) {
            wp_die('Ошибка безопасности');
        }
        
        $quiz_id = intval($_POST['quiz_id']);
        $question_text = sanitize_textarea_field($_POST['question_text']);
        $question_type = sanitize_text_field($_POST['question_type']);
        $is_multiple = isset($_POST['is_multiple']) ? 1 : 0;
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        
        if (empty($question_text) || empty($question_type)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Заполните все обязательные поля</p></div>';
            });
            return;
        }
        
        if (empty($answers)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Добавьте хотя бы один вариант ответа</p></div>';
            });
            return;
        }
        
        // Проверяем наличие правильного ответа
        $has_correct = false;
        foreach ($answers as $answer) {
            if (isset($answer['is_correct'])) {
                $has_correct = true;
                break;
            }
        }
        
        if (!$has_correct) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Отметьте хотя бы один правильный ответ</p></div>';
            });
            return;
        }
        
        global $wpdb;
        $questions_table = $wpdb->prefix . 'quiz_questions_pavlenkodim';
        $answers_table = $wpdb->prefix . 'quiz_answers_pavlenkodim';
        
        // Получаем следующий порядковый номер для вопроса
        $order_num = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(order_num), 0) + 1 FROM $questions_table WHERE quiz_id = %d",
            $quiz_id
        ));
        
        // Сохраняем вопрос
        $question_result = $wpdb->insert(
            $questions_table,
            array(
                'quiz_id' => $quiz_id,
                'question' => $question_text,
                'question_type' => $question_type,
                'is_multiple' => $is_multiple,
                'order_num' => $order_num
            )
        );
        
        if ($question_result) {
            $question_id = $wpdb->insert_id;
            
            // Сохраняем ответы
            $answer_order = 0;
            foreach ($answers as $answer) {
                $answer_text = sanitize_text_field($answer['text']);
                $answer_image = isset($answer['image']) ? esc_url_raw($answer['image']) : '';
                $is_correct = isset($answer['is_correct']) ? 1 : 0;
                
                $wpdb->insert(
                    $answers_table,
                    array(
                        'question_id' => $question_id,
                        'answer_text' => $answer_text,
                        'answer_image' => $answer_image,
                        'is_correct' => $is_correct,
                        'order_num' => $answer_order++
                    )
                );
            }
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Вопрос успешно сохранен</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Ошибка при сохранении вопроса</p></div>';
            });
        }
    }
    
    public function handle_delete_question() {
        if (!wp_verify_nonce($_POST['quiz_nonce'], 'quiz_admin_action')) {
            wp_die('Ошибка безопасности');
        }
        
        $question_id = intval($_POST['question_id']);
        
        global $wpdb;
        $questions_table = $wpdb->prefix . 'quiz_questions_pavlenkodim';
        $answers_table = $wpdb->prefix . 'quiz_answers_pavlenkodim';
        
        // Удаляем ответы
        $wpdb->delete($answers_table, array('question_id' => $question_id));
        
        // Удаляем вопрос
        $result = $wpdb->delete($questions_table, array('id' => $question_id));
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Вопрос удален</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Ошибка при удалении вопроса</p></div>';
            });
        }
    }
}

// Инициализация плагина
new PromocodesByPavlenkodim(); 