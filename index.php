<?php
// Настройки подключения к БД
$db_host = 'localhost';
$db_user = 'u82457';
$db_pass = '7777166';       
$db_name = 'u82457';

// Подключение к MySQL
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Массив допустимых языков (для валидации)
$allowed_languages = [
    'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
    'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'
];

// Массив допустимых значений пола (только мужской и женский)
$allowed_genders = ['male', 'female'];

// Инициализация переменных для данных формы и ошибок
$form_data = [
    'full_name' => '',
    'phone' => '',
    'email' => '',
    'birth_date' => '',
    'gender' => '',
    'biography' => '',
    'contract_accepted' => false,
    'languages' => []
];

$errors = [];
$success_message = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Заполняем $form_data из $_POST
    $form_data['full_name'] = trim($_POST['full_name'] ?? '');
    $form_data['phone'] = trim($_POST['phone'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['birth_date'] = trim($_POST['birth_date'] ?? '');
    $form_data['gender'] = $_POST['gender'] ?? '';
    $form_data['biography'] = trim($_POST['biography'] ?? '');
    $form_data['contract_accepted'] = isset($_POST['contract_accepted']);
    $form_data['languages'] = $_POST['languages'] ?? [];

    // --- Валидация каждого поля ---

    // 1. ФИО: только буквы, пробелы, дефисы, длина 2-150
    if (empty($form_data['full_name'])) {
        $errors['full_name'] = 'ФИО обязательно для заполнения.';
    } elseif (!preg_match('/^[а-яА-Яa-zA-Z\s\-]+$/u', $form_data['full_name'])) {
        $errors['full_name'] = 'ФИО должно содержать только буквы, пробелы и дефисы.';
    } elseif (strlen($form_data['full_name']) < 2) {
        $errors['full_name'] = 'ФИО должно содержать минимум 2 символа.';
    } elseif (strlen($form_data['full_name']) > 150) {
        $errors['full_name'] = 'ФИО не должно превышать 150 символов.';
    }

    // 2. Телефон: допустимые символы и длина 10-12 цифр
    if (empty($form_data['phone'])) {
        $errors['phone'] = 'Телефон обязателен.';
    } else {
        $phone_clean = preg_replace('/[^\d+]/', '', $form_data['phone']);
        if (!preg_match('/^(\+7|8)?\d{10}$/', $phone_clean)) {
            $errors['phone'] = 'Введите корректный номер телефона (например: +7XXXXXXXXXX или 8XXXXXXXXXX).';
        } elseif (strlen($phone_clean) < 10 || strlen($phone_clean) > 12) {
            $errors['phone'] = 'Телефон должен содержать 10-12 цифр.';
        }
    }

    // 3. Email
    if (empty($form_data['email'])) {
        $errors['email'] = 'Email обязателен.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный формат email (пример: name@domain.com).';
    } elseif (strlen($form_data['email']) > 100) {
        $errors['email'] = 'Email не должен превышать 100 символов.';
    }

    // 4. Дата рождения (с проверкой год не больше 2022)
    if (empty($form_data['birth_date'])) {
        $errors['birth_date'] = 'Дата рождения обязательна.';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $form_data['birth_date']);
        if (!$date || $date->format('Y-m-d') !== $form_data['birth_date']) {
            $errors['birth_date'] = 'Некорректная дата. Используйте формат ГГГГ-ММ-ДД.';
        } else {
            $year = (int)$date->format('Y');
            $today = new DateTime('today');
            $age = $today->diff($date)->y;
            
            if ($year > 2022) {
                $errors['birth_date'] = 'Год рождения не может быть больше 2022.';
            } elseif ($year < 1900) {
                $errors['birth_date'] = 'Год рождения не может быть раньше 1900.';
            } elseif ($date > $today) {
                $errors['birth_date'] = 'Дата рождения не может быть позже сегодняшнего дня.';
            } elseif ($age < 16) {
                $errors['birth_date'] = 'Вам должно быть не менее 16 лет.';
            } elseif ($age > 100) {
                $errors['birth_date'] = 'Возраст не может превышать 100 лет.';
            }
        }
    }

    // 5. Пол (только мужской или женский)
    if (empty($form_data['gender'])) {
        $errors['gender'] = 'Выберите пол.';
    } elseif (!in_array($form_data['gender'], $allowed_genders)) {
        $errors['gender'] = 'Недопустимое значение пола.';
    }

    // 6. Любимые языки (хотя бы один, не более 5)
    if (empty($form_data['languages'])) {
        $errors['languages'] = 'Выберите хотя бы один язык программирования.';
    } elseif (count($form_data['languages']) > 5) {
        $errors['languages'] = 'Можно выбрать не более 5 языков программирования.';
    } else {
        foreach ($form_data['languages'] as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                $errors['languages'] = 'Выбран недопустимый язык.';
                break;
            }
        }
    }

    // 7. Биография (необязательное поле, но можно проверить длину)
    if (strlen($form_data['biography']) > 5000) {
        $errors['biography'] = 'Биография слишком длинная (макс. 5000 символов).';
    }

    // 8. Чекбокс согласия
    if (!$form_data['contract_accepted']) {
        $errors['contract_accepted'] = 'Необходимо подтвердить ознакомление с контрактом.';
    }

    // Если ошибок нет, сохраняем в БД
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Вставка в таблицу application
            $stmt = $pdo->prepare("
                INSERT INTO application 
                (full_name, phone, email, birth_date, gender, biography, contract_accepted)
                VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)
            ");
            $stmt->execute([
                ':full_name' => $form_data['full_name'],
                ':phone' => $form_data['phone'],
                ':email' => $form_data['email'],
                ':birth_date' => $form_data['birth_date'],
                ':gender' => $form_data['gender'],
                ':biography' => $form_data['biography'],
                ':contract_accepted' => $form_data['contract_accepted'] ? 1 : 0
            ]);

            $application_id = $pdo->lastInsertId();

            // 2. Вставка в application_language
            $lang_map = [];
            $stmt = $pdo->query("SELECT id, name FROM language");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $lang_map[$row['name']] = $row['id'];
            }

            $stmt = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
            foreach ($form_data['languages'] as $lang_name) {
                if (isset($lang_map[$lang_name])) {
                    $stmt->execute([$application_id, $lang_map[$lang_name]]);
                }
            }

            $pdo->commit();
            $success_message = 'Данные успешно сохранены! ID записи: ' . $application_id;
            
            // Очищаем данные формы
            $form_data = [
                'full_name' => '',
                'phone' => '',
                'email' => '',
                'birth_date' => '',
                'gender' => '',
                'biography' => '',
                'contract_accepted' => false,
                'languages' => []
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['db'] = 'Ошибка при сохранении в БД: ' . $e->getMessage();
        }
    }
}

