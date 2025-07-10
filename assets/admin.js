jQuery(document).ready(function ($) {
  // Генерация промокода для ручного добавления
  $("#generate-code-btn").click(function () {
    var characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    var code = "";
    for (var i = 0; i < 12; i++) {
      code += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    $('input[name="code"]').val(code);

    // Анимация кнопки
    $(this).text("Сгенерировано!").prop("disabled", true);
    setTimeout(() => {
      $(this).text("Сгенерировать").prop("disabled", false);
    }, 1500);
  });

  // Валидация промокода при вводе
  $('input[name="code"]').on("input", function () {
    var value = $(this)
      .val()
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, "");
    if (value.length > 12) {
      value = value.substring(0, 12);
    }
    $(this).val(value);

    // Визуальная обратная связь
    if (value.length === 12) {
      $(this).addClass("valid-code");
    } else {
      $(this).removeClass("valid-code");
    }
  });

  // Подтверждение удаления с улучшенным UX
  $('form[onsubmit*="confirm"]').on("submit", function (e) {
    e.preventDefault();

    var form = $(this);
    var action = form.find('input[name="action"]').val();
    var confirmMessage = "";

    switch (action) {
      case "delete_promocode":
        confirmMessage =
          "Вы уверены, что хотите удалить этот промокод? Это действие нельзя отменить.";
        break;
      case "delete_quiz":
        confirmMessage =
          "Вы уверены, что хотите удалить этот квиз? Все вопросы и ответы будут удалены без возможности восстановления.";
        break;
      case "delete_question":
        confirmMessage =
          "Вы уверены, что хотите удалить этот вопрос? Все связанные ответы также будут удалены.";
        break;
      default:
        confirmMessage = "Вы уверены, что хотите выполнить это действие?";
    }

    if (confirm(confirmMessage)) {
      form.off("submit").submit();
    }
  });

  // === КВИЗ РЕДАКТОР ===

  var answerIndex = 0;

  // Добавление нового варианта ответа
  $("#add-answer").click(function () {
    var questionType = $("#question-type").val();
    if (!questionType) {
      alert("Сначала выберите тип вопроса");
      $("#question-type").focus();
      return;
    }

    var answerHtml = "";
    if (questionType === "checkbox") {
      answerHtml = createTextAnswerHtml(answerIndex);
    } else if (questionType === "image") {
      answerHtml = createImageAnswerHtml(answerIndex);
    }

    var $newAnswer = $(answerHtml);
    $(".answers-list").append($newAnswer);

    // Анимация появления
    $newAnswer.hide().slideDown(300);

    answerIndex++;
    updateAnswerNumbers();
  });

  // Создание HTML для текстового ответа
  function createTextAnswerHtml(index) {
    return `
            <div class="answer-item" data-index="${index}">
                <label>Текст ответа:</label>
                <input type="text" name="answers[${index}][text]" class="regular-text" required placeholder="Введите вариант ответа">
                <br><br>
                <label style="display: inline-flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="answers[${index}][is_correct]" value="1">
                    <strong>Правильный ответ</strong>
                </label>
                <button type="button" class="button remove-answer" style="margin-left: 20px;">Удалить</button>
            </div>
        `;
  }

  // Создание HTML для ответа с картинкой
  function createImageAnswerHtml(index) {
    return `
            <div class="answer-item" data-index="${index}">
                <label>Описание картинки:</label>
                <input type="text" name="answers[${index}][text]" class="regular-text" required placeholder="Описание изображения">
                <br><br>
                <label>URL картинки:</label>
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                    <input type="url" name="answers[${index}][image]" class="regular-text" required placeholder="https://example.com/image.jpg">
                    <button type="button" class="button upload-image" data-index="${index}">📷 Выбрать</button>
                </div>
                <div class="image-preview" style="margin: 10px 0; min-height: 60px; display: none;">
                    <img src="" style="max-width: 150px; max-height: 100px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <label style="display: inline-flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="answers[${index}][is_correct]" value="1">
                    <strong>Правильный ответ</strong>
                </label>
                <button type="button" class="button remove-answer" style="margin-left: 20px;">Удалить</button>
            </div>
        `;
  }

  // Удаление варианта ответа
  $(document).on("click", ".remove-answer", function () {
    var $answerItem = $(this).closest(".answer-item");
    $answerItem.slideUp(300, function () {
      $(this).remove();
      updateAnswerNumbers();
    });
  });

  // Обновление нумерации ответов
  function updateAnswerNumbers() {
    $(".answer-item").each(function (index) {
      $(this)
        .find("input, button")
        .each(function () {
          var name = $(this).attr("name");
          if (name) {
            $(this).attr("name", name.replace(/\[\d+\]/, "[" + index + "]"));
          }
          var dataIndex = $(this).attr("data-index");
          if (dataIndex !== undefined) {
            $(this).attr("data-index", index);
          }
        });
    });
  }

  // Загрузка изображения через медиатеку WordPress
  $(document).on("click", ".upload-image", function (e) {
    e.preventDefault();

    if (typeof wp === "undefined" || !wp.media) {
      alert("Медиатека WordPress недоступна");
      return;
    }

    var button = $(this);
    var index = button.data("index");
    var $answerItem = button.closest(".answer-item");

    var frame = wp.media({
      title: "Выберите изображение для ответа",
      button: {
        text: "Использовать изображение",
      },
      multiple: false,
      library: {
        type: "image",
      },
    });

    frame.on("select", function () {
      var attachment = frame.state().get("selection").first().toJSON();
      var $imageInput = $answerItem.find('input[name="answers[' + index + '][image]"]');
      var $preview = $answerItem.find(".image-preview");
      var $previewImg = $preview.find("img");

      $imageInput.val(attachment.url);
      $previewImg.attr("src", attachment.url);
      $preview.show();

      // Анимация появления превью
      $preview.hide().fadeIn(300);
    });

    frame.open();
  });

  // Превью изображения при вводе URL
  $(document).on("input", 'input[name*="[image]"]', function () {
    var url = $(this).val();
    var $preview = $(this).closest(".answer-item").find(".image-preview");
    var $img = $preview.find("img");

    if (url && isValidImageUrl(url)) {
      $img.attr("src", url);
      $img
        .on("load", function () {
          $preview.fadeIn(300);
        })
        .on("error", function () {
          $preview.hide();
        });
    } else {
      $preview.hide();
    }
  });

  // Проверка валидности URL изображения
  function isValidImageUrl(url) {
    return /\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i.test(url);
  }

  // Очистка формы при смене типа вопроса
  $("#question-type").change(function () {
    var $answersList = $(".answers-list");
    if ($answersList.children().length > 0) {
      if (confirm("При смене типа вопроса все добавленные ответы будут удалены. Продолжить?")) {
        $answersList.empty();
        answerIndex = 0;
      } else {
        // Возвращаем предыдущее значение
        $(this).val($(this).data("prev-value") || "");
      }
    }
    $(this).data("prev-value", $(this).val());
  });

  // Валидация формы вопроса перед отправкой
  $("#question-form").on("submit", function (e) {
    var questionText = $('textarea[name="question_text"]').val().trim();
    var questionType = $("#question-type").val();
    var answers = $(".answer-item").length;
    var correctAnswers = $('.answer-item input[name*="[is_correct]"]:checked').length;

    // Проверки
    if (!questionText) {
      alert("Введите текст вопроса");
      $('textarea[name="question_text"]').focus();
      e.preventDefault();
      return;
    }

    if (!questionType) {
      alert("Выберите тип вопроса");
      $("#question-type").focus();
      e.preventDefault();
      return;
    }

    if (answers < 2) {
      alert("Добавьте минимум 2 варианта ответа");
      e.preventDefault();
      return;
    }

    if (correctAnswers === 0) {
      alert("Отметьте хотя бы один правильный ответ");
      e.preventDefault();
      return;
    }

    // Проверка заполненности полей
    var isValid = true;
    $(".answer-item").each(function () {
      var $item = $(this);
      var textValue = $item.find('input[name*="[text]"]').val().trim();

      if (!textValue) {
        alert("Заполните все поля с текстом ответов");
        $item.find('input[name*="[text]"]').focus();
        isValid = false;
        return false;
      }

      if (questionType === "image") {
        var imageValue = $item.find('input[name*="[image]"]').val().trim();
        if (!imageValue) {
          alert("Заполните все поля с URL изображений");
          $item.find('input[name*="[image]"]').focus();
          isValid = false;
          return false;
        }
      }
    });

    if (!isValid) {
      e.preventDefault();
    }
  });

  // Счетчик символов для текста вопроса
  $('textarea[name="question_text"]').on("input", function () {
    var length = $(this).val().length;
    var $counter = $(this).next(".char-counter");

    if ($counter.length === 0) {
      $counter = $(
        '<div class="char-counter" style="font-size: 12px; color: #666; margin-top: 5px;"></div>'
      );
      $(this).after($counter);
    }

    $counter.text(length + " символов");

    if (length > 500) {
      $counter.css("color", "#d63384");
    } else {
      $counter.css("color", "#666");
    }
  });

  // Автосохранение (можно расширить в будущем)
  var autoSaveTimer;
  $("#question-form input, #question-form textarea, #question-form select").on(
    "input change",
    function () {
      clearTimeout(autoSaveTimer);
      autoSaveTimer = setTimeout(function () {
        // Здесь можно добавить автосохранение
        console.log("Автосохранение...");
      }, 5000);
    }
  );

  // Подсказки для пользователя
  function showTooltip(element, message) {
    var $tooltip = $('<div class="admin-tooltip">' + message + "</div>");
    $tooltip.css({
      position: "absolute",
      background: "#333",
      color: "white",
      padding: "8px 12px",
      borderRadius: "4px",
      fontSize: "12px",
      zIndex: 9999,
      maxWidth: "200px",
    });

    $("body").append($tooltip);

    var offset = $(element).offset();
    $tooltip.css({
      top: offset.top - $tooltip.outerHeight() - 5,
      left: offset.left + $(element).outerWidth() / 2 - $tooltip.outerWidth() / 2,
    });

    setTimeout(function () {
      $tooltip.fadeOut(300, function () {
        $(this).remove();
      });
    }, 3000);
  }

  // Улучшенные уведомления
  function showNotification(message, type = "success") {
    var $notification = $('<div class="admin-notification ' + type + '">' + message + "</div>");
    $notification.css({
      position: "fixed",
      top: "50px",
      right: "20px",
      background: type === "success" ? "#00a32a" : "#d63384",
      color: "white",
      padding: "15px 20px",
      borderRadius: "6px",
      zIndex: 10000,
      boxShadow: "0 4px 15px rgba(0,0,0,0.2)",
      transform: "translateX(100%)",
      transition: "transform 0.3s ease",
    });

    $("body").append($notification);

    setTimeout(function () {
      $notification.css("transform", "translateX(0)");
    }, 100);

    setTimeout(function () {
      $notification.css("transform", "translateX(100%)");
      setTimeout(function () {
        $notification.remove();
      }, 300);
    }, 4000);
  }

  // Инициализация подсказок
  $("[title]").hover(function () {
    var title = $(this).attr("title");
    if (title) {
      showTooltip(this, title);
      $(this).removeAttr("title").data("original-title", title);
    }
  });

  // Добавляем стили для валидации
  $("<style>")
    .prop("type", "text/css")
    .html(
      `
            .valid-code {
                border-color: #00a32a !important;
                box-shadow: 0 0 0 3px rgba(0, 163, 42, 0.1) !important;
            }
            .answer-item {
                position: relative;
            }
            .answer-item:before {
                content: counter(answer-counter);
                counter-increment: answer-counter;
                position: absolute;
                top: -10px;
                left: -10px;
                background: #0073aa;
                color: white;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
            }
            .answers-list {
                counter-reset: answer-counter;
            }
        `
    )
    .appendTo("head");

  // Инициализация функций при загрузке
  $('input[name="code"]').trigger("input");
});
