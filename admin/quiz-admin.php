<?php
// Предотвращение прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Обработка действий
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_quiz':
            $this->handle_create_quiz();
            break;
        case 'save_question':
            $this->handle_save_question();
            break;
        case 'delete_quiz':
            $this->handle_delete_quiz();
            break;
        case 'delete_question':
            $this->handle_delete_question();
            break;
    }
}

// Получение данных
$current_quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$quizzes = $this->get_all_quizzes();
$current_quiz = $current_quiz_id ? $this->get_quiz_data($current_quiz_id) : null;
?>

<div class="wrap">
    <h1>Управление квизами</h1>
    
    <?php if (!$current_quiz_id): ?>
        <!-- Список квизов -->
        <div class="card">
            <h2>Создать новый квиз</h2>
            <form method="post" action="">
                <?php wp_nonce_field('quiz_admin_action', 'quiz_nonce'); ?>
                <input type="hidden" name="action" value="create_quiz">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Название квиза</th>
                        <td>
                            <input type="text" name="quiz_title" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Создать квиз', 'primary'); ?>
            </form>
        </div>
        
        <!-- Список существующих квизов -->
        <div class="card">
            <h2>Существующие квизы</h2>
            
            <?php if (empty($quizzes)): ?>
                <p>Квизы не найдены. Создайте первый квиз!</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Название</th>
                            <th scope="col">Вопросов</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Дата создания</th>
                            <th scope="col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz): ?>
                            <?php $questions_count = $this->get_quiz_questions_count($quiz->id); ?>
                            <tr>
                                <td><?php echo $quiz->id; ?></td>
                                <td><strong><?php echo esc_html($quiz->title); ?></strong></td>
                                <td><?php echo $questions_count; ?></td>
                                <td>
                                    <?php if ($quiz->is_active): ?>
                                        <span class="status-active">Активен</span>
                                    <?php else: ?>
                                        <span class="status-inactive">Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($quiz->created_date)); ?></td>
                                <td>
                                    <a href="?page=quiz-settings&quiz_id=<?php echo $quiz->id; ?>" class="button">Редактировать</a>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Вы уверены?')">
                                        <?php wp_nonce_field('quiz_admin_action', 'quiz_nonce'); ?>
                                        <input type="hidden" name="action" value="delete_quiz">
                                        <input type="hidden" name="quiz_id" value="<?php echo $quiz->id; ?>">
                                        <input type="submit" class="button button-small" value="Удалить">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Информация о шорткодах -->
        <div class="card">
            <h2>Использование шорткодов</h2>
            <p><strong>Для отображения квиза:</strong></p>
            <code>[quiz_form quiz_id="1"]</code>
            <p>Где 1 - это ID квиза из таблицы выше</p>
        </div>
        
    <?php else: ?>
        <!-- Редактирование конкретного квиза -->
        <div class="quiz-editor">
            <div style="margin-bottom: 20px;">
                <a href="?page=quiz-settings" class="button">← Назад к списку квизов</a>
            </div>
            
            <h2>Редактирование квиза: <?php echo esc_html($current_quiz['title']); ?></h2>
            
            <!-- Добавление нового вопроса -->
            <div class="card">
                <h3>Добавить новый вопрос</h3>
                <form method="post" action="" id="question-form">
                    <?php wp_nonce_field('quiz_admin_action', 'quiz_nonce'); ?>
                    <input type="hidden" name="action" value="save_question">
                    <input type="hidden" name="quiz_id" value="<?php echo $current_quiz_id; ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Текст вопроса</th>
                            <td>
                                <textarea name="question_text" rows="3" class="large-text" required></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Тип вопроса</th>
                            <td>
                                <select name="question_type" id="question-type" required>
                                    <option value="">Выберите тип</option>
                                    <option value="checkbox">Чекбоксы (текст)</option>
                                    <option value="image">Выбор картинок</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Мультивыбор</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="is_multiple" value="1">
                                    Разрешить выбор нескольких правильных ответов
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Контейнер для вариантов ответов -->
                    <div id="answers-container">
                        <h4>Варианты ответов</h4>
                        <div class="answers-list">
                            <!-- Варианты будут добавлены через JavaScript -->
                        </div>
                        <button type="button" id="add-answer" class="button">Добавить вариант ответа</button>
                    </div>
                    
                    <?php submit_button('Сохранить вопрос', 'primary'); ?>
                </form>
            </div>
            
            <!-- Список существующих вопросов -->
            <div class="card">
                <h3>Существующие вопросы</h3>
                
                <?php if (empty($current_quiz['questions'])): ?>
                    <p>Вопросы не найдены. Добавьте первый вопрос!</p>
                <?php else: ?>
                    <?php foreach ($current_quiz['questions'] as $question): ?>
                        <div class="question-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <h4><?php echo esc_html($question->question); ?></h4>
                                    <p><strong>Тип:</strong> <?php echo $question->question_type === 'checkbox' ? 'Чекбоксы' : 'Картинки'; ?></p>
                                    <p><strong>Мультивыбор:</strong> <?php echo $question->is_multiple ? 'Да' : 'Нет'; ?></p>
                                    
                                    <div class="answers-preview">
                                        <strong>Варианты ответов:</strong>
                                        <ul>
                                            <?php foreach ($question->answers as $answer): ?>
                                                <li style="margin: 5px 0;">
                                                    <?php if ($question->question_type === 'image' && $answer->answer_image): ?>
                                                        <img src="<?php echo esc_url($answer->answer_image); ?>" style="max-width: 100px; height: auto; margin-right: 10px;">
                                                    <?php endif; ?>
                                                    <?php echo esc_html($answer->answer_text); ?>
                                                    <?php if ($answer->is_correct): ?>
                                                        <strong style="color: green;"> (Правильный)</strong>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Вы уверены?')">
                                        <?php wp_nonce_field('quiz_admin_action', 'quiz_nonce'); ?>
                                        <input type="hidden" name="action" value="delete_question">
                                        <input type="hidden" name="question_id" value="<?php echo $question->id; ?>">
                                        <input type="hidden" name="quiz_id" value="<?php echo $current_quiz_id; ?>">
                                        <input type="submit" class="button button-small" value="Удалить">
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Информация о шорткоде -->
            <div class="card">
                <h3>Шорткод для этого квиза</h3>
                <p>Используйте следующий шорткод для отображения квиза на сайте:</p>
                <code>[quiz_form quiz_id="<?php echo $current_quiz_id; ?>"]</code>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var answerIndex = 0;
    
    // Добавление нового варианта ответа
    $('#add-answer').click(function() {
        var questionType = $('#question-type').val();
        if (!questionType) {
            alert('Сначала выберите тип вопроса');
            return;
        }
        
        var answerHtml = '';
        if (questionType === 'checkbox') {
            answerHtml = `
                <div class="answer-item" style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                    <label>Текст ответа:</label>
                    <input type="text" name="answers[${answerIndex}][text]" class="regular-text" required>
                    <label style="margin-left: 20px;">
                        <input type="checkbox" name="answers[${answerIndex}][is_correct]" value="1">
                        Правильный ответ
                    </label>
                    <button type="button" class="button remove-answer" style="margin-left: 10px;">Удалить</button>
                </div>
            `;
        } else if (questionType === 'image') {
            answerHtml = `
                <div class="answer-item" style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                    <label>Описание картинки:</label>
                    <input type="text" name="answers[${answerIndex}][text]" class="regular-text" required>
                    <br><br>
                    <label>URL картинки:</label>
                    <input type="url" name="answers[${answerIndex}][image]" class="regular-text" required>
                    <button type="button" class="button upload-image" data-index="${answerIndex}">Выбрать из медиатеки</button>
                    <br><br>
                    <label>
                        <input type="checkbox" name="answers[${answerIndex}][is_correct]" value="1">
                        Правильный ответ
                    </label>
                    <button type="button" class="button remove-answer" style="margin-left: 10px;">Удалить</button>
                </div>
            `;
        }
        
        $('.answers-list').append(answerHtml);
        answerIndex++;
    });
    
    // Удаление варианта ответа
    $(document).on('click', '.remove-answer', function() {
        $(this).closest('.answer-item').remove();
    });
    
    // Загрузка изображения через медиатеку WordPress
    $(document).on('click', '.upload-image', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var index = button.data('index');
        
        var frame = wp.media({
            title: 'Выберите изображение',
            button: {
                text: 'Использовать изображение'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('input[name="answers[' + index + '][image]"]').val(attachment.url);
        });
        
        frame.open();
    });
    
    // Очистка формы при смене типа вопроса
    $('#question-type').change(function() {
        $('.answers-list').empty();
        answerIndex = 0;
    });
});
</script> 