# Promocodes by Pavlenkodim

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)

Мощный плагин для WordPress, позволяющий создавать и управлять промокодами, а также создавать интерактивные квизы с автоматической генерацией наград.

## 🎯 Основные возможности

### 📝 Управление промокодами

- **Автоматическая генерация** промокодов (12 символов: латиница + цифры)
- **Ручное создание** промокодов с пользовательским текстом
- **Массовая генерация** от 1 до 100 промокодов одновременно
- **Проверка уникальности** кодов в базе данных
- **Отслеживание использования** с информацией о пользователе и времени
- **Статистика** использования промокодов

### 🧩 Система квизов

- **Два типа вопросов:**
  - **Чекбоксы** - текстовые варианты ответов
  - **Картинки** - выбор из изображений
- **Мультивыбор** - возможность нескольких правильных ответов
- **Автоматическая генерация промокода** при успешном прохождении
- **Настраиваемый порог прохождения** (по умолчанию 70% правильных ответов)
- **Интеграция с медиатекой WordPress** для загрузки изображений

### 🎨 Современный пользовательский интерфейс

- **Адаптивный дизайн** для всех устройств
- **AJAX-интерфейс** без перезагрузки страниц
- **Анимации и переходы** для лучшего UX
- **Валидация в реальном времени**
- **Копирование промокодов** в буфер обмена одним кликом

## 📁 Структура плагина

```
promocodes-by-pavlenkodim/
├── promocode-by-pavlenkodim.php    # Основной файл плагина
├── admin/                          # Админ панель
│   ├── promocodes-admin.php        # Управление промокодами
│   └── quiz-admin.php              # Управление квизами
├── assets/                         # Ресурсы
│   ├── frontend.css                # Стили для фронтенда
│   ├── frontend.js                 # Скрипты для фронтенда
│   ├── admin.css                   # Стили для админки
│   └── admin.js                    # Скрипты для админки
└── README.md                       # Документация
```

## 🚀 Установка

1. **Скачайте плагин** или клонируйте репозиторий:

   ```bash
   git clone https://github.com/pavlenkodim/promocodes-by-pavlenkodim.git
   ```

2. **Загрузите папку плагина** в директорию `/wp-content/plugins/` вашего WordPress сайта

3. **Активируйте плагин** в админ панели WordPress: `Плагины → Установленные → Promocodes by Pavlenkodim → Активировать`

4. **Настройте права доступа** (автоматически для администраторов)

## 🛠️ Использование

### Управление промокодами

1. Перейдите в **WordPress Админ → Промокоды → Промокоды**
2. Используйте доступные инструменты:

#### Массовая генерация промокодов

```
Количество: 1-100
Приз: Текст награды для пользователя
```

#### Ручное создание промокода

```
Промокод: 12 символов (A-Z, 0-9)
Приз: Описание награды
```

#### Статистика

- Общее количество промокодов
- Количество использованных
- Количество доступных

### Создание квизов

1. Перейдите в **WordPress Админ → Промокоды → Квизы**
2. **Создайте новый квиз** с названием
3. **Добавьте вопросы:**

#### Тип "Чекбоксы"

- Введите текст вопроса
- Добавьте варианты ответов
- Отметьте правильные ответы
- Включите мультивыбор при необходимости

#### Тип "Картинки"

- Введите текст вопроса
- Добавьте изображения (URL или через медиатеку)
- Добавьте описания к картинкам
- Отметьте правильные ответы

### Шорткоды

#### Форма проверки промокода

```shortcode
[promocode_form]
```

**С параметрами:**

```shortcode
[promocode_form button_text="Проверить" placeholder="Ваш промокод"]
```

#### Отображение квиза

```shortcode
[quiz_form quiz_id="1"]
```

**Где 1** - это ID квиза из админ панели

### Примеры использования

#### На странице акции:

```html
<h2>Введите промокод и получите приз!</h2>
[promocode_form button_text="Получить приз" placeholder="Промокод из 12 символов"]
```

#### На странице с квизом:

```html
<h2>Пройдите квиз и получите промокод!</h2>
[quiz_form quiz_id="1"]
<p>Для получения промокода ответьте правильно на 70% вопросов.</p>
```

## 🔧 Технические детали

### Системные требования

