# QyzPU Planner — студенттерге арналған жоспарлаушы (IELTS жоспары + сөздік)

PHP + MySQL (PDO) қолданып жасалған веб-қосымша. Пайдаланушы тіркеліп, IELTS-ке дайындық жаттығуларын жоспарлайды.

## Мақсаты
IELTS бөлімдері (Listening, Reading, Writing, Speaking, Grammar, Vocabulary, Mock Test) бойынша жаттығуларды мерзім, басымдық және уақыт бойынша жүйелеп, прогресті бақылау.

## Беттер тізімі (8 бет)
| Файл | Сипаттама |
|---|---|
| index.php | Басты бет |
| register.php | Тіркелу |
| login.php | Кіру |
| logout.php | Шығу |
| dashboard.php | Тапсырмалар тізімі (Read), іздеу, сүзгі, сұрыптау, пагинация |
| task_create.php | Тапсырма құру (Create) |
| task_edit.php | Тапсырманы өңдеу (Update) |
| task_delete.php | Тапсырманы жою (Delete) |
| words.php | Жеке сөздік: CRUD, іздеу, жаттау карточкалары |
| profile.php | Профиль: фото жүктеу, мәліметтер, құпиясөз өзгерту |
| task_template.php | Дайын 4 апталық жоспарды қосу |
| task_toggle.php | Орындалды/күтуде мәртебесін ауыстыру |

## Негізгі функциялар
- Тіркелу, кіру, шығу (`password_hash`, `password_verify`, `session_start`)
- Жабық беттерді қорғау (`require_login()`)
- Толық CRUD (Create, Read, Update, Delete)
- Іздеу, санат/мәртебе/басымдық бойынша сүзу, сұрыптау, пагинация
- Валидация (клиент және сервер жағында)
- Сөздік (CRUD) және жаттау карточкалары
- Профиль: аватар жүктеу (MIME/өлшем тексеріледі), құпиясөз өзгерту

## Қауіпсіздік
- PDO prepared statements (SQL injection-нан қорғау)
- `htmlspecialchars()` — `e()` функциясы (XSS)
- CSRF-токен барлық POST формаларда
- `session_regenerate_id()` кіргенде
- Әр пайдаланушы тек өз тапсырмасын көреді/өзгертеді (`user_id` тексеріледі)

## ER-диаграмма
```
users (id PK, name, email UNIQUE, password, phone, faculty, course, avatar, created_at)
   │ 1
   │ N
words (id PK, user_id FK, word, translation, example, status, created_at)
   │ 1
   │
   │ N
tasks (id PK, user_id FK, category_id FK, title, description,
       priority, status, due_date, duration_min, created_at, updated_at)
   │ N
   │
   │ 1
categories (id PK, name UNIQUE, color)
```

## Орнату (XAMPP)
1. `ielts` қалтасын `C:\xampp\htdocs\` ішіне көшіріңіз.
2. XAMPP Control Panel-де **Apache** және **MySQL** іске қосыңыз.
3. Браузерде ашыңыз: http://localhost/ielts/
4. Дерекқор автоматты түрде құрылады (қосымша: `database.sql` файлын phpMyAdmin арқылы импорттауға болады).

## Құрылым
```
ielts/
├── includes/ (config, db, functions, header, footer, task_form)
├── assets/css/style.css
├── assets/js/app.js
├── *.php беттер
├── database.sql
└── README.md
```
