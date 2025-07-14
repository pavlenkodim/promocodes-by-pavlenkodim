jQuery(document).ready(function ($) {
  // Обработка формы промокода
  $("#promocode-form").on("submit", function (e) {
    e.preventDefault();

    var form = $(this);
    var input = $("#promocode-input");
    var result = $("#promocode-result");
    var code = input.val().trim().toUpperCase();

    // Валидация
    if (!code) {
      showPromocodeResult("Введите промокод", "error");
      return;
    }

    if (code.length !== 12) {
      showPromocodeResult("Промокод должен содержать 12 символов", "error");
      return;
    }

    if (!/^[A-Z0-9]+$/.test(code)) {
      showPromocodeResult("Промокод может содержать только латинские буквы и цифры", "error");
      return;
    }

    // Показываем загрузку
    form.addClass("loading");

    // AJAX запрос
    $.ajax({
      url: promocode_ajax.ajax_url,
      type: "POST",
      data: {
        action: "check_promocode",
        code: code,
        nonce: promocode_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          showPromocodeResult(response.message, "success");
          input.val(""); // Очищаем поле после успешной проверки
        } else {
          showPromocodeResult(response.message, "error");
        }
      },
      error: function () {
        showPromocodeResult("Произошла ошибка. Попробуйте позже.", "error");
      },
      complete: function () {
        form.removeClass("loading");
      },
    });
  });

  // Функция отображения результата промокода
  function showPromocodeResult(message, type) {
    var result = $("#promocode-result");
    result.removeClass("show success error").addClass(type).html(message);

    setTimeout(function () {
      result.addClass("show");
    }, 50);

    // Автоматически скрываем через 5 секунд
    setTimeout(function () {
      result.removeClass("show");
    }, 5000);
  }

  // Форматирование ввода промокода
  $("#promocode-input").on("input", function () {
    var value = $(this)
      .val()
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, "");
    if (value.length > 12) {
      value = value.substring(0, 12);
    }
    $(this).val(value);
  });

  // Обработка форм квизов
  $('[id^="quiz-form-"]').on("submit", function (e) {
    e.preventDefault();

    var form = $(this);
    var quizContainer = form.closest(".quiz-container");
    var quizId = quizContainer.data("quiz-id");
    var resultContainer = $("#quiz-result-" + quizId);

    // Собираем ответы
    var answers = {};
    var hasAnswers = false;

    form.find(".quiz-question").each(function () {
      var questionId = $(this).data("question-id");
      var questionAnswers = [];

      $(this)
        .find("input:checked")
        .each(function () {
          questionAnswers.push($(this).val());
          hasAnswers = true;
        });

      if (questionAnswers.length > 0) {
        if (questionAnswers.length === 1) {
          answers["question_" + questionId] = questionAnswers[0];
        } else {
          answers["question_" + questionId] = questionAnswers;
        }
      }
    });

    // Проверяем, что есть хотя бы один ответ
    if (!hasAnswers) {
      showQuizResult(resultContainer, "Пожалуйста, ответьте хотя бы на один вопрос", "error");
      return;
    }

    // Показываем загрузку
    form.addClass("loading");

    // AJAX запрос
    $.ajax({
      url: promocode_ajax.ajax_url,
      type: "POST",
      data: {
        action: "submit_quiz",
        quiz_id: quizId,
        answers: answers,
        nonce: promocode_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          var message = response.message;
          if (response.correct && response.total) {
            message +=
              "<br><strong>Правильных ответов: " +
              response.correct +
              " из " +
              response.total +
              "</strong>";
          }

          if (response.promocode) {
            message +=
              '<div class="quiz-promocode">' +
              "<h4>🎉 Ваш промокод:</h4>" +
              '<div class="quiz-promocode-code">' +
              response.promocode +
              "</div>" +
              "<p>Скопируйте этот промокод и используйте его для получения приза!</p>" +
              "</div>";
          }

          showQuizResult(resultContainer, message, "success");

          // Блокируем форму после успешного прохождения
          form.find("input").prop("disabled", true);
          form.find("button").prop("disabled", true);
        } else {
          var message = response.message;
          if (response.correct && response.total) {
            message +=
              "<br><strong>Правильных ответов: " +
              response.correct +
              " из " +
              response.total +
              "</strong>";
            message += "<br>Для получения промокода нужно ответить правильно на 70% вопросов.";
          }
          showQuizResult(resultContainer, message, "error");
        }
      },
      error: function () {
        showQuizResult(
          resultContainer,
          "Произошла ошибка при отправке квиза. Попробуйте позже.",
          "error"
        );
      },
      complete: function () {
        form.removeClass("loading");
      },
    });
  });

  // Функция отображения результата квиза
  function showQuizResult(container, message, type) {
    container.removeClass("show success error").addClass(type).html(message);

    setTimeout(function () {
      container.addClass("show");
    }, 50);

    // Прокручиваем к результату
    $("html, body").animate(
      {
        scrollTop: container.offset().top - 50,
      },
      500
    );
  }

  // Улучшение UX для радиокнопок и чекбоксов
  $(".quiz-answer").on("click", function (e) {
    if (e.target.tagName === "INPUT") return;

    var input = $(this).find('input[type="radio"], input[type="checkbox"]');

    if (input.attr("type") === "radio") {
      // Для радиокнопок - снимаем выделение с других в этой группе
      var name = input.attr("name");
      $('input[name="' + name + '"]').prop("checked", false);
      input.prop("checked", true);
    } else {
      // Для чекбоксов - переключаем состояние
      input.prop("checked", !input.prop("checked"));
    }

    // Обновляем визуальное состояние
    updateAnswerStyles();
  });

  // Обновление стилей ответов при изменении состояния
  function updateAnswerStyles() {
    $(".quiz-answer").each(function () {
      var input = $(this).find('input[type="radio"], input[type="checkbox"]');
      var inputType = input.attr("type");

      if (input.is(":checked")) {
        $(this).addClass("selected");
        if (inputType === "checkbox") {
          $(this).addClass("selected-checkbox");
        } else {
          $(this).addClass("selected-radio");
        }
      } else {
        $(this).removeClass("selected selected-checkbox selected-radio");
      }
    });
  }

  // Инициализация стилей при загрузке
  updateAnswerStyles();

  // Копирование промокода в буфер обмена
  $(document).on("click", ".quiz-promocode-code", function () {
    var code = $(this).text();

    // Создаем временный элемент для копирования
    var tempInput = $("<input>");
    $("body").append(tempInput);
    tempInput.val(code).select();

    try {
      document.execCommand("copy");

      // Показываем уведомление о копировании
      var originalText = $(this).text();
      $(this).text("Скопировано!");

      setTimeout(() => {
        $(this).text(originalText);
      }, 2000);
    } catch (err) {
      console.log("Ошибка копирования:", err);
    }

    tempInput.remove();
  });

  // Валидация ответов в реальном времени
  $('.quiz-form input[type="radio"], .quiz-form input[type="checkbox"]').on("change", function () {
    updateAnswerStyles();

    // Проверяем, все ли вопросы имеют ответы
    var form = $(this).closest(".quiz-form");
    var totalQuestions = form.find(".quiz-question").length;
    var answeredQuestions = 0;

    form.find(".quiz-question").each(function () {
      if ($(this).find("input:checked").length > 0) {
        answeredQuestions++;
      }
    });

    // Обновляем состояние кнопки отправки
    var submitBtn = form.find(".quiz-submit-btn");
    if (answeredQuestions === totalQuestions) {
      submitBtn.removeClass("disabled").text("Завершить квиз");
    } else {
      submitBtn
        .addClass("disabled")
        .text("Ответьте на все вопросы (" + answeredQuestions + "/" + totalQuestions + ")");
    }
  });

  // Плавная анимация появления элементов
  function animateElements() {
    $(".quiz-question, .promocode-form").each(function (index) {
      var element = $(this);
      setTimeout(function () {
        element.addClass("animate-in");
      }, index * 200);
    });
  }

  // Инициализация анимаций
  animateElements();

  // Добавляем CSS класс для анимаций
  $("<style>")
    .prop("type", "text/css")
    .html(
      `
            .quiz-question, .promocode-form {
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.6s ease;
            }
            .quiz-question.animate-in, .promocode-form.animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            .quiz-answer.selected {
                border-color: #007cba !important;
                background: rgba(0, 124, 186, 0.05);
            }
            .quiz-submit-btn.disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        `
    )
    .appendTo("head");
});
