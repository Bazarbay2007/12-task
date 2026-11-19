<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('dashboard.php'); }
csrf_check();

// 4 апталық IELTS жоспары: [апта, күн, санат, атауы, сипаттама, басымдық, минут]
$plan = [
 // ---------- 1-апта: деңгейді анықтау және негіз ----------
 [1,1,'Mock Test','Diagnostic test: Listening + Reading','Деңгейді анықтау үшін Cambridge IELTS-тен 1 тест. Әлсіз тұстарды жазып алу','high',120],
 [1,2,'Listening','Listening: Section 1 (форма толтыру)','Аты-жөні, телефон, мекенжай сияқты деректерді дұрыс жазуды жаттығу','medium',40],
 [1,3,'Reading','Reading: Skimming және Scanning','2 мәтін, әр мәтінге 20 минут қою','medium',45],
 [1,4,'Writing','Writing Task 1: Line graph сипаттау','150+ сөз. Кіріспе, негізгі тренд, салыстыру құрылымы','medium',40],
 [1,5,'Vocabulary','Academic Word List: 30 жаңа сөз','Сөзді карточкаға жазып, әрқайсысына 1 сөйлем құру','medium',30],
 [1,6,'Speaking','Speaking Part 1: өзің туралы','Үй, оқу, хобби тақырыптарына дауыстап жауап беру, дауысқа жазу','medium',20],
 // ---------- 2-апта: стратегиялар ----------
 [2,8,'Listening','Listening: Section 2–3 (карта, Multiple Choice)','Карта мен нұсқаулар, дистракторларды ажырату','high',45],
 [2,9,'Reading','Reading: True / False / Not Given','Стратегияны үйрену және 2 мәтін шешу','high',45],
 [2,10,'Writing','Writing Task 2: Essay құрылымы','Intro, 2 body paragraph, conclusion. 1 эссе жоспары','high',50],
 [2,11,'Grammar','Grammar: Tenses және Conditionals','Жаттығулар және Writing-ке қолдану','medium',40],
 [2,12,'Speaking','Speaking Part 2: Cue card ×3','Әр карточкаға 2 минут сөйлеу','high',30],
 [2,13,'Vocabulary','Vocabulary: Education және Environment','Тақырыптық 30 сөз және коллокациялар','medium',30],
 // ---------- 3-апта: тереңдету ----------
 [3,15,'Listening','Listening: Section 4 (лекция, Note completion)','Академиялық лекция, ұзақ тыңдау','high',45],
 [3,16,'Reading','Reading: Matching Headings','Ұзақ мәтіндермен 2 жаттығу','medium',45],
 [3,17,'Writing','Writing Task 1: Bar chart, Table, Process','Әр түрінен 1-ден сипаттау жазу','medium',40],
 [3,18,'Writing','Writing Task 2: Opinion essay толық жазу','250+ сөз, 40 минутта. Қатені тексеру','high',60],
 [3,19,'Grammar','Grammar: Complex sentences, Passive','Күрделі сөйлемдерді Writing-те қолдану','medium',40],
 [3,20,'Speaking','Speaking Part 3: пікірталас сұрақтары','Себеп, мысал, салыстыру арқылы толық жауап беру','high',30],
 // ---------- 4-апта: Mock Test және қорытынды ----------
 [4,22,'Mock Test','Mock Test: Listening + Reading','Нақты уақытпен, жауап парағына толтыру','high',120],
 [4,23,'Mock Test','Mock Test: Writing Task 1 + Task 2','60 минутта екі тапсырма жазу','high',60],
 [4,24,'Mock Test','Speaking mock (серіктеспен)','Part 1–3, 11–14 минут, пікір алу','high',15],
 [4,25,'Mock Test','Қателерді талдау және баллды есептеу','Әлсіз бөлімдердің тізімі мен келесі жоспар','high',60],
 [4,26,'Vocabulary','Vocabulary: қорытынды қайталау','Төрт аптадағы барлық жаңа сөздерді тексеру','medium',30],
 [4,28,'Mock Test','Final Full Mock Test (L + R + W)','Толық тест, мақсатты баллды тексеру','high',170],
];

$cats = [];
foreach (db()->query('SELECT id, name FROM categories') as $r) { $cats[$r['name']] = (int)$r['id']; }

$exists = db()->prepare('SELECT 1 FROM tasks WHERE user_id = ? AND title = ?');
$ins = db()->prepare('INSERT INTO tasks (user_id, category_id, title, description, priority, due_date, duration_min)
                      VALUES (?, ?, ?, ?, ?, ?, ?)');
$added = 0;
foreach ($plan as [$week, $day, $cat, $title, $desc, $prio, $min]) {
    $full = $week . '-апта · ' . $title;
    $exists->execute([$_SESSION['user_id'], $full]);
    if ($exists->fetch()) { continue; }      // қайталанбау үшін
    $ins->execute([$_SESSION['user_id'], $cats[$cat] ?? null, $full, $desc, $prio,
                   date('Y-m-d', strtotime("+$day days")), $min]);
    $added++;
}
flash($added ? 'success' : 'error', $added ? "4 апталық жоспардан $added жаттығу қосылды!" : 'Бұл жоспар бұрыннан қосылған.');
redirect('dashboard.php');
