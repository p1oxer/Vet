document.addEventListener('DOMContentLoaded', function () {
    // Переключение между формами
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const registerLink = document.getElementById('switchToRegister');
    const loginLink = document.getElementById('switchToLogin');
    const formTitle = document.getElementById('form-title');

    // Проверяем состояние формы при загрузке страницы
    const lastFormState = localStorage.getItem('formState');
    if (lastFormState === 'register') {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        formTitle.textContent = 'Регистрация';
    } else {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        formTitle.textContent = 'Вход в систему';
    }

    // Переключение на форму регистрации
    registerLink.addEventListener('click', function (e) {
        e.preventDefault();
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        formTitle.textContent = 'Регистрация';
        localStorage.setItem('formState', 'register'); // Сохраняем состояние формы
    });

    // Переключение на форму входа
    loginLink.addEventListener('click', function (e) {
        e.preventDefault();
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
        formTitle.textContent = 'Вход в систему';
        localStorage.setItem('formState', 'login'); // Сохраняем состояние формы
    });

    // Форматирование номера телефона в реальном времени
    const phoneInput = document.getElementById('phone');

    phoneInput.addEventListener('input', function () {
        let phone = phoneInput.value.replace(/\D/g, ''); // Удаляем все символы кроме цифр

        // Ограничиваем длину номера до 11 цифр (вместе с "7")
        if (phone.length > 11) {
            phone = phone.substring(0, 11);
        }

        // Форматируем номер по частям
        let formattedPhone = '+7';
        if (phone.length > 1) {
            formattedPhone += '-' + phone.substring(1, 4); // Код оператора (первая часть)
        }
        if (phone.length >= 5) {
            formattedPhone += '-' + phone.substring(4, 7); // Вторая часть номера
        }
        if (phone.length >= 8) {
            formattedPhone += '-' + phone.substring(7, 9); // Третья часть номера
        }
        if (phone.length >= 10) {
            formattedPhone += '-' + phone.substring(9, 11); // Четвёртая часть номера
        }

        // Обновляем значение поля ввода
        phoneInput.value = formattedPhone;
    });

    // Проверка совпадения паролей и отправка формы
    const registerFormElement = document.getElementById('registerForm');
    const passwordInput = document.getElementById('regPassword');
    const repeatPasswordInput = document.getElementById('confirmPassword');
    const errorMessage = document.getElementById('errorMessage');

    // Используем событие submit на форме
    registerFormElement.addEventListener('submit', function (event) {
        event.preventDefault(); // Останавливаем отправку формы по умолчанию

        errorMessage.textContent = ''; // Очистить сообщение об ошибке

        // Проверка длины номера телефона (должно быть 11 цифр вместе с "7")
        let phone = phoneInput.value.replace(/\D/g, ''); // Удаляем все символы кроме цифр
        if (phone.length !== 11) {
            errorMessage.textContent = 'Введите полный номер телефона';
            return;
        }

        // Проверка длины пароля
        if (passwordInput.value.length < 6) {
            errorMessage.textContent = 'Пароль должен быть не менее 6 символов';
            return;
        }

        // Проверка совпадения паролей
        if (passwordInput.value !== repeatPasswordInput.value) {
            errorMessage.textContent = 'Пароли не совпадают';
            return;
        }

        // Убираем дефисы перед отправкой формы в базу данных
        phoneInput.value = phone; // Сохраняем номер без дефисов

        // Если все проверки прошли успешно, отправляем форму
        registerFormElement.submit(); // Отправляем форму, если все проверки пройдены
    });
});