- **WordPress:** 5.0 или выше
- **PHP:** 7.4 или выше
- **MySQL:** 5.6 или выше

### База данных

Плагин автоматически создает 4 таблицы:

#### `wp_promocodes_pavlenkodim`

```sql
- id (mediumint) - Уникальный ID
- code (varchar) - Промокод (12 символов)
- prize (text) - Описание приза
- is_used (tinyint) - Статус использования
- used_by (bigint) - ID пользователя
- used_date (datetime) - Дата использования
- created_date (datetime) - Дата создания
```

#### `wp_quizzes_pavlenkodim`

```sql
- id (mediumint) - Уникальный ID
- title (varchar) - Название квиза
- is_active (tinyint) - Статус активности
- created_date (datetime) - Дата создания
```

#### `wp_quiz_questions_pavlenkodim`

```sql
- id (mediumint) - Уникальный ID
- quiz_id (mediumint) - ID квиза
- question (text) - Текст вопроса
- question_type (varchar) - Тип вопроса (checkbox/image)
- is_multiple (tinyint) - Мультивыбор
- order_num (int) - Порядок отображения
```

#### `wp_quiz_answers_pavlenkodim`

```sql
- id (mediumint) - Уникальный ID
- question_id (mediumint) - ID вопроса
- answer_text (text) - Текст ответа
- answer_image (varchar) - URL изображения
- is_correct (tinyint) - Правильность ответа
- order_num (int) - Порядок отображения
```

### AJAX эндпоинты

#### Проверка промокода

```javascript
POST /wp-admin/admin-ajax.php
{
    action: 'check_promocode',
    code: 'ПРОМОКОД123',
    nonce: 'security_nonce'
}
```

#### Отправка квиза

```javascript
POST /wp-admin/admin-ajax.php
{
    action: 'submit_quiz',
    quiz_id: 1,
    answers: {
        'question_1': 'answer_id',
        'question_2': ['answer_id1', 'answer_id2']
    },
    nonce: 'security_nonce'
}
```

## 🛡️ Безопасность

- **Nonce токены** для всех AJAX запросов
- **Санитизация данных** при сохранении
- **Валидация входных данных**
- **Проверка прав доступа** для админ функций
- **Защита от прямого доступа** к файлам
- **Экранирование вывода** для предотвращения XSS

## 🎨 Кастомизация

### CSS классы для стилизации

#### Промокоды

```css
.promocode-form-container  /* Контейнер формы */
/* Контейнер формы */
.promocode-form           /* Сама форма */
.promocode-input-group    /* Группа инпута и кнопки */
.promocode-result         /* Результат проверки */
.promocode-result.success /* Успешная проверка */
.promocode-result.error; /* Ошибка проверки */
```

#### Квизы

```css
.quiz-container          /* Контейнер квиза */
/* Контейнер квиза */
.quiz-form              /* Форма квиза */
.quiz-question          /* Блок вопроса */
.quiz-answers           /* Контейнер ответов */
.quiz-answer            /* Отдельный ответ */
.quiz-submit-btn        /* Кнопка отправки */
.quiz-result; /* Результат квиза */
```

### Хуки для разработчиков

#### Actions

```php
do_action('promocodes_before_check', $code);
do_action('promocodes_after_use', $promocode_id, $user_id);
do_action('quiz_completed', $quiz_id, $user_id, $score);
```

#### Filters

```php
$threshold = apply_filters('quiz_pass_threshold', 70, $quiz_id);
$prize_text = apply_filters('promocode_prize_text', $default_text, $promocode);
```

## 📈 Changelog

### Version 1.0.0

- Первый релиз
- Система промокодов
- Система квизов
- Админ панель
- AJAX интерфейс

## 🤝 Вклад в проект

1. Fork репозиторий
2. Создайте feature branch (`git checkout -b feature/amazing-feature`)
3. Commit изменения (`git commit -m 'Add amazing feature'`)
4. Push в branch (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

## 📄 Лицензия

Этот проект лицензирован под GPL v2 или более поздней версией - смотрите [LICENSE](LICENSE) файл для деталей.

## 👨‍💻 Автор

**Dmitriy Pavlenko**

- GitHub: [@pavlenkodim](https://github.com/pavlenkodim)

## 🙏 Благодарности

- WordPress сообществу за отличную документацию
- Всем тестировщикам и контрибьюторам