// Получаем список языков для отображения в форме
$languages_from_db = [];
$stmt = $pdo->query("SELECT name FROM language ORDER BY name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $languages_from_db[] = $row['name'];
}
if (empty($languages_from_db)) {
    $languages_from_db = $allowed_languages;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание 3 - Анкета</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Анкета разработчика</h1>

        <!-- Блок сообщений об успехе/ошибках -->
        <?php if ($success_message): ?>
            <div class="success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <strong>Пожалуйста, исправьте следующие ошибки:</strong>
                <ul>
                <?php foreach ($errors as $field => $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ===== АНКЕТА (форма) ===== -->
        <form method="post" action="">
            <div class="form-group">
                <label for="full_name">ФИО <span class="required">*</span>:</label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($form_data['full_name']) ?>" class="<?= isset($errors['full_name']) ? 'error-field' : '' ?>" required>
                <div class="hint">Только буквы, пробелы и дефисы. От 2 до 150 символов.</div>
                <?php if (isset($errors['full_name'])): ?><span class="field-error"><?= $errors['full_name'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Телефон <span class="required">*</span>:</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($form_data['phone']) ?>" class="<?= isset($errors['phone']) ? 'error-field' : '' ?>" placeholder="+7 (123) 456-78-90" required>
                <div class="hint">Формат: +7XXXXXXXXXX или 8XXXXXXXXXX (10 цифр после кода).</div>
                <?php if (isset($errors['phone'])): ?><span class="field-error"><?= $errors['phone'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">E-mail <span class="required">*</span>:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email']) ?>" class="<?= isset($errors['email']) ? 'error-field' : '' ?>" placeholder="example@domain.com" required>
                <?php if (isset($errors['email'])): ?><span class="field-error"><?= $errors['email'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="birth_date">Дата рождения <span class="required">*</span>:</label>
                <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($form_data['birth_date']) ?>" class="<?= isset($errors['birth_date']) ? 'error-field' : '' ?>" required>
                <div class="hint">Год рождения не должен быть больше 2022. Вам должно быть от 16 до 100 лет.</div>
                <?php if (isset($errors['birth_date'])): ?><span class="field-error"><?= $errors['birth_date'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Пол <span class="required">*</span>:</label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" value="male" <?= $form_data['gender'] === 'male' ? 'checked' : '' ?> required> Мужской</label>
                    <label><input type="radio" name="gender" value="female" <?= $form_data['gender'] === 'female' ? 'checked' : '' ?>> Женский</label>
                </div>
                <?php if (isset($errors['gender'])): ?><span class="field-error"><?= $errors['gender'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="languages">Любимые языки программирования <span class="required">*</span>:</label>
                <select id="languages" name="languages[]" multiple size="6" class="<?= isset($errors['languages']) ? 'error-field' : '' ?>" required>
                    <?php foreach ($languages_from_db as $lang): ?>
                        <option value="<?= htmlspecialchars($lang) ?>" <?= in_array($lang, $form_data['languages']) ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Выберите один или несколько языков (Ctrl+Click для множественного выбора). Не более 5 языков.</div>
                <?php if (isset($errors['languages'])): ?><span class="field-error"><?= $errors['languages'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="biography">Биография:</label>
                <textarea id="biography" name="biography" rows="6" class="<?= isset($errors['biography']) ? 'error-field' : '' ?>" placeholder="Расскажите о своем опыте программирования..."><?= htmlspecialchars($form_data['biography']) ?></textarea>
                <div class="hint">Необязательное поле. Максимум 5000 символов.</div>
                <?php if (isset($errors['biography'])): ?><span class="field-error"><?= $errors['biography'] ?></span><?php endif; ?>
            </div>

            <div class="form-group checkbox">
                <label>
                    <input type="checkbox" name="contract_accepted" value="1" <?= $form_data['contract_accepted'] ? 'checked' : '' ?> class="<?= isset($errors['contract_accepted']) ? 'error-field' : '' ?>">
                    Я ознакомлен(а) с контрактом <span class="required">*</span>
                </label>
                <?php if (isset($errors['contract_accepted'])): ?><span class="field-error"><?= $errors['contract_accepted'] ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <button type="submit">Сохранить</button>
            </div>
        </form>

        <!-- Ссылка на просмотр сохранённых записей -->
        <div class="view-link">
            <a href="view.php">📋 Просмотреть сохранённые анкеты</a>
        </div>
    </div>
</body>
</html>