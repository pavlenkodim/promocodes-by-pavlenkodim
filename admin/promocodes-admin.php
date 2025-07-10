<?php
// Предотвращение прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Обработка действий
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'generate_promocodes':
            $this->handle_generate_promocodes();
            break;
        case 'add_promocode':
            $this->handle_add_promocode();
            break;
        case 'delete_promocode':
            $this->handle_delete_promocode();
            break;
    }
}

// Получение данных для отображения
$promocodes = $this->get_all_promocodes();
$total_promocodes = count($promocodes);
$used_promocodes = count(array_filter($promocodes, function($p) { return $p->is_used; }));
?>

<div class="wrap">
    <h1>Управление промокодами</h1>
    
    <!-- Статистика -->
    <div class="promocode-stats">
        <div class="stats-box">
            <h3>Статистика</h3>
            <p><strong>Всего промокодов:</strong> <?php echo $total_promocodes; ?></p>
            <p><strong>Использовано:</strong> <?php echo $used_promocodes; ?></p>
            <p><strong>Доступно:</strong> <?php echo $total_promocodes - $used_promocodes; ?></p>
        </div>
    </div>
    
    <!-- Генерация промокодов -->
    <div class="card">
        <h2>Генерация промокодов</h2>
        <form method="post" action="">
            <?php wp_nonce_field('promocode_admin_action', 'promocode_nonce'); ?>
            <input type="hidden" name="action" value="generate_promocodes">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Количество промокодов</th>
                    <td>
                        <input type="number" name="count" min="1" max="100" value="10" required>
                        <p class="description">Укажите количество промокодов для генерации (от 1 до 100)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Приз</th>
                    <td>
                        <input type="text" name="prize" value="Специальный приз!" class="regular-text" required>
                        <p class="description">Текст приза, который увидит пользователь</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Сгенерировать промокоды', 'primary', 'generate'); ?>
        </form>
    </div>
    
    <!-- Добавление отдельного промокода -->
    <div class="card">
        <h2>Добавить промокод вручную</h2>
        <form method="post" action="">
            <?php wp_nonce_field('promocode_admin_action', 'promocode_nonce'); ?>
            <input type="hidden" name="action" value="add_promocode">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Промокод</th>
                    <td>
                        <input type="text" name="code" maxlength="12" class="regular-text" required>
                        <button type="button" id="generate-code-btn" class="button">Сгенерировать</button>
                        <p class="description">12 символов (латинские буквы и цифры)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Приз</th>
                    <td>
                        <input type="text" name="prize" value="Специальный приз!" class="regular-text" required>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Добавить промокод', 'secondary', 'add'); ?>
        </form>
    </div>
    
    <!-- Информация о шорткодах -->
    <div class="card">
        <h2>Использование шорткодов</h2>
        <p><strong>Для формы проверки промокода:</strong></p>
        <code>[promocode_form]</code>
        <p><strong>С настройками:</strong></p>
        <code>[promocode_form button_text="Проверить" placeholder="Ваш промокод"]</code>
    </div>
    
    <!-- Список промокодов -->
    <div class="card">
        <h2>Все промокоды</h2>
        
        <?php if (empty($promocodes)): ?>
            <p>Промокоды не найдены. Создайте первый промокод!</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Промокод</th>
                        <th scope="col">Приз</th>
                        <th scope="col">Статус</th>
                        <th scope="col">Использован кем</th>
                        <th scope="col">Дата создания</th>
                        <th scope="col">Дата использования</th>
                        <th scope="col">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promocodes as $promocode): ?>
                        <tr>
                            <td><?php echo $promocode->id; ?></td>
                            <td><strong><?php echo esc_html($promocode->code); ?></strong></td>
                            <td><?php echo esc_html($promocode->prize); ?></td>
                            <td>
                                <?php if ($promocode->is_used): ?>
                                    <span class="status-used">Использован</span>
                                <?php else: ?>
                                    <span class="status-available">Доступен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($promocode->used_by): ?>
                                    <?php 
                                    $user = get_user_by('id', $promocode->used_by);
                                    echo $user ? esc_html($user->display_name) : 'ID: ' . $promocode->used_by;
                                    ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($promocode->created_date)); ?></td>
                            <td>
                                <?php if ($promocode->used_date): ?>
                                    <?php echo date('d.m.Y H:i', strtotime($promocode->used_date)); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Вы уверены?')">
                                    <?php wp_nonce_field('promocode_admin_action', 'promocode_nonce'); ?>
                                    <input type="hidden" name="action" value="delete_promocode">
                                    <input type="hidden" name="promocode_id" value="<?php echo $promocode->id; ?>">
                                    <input type="submit" class="button button-small" value="Удалить">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Генерация промокода
    $('#generate-code-btn').click(function() {
        var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        var code = '';
        for (var i = 0; i < 12; i++) {
            code += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        $('input[name="code"]').val(code);
    });
});
</script>

 